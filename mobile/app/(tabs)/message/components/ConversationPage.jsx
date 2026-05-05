import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useCallback, useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  Image,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { APP_COLORS } from "../../../../constants/theme";
import { useMessagesApi } from "../../../../hooks/useMessagesApi";

function getInitials(name) {
  const parts = String(name || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) {
    return "U";
  }

  const first = parts[0]?.[0] || "U";
  const second = parts[1]?.[0] || parts[0]?.[1] || "";
  return `${first}${second}`.toUpperCase();
}

function formatConversationTime(value) {
  if (!value) {
    return "";
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return String(value);
  }

  const month = parsed.toLocaleString("en-US", { month: "short" });
  const day = parsed.getDate();
  const year = parsed.getFullYear();
  const hours24 = parsed.getHours();
  const hours = hours24 % 12 || 12;
  const minutes = String(parsed.getMinutes()).padStart(2, "0");
  const period = hours24 >= 12 ? "PM" : "AM";

  return `${month} ${day}, ${year} ${hours}:${minutes} ${period}`;
}

export default function ConversationPage() {
  const router = useRouter();
  const { threadId, recipientIds, recipientName, recipientSubtitle } = useLocalSearchParams();
  const { fetchMessages, sendMessage } = useMessagesApi();

  const parsedThreadId = Number(threadId || 0);
  const parsedRecipientIds = useMemo(() => {
    return String(recipientIds || "")
      .split(",")
      .map((value) => Number(value.trim()))
      .filter((value) => value > 0);
  }, [recipientIds]);

  const [selectedThread, setSelectedThread] = useState(null);
  const [conversation, setConversation] = useState([]);
  const [isLoading, setIsLoading] = useState(parsedThreadId > 0);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const [replyMessage, setReplyMessage] = useState("");
  const [isSending, setIsSending] = useState(false);

  const loadConversation = useCallback(async ({ silent = false } = {}) => {
    if (parsedThreadId <= 0) {
      setSelectedThread(null);
      setConversation([]);
      setIsLoading(false);
      return;
    }

    try {
      if (!silent) {
        setIsLoading(true);
      }

      setErrorMessage(null);
      const response = await fetchMessages({ threadId: parsedThreadId });
      setSelectedThread(response?.selected_thread || null);
      setConversation(Array.isArray(response?.conversation) ? response.conversation : []);
    } catch (error) {
      setErrorMessage(error?.message || "Unable to load conversation.");
      setConversation([]);
    } finally {
      if (!silent) {
        setIsLoading(false);
      }
    }
  }, [fetchMessages, parsedThreadId]);

  useEffect(() => {
    if (parsedThreadId > 0) {
      loadConversation();
      return undefined;
    }

    setIsLoading(false);
    return undefined;
  }, [loadConversation, parsedThreadId]);

  useEffect(() => {
    if (parsedThreadId <= 0) {
      return undefined;
    }

    const intervalId = setInterval(() => {
      loadConversation({ silent: true });
    }, 10000);

    return () => clearInterval(intervalId);
  }, [loadConversation, parsedThreadId]);

  const handleRefresh = useCallback(async () => {
    if (parsedThreadId <= 0) {
      return;
    }

    setIsRefreshing(true);
    try {
      await loadConversation({ silent: true });
    } finally {
      setIsRefreshing(false);
    }
  }, [loadConversation, parsedThreadId]);

  const selectedThreadLabel = selectedThread?.custom_name || selectedThread?.name || String(recipientName || "Recipient");
  const selectedThreadSubtitle = selectedThread?.subtitle || String(recipientSubtitle || "Start a conversation");
  const selectedThreadInitials = getInitials(selectedThreadLabel);

  const renderAvatar = useCallback((name, size = 112) => {
    return (
      <View
        className="items-center justify-center overflow-hidden rounded-full"
        style={{ width: size, height: size, backgroundColor: APP_COLORS.primaryBlueLight }}
      >
        <Text
          className="font-bold"
          style={{
            color: APP_COLORS.primaryBlue,
            fontSize: Math.max(18, size * 0.4),
          }}
        >
          {getInitials(name)}
        </Text>
      </View>
    );
  }, []);

  const renderMessageImages = useCallback((images = []) => {
    if (!Array.isArray(images) || !images.length) {
      return null;
    }

    const columns = images.length > 1 ? 2 : 1;

    return (
      <View className={`mt-2 ${columns > 1 ? "flex-row flex-wrap gap-2" : ""}`}>
        {images.map((image) => (
          <View
            key={image?.url}
            className={`overflow-hidden rounded-[18px] ${columns > 1 ? "w-[48%]" : "w-full"}`}
            style={{ backgroundColor: "#e2e8f0" }}
          >
            <Image
              source={{ uri: image?.url }}
              style={{ width: "100%", height: columns > 1 ? 120 : 220 }}
              resizeMode="cover"
            />
          </View>
        ))}
      </View>
    );
  }, []);

  const renderMessageBubble = useCallback((entry) => {
    const mine = Boolean(entry?.is_mine);
    const messageText = String(entry?.message || "").trim();
    const images = Array.isArray(entry?.images) ? entry.images : [];

    return (
      <View
        key={entry?.id}
        className={`mb-3 flex-row items-end gap-2 ${mine ? "justify-end" : "justify-start"}`}
      >
        {!mine ? (
          <View className="mb-1">
            <View
              className="items-center justify-center overflow-hidden rounded-full"
              style={{ width: 30, height: 30, backgroundColor: APP_COLORS.primaryBlueLight }}
            >
              <Text className="text-[10px] font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                {selectedThreadInitials}
              </Text>
            </View>
          </View>
        ) : null}

        <View
          className="max-w-[84%] rounded-[22px] px-4 py-3"
          style={{
            backgroundColor: mine ? APP_COLORS.primaryBlue : "#e8edf4",
            borderWidth: mine ? 0 : 1,
            borderColor: mine ? "transparent" : "#d7e0ef",
            shadowColor: mine ? "transparent" : "#0f172a",
            shadowOpacity: mine ? 0 : 0.05,
            shadowRadius: mine ? 0 : 10,
            shadowOffset: mine ? { width: 0, height: 0 } : { width: 0, height: 4 },
            elevation: mine ? 0 : 1,
          }}
        >
          {images.length ? renderMessageImages(images) : null}
          {messageText ? (
            <Text className="text-[15px] leading-6" style={{ color: mine ? "#ffffff" : APP_COLORS.statusNeutral }}>
              {messageText}
            </Text>
          ) : null}
          <Text
            className={`mt-2 text-[11px] ${mine ? "text-white/75" : ""}`}
            style={{ color: mine ? "rgba(255,255,255,0.8)" : APP_COLORS.tabInactive }}
          >
            {entry?.time || formatConversationTime(entry?.created_at || entry?.created_at_raw || "")}
          </Text>
        </View>
      </View>
    );
  }, [renderMessageImages, selectedThreadInitials]);

  const handleSendReplyMessage = useCallback(async () => {
    const text = String(replyMessage || "").trim();

    if (!text) {
      setErrorMessage("Type a message before sending.");
      return;
    }

    if (parsedThreadId <= 0 && !parsedRecipientIds.length) {
      setErrorMessage("No recipient selected.");
      return;
    }

    try {
      setIsSending(true);
      setErrorMessage(null);

      const payload = await sendMessage({
        threadId: parsedThreadId,
        recipientIds: parsedRecipientIds,
        message: text,
      });

      const nextThreadId = Number(payload?.thread_id || 0);
      setReplyMessage("");

      if (nextThreadId > 0 && parsedThreadId <= 0) {
        router.replace({
          pathname: "/(tabs)/message/[threadId]",
          params: { threadId: String(nextThreadId) },
        });
        return;
      }

      if (parsedThreadId > 0) {
        await loadConversation({ silent: true });
      } else if (nextThreadId > 0) {
        router.replace({
          pathname: "/(tabs)/message/[threadId]",
          params: { threadId: String(nextThreadId) },
        });
      }
    } catch (error) {
      setErrorMessage(error?.message || "Unable to send message.");
    } finally {
      setIsSending(false);
    }
  }, [loadConversation, parsedRecipientIds, parsedThreadId, replyMessage, router, sendMessage]);

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: "#f7f9fd" }} edges={["bottom"]}>
      <KeyboardAvoidingView
        className="flex-1"
        behavior={Platform.OS === "ios" ? "padding" : "height"}
        keyboardVerticalOffset={Platform.OS === "ios" ? 0 : 0}
      >
        <View className="flex-1 px-4 pt-3">
          <View className="mb-3 flex-row items-center justify-between gap-3">
            <Pressable
              onPress={() => router.replace("/(tabs)/message")}
              className="h-10 w-10 items-center justify-center rounded-full"
              style={{ backgroundColor: "transparent" }}
            >
              <Feather name="chevron-left" size={28} color={APP_COLORS.primaryBlue} />
            </Pressable>

            <Text className="flex-1 px-2 text-center text-[18px] font-semibold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={1}>
              {selectedThreadLabel}
            </Text>

            <View className="h-10 w-10" />
          </View>

          <View style={{ height: 1, backgroundColor: "#d9e3f1" }} />

          <View className="flex-1 py-3">
            <View
              className="flex-1 overflow-hidden rounded-[30px] border border-[#d7e0ef] bg-white"
              style={{
                shadowColor: "#0f172a",
                shadowOpacity: 0.05,
                shadowRadius: 16,
                shadowOffset: { width: 0, height: 8 },
                elevation: 2,
              }}
            >
              <ScrollView
                className="flex-1 px-4 pt-4"
                contentContainerStyle={{ flexGrow: 1, paddingBottom: 24 }}
                refreshControl={parsedThreadId > 0 ? <RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} tintColor={APP_COLORS.primaryBlue} /> : undefined}
                showsVerticalScrollIndicator={false}
                keyboardShouldPersistTaps="handled"
                automaticallyAdjustKeyboardInsets
              >
                <View className="mb-6 items-center px-4 py-5">
                  {renderAvatar(selectedThreadLabel, 112)}
                  <Text className="mt-4 text-center text-[28px] font-extrabold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={1}>
                    {selectedThreadLabel}
                  </Text>
                  <Text className="mt-1 text-center text-[15px] leading-5" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={2}>
                    {selectedThreadSubtitle}
                  </Text>
                </View>
                {isLoading ? (
                  <View className="flex-1 items-center justify-center py-14">
                    <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
                    <Text className="mt-4 text-sm font-medium" style={{ color: APP_COLORS.textSubtle }}>
                      Loading conversation...
                    </Text>
                  </View>
                ) : errorMessage ? (
                  <View className="flex-1 items-center justify-center py-10">
                    <Text className="text-center text-sm" style={{ color: APP_COLORS.primaryRed }}>
                      {errorMessage}
                    </Text>
                  </View>
                ) : conversation.length ? (
                  <View className="pb-2 pt-1">
                    {conversation.map((entry) => renderMessageBubble(entry))}
                  </View>
                ) : (
                  <View className="flex-1 items-center justify-center px-4 py-10">
                    <View className="mb-4 h-20 w-20 items-center justify-center rounded-full" style={{ backgroundColor: APP_COLORS.primaryBlueLight }}>
                      <Feather name="message-circle" size={32} color={APP_COLORS.primaryBlue} />
                    </View>
                    <Text className="text-center text-[18px] font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                      No messages yet
                    </Text>
                    <Text className="mt-2 max-w-sm text-center text-sm leading-6" style={{ color: APP_COLORS.textSubtle }}>
                      Start the conversation by sending the first message below.
                    </Text>
                  </View>
                )}
              </ScrollView>

              <View className="border-t border-[#e5edf6] px-4 py-4">
                <View className="flex-row items-center gap-3 rounded-[20px] border border-[#d7e0ef] bg-[#f8fbff] px-4 py-3">
                  <TextInput
                    value={replyMessage}
                    onChangeText={setReplyMessage}
                    placeholder="Write a message."
                    placeholderTextColor={APP_COLORS.tabInactive}
                    className="flex-1 text-[15px]"
                    style={{ color: APP_COLORS.primaryBlue }}
                    returnKeyType="send"
                    onSubmitEditing={handleSendReplyMessage}
                  />

                  <Pressable
                    onPress={handleSendReplyMessage}
                    disabled={isSending || !String(replyMessage || "").trim()}
                    className="h-11 w-11 items-center justify-center rounded-full"
                    style={{
                      backgroundColor: isSending || !String(replyMessage || "").trim() ? APP_COLORS.primaryBlueLight : APP_COLORS.primaryBlue,
                      opacity: isSending ? 0.8 : 1,
                    }}
                  >
                    {isSending ? (
                      <ActivityIndicator size="small" color={APP_COLORS.primaryBlue} />
                    ) : (
                      <Feather name="send" size={16} color="#ffffff" />
                    )}
                  </Pressable>
                </View>

                {errorMessage ? (
                  <Text className="mt-2 text-xs" style={{ color: APP_COLORS.primaryRed }}>
                    {errorMessage}
                  </Text>
                ) : null}
              </View>
            </View>
          </View>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}