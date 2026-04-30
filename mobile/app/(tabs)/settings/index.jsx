import { useRouter } from "expo-router";
import { useState } from "react";
import { Modal, Pressable, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { useAuth } from "../../../contexts/AuthContext";
import { APP_ROUTES } from "../../../constants/routes";
import { APP_COLORS } from "../../../constants/theme";

// ICONS==============================================
import { Feather } from "@expo/vector-icons";

export default function SettingsScreen() {
  const router = useRouter();
  const { signOut } = useAuth();
  const [isLogoutModalVisible, setIsLogoutModalVisible] = useState(false);

  const containerStyle = { backgroundColor: APP_COLORS.background };

  const settingsButtonStyle = {
    borderWidth: 1,
    borderColor: APP_COLORS.primaryBlue,
    backgroundColor: APP_COLORS.primaryBlueLight,
    
    marginTop: 2,
    marginBottom:2
  }
  const settingsTextStyle = {
    color: APP_COLORS.primaryBlue
  };

  const logoutButtonStyle = {
    borderWidth: 1,
    borderColor: APP_COLORS.primaryRed,
    backgroundColor: APP_COLORS.primaryRedLight,
    
    marginTop: 2,
    marginBottom: 2
  };
  const logoutTextStyle = { color: APP_COLORS.primaryRed };

  const modalCardStyle = { backgroundColor: APP_COLORS.backgroundCard };
  const modalTitleStyle = { color: APP_COLORS.primary };
  const modalMessageStyle = { color: APP_COLORS.primaryMuted };
  const cancelButtonTextStyle = { color: APP_COLORS.primaryMuted };
  const confirmButtonStyle = {
    borderColor: APP_COLORS.accentBorder,
    backgroundColor: APP_COLORS.accentSurface,
  };
  const confirmButtonTextStyle = { color: APP_COLORS.primary };

  const openLogoutModal = () => {
    setIsLogoutModalVisible(true);
  };

  const closeLogoutModal = () => {
    setIsLogoutModalVisible(false);
  };

  const handleConfirmLogout = async () => {
    setIsLogoutModalVisible(false);
    await signOut();
    router.replace(APP_ROUTES.login);
  };

  return (
    <SafeAreaView className="flex-1 pt-6" style={containerStyle} edges={[]}> 
      <View className="px-6">
        <Pressable
          className="flex flex-row gap-4 h-[50px] items-center justify-center rounded-full"
          style={settingsButtonStyle}
        >
          <Feather name="user" size={20} color={APP_COLORS.primaryBlue}/>
          <Text className="text-[16px] font-medium" style={settingsTextStyle}>
            Account
          </Text>
        </Pressable>

        <Pressable
          className="flex flex-row gap-4 h-[50px] items-center justify-center rounded-full"
          style={logoutButtonStyle}
          onPress={openLogoutModal}
        >
          <Feather name="log-out" size={20} color={APP_COLORS.primaryRed} />
          <Text className="text-[16px] font-medium" style={logoutTextStyle}>
            Logout
          </Text>
        </Pressable>
      </View>

      <Modal
        visible={isLogoutModalVisible}
        transparent
        animationType="fade"
        onRequestClose={closeLogoutModal}
      >
        <View
          className="flex-1 items-center justify-center px-6"
          style={{ backgroundColor: "rgba(16, 28, 54, 0.4)" }}
        >
          <View
            className="w-full max-w-[360px] rounded-2xl border border-[#d6dfef] px-[18px] py-[18px]"
            style={modalCardStyle}
          >
            <Text className="text-[20px] font-bold" style={modalTitleStyle}>
              Confirm Logout
            </Text>
            <Text
              className="mt-[10px] text-[15px] leading-5"
              style={modalMessageStyle}
            >
              Are you sure you want to logout?
            </Text>

            <View className="mt-5 flex-row justify-end">
              <Pressable
                className="h-[42px] min-w-[90px] items-center justify-center rounded-[10px] border border-[#c4d0e6] bg-[#f8fbff]"
                onPress={closeLogoutModal}
              >
                <Text className="text-[15px] font-semibold" style={cancelButtonTextStyle}>
                  Cancel
                </Text>
              </Pressable>

              <Pressable
                className="ml-[10px] h-[42px] min-w-[90px] items-center justify-center rounded-[10px] border"
                style={confirmButtonStyle}
                onPress={handleConfirmLogout}
              >
                <Text className="text-[15px] font-bold" style={confirmButtonTextStyle}>
                  Logout
                </Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

export const meta = {
  title: "Settings",
};

export const options = {
  title: "Settings",
};
