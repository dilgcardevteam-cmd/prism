import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams } from "expo-router";
import * as ImagePicker from "expo-image-picker";
import { LinearGradient } from "expo-linear-gradient";
import { useCallback, useEffect, useMemo, useState } from "react";
import Animated, {
  Extrapolation,
  interpolate,
  useAnimatedScrollHandler,
  useAnimatedStyle,
  useSharedValue,
} from "react-native-reanimated";
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

import NoResultsState from "../../../components/common/NoResultsState";
import { APP_COLORS } from "../../../constants/theme";
import { buildApiUrl } from "../../../constants/api";
import { useAuth } from "../../../contexts/AuthContext";
import { useWebAppRequest } from "../../../hooks/useWebAppRequest";

export const meta = {
  title: "Messages",
};

const THREAD_FILTERS = [
  { key: "all", label: "All" },
  { key: "unread", label: "Unread" },
  { key: "groups", label: "Groups" },
];

const THREAD_STATUS = {
  unread: {
    label: "Unread",
    icon: "circle",
    color: APP_COLORS.primaryBlue,
    backgroundColor: APP_COLORS.primaryBlueLight,
  },
  read: {
    label: "Read",
    icon: "check-circle",
    color: APP_COLORS.statusNeutral,
    backgroundColor: APP_COLORS.statusNeutralLight,
  },
};

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

