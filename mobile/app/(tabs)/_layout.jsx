import { Feather } from "@expo/vector-icons";
import { Tabs, usePathname, useRouter } from "expo-router";
import { useRef, useState } from "react";
import {
  Animated,
  Easing,
  Image,
  PanResponder,
  Pressable,
  ScrollView,
  Text,
  View,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useAuth } from "../../contexts/AuthContext";
import { useFetchLoggedUser } from "../../hooks/useFetchLoggedUser";
import ConfirmationModal from "../../components/common/ConfirmationModal";

import {
  APP_ROUTES,
  PROJECT_MONITORING_ROUTES,
  TAB_ROUTES,
} from "../../constants/routes";
import { APP_COLORS } from "../../constants/theme";

const PROJECT_MONITORING_KEY = "project-monitoring";
const PROJECT_MONITORING_SUBMENU_HEIGHT = 208;
const DRAWER_MENU_ITEMS = [
  {
    key: "home",
    label: "Home",
    icon: "grid",
    route: APP_ROUTES.homeTab,
  },
  {
    key: "messages",
    label: "Messages",
    icon: "message-square",
    route: APP_ROUTES.message,
  },
  {
    key: "project-monitoring",
    label: "Project Monitoring",
    icon: "trello",
    children: [
      {
        key: "locally-funded-projects",
        label: "Locally Funded Projects",
        icon: "briefcase",
        route: APP_ROUTES.projectMonitoring.locallyFundedProjects,
      },
      {
        key: "rlip-lime-20-development-fund",
        label: "RLIP/LIME-20% Development Fund",
        icon: "feather",
        route: APP_ROUTES.projectMonitoring.rlipLimeDevelopmentFund,
      },
      {
        key: "project-at-risk",
        label: "Project At Risk",
        icon: "alert-triangle",
        route: APP_ROUTES.projectMonitoring.projectAtRisk,
      },
      {
        key: "sglgif-portal",
        label: "SGLGIF Portal",
        icon: "award",
        route: APP_ROUTES.projectMonitoring.sglgifPortal,
      },
    ],
  },
  {
    key: "rapid-subproject-sustainability-assessment",
    label: "Rapid Subproject Sustainability Assessment",
    icon: "list",
  },
  {
    key: "lgu-reportorial-requirements",
    label: "LGU Reportorial Requirements",
    icon: "file-text",
  },
  {
    key: "pre-implementation-documents",
    label: "Pre-Implementation Documents",
    icon: "folder",
  },
  {
    key: "ticketing-system",
    label: "Ticketing System",
    icon: "message-square",
  },
  {
    key: "data-management",
    label: "Data Management",
    icon: "database",
  },
  {
    key: "user-management",
    label: "User Management",
    icon: "users",
  },
  {
    key: "utilities",
    label: "Utilities",
    icon: "tool",
  },
  {
    key: "settings",
    label: "Settings",
    icon: "settings",
    route: APP_ROUTES.settings,
  },
  {
    key: "logout",
    label: "Log out",
    icon: "log-out",
    action: "logout",
    destructive: true,
  },
];

