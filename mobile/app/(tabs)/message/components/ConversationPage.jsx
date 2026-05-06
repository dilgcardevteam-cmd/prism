import { Feather } from "@expo/vector-icons";
import * as Clipboard from "expo-clipboard";
import * as ImagePicker from "expo-image-picker";
import { Image } from "expo-image";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Animated,
  Keyboard,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  RefreshControl,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { APP_COLORS } from "../../../../constants/theme";
import { useMessagesApi } from "../../../../hooks/useMessagesApi";
import useEcho from "../../../../hooks/useEcho";
import { useAuth } from "../../../../contexts/AuthContext";
import { useWebAppRequest } from "../../../../hooks/useWebAppRequest";
import { createScrollHandler, createScrollInterpolations } from "../../../../utils/animations";

/* ---------------- UTIL ---------------- */

const getInitials = (name) => {
  const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return "U";
  return `${parts[0][0]}${parts[1]?.[0] || parts[0][1] || ""}`.toUpperCase();
};

const formatTime = (value) => {
  if (!value) return "";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;

  return d.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
};

/* ---------------- MAIN ---------------- */

export default function ConversationPage() {
  const router = useRouter();
  const { threadId, recipientIds, recipientName, recipientSubtitle } =
    useLocalSearchParams();

  const { fetchMessages, sendMessage } = useMessagesApi();

  const scrollRef = useRef(null);
  const scrollY = useRef(new Animated.Value(0)).current;

  const parsedThreadId = Number(threadId || 0);

  const parsedRecipientIds = useMemo(() => {
    return String(recipientIds || "")
      .split(",")
      .map((v) => Number(v.trim()))
      .filter(Boolean);
  }, [recipientIds]);

  const [thread, setThread] = useState(null);
  const [messages, setMessages] = useState([]);
  const [reply, setReply] = useState("");
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [isRecipientTyping, setIsRecipientTyping] = useState(false);
  const [attachments, setAttachments] = useState([]);
  const [copyTargetMessageId, setCopyTargetMessageId] = useState(null);
  const [isKeyboardVisible, setIsKeyboardVisible] = useState(false);
  const optimisticCounterRef = useRef(0);

  const { session } = useAuth();
  const { activeBaseUrl } = useWebAppRequest();
  const echoRef = useEcho();
  const typingTimerRef = useRef(null);

  const { headerOpacity, headerTranslate, heroOpacity, heroTranslate } =
    createScrollInterpolations(scrollY);

  /* ---------------- AUTO SCROLL ---------------- */

  const scrollToBottom = useCallback((animated = true) => {
    requestAnimationFrame(() => {
      scrollRef.current?.scrollToEnd({ animated });
    });
  }, []);

  useEffect(() => {
    const showEvent = Platform.OS === "ios" ? "keyboardWillShow" : "keyboardDidShow";
    const hideEvent = Platform.OS === "ios" ? "keyboardWillHide" : "keyboardDidHide";

    const showSubscription = Keyboard.addListener(showEvent, () => {
      setIsKeyboardVisible(true);
      requestAnimationFrame(() => {
        requestAnimationFrame(() => scrollToBottom(false));
      });
    });

    const hideSubscription = Keyboard.addListener(hideEvent, () => {
      setIsKeyboardVisible(false);
    });

    return () => {
      showSubscription.remove();
      hideSubscription.remove();
    };
  }, [scrollToBottom]);

  const normalizeAttachments = useCallback((selectedAssets = []) => {
    return selectedAssets
      .map((asset, index) => ({
        uri: asset?.uri || "",
        name: asset?.fileName || `message-${Date.now()}-${index}.jpg`,
        type: asset?.mimeType || "image/jpeg",
      }))
      .filter((asset) => Boolean(asset.uri));
  }, []);

  const pickAttachments = useCallback(async () => {
    try {
      const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!permission.granted) {
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ["images"],
        allowsMultipleSelection: true,
        selectionLimit: 10,
        quality: 0.85,
      });

      if (result.canceled || !result.assets?.length) {
        return;
      }

      const nextAttachments = normalizeAttachments(result.assets);
      if (!nextAttachments.length) {
        return;
      }

      setAttachments((current) => {
        const merged = [...current, ...nextAttachments];
        return merged.slice(0, 10);
      });
    } catch (_error) {
      // ignore picker failures and leave the composer untouched
    }
  }, [normalizeAttachments]);

  const removeAttachment = useCallback((indexToRemove) => {
    setAttachments((current) => current.filter((_, index) => index !== indexToRemove));
  }, []);

  /* ---------------- LOAD ---------------- */

  const loadConversation = useCallback(async () => {
    if (!parsedThreadId) return;

    const res = await fetchMessages({ threadId: parsedThreadId });

    setThread(res?.selected_thread || null);
    setMessages(res?.conversation || []);

    setLoading(false);

    //  ALWAYS scroll to bottom after load
    scrollToBottom(false);
  }, [parsedThreadId]);

  useEffect(() => {
    if (isKeyboardVisible) {
      scrollToBottom(false);
    }
  }, [isKeyboardVisible, messages, scrollToBottom]);

  useEffect(() => {
    loadConversation();
  }, [loadConversation]);

  /*  real-time polling */
  useEffect(() => {
    if (!parsedThreadId) return;

    const i = setInterval(async () => {
      try {
        const res = await fetchMessages({ threadId: parsedThreadId });
        setMessages(res?.conversation || []);
      } catch (_e) {
        // ignore transient polling errors
      }
    }, 3000);

    return () => clearInterval(i);
  }, [parsedThreadId]);

  /* ---------------- REALTIME (Echo) ---------------- */
  useEffect(() => {
    const echo = echoRef?.current;
    if (!echo || !session?.id || !parsedThreadId) return;

    try {
      const channel = echo.private(`users.${session.id}.messages`);

      channel.listen('.message.thread.updated', (event) => {
        const incomingThreadId = Number(event?.thread_id || 0);

        if (incomingThreadId === parsedThreadId) {
          fetchMessages({ threadId: parsedThreadId }).then((res) => {
            setMessages(res?.conversation || []);
          }).catch(() => {});
          return;
        }

        // For other threads we could trigger a lightweight refresh elsewhere
      });

      channel.listen('.message.typing', (event) => {
        const incomingThreadId = Number(event?.thread_id || 0);
        if (incomingThreadId !== parsedThreadId) return;

        const typing = Boolean(event?.typing);
        const userId = Number(event?.user_id || 0);
        if (userId === session.id) return; // ignore our own typing echoes

        setIsRecipientTyping(typing);
      });

      return () => {
        try {
          echo.leave(`users.${session.id}.messages`);
        } catch (_e) {}
      };
    } catch (_err) {
      // ignore echo errors
    }
  }, [echoRef, session?.id, parsedThreadId, fetchMessages]);

  /*  whenever messages change → stay at bottom */
  useEffect(() => {
    if (messages.length) {
      scrollToBottom(true);
    }
  }, [messages]);

  /* ---------------- SEND ---------------- */

  const handleSend = async () => {
    const text = reply.trim();
    if (!text && !attachments.length) return;

    const pendingAttachments = attachments;
    const pendingReply = reply;

    // optimistic UI: append local pending message immediately
    const optimisticId = `optimistic-${++optimisticCounterRef.current}`;
    const optimisticEntry = {
      id: optimisticId,
      message: text,
      time: 'Sending...',
      is_mine: true,
      is_pending: true,
      images: pendingAttachments.map((attachment) => ({
        url: attachment.uri,
        name: attachment.name,
      })),
    };
    setMessages((prev) => [...prev, optimisticEntry]);
    scrollToBottom(true);

    setSending(true);
    setReply("");
    setAttachments([]);
    setCopyTargetMessageId(null);
    // notify stop typing
    try {
      await fetch(`${activeBaseUrl}/api/mobile/messages/typing`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ thread_id: parsedThreadId, recipient_ids: parsedRecipientIds, typing: false }),
      });
    } catch (_e) {}

    try {
      await sendMessage({
        threadId: parsedThreadId,
        recipientIds: parsedRecipientIds,
        message: text,
        images: pendingAttachments,
      });
    } catch (_e) {
      setMessages((prev) => prev.filter((entry) => entry.id !== optimisticId));
      setReply(pendingReply);
      setAttachments(pendingAttachments);
      setIsRecipientTyping(false);
      setSending(false);
      return;
    }

    try {
      // try to sync with server; fetch will replace optimistic entry with persisted messages
      await loadConversation();
      scrollToBottom(true);
    } catch (_e) {
      // leave optimistic message in UI; polling or echo will reconcile
    } finally {
      // ensure typing indicator cleared locally after send
      setIsRecipientTyping(false);
      setSending(false);
    }
  };

  const copyMessageContent = useCallback(async (msg) => {
    const textContent = String(msg?.message || "").trim();
    const imageLinks = Array.isArray(msg?.images)
      ? msg.images.map((image) => String(image?.url || "").trim()).filter(Boolean)
      : [];
    const copyText = textContent || imageLinks.join("\n");

    if (!copyText) {
      return;
    }

    await Clipboard.setStringAsync(copyText);
    setCopyTargetMessageId(null);
  }, []);

  /* Typing indicator emitter (debounced) */
  const sendTyping = useCallback((typing) => {
    if (!parsedThreadId) return;
    try {
      fetch(`${activeBaseUrl}/api/mobile/messages/typing`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ thread_id: parsedThreadId, recipient_ids: parsedRecipientIds, typing }),
      }).catch(() => {});
    } catch (_e) {}
  }, [parsedThreadId, parsedRecipientIds, activeBaseUrl]);

  const onChangeReply = (value) => {
    setReply(value);

    // clear previous timer
    if (typingTimerRef.current) {
      clearTimeout(typingTimerRef.current);
      typingTimerRef.current = null;
    }

    // notify typing started
    sendTyping(true);

    // schedule stop typing after 2.5s of inactivity
    typingTimerRef.current = setTimeout(() => {
      sendTyping(false);
      typingTimerRef.current = null;
    }, 2500);
  };

  const handleRefresh = async () => {
    setRefreshing(true);
    await loadConversation();
    setRefreshing(false);
  };

  const label =
    thread?.custom_name ||
    thread?.name ||
    String(recipientName || "Recipient");

  const subtitle =
    thread?.subtitle ||
    String(recipientSubtitle || "Start a conversation");

    const initials = getInitials(label);
    const listBottomPadding = 112;

  /* ---------------- MESSAGE ---------------- */

  const renderMessage = (msg) => {
    const mine = msg?.is_mine;
    const messageImages = Array.isArray(msg?.images) ? msg.images : [];
    const showCopyAction = copyTargetMessageId === msg?.id;

    return (
      <Pressable
        key={msg.id}
        delayLongPress={220}
        onLongPress={() => setCopyTargetMessageId((current) => (current === msg?.id ? null : msg?.id))}
        onScrollBeginDrag={() => setCopyTargetMessageId(null)}
        className={`mb-3 flex-row items-end ${mine ? "justify-end" : "justify-start"}`}
      >
          {!mine ? (
            <View className="mr-2 h-9 w-9 items-center justify-center rounded-full bg-[#dbe7f5]">
              <Text className="text-[12px] font-semibold" style={{ color: APP_COLORS.primaryBlue }}>
                {initials}
              </Text>
            </View>
          ) : null}

        <View
          className="relative max-w-[80%] overflow-visible rounded-2xl px-4 py-3"
          style={{
            backgroundColor: mine ? APP_COLORS.primaryBlue : "#e8edf4",
          }}
        >
          {String(msg?.message || "").trim() ? (
            <Text style={{ color: mine ? "#fff" : APP_COLORS.primaryBlue }}>
              {msg.message}
            </Text>
          ) : null}

          {messageImages.length ? (
            <View className={String(msg?.message || "").trim() ? "mt-2 gap-2" : "gap-2"}>
              {messageImages.map((image, index) => (
                <View
                  key={`${msg.id}-image-${index}`}
                  className="overflow-hidden rounded-[18px]"
                  style={{
                    borderWidth: 1,
                    borderColor: mine ? "rgba(255,255,255,0.18)" : "rgba(30, 58, 138, 0.08)",
                    backgroundColor: mine ? "rgba(255,255,255,0.08)" : "#ffffff",
                  }}
                >
                  <Image
                    source={{ uri: image.url }}
                    style={{ width: 220, height: 220, backgroundColor: "#dbe7f5" }}
                    contentFit="cover"
                    transition={150}
                  />
                </View>
              ))}
            </View>
          ) : null}

          <Text
            className="text-[11px] mt-2"
            style={{
              color: mine ? "rgba(255,255,255,0.7)" : APP_COLORS.tabInactive,
            }}
          >
            {formatTime(msg.time)}
          </Text>

          {showCopyAction ? (
            <View
              className={`absolute ${mine ? "-top-11 right-0" : "-top-11 left-0"}`}
              pointerEvents="box-none"
            >
              <Pressable
                onPress={() => copyMessageContent(msg)}
                className="overflow-visible rounded-2xl px-3 py-2"
                style={{ backgroundColor: mine ? "rgba(15, 23, 42, 0.92)" : "rgba(30, 58, 138, 0.92)" }}
              >
                <View className="flex-row items-center gap-1.5">
                  <Feather name="copy" size={12} color="#fff" />
                  <Text className="text-[11px] font-semibold" style={{ color: "#fff" }}>
                    Copy
                  </Text>
                </View>
                <View
                  className={`absolute bottom-[-5px] h-2 w-2 rotate-45 ${mine ? "right-4" : "left-4"}`}
                  style={{ backgroundColor: mine ? "rgba(15, 23, 42, 0.92)" : "rgba(30, 58, 138, 0.92)" }}
                />
              </Pressable>
            </View>
          ) : null}
        </View>
      </Pressable>
    );
  };

  /* ---------------- UI ---------------- */

  return (
    <SafeAreaView className="flex-1 bg-[#f7f9fd]" edges={["bottom"]}>
      <KeyboardAvoidingView
        className="flex-1"
        behavior={Platform.OS === "ios" ? "padding" : "height"}
        keyboardVerticalOffset={Platform.OS === "ios" ? 90 : 0}
      >
        {/* HEADER */}
        <View className="flex-row items-center px-4 py-2">
          <Pressable onPress={() => router.push("/message")}>
            <Feather name="chevron-left" size={26} color={APP_COLORS.primaryBlue} />
          </Pressable>

          <Animated.Text
            numberOfLines={1}
            className="flex-1 text-center text-[18px] font-semibold"
            style={{
              color: APP_COLORS.primaryBlue,
              opacity: headerOpacity,
              transform: [{ translateY: headerTranslate }],
            }}
          >
            {label}
          </Animated.Text>

          <View className="w-6" />
        </View>

        {/* CONTENT */}
        <Animated.ScrollView
          ref={scrollRef}
          onScroll={createScrollHandler(scrollY)}
          onScrollBeginDrag={() => setCopyTargetMessageId(null)}
          scrollEventThrottle={16}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} />
          }
          keyboardShouldPersistTaps="handled"
          contentContainerStyle={{ paddingBottom: 20, paddingHorizontal: 8 }}
        >
          {/* HERO */}
          <View className="items-center py-8">
            <View
              className="h-24 w-24 rounded-full items-center justify-center"
              style={{ backgroundColor: APP_COLORS.primaryBlueLight }}
            >
              <Text className="text-3xl font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                {initials}
              </Text>
            </View>

            <Animated.Text
              className="mt-4 text-2xl font-extrabold"
              style={{
                color: APP_COLORS.primaryBlue,
                opacity: heroOpacity,
                transform: [{ translateY: heroTranslate }],
              }}
            >
              {label}
            </Animated.Text>

            <Text className="text-sm text-gray-400 mt-1">{subtitle}</Text>
          </View>

          {/* MESSAGES */}
          {loading ? (
            <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
          ) : (
            messages.map(renderMessage)
          )}
        </Animated.ScrollView>

        {/* INPUT */}
        <View className="px-3 py-2 bg-white border-t border-[#e5edf6]">
          {attachments.length ? (
            <View className="mb-2 flex-row flex-wrap gap-2">
              {attachments.map((attachment, index) => (
                <View
                  key={`${attachment.uri}-${index}`}
                  className="w-[108px] overflow-hidden rounded-[16px] border border-[#dbe7f5] bg-[#f8fbff]"
                >
                  <Image
                    source={{ uri: attachment.uri }}
                    style={{ width: 108, height: 88, backgroundColor: APP_COLORS.primaryBlueLight }}
                    contentFit="cover"
                  />
                  <View className="flex-row items-center justify-between gap-2 px-2 py-1.5">
                    <Text className="flex-1 text-[10px] font-semibold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={1}>
                      {attachment.name || "Image"}
                    </Text>
                    <Pressable onPress={() => removeAttachment(index)} hitSlop={8}>
                      <Feather name="x" size={14} color={APP_COLORS.primaryBlue} />
                    </Pressable>
                  </View>
                </View>
              ))}
            </View>
          ) : null}

          <View className="flex-row items-center bg-[#f1f5fb] rounded-full px-4 py-2">
            <Pressable
              onPress={pickAttachments}
              disabled={sending}
              className="mr-2 h-10 w-10 items-center justify-center rounded-full"
              style={{ backgroundColor: APP_COLORS.primaryBlueLight }}
            >
              <Feather name="image" size={16} color={APP_COLORS.primaryBlue} />
            </Pressable>

            <View className="flex-1">
              {isRecipientTyping ? (
                <Text className="text-sm mb-1" style={{ color: APP_COLORS.tabInactive }}>
                  {String(recipientName || 'Recipient')} is typing...
                </Text>
              ) : null}

              <TextInput
                value={reply}
                onChangeText={onChangeReply}
                placeholder="Write a message"
                className="flex-1 py-2"
                onFocus={() => scrollToBottom(false)}
              />
            </View>

            <Pressable
              onPress={handleSend}
              disabled={sending || (!reply.trim() && !attachments.length)}
              className="ml-2 h-10 w-10 rounded-full items-center justify-center"
              style={{
                backgroundColor: (reply.trim() || attachments.length)
                  ? APP_COLORS.primaryBlue
                  : APP_COLORS.primaryBlueLight,
              }}
            >
              {sending ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <Feather name="send" size={16} color="#fff" />
              )}
            </Pressable>
          </View>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}