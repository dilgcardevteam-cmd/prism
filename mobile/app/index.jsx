import { useRouter } from "expo-router";
import { useState } from "react";
import {
  Alert,
    Image,
    KeyboardAvoidingView,
    Platform,
    Pressable,
    Text,
    TextInput,
    View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { useAuth } from "../contexts/AuthContext";
import { APP_ROUTES } from "../constants/routes";

export default function LoginScreen() {
  const router = useRouter();
  const { signIn } = useAuth();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleLoginPress = async () => {
    const trimmedUsername = username.trim();
    const enteredPassword = password;

    if (!trimmedUsername || !enteredPassword.trim()) {
      Alert.alert("Login required", "Please enter your username and password.");
      return;
    }

    setIsSubmitting(true);

    try {
      await signIn({ username: trimmedUsername, password: enteredPassword });
      router.replace(APP_ROUTES.homeTab);
    } catch (error) {
      Alert.alert(
        "Login failed",
        error?.message || "The username or password is incorrect."
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-[#f8fafc]">
      <KeyboardAvoidingView
        className="flex-1"
        behavior={Platform.OS === "ios" ? "padding" : undefined}
      >
        <View className="flex-1 justify-center px-6">
          <View className="mb-9 items-center">
            <View className="mb-3 h-[94px] w-[94px] items-center justify-center rounded-full border border-[#dbe3f0] bg-white shadow-md shadow-[#002c76]/10">
              <Image
                source={require("../assets/images/dilg-logo.png")}
                className="h-[78px] w-[78px]"
                resizeMode="contain"
              />
            </View>
            <Text className="mb-1.5 text-[38px] font-extrabold tracking-[0.4px] text-[#002c76]">
              PDMUOMS
            </Text>
            <Text className="text-center text-[17px] leading-[22px] text-[#1e3a8a]">
              PDMU Operations Management System
            </Text>
          </View>

          <View className="rounded-[18px] border border-[#dbe3f0] bg-[#ffffff] px-4 py-[18px]">
            <View className="mb-[18px]">
              <Text className="mb-1.5 text-[13px] font-bold tracking-[0.4px] text-[#002c76]">
                USERNAME
              </Text>
              <TextInput
                value={username}
                onChangeText={setUsername}
                placeholder="Enter username"
                autoCapitalize="none"
                placeholderTextColor="#64748b"
                className="h-[50px] rounded-xl border border-[#0a4cb3] bg-white px-[14px] text-[#002c76]"
              />

              <Text className="mb-1.5 mt-4 text-[13px] font-bold tracking-[0.4px] text-[#002c76]">
                PASSWORD
              </Text>
              <TextInput
                value={password}
                onChangeText={setPassword}
                placeholder="Enter password"
                secureTextEntry
                placeholderTextColor="#64748b"
                className="h-[50px] rounded-xl border border-[#0a4cb3] bg-white px-[14px] text-[#002c76]"
              />
            </View>

            <Pressable
              className="h-[50px] w-full items-center justify-center self-center rounded-xl border border-[#0a4cb3] bg-[#dbeafe]"
              onPress={handleLoginPress}
              disabled={isSubmitting}
              style={({ pressed }) => ({ opacity: pressed || isSubmitting ? 0.75 : 1 })}
            >
              <Text className="text-[20px] font-bold text-[#002c76]">
                {isSubmitting ? "Signing in..." : "Login"}
              </Text>
            </Pressable>
          </View>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
