import React, { useEffect, useRef, useState } from "react";
import {
  Animated,
  TextInput,
  View,
  Easing,
  Pressable,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";

import { APP_COLORS } from "../../constants/theme";

const FloatingInput = ({
  label,
  value,
  onChangeText,
  secureTextEntry = false,
}) => {
  const [isFocused, setIsFocused] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const animatedValue = useRef(new Animated.Value(value ? 1 : 0)).current;

  useEffect(() => {
    Animated.timing(animatedValue, {
      toValue: isFocused || value ? 1 : 0,
      duration: 220,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: false,
    }).start();
  }, [isFocused, value]);

  const labelStyle = {
    position: "absolute",
    left: 0,
    top: animatedValue.interpolate({
      inputRange: [0, 1],
      outputRange: [18, -10],
    }),
    fontSize: animatedValue.interpolate({
      inputRange: [0, 1],
      outputRange: [16, 12],
    }),
    color: isFocused
      ? APP_COLORS.primaryBlue
      : '#9c9c9c',
  };

  return (
    <View style={{ marginBottom: 24 }}>
      {/* LABEL */}
      <Animated.Text style={labelStyle}>
        {label}
      </Animated.Text>

      {/* INPUT WRAPPER */}
      <View style={{ position: "relative" }}>
        <TextInput
          value={value}
          onChangeText={onChangeText}
          secureTextEntry={secureTextEntry && !showPassword}
          onFocus={() => setIsFocused(true)}
          onBlur={() => setIsFocused(false)}
          style={{
            borderBottomWidth: 1,
            borderBottomColor: isFocused
              ? APP_COLORS.primaryBlue
              : '#9c9c9c',
            paddingTop: 18,
            paddingBottom: 8,
            paddingRight: secureTextEntry ? 40 : 0, // space for icon
            color: APP_COLORS.primaryBlue,
          }}
        />

        {/* EYE ICON */}
        {secureTextEntry && (
          <Pressable
            onPress={() => setShowPassword(!showPassword)}
            style={{
              position: "absolute",
              right: 0,
              top: 12,
              padding: 4,
            }}
          >
            <Ionicons
              name={showPassword ? "eye-off-outline" : "eye-outline"}
              size={20}
              color={APP_COLORS.primaryBlue}
            />
          </Pressable>
        )}
      </View>
    </View>
  );
};

export default FloatingInput;