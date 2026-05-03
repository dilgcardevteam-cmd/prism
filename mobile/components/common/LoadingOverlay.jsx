import { Modal, Text, View } from "react-native";

export default function LoadingOverlay({
  visible = false,
  message,
  children,
  overlayClassName = "bg-black/40",
  cardClassName = "bg-white rounded-full items-center justify-center",
  messageClassName = "mt-2 text-sm font-semibold text-gray-600",
}) {
  if (!visible) return null;

  return (
    <Modal transparent visible={visible} animationType="fade">
      <View className={`absolute inset-0 z-50 items-center justify-center ${overlayClassName}`}>
        
        {/* BIGGER CIRCLE */}
        <View className={`h-56 w-56 ${cardClassName}`}>
          {children}

          {message ? (
            <Text className={messageClassName}>
              {message}
            </Text>
          ) : null}
        </View>

      </View>
    </Modal>
  );
}