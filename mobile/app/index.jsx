import { useRouter } from "expo-router";
import { useState } from "react";
import { Alert, KeyboardAvoidingView, Platform, Pressable, Text, View} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";

import LottieView from "lottie-react-native";

import FloatingInput from "../components/common/FloatingInput";
import LoadingOverlay from "../components/common/LoadingOverlay";

import { useAuth } from "../contexts/AuthContext";
import { APP_ROUTES } from "../constants/routes";
import { APP_COLORS } from "../constants/theme";

export default function LoginScreen() {
  const router = useRouter();
  const { signIn } = useAuth();

  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleLoginPress = async () => {
    if (!username.trim() || !password.trim()) {
      Alert.alert("Login required", "Please enter your credentials.");
      return;
    }

    setIsSubmitting(true);
    try {
      await signIn({ username, password });
      router.replace(APP_ROUTES.homeTab);
    } catch (error) {
      Alert.alert("Login failed", error?.message || "Invalid credentials.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-white">
      <KeyboardAvoidingView
        className="flex-1"
        behavior={Platform.OS === "ios" ? "padding" : undefined}
      >
        <LinearGradient
          className="flex justify-center"
          colors={['#031b42', APP_COLORS.primaryBlue,]}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={{ height: "40%", paddingHorizontal: 24, paddingTop: 60 }}
        >

          <Text className="font-sans text-4xl font-bold text-white">
            PRISM
          </Text>
          <Text className="mt-3 max-w-sm font-sans text-2xl font-semibold leading-tight text-white">
            Reporting, Inspection and Monitoring System
          </Text>
        </LinearGradient>

        <View className="relative -mt-16 flex-1 rounded-t-[30px] bg-white px-6 pt-8">
          <FloatingInput
            label="Username"
            value={username}
            onChangeText={setUsername}
          />
          
          <FloatingInput
            label="Password"
            value={password}
            onChangeText={setPassword}
            secureTextEntry
          />

          <Pressable className="mt-3 self-end">
            <Text
              className="font-sans text-base font-semibold"
              style={{ color: APP_COLORS.primaryBlue }}
            >
              Forgot password?
            </Text>
          </Pressable>

          <Pressable
            onPress={handleLoginPress}
            disabled={isSubmitting}
            className="mt-10 overflow-hidden rounded-full disabled:opacity-70"
          >
            <LinearGradient
              colors={[APP_COLORS.primaryBlue, '#0c254d']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={{
                alignItems: "center",
                justifyContent: "center",
                borderRadius: 999,
                paddingVertical: 16,
              }}
            >
              <Text className="font-sans text-lg font-bold text-white">
                {isSubmitting ? "Signing in..." : "SIGN IN"}
              </Text>
            </LinearGradient>
          </Pressable>

          <Text className="absolute bottom-0 mt-4 text-center font-sans text-base text-gray-500">
            © 2026 junoDotDev. All rights reserved.
          </Text>
        </View>
      </KeyboardAvoidingView>

      {/* LOADER */}
      <LoadingOverlay visible={isSubmitting} message="Signing you in...">
        <LottieView
          source={require("../assets/animations/loading-rocketman.json")}
          autoPlay
          loop
          style={{ width: 120, height: 120 }}
        />
      </LoadingOverlay>
    </SafeAreaView>
  );
}