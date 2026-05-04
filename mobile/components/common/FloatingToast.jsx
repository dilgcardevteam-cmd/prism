import { useEffect, useRef } from "react";
import { Animated, PanResponder, Pressable, Text, View } from "react-native";

const TOAST_VARIANTS = {
  success: {
    container: "border-[#bbf7d0] bg-[#f0fdf4]",
    label: "text-[#166534]",
    accent: "#16a34a",
  },
  error: {
    container: "border-[#fecaca] bg-[#fef2f2]",
    label: "text-[#991b1b]",
    accent: "#dc2626",
  },
  warning: {
    container: "border-[#fde68a] bg-[#fffbeb]",
    label: "text-[#92400e]",
    accent: "#d97706",
  },
  info: {
    container: "border-[#bfdbfe] bg-[#eff6ff]",
    label: "text-[#1e3a8a]",
    accent: "#1d4ed8",
  },
};

export default function FloatingToast({
  visible,
  message,
  type = "info",
  duration = 2400,
  onClose,
}) {
  const safeMessage = String(message || "").trim();
  const variant = TOAST_VARIANTS[type] || TOAST_VARIANTS.info;

  useEffect(() => {
    if (!visible || !safeMessage) {
      return undefined;
    }

    const timer = setTimeout(() => {
      if (typeof onClose === "function") {
        onClose();
      }
    }, Number.isFinite(duration) ? duration : 2400);

    return () => clearTimeout(timer);
  }, [duration, onClose, safeMessage, visible]);

  const translateY = useRef(new Animated.Value(0)).current;
  const isDismissing = useRef(false);

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: () => true,
      onMoveShouldSetPanResponder: (_, gestureState) => Math.abs(gestureState.dy) > 4,
      onPanResponderMove: (_, gestureState) => {
        // Only allow downward movement
        const dy = Math.max(0, gestureState.dy);
        translateY.setValue(dy);
      },
      onPanResponderRelease: (_, gestureState) => {
        const dy = Math.max(0, gestureState.dy);
        const THRESHOLD = 60;
        if (dy > THRESHOLD && !isDismissing.current) {
          isDismissing.current = true;
          Animated.timing(translateY, {
            toValue: 200,
            duration: 180,
            useNativeDriver: true,
          }).start(() => {
            isDismissing.current = false;
            if (typeof onClose === "function") onClose();
            translateY.setValue(0);
          });
          return;
        }

        // Snap back
        Animated.spring(translateY, { toValue: 0, useNativeDriver: true }).start();
      },
    })
  ).current;

  // Slide in animation when visible
  useEffect(() => {
    translateY.setValue(40);
    Animated.timing(translateY, { toValue: 0, duration: 220, useNativeDriver: true }).start();
  }, [translateY, visible]);

  if (!visible || !safeMessage) {
    return null;
  }

  return (
    <View pointerEvents="box-none" className="absolute left-4 right-4 bottom-16 z-[300]">
      <Animated.View
        {...panResponder.panHandlers}
        pointerEvents="auto"
        style={{ transform: [{ translateY }] }}
      >
        <View className={`rounded-2xl border px-4 py-4 shadow-lg ${variant.container}`}>
          <View className="flex-row items-center gap-3">
            <View
              className="mt-0.5 h-2.5 w-2.5 rounded-full"
              style={{ backgroundColor: variant.accent }}
            />
            <Text className={`flex-1 text-[13px] leading-5 ${variant.label}`} style={{ fontFamily: "Montserrat-SemiBold" }}>
              {safeMessage}
            </Text>
            <Pressable
              onPress={onClose}
              accessibilityRole="button"
              accessibilityLabel="Dismiss toast"
              className="ml-2 h-6 w-6 items-center justify-center rounded-full bg-white/70"
              style={({ pressed }) => ({ opacity: pressed ? 0.7 : 1 })}
            >
              <Text className={`text-[14px] ${variant.label}`}>×</Text>
            </Pressable>
          </View>
        </View>
      </Animated.View>
    </View>
  );
}
