import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams } from "expo-router";
import * as ImagePicker from "expo-image-picker";
import { useCallback, useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  Image,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { APP_COLORS } from "../../../constants/theme";
import { useMessagesApi } from "../../../hooks/useMessagesApi";

const AVATAR_PALETTE = [
  APP_COLORS.primaryBlue,
  APP_COLORS.primaryRed,
  "#0f766e",
  "#7c3aed",
  "#ea580c",
  "#059669",
];

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

function colorFromSeed(seed) {
  const text = String(seed || "0");
  let hash = 0;

  for (let index = 0; index < text.length; index += 1) {
    hash = (hash * 31 + text.charCodeAt(index)) >>> 0;
  }

  return AVATAR_PALETTE[hash % AVATAR_PALETTE.length];
}

function formatConversationTime(value) {
  if (!value) {
    return "";
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return String(value);
  }

  return new Intl.DateTimeFormat("en-PH", {
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  }).format(parsed);
}

export default function MessageConversationScreen() {
  const { threadId } = useLocalSearchParams();
  const { fetchMessages, sendMessage } = useMessagesApi();

  const parsedThreadId = Number(threadId || 0);

  const [selectedThread, setSelectedThread] = useState(null);
  const [conversation, setConversation] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const [replyMessage, setReplyMessage] = useState("");
  const [replyImage, setReplyImage] = useState(null);
  const [isSending, setIsSending] = useState(false);

  const loadConversation = useCallback(async ({ silent = false } = {}) => {
    if (parsedThreadId <= 0) {
      setErrorMessage("Invalid conversation.");
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
    loadConversation();
  }, [loadConversation]);

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
    setIsRefreshing(true);
    try {
      await loadConversation({ silent: true });
    } finally {
      setIsRefreshing(false);
    }
  }, [loadConversation]);

  const pickImage = useCallback(async () => {
    try {
      const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!permission.granted) {
        setErrorMessage("Please allow photo library access to attach images.");
        return null;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ["images"],
        allowsEditing: false,
        quality: 0.85,
      });

      if (result.canceled || !result.assets?.[0]) {
        return null;
      }

      const asset = result.assets[0];
      return {
        uri: asset.uri,
        name: asset.fileName || `message-${Date.now()}.jpg`,
        type: asset.mimeType || "image/jpeg",
      };
    } catch (_error) {
      setErrorMessage("Unable to open the photo picker.");
      return null;
    }
  }, []);

  const selectedThreadLabel = selectedThread?.custom_name || selectedThread?.name || "Conversation";
  const selectedThreadSubtitle = selectedThread?.subtitle || "Messaging thread";

  const renderAvatar = useCallback((name, seed, size = 44, compact = false) => {
    const initials = getInitials(name);
    const backgroundColor = colorFromSeed(seed);

    return (
      <View
        className="items-center justify-center overflow-hidden rounded-full"
        style={{ width: size, height: size, backgroundColor }}
      >
        <Text
          className="font-extrabold"
          style={{
            color: "#ffffff",
            fontSize: compact ? Math.max(10, size * 0.28) : Math.max(12, size * 0.32),
          }}
        >
          {initials}
        </Text>
      </View>
    );
  }, []);

  const selectedThreadAvatarSeed = selectedThread?.custom_name || selectedThread?.name || String(parsedThreadId || 0);

  const renderMessageImages = useCallback((images = []) => {
    if (!Array.isArray(images) || !images.length) {
      return null;
    }

    const imageCount = images.length;
    const columns = imageCount > 1 ? 2 : 1;

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

  const renderImagePreview = useCallback((image, onRemove) => {
    if (!image?.uri) {
      return null;
    }

    return (
      <View className="mt-3 overflow-hidden rounded-[22px] border border-[#dbe5f1] bg-[#f8fbff]">
        <View className="flex-row items-center justify-between gap-3 px-3 py-2">
          <Text className="flex-1 text-xs font-semibold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={1}>
            {image.name || "Attachment"}
          </Text>
          <Pressable onPress={onRemove} className="h-8 w-8 items-center justify-center rounded-full bg-white">
            <Feather name="x" size={16} color={APP_COLORS.primaryBlue} />
          </Pressable>
        </View>
        <Image
          source={{ uri: image.uri }}
          style={{ width: "100%", height: 180 }}
          resizeMode="cover"
        />
      </View>
    );
  }, []);

  const handleSendReplyMessage = useCallback(async () => {
    const text = String(replyMessage || "").trim();

    if (!text && !replyImage) {
      setErrorMessage("Type a message or attach an image.");
      return;
    }

    try {
      setIsSending(true);
      setErrorMessage(null);

      await sendMessage({
        threadId: parsedThreadId,
        message: text,
        image: replyImage,
      });

      setReplyMessage("");
      setReplyImage(null);
      await loadConversation({ silent: true });
    } catch (error) {
      setErrorMessage(error?.message || "Unable to send message.");
    } finally {
      setIsSending(false);
    }
  }, [loadConversation, parsedThreadId, replyImage, replyMessage, sendMessage]);

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
          <View className="mb-1">{renderAvatar(selectedThreadLabel, selectedThreadAvatarSeed, 30, true)}</View>
        ) : null}

        <View
          className="max-w-[84%] rounded-[22px] px-4 py-3"
          style={{
            backgroundColor: mine ? APP_COLORS.primaryBlue : APP_COLORS.backgroundCard,
            borderWidth: mine ? 0 : 1,
            borderColor: mine ? "transparent" : `${APP_COLORS.accentBorder}20`,
            shadowColor: mine ? "transparent" : "#0f172a",
            shadowOpacity: mine ? 0 : 0.04,
            shadowRadius: 10,
            shadowOffset: { width: 0, height: 4 },
            elevation: mine ? 0 : 1,
          }}
        >
          {images.length ? renderMessageImages(images) : null}
          {messageText ? (
            <Text
              className="text-[15px] leading-6"
              style={{ color: mine ? "#ffffff" : APP_COLORS.primaryBlue }}
            >
              {messageText}
            </Text>
          ) : null}
          <Text
            className={`mt-2 text-[11px] ${mine ? "text-white/75" : ""}`}
            style={{ color: mine ? "rgba(255,255,255,0.78)" : APP_COLORS.tabInactive }}
          >
            {entry?.time || formatConversationTime(entry?.created_at || entry?.created_at_raw || "")}
          </Text>
        </View>
      </View>
    );
  }, [renderAvatar, renderMessageImages, selectedThreadAvatarSeed, selectedThreadLabel]);

  const selectedThreadMembers = useMemo(() => {
    return Array.isArray(selectedThread?.members) ? selectedThread.members : [];
  }, [selectedThread?.members]);

  return (
    <SafeAreaView className="flex-1 bg-[#f8fafc]" edges={[]}>
      <ScrollView
        className="flex-1"
        contentContainerStyle={{ flexGrow: 1 }}
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} tintColor={APP_COLORS.primaryBlue} />}
        showsVerticalScrollIndicator={false}
      >
        <View className="flex-1 rounded-t-[30px] bg-white px-4 pb-6 pt-4">
          <View className="mt-2 overflow-hidden rounded-[28px] border border-[#dbe5f1] bg-white">
            <View className="border-b border-[#e5edf6] px-4 py-4">
              <View className="flex-row items-start gap-3">
                {renderAvatar(selectedThreadLabel, selectedThreadAvatarSeed, 48)}
                <View className="flex-1">
                  <Text className="text-lg font-extrabold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={1}>
                    {selectedThreadLabel}
                  </Text>
                  <Text className="mt-1 text-sm leading-5" style={{ color: APP_COLORS.textSubtle }}>
                    {selectedThreadSubtitle}
                  </Text>
                </View>
              </View>

              {selectedThread?.is_group && selectedThreadMembers.length ? (
                <View className="mt-4 flex-row flex-wrap gap-2">
                  {selectedThreadMembers.slice(0, 5).map((member) => (
                    <View
                      key={member.idno}
                      className="flex-row items-center gap-2 rounded-full px-3 py-2"
                      style={{ backgroundColor: APP_COLORS.accentSurface }}
                    >
                      <View className="h-6 w-6 items-center justify-center rounded-full" style={{ backgroundColor: APP_COLORS.primaryBlue }}>
                        <Text className="text-[9px] font-extrabold text-white">{getInitials(member.name)}</Text>
                      </View>
                      <Text className="max-w-[120px] text-xs font-semibold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={1}>
                        {member.name}
                      </Text>
                    </View>
                  ))}
                </View>
              ) : null}
            </View>

            <View className="px-4 py-4">
              {isLoading ? (
                <View className="items-center justify-center py-14">
                  <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
                  <Text className="mt-4 text-sm font-medium" style={{ color: APP_COLORS.textSubtle }}>
                    Loading conversation...
                  </Text>
                </View>
              ) : errorMessage ? (
                <View className="items-center justify-center py-10">
                  <Text className="text-center text-sm" style={{ color: APP_COLORS.primaryRed }}>
                    {errorMessage}
                  </Text>
                </View>
              ) : conversation.length ? conversation.map((entry) => renderMessageBubble(entry)) : (
                <View className="items-center justify-center py-10">
                  <View className="mb-4 h-16 w-16 items-center justify-center rounded-full" style={{ backgroundColor: APP_COLORS.primaryBlueLight }}>
                    <Feather name="message-circle" size={28} color={APP_COLORS.primaryBlue} />
                  </View>
                  <Text className="text-center text-base font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                    No messages yet
                  </Text>
                  <Text className="mt-2 max-w-xs text-center text-sm leading-6" style={{ color: APP_COLORS.textSubtle }}>
                    Start the conversation with a short note or attach an image.
                  </Text>
                </View>
              )}
            </View>

            <View className="border-t border-[#e5edf6] px-4 py-4">
              <Text className="text-xs font-semibold uppercase tracking-wide" style={{ color: APP_COLORS.tabInactive }}>
                Reply
              </Text>

              <TextInput
                value={replyMessage}
                onChangeText={setReplyMessage}
                placeholder="Write a reply..."
                placeholderTextColor={APP_COLORS.tabInactive}
                multiline
                className="mt-2 min-h-[92px] rounded-[18px] border border-[#dbe5f1] bg-[#f8fbff] px-4 py-3 text-sm"
                style={{ color: APP_COLORS.primaryBlue, textAlignVertical: "top" }}
              />

              {renderImagePreview(replyImage, () => setReplyImage(null))}

              <View className="mt-4 flex-row items-center justify-between gap-3">
                <Pressable
                  onPress={async () => {
                    const image = await pickImage();
                    if (image) {
                      setReplyImage(image);
                    }
                  }}
                  className="flex-row items-center gap-2 rounded-full px-4 py-3"
                  style={{ backgroundColor: APP_COLORS.primaryBlueLight }}
                >
                  <Feather name="image" size={16} color={APP_COLORS.primaryBlue} />
                  <Text className="text-sm font-semibold" style={{ color: APP_COLORS.primaryBlue }}>
                    Add image
                  </Text>
                </Pressable>

                <Pressable
                  onPress={handleSendReplyMessage}
                  disabled={isSending}
                  className="flex-row items-center gap-2 rounded-full px-5 py-3"
                  style={{ backgroundColor: APP_COLORS.primaryBlue, opacity: isSending ? 0.72 : 1 }}
                >
                  {isSending ? <ActivityIndicator size="small" color="#ffffff" /> : <Feather name="send" size={16} color="#ffffff" />}
                  <Text className="text-sm font-bold text-white">Send</Text>
                </Pressable>
              </View>
            </View>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
