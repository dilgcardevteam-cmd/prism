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

// ICONS
import { Feather } from "@expo/vector-icons";

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
          <Text className="text-sm font-medium text-slate-900" numberOfLines={2}>
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
                <Text className="text-2xl font-bold" style={{ color: APP_COLORS.primaryBlue }}>
                  {initials}
                </Text>
              </View>

              {/* Info */}
              <View className="flex-1 justify-center">
                <Text className="text-lg font-bold text-slate-900 mb-1">
                  {fullName}
                </Text>
                <Text className="text-xs text-slate-500 mb-2">
                  @{displayProfile?.username || "user"}
                </Text>
                
                {/* Status Badge */}
                <View className="flex-row items-center gap-2 px-3 py-1.5 rounded-full" style={{ backgroundColor: `${getStatusColor(displayProfile?.status)}20`, alignSelf: "flex-start" }}>
                  <View
                    className="h-2 w-2 rounded-full"
                    style={{ backgroundColor: getStatusColor(displayProfile?.status) }}
                  />
                  <Text className="text-xs font-semibold" style={{ color: getStatusColor(displayProfile?.status) }}>
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
            <Text className="text-center text-sm font-medium text-slate-600 mb-4">
              {errorMessage}
            </Text>
            <Pressable
              className="px-4 py-2 rounded-lg"
              style={{ backgroundColor: APP_COLORS.primaryBlue }}
              onPress={handleRefresh}
            >
              <Text className="text-white text-sm font-semibold">Retry</Text>
            </Pressable>
          </View>
        ) : (
          <View className="px-6 pb-8">
            {/* Contact Information */}
            <View className="mb-8">
              <Text className="text-base font-bold mb-4" style={{ color: APP_COLORS.primaryBlue }}>
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
                <Text className="text-base font-bold text-slate-900 mb-4" style={{ color: APP_COLORS.primaryBlue }}>
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
                <Text className="text-base font-bold text-slate-900 mb-4" style={{ color: APP_COLORS.primaryBlue }}>
                  Location
                </Text>
                <View className="rounded-xl bg-slate-50 p-4" style={{ backgroundColor: `${APP_COLORS.primary}08` }}>
                  <ProfileField icon="globe" label="Region" value={displayProfile?.region} />
                  <ProfileField icon="map-pin" label="Province" value={displayProfile?.province} />
                  <ProfileField icon="building" label="Office" value={displayProfile?.office} />
                </View>
              </View>
            )}

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
        isSaveDisabled={isSaveDisabled}
        isPhoneValid={isPhoneValid}
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
    </SafeAreaView>
  );
}

export const meta = {
  title: "Profile",
};

export const options = {
  title: "Profile",
};
