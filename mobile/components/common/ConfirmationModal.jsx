import { Modal, Pressable, Text, View } from "react-native";

export default function ConfirmationModal({
  visible,
  title = "Confirm Action",
  message,
  confirmLabel = "Confirm",
  cancelLabel = "Cancel",
  onConfirm,
  onCancel,
  destructive = false,
  loading = false,
}) {
  const confirmButtonClassName = destructive
    ? "border-red-600 bg-red-600"
    : "border-blue-700 bg-blue-700";

  return (
    <Modal
      transparent
      visible={visible}
      animationType="fade"
      onRequestClose={onCancel}
    >
      <View className="flex-1 items-center justify-center bg-black/45 px-6">
        <View className="w-full max-w-[360px] rounded-2xl bg-white px-5 py-5">
          <Text className="text-[18px] font-semibold leading-6 text-slate-900">
            {title}
          </Text>

          {message ? (
            <Text className="mt-2 text-[14px] leading-5 text-slate-700">
              {message}
            </Text>
          ) : null}

          <View className="mt-[18px] flex-row items-center justify-end">
            <Pressable
              accessibilityRole="button"
              onPress={onCancel}
              disabled={loading}
              className="min-w-[100px] rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5"
              style={({ pressed }) => ({ opacity: pressed || loading ? 0.82 : 1 })}
            >
              <Text className="text-center text-[14px] font-semibold text-slate-700">
                {cancelLabel}
              </Text>
            </Pressable>

            <Pressable
              accessibilityRole="button"
              onPress={onConfirm}
              disabled={loading}
              className={`ml-2 min-w-[112px] rounded-xl border px-4 py-2.5 ${confirmButtonClassName}`}
              style={({ pressed }) => ({ opacity: pressed || loading ? 0.86 : 1 })}
            >
              <Text className="text-center text-[14px] font-semibold text-white">
                {loading ? "Please wait..." : confirmLabel}
              </Text>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}
