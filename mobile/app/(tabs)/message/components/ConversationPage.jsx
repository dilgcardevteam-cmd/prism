import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Animated,
  Image,
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
import { createScrollHandler, createScrollInterpolations } from "../../../../utils/animations";

/* ---------------- UTILITIES ---------------- */

const getInitials = (name) => {
  const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return "U";
  return `${parts[0][0]}${parts[1]?.[0] || parts[0][1] || ""}`.toUpperCase();
};

const formatTime = (value) => {
  if (!value) return "";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;

  const month = d.toLocaleString("en-US", { month: "short" });
  const day = d.getDate();
  const year = d.getFullYear();
  const h = d.getHours();
  const m = String(d.getMinutes()).padStart(2, "0");
  const period = h >= 12 ? "PM" : "AM";

  return `${month} ${day}, ${year} ${h % 12 || 12}:${m} ${period}`;
};

/* ---------------- MAIN ---------------- */

export default function ConversationPage() {
  const router = useRouter();
  const { threadId, recipientIds, recipientName, recipientSubtitle } =
    useLocalSearchParams();

  const { fetchMessages, sendMessage } = useMessagesApi();

  /* ---------------- PARSED PARAMS ---------------- */

  const parsedThreadId = Number(threadId || 0);

  const parsedRecipientIds = useMemo(() => {
    return String(recipientIds || "")
      .split(",")
      .map((v) => Number(v.trim()))
      .filter((v) => v > 0);
  }, [recipientIds]);

  /* ---------------- STATE ---------------- */

  const [thread, setThread] = useState(null);
  const [messages, setMessages] = useState([]);
  const [reply, setReply] = useState("");
  const [loading, setLoading] = useState(parsedThreadId > 0);
  const [refreshing, setRefreshing] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState(null);

  /* ---------------- ANIMATION ---------------- */

  const scrollY = useRef(new Animated.Value(0)).current;
  
  const { headerOpacity, headerTranslate, heroOpacity, heroTranslate } =
    createScrollInterpolations(scrollY);

  /* ---------------- DATA ---------------- */

  const loadConversation = useCallback(async ({ silent = false } = {}) => {
    if (!parsedThreadId) return;

    try {
      if (!silent) setLoading(true);
      setError(null);

      const res = await fetchMessages({ threadId: parsedThreadId });

      setThread(res?.selected_thread || null);
      setMessages(res?.conversation || []);
    } catch (e) {
      setError(e?.message || "Failed to load");
    } finally {
      if (!silent) setLoading(false);
    }
  }, [parsedThreadId]);

  useEffect(() => {
    loadConversation();
  }, [loadConversation]);

  useEffect(() => {
    if (!parsedThreadId) return;

    const i = setInterval(() => loadConversation({ silent: true }), 10000);
    return () => clearInterval(i);
  }, [parsedThreadId]);

  /* ---------------- ACTIONS ---------------- */

  const handleRefresh = async () => {
    setRefreshing(true);
    await loadConversation({ silent: true });
    setRefreshing(false);
  };

  const handleSend = async () => {
    const text = reply.trim();
    if (!text) return;

    try {
      setSending(true);
      setReply("");

      const res = await sendMessage({
        threadId: parsedThreadId,
        recipientIds: parsedRecipientIds,
        message: text,
      });

      const nextId = Number(res?.thread_id || 0);

      if (nextId && !parsedThreadId) {
        router.replace({
          pathname: "/(tabs)/message/[threadId]",
          params: { threadId: String(nextId) },
        });
        return;
      }

      await loadConversation({ silent: true });
    } catch (e) {
      setError(e?.message || "Send failed");
    } finally {
      setSending(false);
    }
  };

  /* ---------------- DERIVED ---------------- */

  const label =
    thread?.custom_name ||
    thread?.name ||
    String(recipientName || "Recipient");

  const subtitle =
    thread?.subtitle ||
    String(recipientSubtitle || "Start a conversation");

  const initials = getInitials(label);

  /* ---------------- RENDER ---------------- */

  const renderMessage = (msg) => {
    const mine = msg?.is_mine;

    return (
      <View
        key={msg.id}
        className={`mb-3 flex-row ${mine ? "justify-end" : "justify-start"}`}
      >
        <View
          className="max-w-[80%] px-4 py-3 rounded-2xl"
          style={{
            backgroundColor: mine
              ? APP_COLORS.primaryBlue
              : "#e8edf4",
          }}
        >
          <Text style={{ color: mine ? "#fff" : APP_COLORS.primaryBlue }}>
            {msg.message}
          </Text>

          <Text
            className="text-[11px] mt-2"
            style={{
              color: mine
                ? "rgba(255,255,255,0.7)"
                : APP_COLORS.tabInactive,
            }}
          >
            {formatTime(msg.created_at)}
          </Text>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView className="flex-1 bg-[#f7f9fd]" edges={["bottom"]}>
      <KeyboardAvoidingView
        className="flex-1"
        behavior={Platform.OS === "ios" ? "padding" : "height"}
      >
        {/* HEADER */}
        <View className="flex-row items-center px-4 py-2">
          <Pressable onPress={() => router.back()}>
            <Feather
              name="chevron-left"
              size={26}
              color={APP_COLORS.primaryBlue}
            />
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
          onScroll={createScrollHandler(scrollY)}
          scrollEventThrottle={16}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
            />
          }
          className="flex-1 px-4"
        >
          {/* HERO */}
          <View className="items-center py-8">
            <View
              className="h-24 w-24 rounded-full items-center justify-center"
              style={{ backgroundColor: APP_COLORS.primaryBlueLight }}
            >
              <Text
                className="text-3xl font-bold"
                style={{ color: APP_COLORS.primaryBlue }}
              >
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

            <Text className="text-sm text-gray-400 mt-1">
              {subtitle}
            </Text>
          </View>

          {/* MESSAGES */}
          {loading ? (
            <ActivityIndicator
              size="large"
              color={APP_COLORS.primaryBlue}
            />
          ) : (
            messages.map(renderMessage)
          )}
        </Animated.ScrollView>

        {/* INPUT */}
        <View className="px-3 py-2 bg-white border-t border-[#e5edf6]">
          <View className="flex-row items-center bg-[#f1f5fb] rounded-full px-4 py-2">
            <TextInput
              value={reply}
              onChangeText={setReply}
              placeholder="Write a message"
              className="flex-1"
            />

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