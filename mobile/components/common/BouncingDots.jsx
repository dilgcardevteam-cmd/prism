import { useEffect } from "react";
import Animated, { useSharedValue, useAnimatedStyle, withRepeat, withTiming, Easing, withDelay } from "react-native-reanimated";
import { View } from "react-native";

export default function BouncingDots({ 
  size = 8, 
  color = "#ffffff",
  style,
}) {
  const dot1Y = useSharedValue(0);
  const dot2Y = useSharedValue(0);
  const dot3Y = useSharedValue(0);

  useEffect(() => {
    const duration = 600;
    const bounceAmount = 12;

    dot1Y.value = withRepeat(
      withTiming(-bounceAmount, { duration, easing: Easing.inOut(Easing.ease) }),
      -1,
      true
    );

    dot2Y.value = withRepeat(
      withDelay(
        100,
        withTiming(-bounceAmount, { duration, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );

    dot3Y.value = withRepeat(
      withDelay(
        200,
        withTiming(-bounceAmount, { duration, easing: Easing.inOut(Easing.ease) })
      ),
      -1,
      true
    );
  }, [dot1Y, dot2Y, dot3Y]);

  const dot1Style = useAnimatedStyle(() => ({
    transform: [{ translateY: dot1Y.value }],
  }));

  const dot2Style = useAnimatedStyle(() => ({
    transform: [{ translateY: dot2Y.value }],
  }));

  const dot3Style = useAnimatedStyle(() => ({
    transform: [{ translateY: dot3Y.value }],
  }));

  return (
    <View style={[{ flexDirection: "row", gap: 6, alignItems: "center" }, style]}>
      <Animated.View
        style={[
          {
            width: size,
            height: size,
            borderRadius: size / 2,
            backgroundColor: color,
          },
          dot1Style,
        ]}
      />
      <Animated.View
        style={[
          {
            width: size,
            height: size,
            borderRadius: size / 2,
            backgroundColor: color,
          },
          dot2Style,
        ]}
      />
      <Animated.View
        style={[
          {
            width: size,
            height: size,
            borderRadius: size / 2,
            backgroundColor: color,
          },
          dot3Style,
        ]}
      />
    </View>
  );
}
