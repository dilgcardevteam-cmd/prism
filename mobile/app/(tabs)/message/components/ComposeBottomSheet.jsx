import { Feather } from "@expo/vector-icons";
import {
  Modal,
  KeyboardAvoidingView,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  Platform,
} from "react-native";
import { APP_COLORS } from "../../../../constants/theme";

export default function ComposeBottomSheet({
  visible,
  onClose,
  composeQuery,
  setComposeQuery,
  composeRecipientOptions,
  onPickRecipient,
}) {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <KeyboardAvoidingView
        className="flex-1 bg-black/50"
        behavior={Platform.OS === "ios" ? "padding" : "height"}
        keyboardVerticalOffset={0}
      >
        <View className="flex-1 justify-end">
          {/* Bottom Sheet */}
          <View className="max-h-[88%] bg-white rounded-t-3xl px-4 pt-4 pb-6">
            
            {/* Header */}
            <View className="flex-row items-start justify-between mb-3">
              <View className="flex-1 pr-3">
                <Text
                  className="text-base"
                  style={{ color: APP_COLORS.primaryBlue, fontFamily: "Montserrat-Bold" }}
                >
                  Start a conversation
                </Text>
                <Text
                  className="text-xs mt-1"
                  style={{ color: APP_COLORS.textSubtle, fontFamily: "Montserrat-Regular" }}
                >
                  Tap a recipient to open the conversation page.
                </Text>
              </View>

              <Pressable
                onPress={onClose}
                className="h-9 w-9 items-center justify-center rounded-full bg-slate-100 active:opacity-70"
              >
                <Feather name="x" size={18} color={APP_COLORS.primaryBlue} />
              </Pressable>
            </View>

            {/* Search */}
            <View className="flex-row items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-2">
              <Feather name="search" size={16} color={APP_COLORS.tabInactive} />
              <TextInput
                value={composeQuery}
                onChangeText={setComposeQuery}
                placeholder="Search recipients"
                placeholderTextColor={APP_COLORS.tabInactive}
                className="flex-1 ml-2 text-sm"
                style={{ color: APP_COLORS.primaryBlue, fontFamily: "Montserrat-Regular" }}
              />
            </View>

            {/* List */}
            <ScrollView
              showsVerticalScrollIndicator={false}
              className="mt-3"
              keyboardShouldPersistTaps="handled"
            >
              <View className="rounded-2xl border border-slate-200 overflow-hidden bg-white">
                {composeRecipientOptions.length ? (
                  composeRecipientOptions.map((user, index) => (
                    <Pressable
                      key={user.id}
                      onPress={() => onPickRecipient(user)}
                      className="flex-row items-center px-3 py-3 active:opacity-80"
                      style={{
                        borderBottomWidth:
                          index !== composeRecipientOptions.length - 1 ? 1 : 0,
                        borderBottomColor: "#eef3f8",
                      }}
                    >
                      {/* Avatar */}
                      <View
                        className="h-11 w-11 items-center justify-center rounded-xl mr-3"
                        style={{ backgroundColor: `${APP_COLORS.primaryBlue}15` }}
                      >
                        <Text
                          className="text-[11px]"
                          style={{
                            color: APP_COLORS.primaryBlue,
                            fontFamily: "Montserrat-Bold",
                          }}
                        >
                          {(user.name || "U")
                            .split(" ")
                            .map((p) => p[0])
                            .slice(0, 2)
                            .join("")
                            .toUpperCase()}
                        </Text>
                      </View>

                      {/* Info */}
                      <View className="flex-1">
                        <Text
                          numberOfLines={1}
                          className="text-sm"
                          style={{
                            color: APP_COLORS.primaryBlue,
                            fontFamily: "Montserrat-SemiBold",
                          }}
                        >
                          {user?.name || "Unknown User"}
                        </Text>
                        <Text
                          numberOfLines={1}
                          className="text-xs mt-0.5"
                          style={{
                            color: APP_COLORS.textSubtle,
                            fontFamily: "Montserrat-Regular",
                          }}
                        >
                          {[user?.position, user?.office]
                            .filter(Boolean)
                            .join(" • ") || "PDMU User"}
                        </Text>
                      </View>

                      <Feather
                        name="chevron-right"
                        size={18}
                        color={APP_COLORS.tabInactive}
                      />
                    </Pressable>
                  ))
                ) : (
                  <View className="px-4 py-6 items-center">
                    <Feather
                      name="users"
                      size={28}
                      color={APP_COLORS.tabInactive}
                    />
                    <Text
                      className="text-sm mt-3"
                      style={{
                        color: APP_COLORS.primaryBlue,
                        fontFamily: "Montserrat-SemiBold",
                      }}
                    >
                      No recipients available
                    </Text>
                    <Text
                      className="text-xs mt-1 text-center"
                      style={{
                        color: APP_COLORS.textSubtle,
                        fontFamily: "Montserrat-Regular",
                      }}
                    >
                      There are no selectable users right now.
                    </Text>
                  </View>
                )}
              </View>
            </ScrollView>
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}