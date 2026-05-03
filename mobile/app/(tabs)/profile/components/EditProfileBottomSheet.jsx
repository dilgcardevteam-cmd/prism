import { useEffect, useState } from "react";
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
import { Feather } from "@expo/vector-icons";

import { APP_COLORS } from "../../../../constants/theme";

const POSITION_OPTIONS = [
  "Engineer II",
  "Engineer III",
  "Unit Chief",
  "Assistant Unit Chief",
  "Financial Analyst II",
  "Financial Analyst III",
  "Project Evaluation Officer II",
  "Project Evaluation Officer III",
  "Information Systems Analyst III",
];

export default function EditProfileBottomSheet({
  visible,
  onClose,
  editFirstName,
  setEditFirstName,
  editLastName,
  setEditLastName,
  editPhone,
  onPhoneChange,
  editPosition,
  setEditPosition,
  isSaving,
  onSave,
  isSaveDisabled = false,
  isPhoneValid = false,
}) {
  const [isPositionDropdownOpen, setIsPositionDropdownOpen] = useState(false);

  useEffect(() => {
    if (!visible) {
      setIsPositionDropdownOpen(false);
    }
  }, [visible]);

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <KeyboardAvoidingView
        behavior={Platform.OS === "ios" ? "padding" : "height"}
        className="flex-1"
      >
        <View className="flex-1 bg-black/50 justify-end">
          <View className="bg-white rounded-t-2xl p-6 pb-10">
            <View className="flex-row items-center justify-between mb-6">
              <Text className="text-xl font-bold text-slate-900">Edit Profile</Text>
              <Pressable
                onPress={onClose}
                className="h-8 w-8 rounded-full items-center justify-center"
                style={{ backgroundColor: "#f1f5f9" }}
              >
                <Feather name="x" size={20} color={APP_COLORS.primary} />
              </Pressable>
            </View>

            <ScrollView
              showsVerticalScrollIndicator={false}
              bounces={false}
              keyboardShouldPersistTaps="handled"
            >
              <View className="mb-5">
                <Text className="text-sm font-semibold text-slate-700 mb-2">First Name</Text>
                <TextInput
                  className="border border-slate-300 rounded-lg px-4 py-3 text-slate-900"
                  placeholder="Enter first name"
                  value={editFirstName}
                  onChangeText={setEditFirstName}
                  placeholderTextColor="#cbd5e1"
                />
              </View>

              <View className="mb-5">
                <Text className="text-sm font-semibold text-slate-700 mb-2">Last Name</Text>
                <TextInput
                  className="border border-slate-300 rounded-lg px-4 py-3 text-slate-900"
                  placeholder="Enter last name"
                  value={editLastName}
                  onChangeText={setEditLastName}
                  placeholderTextColor="#cbd5e1"
                />
              </View>

              <View className="mb-5">
                <Text className="text-sm font-semibold text-slate-700 mb-2">Mobile Number</Text>
                <View
                  className="flex-row items-center rounded-lg px-4 py-3"
                  style={{
                    borderWidth: 1,
                    borderColor: isPhoneValid ? APP_COLORS.success : APP_COLORS.primaryRed,
                  }}
                >
                  <TextInput
                    className="flex-1 text-slate-900"
                    placeholder="9XXXXXXXXX"
                    value={editPhone}
                    onChangeText={onPhoneChange}
                    keyboardType="numeric"
                    maxLength={11}
                    placeholderTextColor="#cbd5e1"
                  />
                </View>
                <Text
                  className="text-xs mt-1"
                  style={{ color: isPhoneValid ? APP_COLORS.success : APP_COLORS.primaryRed }}
                >
                  Must be 11 digits, starting with 09
                </Text>
              </View>

              <View className="mb-6">
                <Text className="text-sm font-semibold text-slate-700 mb-2">Position</Text>
                <Pressable
                  className="border border-slate-300 rounded-lg px-4 py-3 flex-row items-center justify-between"
                  onPress={() => setIsPositionDropdownOpen((prev) => !prev)}
                >
                  <Text className="text-slate-900">{editPosition || "Select position"}</Text>
                  <Feather
                    name={isPositionDropdownOpen ? "chevron-up" : "chevron-down"}
                    size={20}
                    color={APP_COLORS.primary}
                  />
                </Pressable>

                {isPositionDropdownOpen ? (
                  <View className="border border-t-0 border-slate-300 rounded-b-lg bg-white overflow-hidden">
                    <ScrollView
                      nestedScrollEnabled
                      keyboardShouldPersistTaps="handled"
                      style={{ maxHeight: 200 }}
                    >
                      {POSITION_OPTIONS.map((option) => (
                        <Pressable
                          key={option}
                          className="px-4 py-3 border-b border-slate-200"
                          style={({ pressed }) => ({
                            backgroundColor: pressed ? "#f1f5f9" : "white",
                          })}
                          onPress={() => {
                            setEditPosition(option);
                            setIsPositionDropdownOpen(false);
                          }}
                        >
                          <Text
                            className={`text-base ${editPosition === option ? "font-semibold" : "font-normal"}`}
                            style={{
                              color: editPosition === option ? APP_COLORS.primaryBlue : "#0f172a",
                            }}
                          >
                            {option}
                          </Text>
                        </Pressable>
                      ))}
                    </ScrollView>
                  </View>
                ) : null}
              </View>

              <View className="flex-row gap-3 mt-8">
                <Pressable
                  className="flex-1 h-12 rounded-lg border items-center justify-center"
                  style={{
                    borderColor: APP_COLORS.accentBorder,
                    backgroundColor: APP_COLORS.accentSurface,
                  }}
                  onPress={onClose}
                  disabled={isSaving}
                >
                  <Text className="font-semibold" style={{ color: APP_COLORS.primary }}>
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
                  <Text className="font-semibold text-white">
                    {isSaving ? "Saving..." : "Save Changes"}
                  </Text>
                </Pressable>
              </View>
            </ScrollView>
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}
