import {
    DarkTheme,
    DefaultTheme,
    ThemeProvider,
} from "@react-navigation/native";
import { Stack, useRouter, useSegments } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { useEffect } from "react";
import { ActivityIndicator, View, useColorScheme, Text, TextInput } from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import "react-native-reanimated";
import "../global.css";
import { useFonts } from "expo-font";

import { AuthProvider, useAuth } from "../contexts/AuthContext";
import { APP_ROUTES } from "../constants/routes";
import { APP_COLORS } from "../constants/theme";
import { TYPOGRAPHY_DEFAULTS } from "../constants/typography";

export const unstable_settings = {
  anchor: "index",
};

function isLightHexColor(hexColor) {
  const normalized = hexColor.replace("#", "");
  const full =
    normalized.length === 3
      ? normalized
          .split("")
          .map((char) => char + char)
          .join("")
      : normalized;

  const r = parseInt(full.slice(0, 2), 16);
  const g = parseInt(full.slice(2, 4), 16);
  const b = parseInt(full.slice(4, 6), 16);
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

  return luminance > 0.6;
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <AuthProvider>
        <RootNavigator />
      </AuthProvider>
    </GestureHandlerRootView>
  );
}

function RootNavigator() {
  const colorScheme = useColorScheme();
  const router = useRouter();
  const segments = useSegments();
  const { isAuthenticated, isHydrating } = useAuth();
  const appBackgroundColor = APP_COLORS.background;
  const statusBarStyle = isLightHexColor(appBackgroundColor) ? "dark" : "light";

  const [fontsLoaded] = useFonts({
    Montserrat: require("../assets/fonts/Montserrat-Regular.ttf"),
    "Montserrat-Regular": require("../assets/fonts/Montserrat-Regular.ttf"),
    "Montserrat-Thin": require("../assets/fonts/Montserrat-Thin.ttf"),
    "Montserrat-SemiBold": require("../assets/fonts/Montserrat-SemiBold.ttf"),
    "Montserrat-Bold": require("../assets/fonts/Montserrat-Bold.ttf"),
  });

  useEffect(() => {
    if (isHydrating) {
      return;
    }

    const inTabs = segments[0] === "(tabs)";

    if (!isAuthenticated && inTabs) {
      router.replace(APP_ROUTES.login);
      return;
    }

    if (isAuthenticated && !inTabs) {
      router.replace(APP_ROUTES.homeTab);
    }
  }, [isAuthenticated, isHydrating, router, segments]);

  if (!fontsLoaded) {
    return (
      <View
        style={{ flex: 1, backgroundColor: appBackgroundColor }}
        className="items-center justify-center"
      >
        <ActivityIndicator size="large" color={APP_COLORS.primary} />
      </View>
    );
  }

  // Apply Montserrat as the default font for all Text and TextInput components
  try {
    if (Text) {
      if (!Text.hasOwnProperty("defaultProps") || Text.defaultProps == null) {
        Text.defaultProps = {};
      }

      const existingTextStyle = Text.defaultProps.style || [];
      const existingTextStyleArray = Array.isArray(existingTextStyle)
        ? existingTextStyle
        : [existingTextStyle];

      Text.defaultProps.allowFontScaling = TYPOGRAPHY_DEFAULTS.allowFontScaling;
      Text.defaultProps.maxFontSizeMultiplier = TYPOGRAPHY_DEFAULTS.maxFontSizeMultiplier;

      Text.defaultProps.style = [
        {
          fontFamily: TYPOGRAPHY_DEFAULTS.fontFamily.regular,
          includeFontPadding: TYPOGRAPHY_DEFAULTS.android.includeFontPadding,
        },
        ...existingTextStyleArray,
      ];
    }

    if (TextInput) {
      if (!TextInput.hasOwnProperty("defaultProps") || TextInput.defaultProps == null) {
        TextInput.defaultProps = {};
      }

      const existingInputStyle = TextInput.defaultProps.style || [];
      const existingInputStyleArray = Array.isArray(existingInputStyle)
        ? existingInputStyle
        : [existingInputStyle];

      TextInput.defaultProps.allowFontScaling = TYPOGRAPHY_DEFAULTS.allowFontScaling;
      TextInput.defaultProps.maxFontSizeMultiplier = TYPOGRAPHY_DEFAULTS.maxFontSizeMultiplier;

      TextInput.defaultProps.style = [
        {
          fontFamily: TYPOGRAPHY_DEFAULTS.fontFamily.regular,
          includeFontPadding: TYPOGRAPHY_DEFAULTS.android.includeFontPadding,
        },
        ...existingInputStyleArray,
      ];
    }
  } catch (_error) {
    // swallow in case global default assignment isn't supported on a platform
  }

  if (isHydrating) {
    return (
      <View
        style={{ flex: 1, backgroundColor: appBackgroundColor }}
        className="items-center justify-center"
      >
        <ActivityIndicator size="large" color={APP_COLORS.primary} />
      </View>
    );
  }

  return (
    <ThemeProvider value={colorScheme === "dark" ? DarkTheme : DefaultTheme}>
      <Stack>
        <Stack.Screen name="index" options={{ headerShown: false }} />
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      </Stack>
      <StatusBar style={statusBarStyle} backgroundColor={appBackgroundColor} />
    </ThemeProvider>
  );
}