export default function TabLayout() {
  const router = useRouter();
  const pathname = usePathname();
  const insets = useSafeAreaInsets();
  const { firstName, lastName } = useFetchLoggedUser();
  const { signOut } = useAuth();
  const [isDrawerVisible, setIsDrawerVisible] = useState(false);
  const [isLogoutModalVisible, setIsLogoutModalVisible] = useState(false);
  const [isSigningOut, setIsSigningOut] = useState(false);
  const [expandedMenus, setExpandedMenus] = useState({
    "project-monitoring": false,
  });
  const drawerProgress = useRef(new Animated.Value(0)).current;
  const projectMonitoringAnimation = useRef(new Animated.Value(0)).current;
  const drawerWidth = 320;
  const headerStyle = {
    backgroundColor: APP_COLORS.tabBackgroundLight,
    borderBottomColor: APP_COLORS.tabBorderLight,
    borderBottomWidth: 1,
    elevation: 0,
    shadowOpacity: 0,
  };
  const headerTitleStyle = {
    color: APP_COLORS.primary,
    fontSize: 18,
    fontWeight: "500",
    fontFamily: "Montserrat-SemiBold",
    marginLeft: -8,
  };
  const drawerPanelStyle = {
    width: 318,
    maxWidth: "88%",
    backgroundColor: APP_COLORS.primary,
    shadowColor: "#0f172a",
    shadowOpacity: 0.24,
    shadowRadius: 14,
    shadowOffset: { width: 6, height: 0 },
    elevation: 18,
  };
  const isMessagesTab = pathname === "/(tabs)/message" || pathname === "/message";
  const isConversationTab =
    pathname === "/(tabs)/message/[threadId]" ||
    pathname === "/message/[threadId]" ||
    pathname?.startsWith("/(tabs)/message/") ||
    pathname?.startsWith("/message/");

  const openDrawer = () => {
    if (isDrawerVisible) {
      return;
    }

    setIsDrawerVisible(true);
    Animated.timing(drawerProgress, {
      toValue: 1,
      duration: 260,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true,
    }).start();
  };

  const closeDrawer = () => {
    Animated.timing(drawerProgress, {
      toValue: 0,
      duration: 220,
      easing: Easing.in(Easing.cubic),
      useNativeDriver: true,
    }).start(({ finished }) => {
      if (finished) {
        setIsDrawerVisible(false);
      }
    });
  };

  const toggleMenuSection = (sectionKey) => {
    const willExpand = !expandedMenus[sectionKey];

    if (sectionKey === PROJECT_MONITORING_KEY) {
      Animated.timing(projectMonitoringAnimation, {
        toValue: willExpand ? 1 : 0,
        duration: 220,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: false,
      }).start();
    }

    setExpandedMenus((currentState) => ({
      ...currentState,
      [sectionKey]: !currentState[sectionKey],
    }));
  };

  const handleDrawerItemPress = (routePath) => {
    if (!routePath) {
      return;
    }

    router.push(routePath);
    closeDrawer();
  };

  const handleLogoutConfirm = async () => {
    setIsSigningOut(true);

    try {
      await signOut();
      setIsLogoutModalVisible(false);
      closeDrawer();
      router.replace(APP_ROUTES.login);
    } catch (_error) {
      // ignore logout errors and keep user on current screen
    } finally {
      setIsSigningOut(false);
    }
  };

  // Global edge-swipe gesture to open drawer: start near left edge and swipe right.
  const edgePanResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: (evt) => {
        try {
          const x = evt?.nativeEvent?.pageX ?? 9999;
          return x <= 28 && !isDrawerVisible;
        } catch (_e) {
          return false;
        }
      },
      onMoveShouldSetPanResponder: (_, gestureState) => {
        return Math.abs(gestureState.dx) > 10 && Math.abs(gestureState.dy) < 20;
      },
      onPanResponderRelease: (_, gestureState) => {
        if (gestureState.dx > 60) {
          openDrawer();
        }
      },
    })
  ).current;

  const renderTabBar = () => {
    // Always hide the bottom tab bar (we're moving messages/settings into drawer)
    return null;
  };

  return (
    <View className="flex-1" {...edgePanResponder.panHandlers}>
      <Tabs
        screenOptions={{
          headerShown: true,
          tabBarHideOnKeyboard: true,
          headerTitleAlign: "left",
          headerStyle,
          headerTitleStyle,
          headerLeft: () => (
            <Pressable
              onPress={openDrawer}
              className="ml-[14px] mr-4 p-1"
              style={({ pressed }) => ({ opacity: pressed ? 0.6 : 1 })}
              hitSlop={8}
              accessibilityRole="button"
              accessibilityLabel="Open menu"
            >
              <Feather name="menu" size={24} color={APP_COLORS.primary} />
            </Pressable>
          ),
          headerRight: () => (
            isConversationTab ? null : (
              <View className="mr-[10px] flex-row items-center gap-2">
                {isMessagesTab ? (
                  <Pressable
                    onPress={() => router.setParams({ compose: "1" })}
                    className="p-1"
                    style={({ pressed }) => ({ opacity: pressed ? 0.6 : 1 })}
                    hitSlop={8}
                    accessibilityRole="button"
                    accessibilityLabel="Create new message"
                  >
                    <Feather name="edit-3" size={22} color={APP_COLORS.primary} />
                  </Pressable>
                ) : null}
                <Pressable
                  onPress={() => router.push(APP_ROUTES.notifications)}
                  className="p-1"
                  style={({ pressed }) => ({ opacity: pressed ? 0.6 : 1 })}
                  hitSlop={8}
                  accessibilityRole="button"
                  accessibilityLabel="Open notifications"
                >
                  <Feather name="bell" size={22} color={APP_COLORS.primary} />
                </Pressable>
              </View>
            )
          ),
        }}
        tabBar={renderTabBar}
      >
        {TAB_ROUTES.map((tab) => (
          <Tabs.Screen
            style={{fontFamily: "Montserrat-Regular"}}
            key={tab.route}
            name={tab.route}
            options={{
              title: tab.title,
            }}
          />
        ))}

        {PROJECT_MONITORING_ROUTES.map((projectRoute) => (
          <Tabs.Screen
            key={projectRoute.route}
            name={projectRoute.route}
            options={{
              title: projectRoute.title,
              href: null,
            }}
          />
        ))}

        {/* Explicitly register screens that used to be tabs so we can set friendly titles */}
        <Tabs.Screen
          name="message/index"
          options={{ title: "" }}
        />

        <Tabs.Screen
          name="message/[threadId]"
          options={{ title: "", href: null }}
        />

        <Tabs.Screen
          name="notifications/index"
          options={{ title: "Notifications" }}
        />

        <Tabs.Screen
          name="settings/index"
          options={{ title: "Settings" }}
        />
      </Tabs>

      {isDrawerVisible ? (
        <View className="absolute inset-0 z-40 flex-row" pointerEvents="box-none">
          <Pressable
            className="absolute inset-0"
            style={{ backgroundColor: "rgba(15, 23, 42, 0.28)" }}
            onPress={closeDrawer}
          />

          <Animated.View
            className="h-full rounded-r-[14px] px-[18px]"
            style={[
              drawerPanelStyle,
              {
                paddingTop: Math.max(insets.top + 12, 28),
                paddingBottom: Math.max(insets.bottom + 8, 16),
              },
              {
                transform: [
                  {
                    translateX: drawerProgress.interpolate({
                      inputRange: [0, 1],
                      outputRange: [-drawerWidth, 0],
                    }),
                  },
                ],
              },
            ]}
          >
            <View className="flex-1">
              <View className="flex-row items-center px-1">
                <Image
                  source={require("../../assets/images/icon.png")}
                  className="h-12 w-12"
                  resizeMode="contain"
                />
                
                    <Text className="ml-2.5 text-[52px] font-bold leading-[56px] tracking-[0.2px] text-white"
                      style={{fontFamily: "Montserrat-Bold"}}
                >
                  PRISM
                </Text>
              </View>

              <ScrollView
                className="mt-3 flex-1"
                contentContainerStyle={{ paddingBottom: 18 }}
                showsVerticalScrollIndicator={false}
              >
                <View
                  className="my-4 border-b"
                  style={{ borderBottomColor: "rgba(255, 255, 255, 0.12)" }}
                />

                <Pressable
                  className="mt-1 mb-4 rounded-xl px-3 py-3"
                  style={({ pressed }) => ({
                    backgroundColor: pressed
                      ? "rgba(255, 255, 255, 0.12)"
                      : "rgba(255,255,255,0.08)",
                    opacity: pressed ? 0.8 : 1,
                  })}
                  onPress={() => {
                    router.push("profile");
                    closeDrawer();
                  }}
                >
                  <View className="flex-row items-center">
                    <View className="h-10 w-10 rounded-full items-center justify-center bg-white/10">
                      <Feather name="user" size={20} color="#EAF1FF" />
                    </View>
                    <View className="ml-3 flex-1">
                      <Text
                        numberOfLines={1}
                        className="text-[16px] font-semibold text-white"
                        style={{ fontFamily: "Montserrat-SemiBold" }}
                      >
                        {(firstName ?? "User") + (lastName ? " " + lastName : "")}
                      </Text>
                      <Text className="mt-0.5 text-[12px] text-white/70" style={{ fontFamily: "Montserrat-Regular" }}>
                        Mobile User
                      </Text>
                    </View>
                  </View>
                </Pressable>

                {DRAWER_MENU_ITEMS.map((item, idx) => {
                const hasChildren = Array.isArray(item.children) && item.children.length > 0;
                const isExpanded = expandedMenus[item.key];
                const isProjectMonitoringSection = item.key === PROJECT_MONITORING_KEY;
                const submenuHeight = projectMonitoringAnimation.interpolate({
                  inputRange: [0, 1],
                  outputRange: [0, PROJECT_MONITORING_SUBMENU_HEIGHT],
                });
                const submenuTranslateY = projectMonitoringAnimation.interpolate({
                  inputRange: [0, 1],
                  outputRange: [-8, 0],
                });
                const chevronRotate = projectMonitoringAnimation.interpolate({
                  inputRange: [0, 1],
                  outputRange: ["0deg", "180deg"],
                });

                  return (
                    <View key={item.key ?? item.label ?? idx}>
                      {item.key === PROJECT_MONITORING_KEY || item.key === "data-management" || item.key === "settings" ? (
                        <View
                          className="my-6 border-t"
                          style={{ borderTopColor: "rgba(255, 255, 255, 0.16)" }}
                        />
                      ) : null}

                      <Pressable
                        className="mb-2 flex-row items-center rounded-xl px-2 py-2.5"
                        style={({ pressed }) => ({
                          backgroundColor: pressed
                            ? "rgba(255, 255, 255, 0.12)"
                            : "transparent",
                          opacity: pressed ? 0.9 : 1,
                        })}
                        accessibilityRole="button"
                        accessibilityState={hasChildren ? { expanded: !!isExpanded } : undefined}
                        onPress={() => {
                          if (hasChildren) {
                            toggleMenuSection(item.key);
                            return;
                          }

                          if (item.action === "logout") {
                            setIsLogoutModalVisible(true);
                            return;
                          }

                          handleDrawerItemPress(item.route);
                        }}
                      >
                        <Feather
                          name={item.icon}
                          size={16}
                          color={item.destructive ? APP_COLORS.primaryRed : "#EAF1FF"}
                        />
                        <Text
                          className="ml-3 flex-1 text-[13px] leading-[18px]"
                          style={{
                            color: item.destructive
                              ? "#FCA5A5"
                              : "rgba(255, 255, 255, 0.9)",
                            fontWeight: item.destructive ? "600" : "400",
                            fontFamily: item.destructive ? "Montserrat-SemiBold" : "Montserrat-Regular",
                          }}
                        >
                          {item.label}
                        </Text>
                        {hasChildren ? (
                          isProjectMonitoringSection ? (
                            <Animated.View style={{ transform: [{ rotate: chevronRotate }] }}>
                              <Feather
                                name="chevron-down"
                                size={16}
                                color="#C4D7FF"
                              />
                            </Animated.View>
                          ) : (
                            <Feather
                              name={isExpanded ? "chevron-up" : "chevron-down"}
                              size={16}
                              color="#C4D7FF"
                            />
                          )
                        ) : null}
                      </Pressable>

                      {hasChildren && isProjectMonitoringSection ? (
                        <Animated.View
                          className="overflow-hidden"
                          pointerEvents={isExpanded ? "auto" : "none"}
                          style={{
                            height: submenuHeight,
                            opacity: projectMonitoringAnimation,
                            transform: [{ translateY: submenuTranslateY }],
                          }}
                        >
                          <View className="mb-2 ml-3 rounded-xl border border-white/20 bg-white/10 px-2 py-2">
                            {item.children.map((childItem, cidx) => (
                              <Pressable
                                key={childItem.key ?? childItem.label ?? cidx}
                                className="mb-1.5 flex-row items-center rounded-lg px-2 py-2"
                                style={({ pressed }) => ({
                                  backgroundColor: pressed
                                    ? "rgba(255, 255, 255, 0.16)"
                                    : "transparent",
                                  opacity: pressed ? 0.92 : 1,
                                })}
                                accessibilityRole="button"
                                onPress={() => {
                                  handleDrawerItemPress(childItem.route);
                                }}
                              >
                                <Feather
                                  name={childItem.icon}
                                  size={14}
                                  color="#EAF1FF"
                                />
                                <Text className="ml-3 flex-1 text-[13px] leading-[18px] text-white" style={{ fontFamily: "Montserrat-Regular" }}>
                                  {childItem.label}
                                </Text>
                              </Pressable>
                            ))}
                          </View>
                        </Animated.View>
                      ) : null}

                      {hasChildren && !isProjectMonitoringSection && isExpanded ? (
                        <View className="mb-2 ml-3 rounded-xl border border-white/20 bg-white/10 px-2 py-2">
                          {item.children.map((childItem, cidx) => (
                            <Pressable
                              key={childItem.key ?? childItem.label ?? cidx}
                              className="mb-1.5 flex-row items-center rounded-lg px-2 py-2"
                              style={({ pressed }) => ({
                                backgroundColor: pressed
                                  ? "rgba(255, 255, 255, 0.16)"
                                  : "transparent",
                                opacity: pressed ? 0.92 : 1,
                              })}
                              accessibilityRole="button"
                              onPress={() => {
                                handleDrawerItemPress(childItem.route);
                              }}
                            >
                              <Feather
                                name={childItem.icon}
                                size={14}
                                color="#EAF1FF"
                              />
                              <Text className="ml-3 flex-1 text-[13px] leading-[18px] text-white" style={{ fontFamily: "Montserrat-Regular" }}>
                                {childItem.label}
                              </Text>
                            </Pressable>
                          ))}
                        </View>
                      ) : null}
                    </View>
                  );
                })}
              </ScrollView>

            </View>
          </Animated.View>
        </View>
      ) : null}

      <ConfirmationModal
        visible={isLogoutModalVisible}
        title="Log out"
        message="Are you sure you want to log out of your account?"
        confirmLabel="Log out"
        cancelLabel="Cancel"
        destructive
        loading={isSigningOut}
        onConfirm={handleLogoutConfirm}
        onCancel={() => {
          if (!isSigningOut) {
            setIsLogoutModalVisible(false);
          }
        }}
      />
    </View>
  );
}