function formatRelativeTime(value) {
  if (!value) {
    return "Just now";
  }

  const createdAt = new Date(value);
  if (Number.isNaN(createdAt.getTime())) {
    return "Just now";
  }

  const diffInMinutes = Math.max(1, Math.floor((Date.now() - createdAt.getTime()) / 60000));
  if (diffInMinutes < 60) {
    return `${diffInMinutes}m ago`;
  }

  const diffInHours = Math.floor(diffInMinutes / 60);
  if (diffInHours < 24) {
    return `${diffInHours}h ago`;
  }

  const diffInDays = Math.floor(diffInHours / 24);
  return `${diffInDays}d ago`;
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

function normalizeText(value) {
  return String(value || "").trim().toLowerCase();
}

export default function MessageScreen() {
  const params = useLocalSearchParams();
  const { activeBaseUrl, fetchJsonWithFallback } = useWebAppRequest();
  const scrollY = useSharedValue(0);
  const [threads, setThreads] = useState([]);
  const [selectedThreadId, setSelectedThreadId] = useState(0);
  const [selectedThread, setSelectedThread] = useState(null);
  const [conversation, setConversation] = useState([]);
  const [availableUsers, setAvailableUsers] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [activeFilter, setActiveFilter] = useState("all");
  const [isComposeOpen, setIsComposeOpen] = useState(false);
  const [composeQuery, setComposeQuery] = useState("");
  const [composeRecipients, setComposeRecipients] = useState([]);
  const [composeMessage, setComposeMessage] = useState("");
  const [composeImage, setComposeImage] = useState(null);
  const [replyMessage, setReplyMessage] = useState("");
  const [replyImage, setReplyImage] = useState(null);
  const [isSending, setIsSending] = useState(false);

  const loadMessages = useCallback(
    async ({ threadId = selectedThreadId, silent = false } = {}) => {
      try {
        if (!silent) {
          setIsLoading(true);
        }

        setErrorMessage(null);

        const query = threadId > 0 ? `?thread=${encodeURIComponent(threadId)}` : "";
        const response = await fetchJsonWithFallback(`/api/mobile/messages${query}`, {
          method: "GET",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
          },
        });

        const nextThreads = Array.isArray(response?.threads) ? response.threads : [];
        setThreads(nextThreads);
        setSelectedThreadId(Number(response?.selected_thread_id || 0));
        setSelectedThread(response?.selected_thread || null);
        setConversation(Array.isArray(response?.conversation) ? response.conversation : []);
        setAvailableUsers(Array.isArray(response?.available_users) ? response.available_users : []);
        setUnreadCount(Number(response?.unread_count || 0));
      } catch (error) {
        setErrorMessage(error?.message || "Unable to load messages.");
        setThreads([]);
        setSelectedThreadId(0);
        setSelectedThread(null);
        setConversation([]);
        setAvailableUsers([]);
        setUnreadCount(0);
      } finally {
        if (!silent) {
          setIsLoading(false);
        }
      }
    },
    [fetchJsonWithFallback, selectedThreadId]
  );

  useEffect(() => {
    loadMessages();
  }, [loadMessages]);

  useEffect(() => {
    if (String(params?.compose || "") === "1") {
      setIsComposeOpen(true);
    }
  }, [params?.compose]);

  useEffect(() => {
    if (isComposeOpen) {
      return undefined;
    }

    const intervalId = setInterval(() => {
      loadMessages({ threadId: selectedThreadId, silent: true });
    }, 10000);

    return () => clearInterval(intervalId);
  }, [isComposeOpen, loadMessages, selectedThreadId]);

  const handleRefresh = useCallback(async () => {
    setIsRefreshing(true);
    try {
      await loadMessages({ threadId: selectedThreadId, silent: true });
    } finally {
      setIsRefreshing(false);
    }
  }, [loadMessages, selectedThreadId]);

  const filteredThreads = useMemo(() => {
    const query = normalizeText(searchQuery);

    return threads.filter((thread) => {
      const matchesSearch =
        query === "" ||
        [thread?.name, thread?.subtitle, thread?.preview, thread?.preview_sender]
          .map(normalizeText)
          .some((value) => value.includes(query));

      const matchesFilter =
        activeFilter === "all" ||
        (activeFilter === "unread" && Number(thread?.unread || 0) > 0) ||
        (activeFilter === "groups" && Boolean(thread?.is_group));

      return matchesSearch && matchesFilter;
    });
  }, [activeFilter, searchQuery, threads]);

  const composeRecipientOptions = useMemo(() => {
    const query = normalizeText(composeQuery);
    const selectedIds = new Set(composeRecipients.map((item) => Number(item.id)));

    return availableUsers.filter((user) => {
      if (selectedIds.has(Number(user?.id || 0))) {
        return false;
      }

      if (query === "") {
        return true;
      }

      return normalizeText(user?.search || user?.name || "").includes(query);
    });
  }, [availableUsers, composeQuery, composeRecipients]);

  const unreadPillCount = unreadCount > 99 ? "99+" : String(unreadCount || 0);
  const selectedThreadLabel = selectedThread?.custom_name || selectedThread?.name || "Messages";
  const selectedThreadSubtitle = selectedThread?.subtitle || "Select a conversation to view the latest messages.";

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

  const sendMessageRequest = useCallback(
    async ({ threadId = 0, recipientIds = [], message = "", image = null }) => {
      if (isSending) {
        return null;
      }

      setIsSending(true);
      setErrorMessage(null);

      try {
        const formData = new FormData();

        if (threadId > 0) {
          formData.append("thread_id", String(threadId));
        }

        recipientIds.forEach((recipientId) => {
          formData.append("recipient_ids[]", String(recipientId));
        });

        formData.append("message", message);

        if (image?.uri) {
          formData.append("image", {
            uri: image.uri,
            name: image.name || `message-${Date.now()}.jpg`,
            type: image.type || "image/jpeg",
          });
        }

        const response = await fetch(buildApiUrl("/api/mobile/messages", activeBaseUrl), {
          method: "POST",
          credentials: "include",
          headers: {
            Accept: "application/json",
          },
          body: formData,
        });

        const payload = await response.json().catch(() => null);

        if (!response.ok) {
          throw new Error(payload?.message || "Unable to send the message.");
        }

        return payload;
      } finally {
        setIsSending(false);
      }
    },
    [activeBaseUrl, isSending]
  );

  const handleThreadPress = useCallback((threadId) => {
    const nextThreadId = Number(threadId || 0);
    if (nextThreadId <= 0) {
      return;
    }

    setIsComposeOpen(false);
    setSelectedThreadId(nextThreadId);
    loadMessages({ threadId: nextThreadId });
  }, [loadMessages]);

  const handleToggleCompose = useCallback(() => {
    setIsComposeOpen((current) => !current);
    setErrorMessage(null);
  }, []);

  const handleAddComposeRecipient = useCallback((user) => {
    const recipientId = Number(user?.id || 0);
    if (recipientId <= 0) {
      return;
    }

    setComposeRecipients((current) => {
      if (current.some((item) => Number(item.id) === recipientId)) {
        return current;
      }

      return [
        ...current,
        {
          id: recipientId,
          name: String(user?.name || "Unknown User"),
          initials: getInitials(user?.name || "Unknown User"),
        },
      ];
    });
    setComposeQuery("");
  }, []);

  const handleRemoveComposeRecipient = useCallback((recipientId) => {
    setComposeRecipients((current) => current.filter((item) => Number(item.id) !== Number(recipientId)));
  }, []);

  const handleSendComposeMessage = useCallback(async () => {
    const text = String(composeMessage || "").trim();

    if (!composeRecipients.length) {
      setErrorMessage("Please select at least one recipient.");
      return;
    }

    if (!text && !composeImage) {
      setErrorMessage("Type a message or attach an image.");
      return;
    }

    const payload = await sendMessageRequest({
      recipientIds: composeRecipients.map((item) => item.id),
      message: text,
      image: composeImage,
    });

    if (!payload) {
      return;
    }

    setComposeMessage("");
    setComposeRecipients([]);
    setComposeImage(null);
    setIsComposeOpen(false);

    const nextThreadId = Number(payload?.thread_id || 0);
    await loadMessages({ threadId: nextThreadId > 0 ? nextThreadId : selectedThreadId });
  }, [composeImage, composeMessage, composeRecipients, loadMessages, sendMessageRequest, selectedThreadId]);

  const handleSendReplyMessage = useCallback(async () => {
    const text = String(replyMessage || "").trim();

    if (!selectedThreadId) {
      setErrorMessage("Choose a conversation first.");
      return;
    }

    if (!text && !replyImage) {
      setErrorMessage("Type a message or attach an image.");
      return;
    }

    const payload = await sendMessageRequest({
      threadId: selectedThreadId,
      message: text,
      image: replyImage,
    });

    if (!payload) {
      return;
    }

    setReplyMessage("");
    setReplyImage(null);
    await loadMessages({ threadId: selectedThreadId });
  }, [loadMessages, replyImage, replyMessage, selectedThreadId, sendMessageRequest]);

  const selectedThreadMembers = Array.isArray(selectedThread?.members) ? selectedThread.members : [];
  const selectedThreadAvatarSeed = selectedThread?.custom_name || selectedThread?.name || String(selectedThreadId || 0);

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

  const renderThreadAvatar = useCallback((thread) => {
    const threadName = String(thread?.name || "Unknown User");

    if (thread?.is_group) {
      const members = Array.isArray(thread?.avatar_members) ? thread.avatar_members : [];
      const primaryName = members[0] || threadName;
      const secondaryName = members[1] || primaryName;

      return (
        <View className="relative h-12 w-12">
          <View className="absolute right-0 top-0">
            {renderAvatar(secondaryName, `${secondaryName}|secondary`, 26, true)}
          </View>
          <View className="absolute bottom-0 left-0">
            {renderAvatar(primaryName, `${primaryName}|primary`, 32, true)}
          </View>
        </View>
      );
    }

    return (
      <View
        className="items-center justify-center overflow-hidden rounded-full border"
        style={{
          width: 46,
          height: 46,
          backgroundColor: APP_COLORS.primaryBlueLight,
          borderColor: `${APP_COLORS.primaryBlue}22`,
        }}
      >
        <Feather name="user" size={22} color={APP_COLORS.primaryBlue} />
      </View>
    );
  }, [renderAvatar]);

  const renderSelectedThreadAvatar = useCallback(() => {
    if (selectedThread?.is_group) {
      const members = selectedThreadMembers.length ? selectedThreadMembers : [{ name: selectedThreadLabel }];
      const primaryName = members[0]?.name || selectedThreadLabel;
      const secondaryName = members[1]?.name || primaryName;

      return (
        <View className="relative h-14 w-14">
          <View className="absolute right-0 top-0">
            {renderAvatar(secondaryName, `${secondaryName}|detail-secondary`, 28, true)}
          </View>
          <View className="absolute bottom-0 left-0">
            {renderAvatar(primaryName, `${primaryName}|detail-primary`, 36, true)}
          </View>
        </View>
      );
    }

    return renderAvatar(selectedThreadLabel, selectedThreadAvatarSeed, 48);
  }, [renderAvatar, selectedThread?.is_group, selectedThreadAvatarSeed, selectedThreadLabel, selectedThreadMembers]);

  const renderThreadCard = useCallback((thread) => {
    const isActive = Number(thread?.thread_id || 0) === Number(selectedThreadId || 0);
    const unread = Number(thread?.unread || 0);
    const previewPrefix = thread?.preview_is_mine ? "You:" : thread?.preview_sender || "";
    const threadStatus = unread > 0 ? THREAD_STATUS.unread : THREAD_STATUS.read;
    const timeLabel = formatRelativeTime(thread?.time);

    return (
      <Pressable
        key={thread?.thread_id}
        onPress={() => handleThreadPress(thread?.thread_id)}
        className="mb-1 overflow-hidden border-b border-[#e5edf6] bg-white"
        style={({ pressed }) => ({
          opacity: pressed ? 0.94 : 1,
          backgroundColor: isActive ? "#eff6ff" : "#ffffff",
        })}
      >
        <View className="px-2 py-3">
          <View className="flex-row items-start gap-3">
            <View className="pt-0.5">{renderThreadAvatar(thread)}</View>

            <View className="flex-1">
              <View className="flex-row items-start justify-between gap-2">
                <View className="flex-1 pr-2">
                  <Text
                    className={`text-[15px] ${unread > 0 ? "font-extrabold" : "font-semibold"}`}
                    style={{ color: APP_COLORS.primaryBlue }}
                    numberOfLines={1}
                  >
                    {thread?.name || "Unknown User"}
                  </Text>
                  <Text
                    className="mt-0.5 text-[12px] leading-5"
                    style={{ color: unread > 0 ? APP_COLORS.statusNeutral : APP_COLORS.tabInactive }}
                    numberOfLines={2}
                  >
                    {previewPrefix ? `${previewPrefix} ` : ""}
                    {thread?.preview || "No messages yet."}
                  </Text>
                </View>

                <View className="items-end gap-2">
                  <Text className="text-[11px] font-medium" style={{ color: APP_COLORS.tabInactive }}>
                    {timeLabel}
                  </Text>
                  <View className="flex-row items-center gap-1.5">
                    <Feather
                      name={threadStatus.icon}
                      size={12}
                      color={threadStatus.color}
                    />
                    <Text className="text-[10px] font-bold uppercase tracking-wide" style={{ color: threadStatus.color }}>
                      {threadStatus.label}
                    </Text>
                  </View>
                </View>
              </View>
            </View>

            <Feather
              name="chevron-right"
              size={18}
              color={APP_COLORS.tabInactive}
              style={{ marginTop: 10 }}
            />
          </View>
        </View>
      </Pressable>
    );
  }, [handleThreadPress, renderThreadAvatar, selectedThreadId]);

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

  const emptyThreadState = !isLoading && !errorMessage && !filteredThreads.length;
  const composeSearchLower = normalizeText(composeQuery);
  const handleScroll = useAnimatedScrollHandler({
    onScroll: (event) => {
      scrollY.value = event.contentOffset.y;
    },
  });

  const heroParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(scrollY.value, [0, 120], [0, 30], Extrapolation.CLAMP),
      },
    ],
  }));

  const contentParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(scrollY.value, [0, 180], [0, -18], Extrapolation.CLAMP),
      },
    ],
  }));

  return (
    <SafeAreaView className="flex-1" style={{ backgroundColor: APP_COLORS.primaryBlue }} edges={[]}>
      <Animated.ScrollView
        className="flex-1"
        contentContainerStyle={{ flexGrow: 1 }}
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} tintColor={APP_COLORS.primaryBlue} />}
        showsVerticalScrollIndicator={false}
        onScroll={handleScroll}
        scrollEventThrottle={16}
      >
        <Animated.View style={heroParallaxStyle}>
          <LinearGradient
            colors={[APP_COLORS.primaryBlue, "#0a3b8f", "#0b4cb3"]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            className="px-5 pb-8 pt-6"
          >
            <View className="flex-row items-start gap-4">
              <View className="flex-1">
                <Text className="text-3xl font-extrabold text-white">Messages</Text>
              </View>
            </View>
          </LinearGradient>
        </Animated.View>

        <Animated.View className="-mt-6 flex-1 rounded-t-[30px] bg-white px-4 pb-6 pt-4" style={contentParallaxStyle}>
          {isComposeOpen ? (
            <View className="mb-4 overflow-hidden rounded-[28px] border border-[#dbe5f1] bg-[#fbfdff]">
              <View className="flex-row items-center justify-between border-b border-[#e3ebf4] px-4 py-4">
                <View>
                  <Text className="text-base font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                    Start a conversation
                  </Text>
                  <Text className="mt-1 text-xs" style={{ color: APP_COLORS.textSubtle }}>
                    Select one or more recipients and send a message.
                  </Text>
                </View>
                <Pressable onPress={() => setIsComposeOpen(false)} className="h-9 w-9 items-center justify-center rounded-full bg-white">
                  <Feather name="x" size={18} color={APP_COLORS.primaryBlue} />
                </Pressable>
              </View>

              <View className="px-4 py-4">
                <View className="rounded-[22px] border border-[#dbe5f1] bg-white px-4 py-3">
                  <Text className="text-xs font-semibold uppercase tracking-wide" style={{ color: APP_COLORS.tabInactive }}>
                    Recipients
                  </Text>
                  <View className="mt-2 flex-row flex-wrap gap-2">
                    {composeRecipients.length ? composeRecipients.map((recipient) => (
                      <View
                        key={recipient.id}
                        className="flex-row items-center gap-2 rounded-full px-3 py-2"
                        style={{ backgroundColor: APP_COLORS.primaryBlueLight }}
                      >
                        <View className="h-6 w-6 items-center justify-center rounded-full" style={{ backgroundColor: APP_COLORS.primaryBlue }}>
                          <Text className="text-[9px] font-extrabold text-white">{recipient.initials}</Text>
                        </View>
                        <Text className="max-w-[140px] text-xs font-semibold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={1}>
                          {recipient.name}
                        </Text>
                        <Pressable onPress={() => handleRemoveComposeRecipient(recipient.id)}>
                          <Feather name="x" size={14} color={APP_COLORS.primaryBlue} />
                        </Pressable>
                      </View>
                    )) : (
                      <Text className="text-sm" style={{ color: APP_COLORS.textSubtle }}>
                        Add recipients to begin.
                      </Text>
                    )}
                  </View>

                  <View className="mt-3 flex-row items-center gap-2 rounded-full border border-[#dbe5f1] bg-[#f8fbff] px-3 py-2">
                    <Feather name="search" size={16} color={APP_COLORS.tabInactive} />
                    <TextInput
                      value={composeQuery}
                      onChangeText={setComposeQuery}
                      placeholder="Search recipients"
                      placeholderTextColor={APP_COLORS.tabInactive}
                      className="flex-1 text-sm"
                      style={{ color: APP_COLORS.primaryBlue }}
                    />
                  </View>
                </View>

                <View className="mt-3 max-h-60 overflow-hidden rounded-[22px] border border-[#dbe5f1] bg-white">
                  <ScrollView nestedScrollEnabled showsVerticalScrollIndicator={false}>
                    {composeRecipientOptions.length ? composeRecipientOptions.map((user) => (
                      <Pressable
                        key={user.id}
                        onPress={() => handleAddComposeRecipient(user)}
                        className="flex-row items-center gap-3 border-b border-[#eef3f8] px-4 py-3"
                      >
                        <View className="h-10 w-10 items-center justify-center rounded-2xl" style={{ backgroundColor: `${APP_COLORS.primaryBlue}12` }}>
                          <Text className="text-xs font-extrabold" style={{ color: APP_COLORS.primaryBlue }}>
                            {getInitials(user?.name || "U")}
                          </Text>
                        </View>
                        <View className="flex-1">
                          <Text className="text-sm font-semibold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={1}>
                            {user?.name || "Unknown User"}
                          </Text>
                          <Text className="mt-0.5 text-xs" style={{ color: APP_COLORS.textSubtle }} numberOfLines={1}>
                            {[user?.position, user?.office].filter(Boolean).join(" · ") || "PDMU User"}
                          </Text>
                        </View>
                        <Feather name="plus-circle" size={18} color={APP_COLORS.primaryBlue} />
                      </Pressable>
                    )) : (
                      <NoResultsState
                        title={composeSearchLower ? "No recipients matched" : "No recipients available"}
                        description={composeSearchLower ? "Try another name or remove a filter." : "There are no selectable users right now."}
                        containerClassName="rounded-none border-0 bg-transparent px-4 py-6"
                        titleClassName="text-[15px] font-semibold text-[#1e3a8a]"
                        descriptionClassName="mt-1 text-[12px] leading-[18px] text-[#64748b]"
                        animationStyle={{ width: 180, height: 180 }}
                      />
                    )}
                  </ScrollView>
                </View>

                <View className="mt-4 rounded-[22px] border border-[#dbe5f1] bg-white px-4 py-3">
                  <Text className="text-xs font-semibold uppercase tracking-wide" style={{ color: APP_COLORS.tabInactive }}>
                    Message
                  </Text>
                  <TextInput
                    value={composeMessage}
                    onChangeText={setComposeMessage}
                    placeholder="Write something thoughtful..."
                    placeholderTextColor={APP_COLORS.tabInactive}
                    multiline
                    className="mt-2 min-h-[96px] rounded-[18px] border border-[#dbe5f1] bg-[#f8fbff] px-4 py-3 text-sm"
                    style={{ color: APP_COLORS.primaryBlue, textAlignVertical: "top" }}
                  />

                  {renderImagePreview(composeImage, () => setComposeImage(null))}

                  <View className="mt-4 flex-row items-center justify-between gap-3">
                    <Pressable
                      onPress={async () => {
                        const image = await pickImage();
                        if (image) {
                          setComposeImage(image);
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
                      onPress={handleSendComposeMessage}
                      disabled={isSending}
                      className="flex-row items-center gap-2 rounded-full px-5 py-3"
                      style={{ backgroundColor: APP_COLORS.primaryBlue, opacity: isSending ? 0.75 : 1 }}
                    >
                      {isSending ? <ActivityIndicator size="small" color="#ffffff" /> : <Feather name="send" size={16} color="#ffffff" />}
                      <Text className="text-sm font-bold text-white">Send</Text>
                    </Pressable>
                  </View>
                </View>
              </View>
            </View>
          ) : null}

          <View className="mb-3">
            <View className="mb-3 flex-row items-center justify-between">
              <Text className="text-base font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                Conversations
              </Text>
              <Text className="text-xs font-semibold" style={{ color: APP_COLORS.tabInactive }}>
                {filteredThreads.length} shown
              </Text>
            </View>

            <View className="flex-row gap-2">
              {THREAD_FILTERS.map((filter) => {
                const isActive = activeFilter === filter.key;
                return (
                  <Pressable
                    key={filter.key}
                    onPress={() => setActiveFilter(filter.key)}
                    className="rounded-full px-4 py-2"
                    style={{
                      backgroundColor: isActive ? APP_COLORS.primaryBlue : APP_COLORS.backgroundCard,
                      borderWidth: 1,
                      borderColor: isActive ? APP_COLORS.primaryBlue : `${APP_COLORS.accentBorder}20`,
                    }}
                  >
                    <View className="flex-row items-center gap-1.5">
                      <Text className="text-xs font-bold" style={{ color: isActive ? "#ffffff" : APP_COLORS.primaryBlue }}>
                        {filter.label}
                      </Text>
                      {filter.key === "unread" ? (
                        <View
                          className="min-w-[16px] rounded-full px-1.5 py-[1px]"
                          style={{ backgroundColor: isActive ? "rgba(255,255,255,0.22)" : APP_COLORS.primaryBlue }}
                        >
                          <Text className="text-[10px] font-extrabold text-white">{unreadPillCount}</Text>
                        </View>
                      ) : null}
                    </View>
                  </Pressable>
                );
              })}
            </View>
          </View>

          <View className="mb-4 rounded-[22px] border border-[#dbe5f1] bg-[#f8fbff] px-4 py-3">
            <View className="flex-row items-center gap-2 rounded-full border border-[#dbe5f1] bg-white px-4 py-3">
              <Feather name="search" size={16} color={APP_COLORS.tabInactive} />
              <TextInput
                value={searchQuery}
                onChangeText={setSearchQuery}
                placeholder="Search conversations"
                placeholderTextColor={APP_COLORS.tabInactive}
                className="flex-1 text-sm"
                style={{ color: APP_COLORS.primaryBlue }}
              />
            </View>
          </View>

          {isLoading ? (
            <View className="items-center justify-center py-14">
              <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
              <Text className="mt-4 text-sm font-medium" style={{ color: APP_COLORS.textSubtle }}>
                Loading messages...
              </Text>
            </View>
          ) : errorMessage ? (
            <View className="items-center justify-center py-12">
              <View className="mb-4 h-16 w-16 items-center justify-center rounded-full" style={{ backgroundColor: APP_COLORS.primaryRedLight }}>
                <Feather name="alert-circle" size={28} color={APP_COLORS.primaryRed} />
              </View>
              <Text className="text-center text-lg font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                Messages unavailable
              </Text>
              <Text className="mt-2 max-w-sm text-center text-sm leading-6" style={{ color: APP_COLORS.textSubtle }}>
                {errorMessage}
              </Text>
              <Pressable
                onPress={handleRefresh}
                className="mt-5 rounded-full px-5 py-3"
                style={{ backgroundColor: APP_COLORS.primaryBlue }}
              >
                <Text className="text-sm font-semibold text-white">Try again</Text>
              </Pressable>
            </View>
          ) : emptyThreadState ? (
            <NoResultsState
              title="No conversations found"
              description="Try another keyword or switch to a different filter."
              containerClassName="rounded-[24px] border border-[#dbe5f1] bg-white px-4 py-5"
              animationStyle={{ width: 220, height: 220 }}
            />
          ) : (
            <View className="mb-2">
              {filteredThreads.map((thread) => renderThreadCard(thread))}
            </View>
          )}

          <View className="mt-2 overflow-hidden rounded-[28px] border border-[#dbe5f1] bg-white">
            <View className="border-b border-[#e5edf6] px-4 py-4">
              <View className="flex-row items-start gap-3">
                {renderSelectedThreadAvatar()}
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
              {conversation.length ? conversation.map((entry) => renderMessageBubble(entry)) : (
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
                placeholder={selectedThreadId ? "Write a reply..." : "Select a conversation first"}
                placeholderTextColor={APP_COLORS.tabInactive}
                multiline
                editable={Boolean(selectedThreadId)}
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
                  disabled={!selectedThreadId}
                  className="flex-row items-center gap-2 rounded-full px-4 py-3"
                  style={{ backgroundColor: APP_COLORS.primaryBlueLight, opacity: selectedThreadId ? 1 : 0.5 }}
                >
                  <Feather name="image" size={16} color={APP_COLORS.primaryBlue} />
                  <Text className="text-sm font-semibold" style={{ color: APP_COLORS.primaryBlue }}>
                    Add image
                  </Text>
                </Pressable>

                <Pressable
                  onPress={handleSendReplyMessage}
                  disabled={!selectedThreadId || isSending}
                  className="flex-row items-center gap-2 rounded-full px-5 py-3"
                  style={{ backgroundColor: APP_COLORS.primaryBlue, opacity: !selectedThreadId || isSending ? 0.72 : 1 }}
                >
                  {isSending ? <ActivityIndicator size="small" color="#ffffff" /> : <Feather name="send" size={16} color="#ffffff" />}
                  <Text className="text-sm font-bold text-white">Send</Text>
                </Pressable>
              </View>
            </View>
          </View>
        </Animated.View>
      </Animated.ScrollView>
    </SafeAreaView>
  );
}
