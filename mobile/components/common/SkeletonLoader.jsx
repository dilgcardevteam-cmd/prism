import { useEffect } from "react";
import Animated, { useSharedValue, useAnimatedStyle, withRepeat, withTiming, Easing } from "react-native-reanimated";
import { View } from "react-native";

export default function SkeletonLoader({
  width = "100%",
  height = 16,
  borderRadius = 8,
  style,
  count = 1,
  gap = 8,
}) {
  const opacity = useSharedValue(0.6);

  useEffect(() => {
    opacity.value = withRepeat(
      withTiming(1, { duration: 800, easing: Easing.inOut(Easing.ease) }),
      -1,
      true
    );
  }, [opacity]);

  const animatedStyle = useAnimatedStyle(() => ({
    opacity: opacity.value,
  }));

  const skeletons = Array.from({ length: count }).map((_, index) => (
    <Animated.View
      key={index}
      style={[
        {
          width,
          height,
          borderRadius,
          backgroundColor: "#e5e7eb",
          marginBottom: index < count - 1 ? gap : 0,
        },
        animatedStyle,
        style,
      ]}
    />
  ));

  return <View>{skeletons}</View>;
}
