import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from "react-native";
import { useMemo, useState } from "react";
import { Feather } from "@expo/vector-icons";

import { APP_COLORS } from "../../../../constants/theme";

const FONT_STYLES = {
  regular: { fontFamily: "Montserrat-Regular" },
  medium: { fontFamily: "Montserrat-Medium" },
  semiBold: { fontFamily: "Montserrat-SemiBold" },
  bold: { fontFamily: "Montserrat-Bold" },
};

function RequirementItem({ label, met }) {
  return (
    <View className="flex-row items-center mb-2">
      <View
        className="h-5 w-5 rounded-full items-center justify-center mr-2"
        style={{
          backgroundColor: met ? APP_COLORS.successLight : "#e5e7eb",
        }}
      >
        <Feather
          name={met ? "check" : "minus"}
          size={12}
          color={met ? APP_COLORS.success : "#64748b"}
        />
      </View>
      <Text
        className="text-xs"
        style={{
          color: met ? APP_COLORS.success : "#64748b",
          fontFamily: met ? "Montserrat-SemiBold" : "Montserrat-Medium",
        }}
      >
        {label}
      </Text>
    </View>
  );
}

export default function ChangePasswordBottomSheet({
  visible,
  onClose,
  currentPassword,
  newPassword,
  confirmNewPassword,
  onCurrentPasswordChange,
  onNewPasswordChange,
  onConfirmNewPasswordChange,
  passwordRequirements,
  isConfirmPasswordMatching,
  isSaving,
  onSave,
  isSaveDisabled = true,
}) {
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const fieldBorderColor = useMemo(
    () => ({
      borderWidth: 1,
      borderColor: "#cbd5e1",
      backgroundColor: "#ffffff",
    }),
    []
  );

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : "height"} className="flex-1">
        <View className="flex-1 bg-black/50 justify-end">
          <View className="bg-white rounded-t-2xl p-6 pb-10">
            <View className="flex-row items-center justify-between mb-6">
              <View>
                <Text className="text-xl font-bold text-slate-900" style={FONT_STYLES.bold}>Change Password</Text>
                <Text className="text-xs mt-1" style={[{ color: APP_COLORS.textSubtle }, FONT_STYLES.regular]}>
                  Use a strong password to secure your account.
                </Text>
              </View>

              <Pressable
                onPress={onClose}
                disabled={isSaving}
                className="h-8 w-8 rounded-full items-center justify-center"
                style={{ backgroundColor: "#f1f5f9" }}
              >
                <Feather name="x" size={20} color={APP_COLORS.primary} />
              </Pressable>
            </View>

            <ScrollView showsVerticalScrollIndicator={false} bounces={false} keyboardShouldPersistTaps="handled">
              <View className="mb-4">
                <Text className="text-sm font-semibold text-slate-700 mb-2" style={FONT_STYLES.semiBold}>Current Password</Text>
                <View className="rounded-lg px-4 py-3 flex-row items-center" style={fieldBorderColor}>
                  <TextInput
                    className="flex-1 text-slate-900"
                    placeholder="Enter current password"
                    value={currentPassword}
                    onChangeText={onCurrentPasswordChange}
                    secureTextEntry={!showCurrentPassword}
                    autoCapitalize="none"
                    autoCorrect={false}
                    placeholderTextColor="#94a3b8"
                    style={FONT_STYLES.medium}
                  />
                  <Pressable onPress={() => setShowCurrentPassword((prev) => !prev)}>
                    <Feather
                      name={showCurrentPassword ? "eye-off" : "eye"}
                      size={18}
                      color={APP_COLORS.textSubtle}
                    />
                  </Pressable>
                </View>
              </View>

              <View className="mb-4">
                <Text className="text-sm font-semibold text-slate-700 mb-2" style={FONT_STYLES.semiBold}>New Password</Text>
                <View className="rounded-lg px-4 py-3 flex-row items-center" style={fieldBorderColor}>
                  <TextInput
                    className="flex-1 text-slate-900"
                    placeholder="Enter new password"
                    value={newPassword}
                    onChangeText={onNewPasswordChange}
                    secureTextEntry={!showNewPassword}
                    autoCapitalize="none"
                    autoCorrect={false}
                    placeholderTextColor="#94a3b8"
                    style={FONT_STYLES.medium}
                  />
                  <Pressable onPress={() => setShowNewPassword((prev) => !prev)}>
                    <Feather
                      name={showNewPassword ? "eye-off" : "eye"}
                      size={18}
                      color={APP_COLORS.textSubtle}
                    />
                  </Pressable>
                </View>
              </View>

              <View className="mb-4">
                <Text className="text-sm font-semibold text-slate-700 mb-2" style={FONT_STYLES.semiBold}>Confirm New Password</Text>
                <View className="rounded-lg px-4 py-3 flex-row items-center" style={fieldBorderColor}>
                  <TextInput
                    className="flex-1 text-slate-900"
                    placeholder="Confirm new password"
                    value={confirmNewPassword}
                    onChangeText={onConfirmNewPasswordChange}
                    secureTextEntry={!showConfirmPassword}
                    autoCapitalize="none"
                    autoCorrect={false}
                    placeholderTextColor="#94a3b8"
                    style={FONT_STYLES.medium}
                  />
                  <Pressable onPress={() => setShowConfirmPassword((prev) => !prev)}>
                    <Feather
                      name={showConfirmPassword ? "eye-off" : "eye"}
                      size={18}
                      color={APP_COLORS.textSubtle}
                    />
                  </Pressable>
                </View>

                {confirmNewPassword.length > 0 ? (
                  <Text
                    className="text-xs mt-1"
                    style={[{ color: isConfirmPasswordMatching ? APP_COLORS.success : APP_COLORS.primaryRed }, FONT_STYLES.medium]}
                  >
                    {isConfirmPasswordMatching ? "Passwords match" : "Passwords do not match"}
                  </Text>
                ) : null}
              </View>

              <View
                className="rounded-xl p-4 mb-6"
                style={{ backgroundColor: "#f8fafc", borderWidth: 1, borderColor: "#e2e8f0" }}
              >
                <Text className="text-sm font-semibold mb-3" style={[{ color: APP_COLORS.primaryBlue }, FONT_STYLES.semiBold]}>
                  Password Requirements
                </Text>

                <RequirementItem label="Minimum of 8 characters" met={passwordRequirements?.minLength} />
                <RequirementItem label="Mix of uppercase and lowercase letters" met={passwordRequirements?.upperLower} />
                <RequirementItem label="Has at least one number (0-9)" met={passwordRequirements?.hasNumber} />
                <RequirementItem label="Has at least one special character" met={passwordRequirements?.hasSpecial} />
              </View>

              <View className="flex-row gap-3 mt-2">
                <Pressable
                  className="flex-1 h-12 rounded-lg border items-center justify-center"
                  style={{
                    borderColor: APP_COLORS.accentBorder,
                    backgroundColor: APP_COLORS.accentSurface,
                  }}
                  onPress={onClose}
                  disabled={isSaving}
                >
                  <Text className="font-semibold" style={[{ color: APP_COLORS.primary }, FONT_STYLES.semiBold]}>
                    Cancel
                  </Text>
                </Pressable>

                <Pressable
                  className="flex-1 h-12 rounded-lg items-center justify-center flex-row gap-2"
                  style={{
                    backgroundColor: isSaveDisabled ? APP_COLORS.tabInactive : APP_COLORS.primaryBlue,
                    opacity: isSaveDisabled && !isSaving ? 0.55 : 1,
                  }}
                  onPress={onSave}
                  disabled={isSaving || isSaveDisabled}
                >
                  {isSaving ? <ActivityIndicator color="white" size="small" /> : null}
                  <Text className="font-semibold text-white" style={FONT_STYLES.semiBold}>{isSaving ? "Saving..." : "Save"}</Text>
                </Pressable>
              </View>
            </ScrollView>
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}
