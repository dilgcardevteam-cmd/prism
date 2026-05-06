import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Animated,
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
    loadConversation();
  }, [loadConversation]);

  /*  real-time polling */
  useEffect(() => {
    if (!parsedThreadId) return;

    const i = setInterval(async () => {
      const res = await fetchMessages({ threadId: parsedThreadId });
      setMessages(res?.conversation || []);
    }, 10000);

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
    if (!text) return;

    setSending(true);
    setReply("");
    // notify stop typing
    try {
      await fetch(`${activeBaseUrl}/api/mobile/messages/typing`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ thread_id: parsedThreadId, recipient_ids: parsedRecipientIds, typing: false }),
      });
    } catch (_e) {}

    await sendMessage({
      threadId: parsedThreadId,
      recipientIds: parsedRecipientIds,
      message: text,
    });

    await loadConversation();
    scrollToBottom(true);

    setSending(false);
  };

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

    return (
      <View
        key={msg.id}
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
            className="max-w-[80%] rounded-2xl px-4 py-3"
          style={{
            backgroundColor: mine ? APP_COLORS.primaryBlue : "#e8edf4",
          }}
        >
          <Text style={{ color: mine ? "#fff" : APP_COLORS.primaryBlue }}>
            {msg.message}
          </Text>

          <Text
            className="text-[11px] mt-2"
            style={{
              color: mine ? "rgba(255,255,255,0.7)" : APP_COLORS.tabInactive,
            }}
          >
            {formatTime(msg.time)}
          </Text>
        </View>
      </View>
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
          <Pressable onPress={() => router.back()}>
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
          <View className="flex-row items-center bg-[#f1f5fb] rounded-full px-4 py-2">
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
              />
            </View>

            <Pressable
              onPress={handleSend}
              disabled={!reply.trim() || sending}
              className="ml-2 h-10 w-10 rounded-full items-center justify-center"
              style={{
                backgroundColor: reply.trim()
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