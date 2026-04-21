import { useEffect } from "react";
import { Modal, Pressable, Text, View } from "react-native";

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

  if (!visible || !safeMessage) {
    return null;
  }

  return (
    <Modal transparent visible={visible} animationType="fade" onRequestClose={onClose}>
      <View pointerEvents="box-none" className="absolute left-4 right-4 bottom-4 z-[300]">
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
      </View>
    </Modal>
  );
}
