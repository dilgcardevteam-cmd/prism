import { Feather } from "@expo/vector-icons";
import { useRouter } from "expo-router";
import { useCallback, useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  Linking,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  View,
} from "react-native";
import { LinearGradient } from "expo-linear-gradient";

import { APP_COLORS } from "../../../constants/theme";
import { useAuth } from "../../../contexts/AuthContext";
import { useWebAppRequest } from "../../../hooks/useWebAppRequest";

export const meta = {
  title: "Notifications",
};

export default function NotificationsScreen() {
  const router = useRouter();
  const { session } = useAuth();
  const { fetchJsonWithFallback } = useWebAppRequest();
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);

  const loadNotifications = useCallback(async ({ silent = false } = {}) => {
    try {
      if (!silent) {
        setIsLoading(true);
      }

      setErrorMessage(null);

      const query = session?.id ? `?user_id=${encodeURIComponent(session.id)}` : "";

      const response = await fetchJsonWithFallback(`/api/mobile/notifications${query}`, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
      });

      setNotifications(Array.isArray(response?.notifications) ? response.notifications : []);
      setUnreadCount(Number(response?.unread_count || 0));
    } catch (error) {
      setErrorMessage(error?.message || "Unable to load notifications.");
      setNotifications([]);
      setUnreadCount(0);
    } finally {
      if (!silent) {
        setIsLoading(false);
      }
    }
  }, [fetchJsonWithFallback, session?.id]);

  useEffect(() => {
    loadNotifications();
  }, [loadNotifications]);

  const handleRefresh = useCallback(async () => {
    setIsRefreshing(true);
    try {
      await loadNotifications({ silent: true });
    } finally {
      setIsRefreshing(false);
    }
  }, [loadNotifications]);

  const totalCount = notifications.length;
  const unreadPercent = useMemo(() => {
    if (!totalCount) return 0;
    return Math.round((unreadCount / totalCount) * 100);
  }, [totalCount, unreadCount]);

  const formatRelativeTime = useCallback((value) => {
    if (!value) return "Just now";

    const createdAt = new Date(value);
    if (Number.isNaN(createdAt.getTime())) return "Just now";

    const diffInMinutes = Math.max(1, Math.floor((Date.now() - createdAt.getTime()) / 60000));
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h ago`;

    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays}d ago`;
  }, []);

  const handleOpenNotification = useCallback(async (item) => {
    if (!item?.url) {
      return;
    }

    if (item.url.startsWith("/")) {
      router.push(item.url);
      return;
    }

    try {
      await Linking.openURL(item.url);
    } catch (_error) {
      // Ignore invalid links.
    }
  }, [router]);

  const NotificationCard = ({ item }) => {
    const initials = (item?.sender_name || "N")
      .split(" ")
      .filter(Boolean)
      .slice(0, 2)
      .map((word) => word[0]?.toUpperCase())
      .join("") || "N";

    return (
      <Pressable
        onPress={() => handleOpenNotification(item)}
        className="mb-3 overflow-hidden rounded-3xl border"
        style={({ pressed }) => ({
          opacity: pressed ? 0.94 : 1,
          borderColor: `${APP_COLORS.accentBorder}18`,
          backgroundColor: APP_COLORS.backgroundCard,
        })}
      >
        <View className="p-4">
          <View className="flex-row gap-3">
            <View
              className="h-12 w-12 items-center justify-center rounded-2xl"
              style={{ backgroundColor: item?.is_read ? APP_COLORS.accentSurface : APP_COLORS.primaryBlueLight }}
            >
              <Text className="text-sm font-extrabold" style={{ color: APP_COLORS.primaryBlue }}>
                {initials}
              </Text>
            </View>

            <View className="flex-1">
              <View className="flex-row items-start justify-between gap-3">
                <View className="flex-1">
                  <Text className="text-sm font-semibold" style={{ color: APP_COLORS.primaryBlue }} numberOfLines={2}>
                    {item?.sender_name || "System Notification"}
                  </Text>
                  <Text className="mt-1 text-sm leading-5" style={{ color: APP_COLORS.textSubtle }} numberOfLines={3}>
                    {item?.message}
                  </Text>
                </View>

                <View
                  className="h-3 w-3 rounded-full"
                  style={{ backgroundColor: item?.is_read ? APP_COLORS.statusNeutralLight : APP_COLORS.primaryRed }}
                />
              </View>

              <View className="mt-3 flex-row flex-wrap items-center gap-2">
                {item?.document_type ? (
                  <View className="rounded-full px-3 py-1" style={{ backgroundColor: `${APP_COLORS.primaryBlue}10` }}>
                    <Text className="text-xs font-semibold" style={{ color: APP_COLORS.primaryBlue }}>
                      {item.document_type}
                    </Text>
                  </View>
                ) : null}

                {item?.quarter ? (
                  <View className="rounded-full px-3 py-1" style={{ backgroundColor: `${APP_COLORS.primaryYellow}33` }}>
                    <Text className="text-xs font-semibold" style={{ color: APP_COLORS.primaryBlue }}>
                      {item.quarter}
                    </Text>
                  </View>
                ) : null}

                <View className="flex-row items-center gap-1">
                  <Feather name="clock" size={12} color={APP_COLORS.tabInactive} />
                  <Text className="text-xs" style={{ color: APP_COLORS.tabInactive }}>
                    {formatRelativeTime(item?.created_at)}
                  </Text>
                </View>
              </View>
            </View>
          </View>
        </View>
      </Pressable>
    );
  };

  const renderContent = () => {
    if (isLoading) {
      return (
        <View className="flex-1 items-center justify-center py-16">
          <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
          <Text className="mt-4 text-sm font-medium" style={{ color: APP_COLORS.textSubtle }}>
            Loading notifications...
          </Text>
        </View>
      );
    }

    if (errorMessage) {
      return (
        <View className="flex-1 items-center justify-center px-6 py-16">
          <View className="mb-4 h-16 w-16 items-center justify-center rounded-full" style={{ backgroundColor: APP_COLORS.primaryRedLight }}>
            <Feather name="alert-circle" size={28} color={APP_COLORS.primaryRed} />
          </View>
          <Text className="text-center text-lg font-bold" style={{ color: APP_COLORS.primaryBlue }}>
            Notifications unavailable
          </Text>
          <Text className="mt-2 text-center text-sm leading-6" style={{ color: APP_COLORS.textSubtle }}>
            {errorMessage}
          </Text>
          <Pressable
            onPress={handleRefresh}
            className="mt-6 rounded-full px-5 py-3"
            style={{ backgroundColor: APP_COLORS.primaryBlue }}
          >
            <Text className="text-sm font-semibold text-white">Try again</Text>
          </Pressable>
        </View>
      );
    }

    if (!notifications.length) {
      return (
        <View className="flex-1 items-center justify-center px-6 py-16">
          <View className="mb-4 h-18 w-18 items-center justify-center rounded-[28px]" style={{ backgroundColor: APP_COLORS.primaryBlueLight }}>
            <Feather name="bell-off" size={28} color={APP_COLORS.primaryBlue} />
          </View>
          <Text className="text-center text-xl font-extrabold" style={{ color: APP_COLORS.primaryBlue }}>
            All caught up
          </Text>
          <Text className="mt-2 max-w-sm text-center text-sm leading-6" style={{ color: APP_COLORS.textSubtle }}>
            New alerts, approvals, and updates will appear here as they arrive.
          </Text>
        </View>
      );
    }

    return (
      <View className="px-4 pb-8 pt-4">
        {notifications.map((item) => (
          <NotificationCard key={item.id} item={item} />
        ))}
      </View>
    );
  };

  return (
    <ScrollView
      className="flex-1 bg-white"
      contentContainerStyle={{ flexGrow: 1 }}
      refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} tintColor={APP_COLORS.primaryBlue} />}
      showsVerticalScrollIndicator={false}
    >
      <LinearGradient
        colors={[APP_COLORS.primaryBlue, "#0b4aa3", APP_COLORS.primaryBlueLight]}
        locations={[0, 0.6, 1]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        className="px-5 pb-6 pt-6"
      >
        <View className="flex-row items-start justify-between">
          <View className="flex-1 pr-4">
            <View className="mb-3 flex-row items-center gap-2 self-start rounded-full bg-white/15 px-3 py-1.5">
              <Feather name="bell" size={14} color="#fff" />
              <Text className="text-xs font-semibold uppercase tracking-[1.6px] text-white">
                Notifications
              </Text>
            </View>
            <Text className="text-3xl font-black leading-tight text-white">
              Stay on top of every update.
            </Text>
            <Text className="mt-3 text-sm leading-6 text-white/85">
              Review system alerts, document notices, and project updates in one polished feed.
            </Text>
          </View>

          <View className="items-end gap-3">
            <View className="rounded-3xl bg-white/15 px-4 py-3">
              <Text className="text-[11px] font-semibold uppercase tracking-[1.4px] text-white/80">
                Unread
              </Text>
              <Text className="mt-1 text-3xl font-black text-white">
                {unreadCount}
              </Text>
            </View>
            <View className="rounded-2xl bg-white px-4 py-2.5">
              <Text className="text-xs font-semibold uppercase tracking-[1.2px]" style={{ color: APP_COLORS.primaryBlue }}>
                {unreadPercent}% unread
              </Text>
            </View>
          </View>
        </View>

        <View className="mt-6 flex-row gap-3">
          <View className="flex-1 rounded-3xl bg-white/15 px-4 py-4">
            <Text className="text-xs font-semibold uppercase tracking-[1.2px] text-white/75">
              Total
            </Text>
            <Text className="mt-1 text-2xl font-black text-white">{totalCount}</Text>
          </View>
          <View className="flex-1 rounded-3xl bg-white/15 px-4 py-4">
            <Text className="text-xs font-semibold uppercase tracking-[1.2px] text-white/75">
              Read
            </Text>
            <Text className="mt-1 text-2xl font-black text-white">
              {Math.max(totalCount - unreadCount, 0)}
            </Text>
          </View>
        </View>
      </LinearGradient>

      <View className="-mt-5 flex-1 rounded-t-[32px] bg-white px-0">
        <View className="px-5 pt-5">
          <View className="flex-row items-center justify-between">
            <Text className="text-base font-extrabold" style={{ color: APP_COLORS.primaryBlue }}>
              Recent Activity
            </Text>
            <Pressable onPress={handleRefresh} className="rounded-full px-3 py-2" style={{ backgroundColor: APP_COLORS.primaryBlueLight }}>
              <Text className="text-xs font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                Refresh
              </Text>
            </Pressable>
          </View>
          <View className="mt-4 h-px w-full" style={{ backgroundColor: `${APP_COLORS.tabBorderLight}` }} />
        </View>

        {renderContent()}
      </View>
    </ScrollView>
  );
}

export const options = {
  title: "Notification",
};
