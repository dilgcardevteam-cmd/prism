import { useRouter } from "expo-router";
import { useEffect, useRef, useState } from "react";
import { Alert, KeyboardAvoidingView, Platform, Pressable, Text, TextInput, View} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";

import LottieView from "lottie-react-native";

import FloatingInput from "../components/common/FloatingInput";
import LoginLoading from "../components/common/LoginLoading";

import { useAuth } from "../contexts/AuthContext";
import { APP_ROUTES } from "../constants/routes";
import { APP_COLORS } from "../constants/theme";

export default function LoginScreen() {
  const router = useRouter();
  const {
    signIn,
    submitMandatoryPasswordChange,
    verifyLoginOtp,
    resendLoginOtp,
    resendAvailableAt,
    cancelLoginOtp,
  } = useAuth();

  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [newPasswordConfirmation, setNewPasswordConfirmation] = useState("");
  const [otp, setOtp] = useState("");
  const [requiresOtp, setRequiresOtp] = useState(false);
  const [requiresPasswordChange, setRequiresPasswordChange] = useState(false);
  const [isMandatoryPasswordOtp, setIsMandatoryPasswordOtp] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [clock, setClock] = useState(Date.now());
  const submitLock = useRef(false);

  useEffect(() => {
    if (!requiresOtp) {
      return undefined;
    }

    const timer = setInterval(() => setClock(Date.now()), 1000);
    return () => clearInterval(timer);
  }, [requiresOtp]);

  const handleLoginPress = async () => {
    if (submitLock.current) return;
    if (!username.trim() || !password.trim()) {
      Alert.alert("Login required", "Please enter your credentials.");
      return;
    }

    submitLock.current = true;
    setIsSubmitting(true);
    try {
      const result = await signIn({ username, password });
      if (result?.requiresPasswordChange) {
        setPassword("");
        setIsMandatoryPasswordOtp(true);
        setRequiresPasswordChange(!result.requiresOtp);
        setRequiresOtp(Boolean(result.requiresOtp));
      } else if (result?.requiresOtp) {
        setPassword("");
        setRequiresOtp(true);
      } else {
        router.replace(APP_ROUTES.homeTab);
      }
    } catch (error) {
      Alert.alert("Login failed", error?.message || "Invalid credentials.");
    } finally {
      setIsSubmitting(false);
      submitLock.current = false;
    }
  };

  const handleMandatoryPasswordChange = async () => {
    if (submitLock.current) return;
    if (!newPassword || !newPasswordConfirmation) {
      Alert.alert("Password required", "Enter and confirm your new password.");
      return;
    }

    submitLock.current = true;
    setIsSubmitting(true);
    try {
      await submitMandatoryPasswordChange(newPassword, newPasswordConfirmation);
      setNewPassword("");
      setNewPasswordConfirmation("");
      setRequiresPasswordChange(false);
      setIsMandatoryPasswordOtp(true);
      setRequiresOtp(true);
    } catch (error) {
      Alert.alert("Unable to change password", error?.message || "Please try again.");
    } finally {
      setIsSubmitting(false);
      submitLock.current = false;
    }
  };

  const handleOtpPress = async () => {
    if (submitLock.current) return;
    if (!/^\d{6}$/.test(otp)) {
      Alert.alert("Verification required", "Enter the six-digit code sent to your email.");
      return;
    }

    submitLock.current = true;
    setIsSubmitting(true);
    try {
      await verifyLoginOtp(otp);
      router.replace(APP_ROUTES.homeTab);
    } catch (error) {
      Alert.alert("Verification failed", error?.message || "The code could not be verified.");
    } finally {
      setIsSubmitting(false);
      submitLock.current = false;
    }
  };

  const handleResendOtp = async () => {
    if (submitLock.current) return;
    submitLock.current = true;
    setIsSubmitting(true);

    try {
      await resendLoginOtp();
      setOtp("");
      Alert.alert("Code sent", "A new verification code has been sent to your registered email.");
    } catch (error) {
      Alert.alert("Unable to resend", error?.message || "Please try again later.");
    } finally {
      setIsSubmitting(false);
      submitLock.current = false;
    }
  };

  const resendSeconds = resendAvailableAt
    ? Math.max(0, Math.ceil((new Date(resendAvailableAt).getTime() - clock) / 1000))
    : 0;

  const handleBackToLogin = () => {
    cancelLoginOtp();
    setRequiresOtp(false);
    setRequiresPasswordChange(false);
    setIsMandatoryPasswordOtp(false);
    setOtp("");
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
          {requiresOtp ? (
            <>
              <Text className="mb-5 text-center font-sans text-base text-gray-600">
                {isMandatoryPasswordOtp
                  ? "A six-digit code is being sent to confirm your password change. It may take a moment to arrive."
                  : "A six-digit code is being sent to your registered email address. It may take a moment to arrive."}
              </Text>
              <TextInput
                value={otp}
                onChangeText={(value) => setOtp(value.replace(/\D/g, "").slice(0, 6))}
                keyboardType="number-pad"
                textContentType="oneTimeCode"
                autoComplete="sms-otp"
                maxLength={6}
                placeholder="000000"
                accessibilityLabel="Six-digit verification code"
                style={{
                  borderBottomWidth: 1,
                  borderBottomColor: APP_COLORS.primaryBlue,
                  color: APP_COLORS.primaryBlue,
                  fontSize: 24,
                  letterSpacing: 8,
                  paddingVertical: 12,
                  textAlign: "center",
                }}
              />
              <Pressable className="mt-4 self-center" onPress={handleBackToLogin}>
                <Text className="font-sans text-sm font-semibold" style={{ color: APP_COLORS.primaryBlue }}>
                  Back to sign in
                </Text>
              </Pressable>
              <Pressable
                className="mt-4 self-center"
                onPress={handleResendOtp}
                disabled={isSubmitting || resendSeconds > 0}
              >
                <Text className="font-sans text-sm font-semibold" style={{ color: resendSeconds > 0 ? "#64748b" : APP_COLORS.primaryBlue }}>
                  {resendSeconds > 0
                    ? `Resend code in ${Math.floor(resendSeconds / 60)}:${String(resendSeconds % 60).padStart(2, "0")}`
                    : "Resend code"}
                </Text>
              </Pressable>
            </>
          ) : requiresPasswordChange ? (
            <>
              <Text className="mb-5 text-center font-sans text-base text-gray-600">
                A system administrator requires you to change your password before continuing.
              </Text>
              <FloatingInput
                label="New password"
                value={newPassword}
                onChangeText={setNewPassword}
                secureTextEntry
              />
              <FloatingInput
                label="Confirm new password"
                value={newPasswordConfirmation}
                onChangeText={setNewPasswordConfirmation}
                secureTextEntry
              />
              <Text className="mt-2 text-sm text-gray-500">
                Use at least 8 characters with uppercase and lowercase letters, a number, and a symbol.
              </Text>
            </>
          ) : (
            <>
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
            </>
          )}

          <Pressable
            onPress={requiresOtp
              ? handleOtpPress
              : requiresPasswordChange
                ? handleMandatoryPasswordChange
                : handleLoginPress}
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
                {isSubmitting
                  ? "Please wait..."
                  : requiresOtp
                    ? "VERIFY CODE"
                    : requiresPasswordChange
                      ? "CHANGE PASSWORD"
                      : "SIGN IN"}
              </Text>
            </LinearGradient>
          </Pressable>

          <Text className="absolute bottom-0 mt-4 text-center font-sans text-base text-gray-500">
            © 2026 junoDotDev. All rights reserved.
          </Text>
        </View>
      </KeyboardAvoidingView>

      {/* LOADER */}
      <LoginLoading visible={isSubmitting} message={requiresOtp ? "Verifying code..." : "Signing you in..."}>
        <LottieView
          source={require("../assets/animations/loading-rocketman.json")}
          autoPlay
          loop
          style={{ width: 120, height: 120 }}
        />
      </LoginLoading>
    </SafeAreaView>
  );
}