import { useRouter } from "expo-router";
import { useCallback, useState } from "react";
import {
  ActivityIndicator,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  View,
  Alert,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { useAuth } from "../../../contexts/AuthContext";
import { APP_ROUTES } from "../../../constants/routes";
import { APP_COLORS } from "../../../constants/theme";
import { useUserProfile } from "../../../hooks/useUserProfile";
import { useWebAppRequest } from "../../../hooks/useWebAppRequest";
import ConfirmationModal from "../../../components/common/ConfirmationModal";
import EditProfileBottomSheet from "./components/EditProfileBottomSheet";
import ChangePasswordBottomSheet from "./components/ChangePasswordBottomSheet";

// ICONS
import { Feather } from "@expo/vector-icons";

const FONT_STYLES = {
  regular: { fontFamily: "Montserrat-Regular" },
  medium: { fontFamily: "Montserrat-Medium" },
  semiBold: { fontFamily: "Montserrat-SemiBold" },
  bold: { fontFamily: "Montserrat-Bold" },
};

export default function ProfileScreen() {
  const router = useRouter();
  const { signOut, session } = useAuth();
  const { fetchJsonWithFallback } = useWebAppRequest();
  const { profile, isLoading, errorMessage, refreshProfile } = useUserProfile();
  const [isEditConfirmVisible, setIsEditConfirmVisible] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isEditSheetVisible, setIsEditSheetVisible] = useState(false);
  const [profileOverrides, setProfileOverrides] = useState({});
  const [editBaseline, setEditBaseline] = useState({});
  
  // Edit form state
  const [editFirstName, setEditFirstName] = useState(profile?.first_name || "");
  const [editLastName, setEditLastName] = useState(profile?.last_name || "");
  const [editPhone, setEditPhone] = useState(profile?.phone || "");
  const [editPosition, setEditPosition] = useState(profile?.position || "");
  const [isSavingEdit, setIsSavingEdit] = useState(false);
  const [isChangePasswordSheetVisible, setIsChangePasswordSheetVisible] = useState(false);
  const [isPasswordConfirmVisible, setIsPasswordConfirmVisible] = useState(false);
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmNewPassword, setConfirmNewPassword] = useState("");
  const [isSavingPassword, setIsSavingPassword] = useState(false);

  const handleRefresh = useCallback(async () => {
    setIsRefreshing(true);
    try {
      setProfileOverrides({});
      await refreshProfile();
    } finally {
      setIsRefreshing(false);
    }
  }, [refreshProfile]);

  const handleOpenEditSheet = () => {
    const currentProfile = {
      ...(profile || {}),
      ...(profileOverrides || {}),
    };

    const nextBaseline = {
      first_name: currentProfile?.first_name || "",
      last_name: currentProfile?.last_name || "",
      phone: currentProfile?.phone || "",
      position: currentProfile?.position || "",
    };

    setEditBaseline(nextBaseline);
    setEditFirstName(nextBaseline.first_name);
    setEditLastName(nextBaseline.last_name);
    setEditPhone(nextBaseline.phone);
    setEditPosition(nextBaseline.position);
    setIsEditSheetVisible(true);
  };

  const validatePhoneNumber = (phone) => {
    return /^09\d{9}$/.test(String(phone || ""));
  };

  const isPhoneValid = validatePhoneNumber(editPhone);
  const hasChanges =
    editFirstName !== (editBaseline.first_name || "") ||
    editLastName !== (editBaseline.last_name || "") ||
    editPhone !== (editBaseline.phone || "") ||
    editPosition !== (editBaseline.position || "");
  
  // Only validate phone if it was actually changed
  const phoneWasChanged = editPhone !== (editBaseline.phone || "");
  const phoneValidIfChanged = !phoneWasChanged ? true : isPhoneValid;
  const isSaveDisabled = !hasChanges || !phoneValidIfChanged || isSavingEdit;

  const handlePhoneChange = (text) => {
    // Only allow digits and restrict to 11 digits
    const cleaned = text.replace(/[^0-9]/g, "").slice(0, 11);
    setEditPhone(cleaned);
  };

  const handleSaveEdit = () => {
    if (!isPhoneValid) {
      Alert.alert("Validation Error", "Mobile number must be 11 digits and start with 09");
      return;
    }

    setIsEditSheetVisible(false);
    setIsEditConfirmVisible(true);
  };

  const handleConfirmSaveEdit = async () => {
    setIsEditConfirmVisible(false);

    if (!session?.id) {
      Alert.alert("Error", "Unable to identify the current user. Please log in again.");
      return;
    }

    setIsSavingEdit(true);
    try {
      const payload = {
        user_id: session?.id,
        first_name: editFirstName,
        last_name: editLastName,
        phone: editPhone,
        position: editPosition,
      };

      const response = await fetchJsonWithFallback("/api/mobile/user/profile/update", {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const updatedUser = response?.user || {};

      // Reflect latest saved values immediately on the profile screen.
      setProfileOverrides({
        first_name: updatedUser?.first_name ?? editFirstName,
        last_name: updatedUser?.last_name ?? editLastName,
        phone: updatedUser?.phone ?? editPhone,
        position: updatedUser?.position ?? editPosition,
      });

      Alert.alert("Success", response?.message || "Profile updated successfully");
      setIsEditSheetVisible(false);
      await refreshProfile();
    } catch (error) {
      Alert.alert("Error", error?.message || "Failed to update profile");
    } finally {
      setIsSavingEdit(false);
    }
  };

  const passwordRequirements = {
    minLength: newPassword.length >= 8,
    upperLower: /[A-Z]/.test(newPassword) && /[a-z]/.test(newPassword),
    hasNumber: /[0-9]/.test(newPassword),
    hasSpecial: /[^A-Za-z0-9]/.test(newPassword),
  };

  const arePasswordRequirementsMet = Object.values(passwordRequirements).every(Boolean);
  const isPasswordConfirmationMatching =
    confirmNewPassword.length > 0 && newPassword === confirmNewPassword;

  const isChangePasswordSaveDisabled =
    !currentPassword ||
    !newPassword ||
    !confirmNewPassword ||
    !arePasswordRequirementsMet ||
    !isPasswordConfirmationMatching ||
    isSavingPassword;

  const resetChangePasswordForm = () => {
    setCurrentPassword("");
    setNewPassword("");
    setConfirmNewPassword("");
  };

  const handleOpenChangePasswordSheet = () => {
    resetChangePasswordForm();
    setIsChangePasswordSheetVisible(true);
  };

  const handleCloseChangePasswordSheet = () => {
    if (isSavingPassword) {
      return;
    }

    setIsChangePasswordSheetVisible(false);
    resetChangePasswordForm();
  };

  const handleSaveChangePassword = () => {
    if (isChangePasswordSaveDisabled) {
      return;
    }

    setIsChangePasswordSheetVisible(false);
    setIsPasswordConfirmVisible(true);
  };

  const handleConfirmChangePassword = async () => {
    setIsPasswordConfirmVisible(false);

    if (!session?.id) {
      Alert.alert("Error", "Unable to identify the current user. Please log in again.");
      return;
    }

    setIsSavingPassword(true);
    try {
      const payload = {
        user_id: session?.id,
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmNewPassword,
      };

      const response = await fetchJsonWithFallback("/api/mobile/user/password/update", {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      await signOut();

      Alert.alert(
        "Password Updated",
        response?.message || "Your password was updated successfully. Please log in again.",
        [
          {
            text: "OK",
            onPress: () => {
              router.replace(APP_ROUTES.login);
            },
          },
        ]
      );

      setIsChangePasswordSheetVisible(false);
      resetChangePasswordForm();
    } catch (error) {
      Alert.alert("Error", error?.message || "Failed to update password.");
      setIsChangePasswordSheetVisible(true);
    } finally {
      setIsSavingPassword(false);
    }
  };

  const displayProfile = {
    ...(profile || {}),
    ...(profileOverrides || {}),
  };

  const fullName = `${displayProfile?.first_name || ""} ${displayProfile?.last_name || ""}`.trim() || "User Profile";
  const initials = (displayProfile?.first_name?.[0] || "U") + (displayProfile?.last_name?.[0] || "P");

  const getStatusColor = (status) => {
    switch (status) {
      case "active":
        return APP_COLORS.success;
      case "inactive":
        return APP_COLORS.primaryRed;
      default:
        return APP_COLORS.tabInactive;
    }
  };

  const ProfileField = ({ icon, label, value }) => {
    if (!value) return null;
    return (
      <View className="flex-row items-start gap-3 py-3">
        <View className="h-10 w-10 rounded-lg items-center justify-center" style={{ backgroundColor: APP_COLORS.primaryBlueLight }}>
          <Feather name={icon} size={18} color={APP_COLORS.primaryBlue} />
        </View>
        <View className="flex-1">
          <Text className="text-xs font-semibold text-slate-500 mb-1" style={{ color: APP_COLORS.textSubtle }}>
            {label}
          </Text>
          <Text className="text-sm font-medium text-slate-900" numberOfLines={2} style={FONT_STYLES.medium}>
            {value}
          </Text>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView className="flex-1 bg-white" edges={[]}>
      <ScrollView
        contentContainerStyle={{ flexGrow: 1 }}
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} tintColor={APP_COLORS.primaryBlue} />}
        showsVerticalScrollIndicator={false}
      >
        {/* Header Section */}
          <View className="px-6 pt-6 pb-8" style={{ background: `linear-gradient(180deg, ${APP_COLORS.primaryBlueLight} 0%, white 100%)` }}>
          <View className="flex-row items-start justify-between">
            {/* Avatar and Info */}
            <View className="flex-row gap-4 flex-1">
              {/* Avatar */}
              <View className="h-18 w-18 rounded-full items-center justify-center" style={{ backgroundColor: APP_COLORS.primaryBlueLight, minWidth: 80 }}>
                <Text className="text-2xl font-bold" style={[{ color: APP_COLORS.primaryBlue }, FONT_STYLES.bold]}>
                  {initials}
                </Text>
              </View>

              {/* Info */}
              <View className="flex-1 justify-center">
                <Text className="text-lg font-bold text-slate-900 mb-1" style={FONT_STYLES.bold}>
                  {fullName}
                </Text>
                <Text className="text-xs text-slate-500 mb-2" style={FONT_STYLES.regular}>
                  @{displayProfile?.username || "user"}
                </Text>
                
                {/* Status Badge */}
                <View className="flex-row items-center gap-2 px-3 py-1.5 rounded-full" style={{ backgroundColor: `${getStatusColor(displayProfile?.status)}20`, alignSelf: "flex-start" }}>
                  <View
                    className="h-2 w-2 rounded-full"
                    style={{ backgroundColor: getStatusColor(displayProfile?.status) }}
                  />
                  <Text className="text-xs font-semibold" style={[{ color: getStatusColor(displayProfile?.status) }, FONT_STYLES.semiBold]}>
                    {(displayProfile?.status || "unknown")?.charAt(0).toUpperCase() + (displayProfile?.status || "unknown").slice(1)}
                  </Text>
                </View>
              </View>
            </View>

            {/* Edit Button */}
            <Pressable
              className="h-10 w-10 rounded-lg items-center justify-center ml-2"
              style={({ pressed }) => ({
                backgroundColor: pressed ? `${APP_COLORS.primaryBlue}20` : `${APP_COLORS.primaryBlue}10`,
              })}
              onPress={handleOpenEditSheet}
            >
              <Feather name="edit-2" size={20} color={APP_COLORS.primaryBlue} />
            </Pressable>
          </View>
        </View>

        {/* Content Section */}
        {isLoading ? (
          <View className="flex-1 items-center justify-center">
            <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
          </View>
        ) : errorMessage ? (
          <View className="flex-1 items-center justify-center px-6">
            <View className="h-12 w-12 rounded-full items-center justify-center mb-3" style={{ backgroundColor: APP_COLORS.primaryRedLight }}>
              <Feather name="alert-circle" size={24} color={APP_COLORS.primaryRed} />
            </View>
            <Text className="text-center text-sm font-medium text-slate-600 mb-4" style={FONT_STYLES.medium}>
              {errorMessage}
            </Text>
            <Pressable
              className="px-4 py-2 rounded-lg"
              style={{ backgroundColor: APP_COLORS.primaryBlue }}
              onPress={handleRefresh}
            >
              <Text className="text-white text-sm font-semibold" style={FONT_STYLES.semiBold}>Retry</Text>
            </Pressable>
          </View>
        ) : (
          <View className="px-6 pb-8">
            {/* Contact Information */}
            <View className="mb-8">
              <Text className="text-base font-bold mb-4" style={[{ color: APP_COLORS.primaryBlue }, FONT_STYLES.bold]}>
                Contact Information
              </Text>
              <View className="rounded-xl bg-slate-50 p-4" style={{ backgroundColor: `${APP_COLORS.primary}08` }}>
                <ProfileField icon="mail" label="Email Address" value={displayProfile?.email} />
                <ProfileField icon="phone" label="Phone Number" value={displayProfile?.phone} />
              </View>
            </View>

            {/* Organization Information */}
            {(displayProfile?.agency || displayProfile?.position || displayProfile?.role) && (
              <View className="mb-8">
                <Text className="text-base font-bold text-slate-900 mb-4" style={[{ color: APP_COLORS.primaryBlue }, FONT_STYLES.bold]}>
                  Organization
                </Text>
                <View className="rounded-xl bg-slate-50 p-4" style={{ backgroundColor: `${APP_COLORS.primary}08` }}>
                  <ProfileField icon="briefcase" label="Agency" value={displayProfile?.agency} />
                  <ProfileField icon="tag" label="Position" value={displayProfile?.position} />
                  <ProfileField icon="user-check" label="Role" value={displayProfile?.role?.replace(/^user_/, "").replace(/_/g, " ").toUpperCase()} />
                </View>
              </View>
            )}

            {/* Location Information */}
            {(displayProfile?.region || displayProfile?.province || displayProfile?.office) && (
              <View className="mb-8">
                <Text className="text-base font-bold text-slate-900 mb-4" style={[{ color: APP_COLORS.primaryBlue }, FONT_STYLES.bold]}>
                  Location
                </Text>
                <View className="rounded-xl bg-slate-50 p-4" style={{ backgroundColor: `${APP_COLORS.primary}08` }}>
                  <ProfileField icon="globe" label="Region" value={displayProfile?.region} />
                  <ProfileField icon="map-pin" label="Province" value={displayProfile?.province} />
                  <ProfileField icon="building" label="Office" value={displayProfile?.office} />
                </View>
              </View>
            )}

            <View className="mb-2">
              <Text className="text-base font-bold text-slate-900 mb-4" style={[{ color: APP_COLORS.primaryBlue }, FONT_STYLES.bold]}>
                Security
              </Text>
              <Pressable
                className="rounded-xl border px-4 py-4"
                style={({ pressed }) => ({
                  borderColor: `${APP_COLORS.primaryBlue}33`,
                  backgroundColor: pressed ? `${APP_COLORS.primaryBlue}18` : `${APP_COLORS.primaryBlue}10`,
                })}
                onPress={handleOpenChangePasswordSheet}
              >
                <View className="flex-row items-center justify-between">
                  <View className="flex-row items-center gap-3 flex-1 pr-3">
                    <View
                      className="h-10 w-10 rounded-lg items-center justify-center"
                      style={{ backgroundColor: "#ffffff" }}
                    >
                      <Feather name="lock" size={18} color={APP_COLORS.primaryBlue} />
                    </View>

                    <View className="flex-1">
                      <Text className="text-sm font-semibold" style={[{ color: APP_COLORS.primaryBlue }, FONT_STYLES.semiBold]}>
                        Change Password
                      </Text>
                      <Text className="text-xs mt-1" style={[{ color: APP_COLORS.textSubtle }, FONT_STYLES.regular]}>
                        Update your password to keep your account secure.
                      </Text>
                    </View>
                  </View>

                  <Feather name="chevron-right" size={18} color={APP_COLORS.primaryBlue} />
                </View>
              </Pressable>
            </View>

          </View>
        )}
      </ScrollView>

      <EditProfileBottomSheet
        visible={isEditSheetVisible}
        onClose={() => setIsEditSheetVisible(false)}
        editFirstName={editFirstName}
        setEditFirstName={setEditFirstName}
        editLastName={editLastName}
        setEditLastName={setEditLastName}
        editPhone={editPhone}
        onPhoneChange={handlePhoneChange}
        editPosition={editPosition}
        setEditPosition={setEditPosition}
        isSaving={isSavingEdit}
        isSaveDisabled={isSaveDisabled}
        isPhoneValid={isPhoneValid}
        onSave={handleSaveEdit}
      />

      <ConfirmationModal
        visible={isEditConfirmVisible}
        title="Save profile changes"
        message="Do you want to apply these profile updates?"
        confirmLabel="Confirm"
        cancelLabel="Cancel"
        onConfirm={handleConfirmSaveEdit}
        onCancel={() => {
          setIsEditConfirmVisible(false);
          setIsEditSheetVisible(true);
        }}
        loading={isSavingEdit}
      />

      <ChangePasswordBottomSheet
        visible={isChangePasswordSheetVisible}
        onClose={handleCloseChangePasswordSheet}
        currentPassword={currentPassword}
        newPassword={newPassword}
        confirmNewPassword={confirmNewPassword}
        onCurrentPasswordChange={setCurrentPassword}
        onNewPasswordChange={setNewPassword}
        onConfirmNewPasswordChange={setConfirmNewPassword}
        passwordRequirements={passwordRequirements}
        isConfirmPasswordMatching={isPasswordConfirmationMatching}
        isSaving={isSavingPassword}
        isSaveDisabled={isChangePasswordSaveDisabled}
        onSave={handleSaveChangePassword}
      />

      <ConfirmationModal
        visible={isPasswordConfirmVisible}
        title="Change password"
        message="Changing your password will log you out for security. Do you want to continue?"
        confirmLabel="Save & Log out"
        cancelLabel="Back"
        onConfirm={handleConfirmChangePassword}
        onCancel={() => {
          setIsPasswordConfirmVisible(false);
          setIsChangePasswordSheetVisible(true);
        }}
        loading={isSavingPassword}
      />
    </SafeAreaView>
  );
}

export const meta = {
  title: "Profile",
};

export const options = {
  title: "Profile",
};
