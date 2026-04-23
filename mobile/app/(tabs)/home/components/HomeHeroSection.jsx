import { Text, View } from "react-native";
import Animated from "react-native-reanimated";
import { LinearGradient } from "expo-linear-gradient";

import { APP_COLORS } from "../../../../constants/theme";

export default function HomeHeroSection({ greeting, firstName, summaryLabel, totalProjects, isLoadingSummary, style }) {
  return (
    <Animated.View style={style}>
      <LinearGradient
        colors={["#4069ad", APP_COLORS.primaryBlue]}
        locations={[0.2, 1]}
        start={{ x: 1, y: 0 }}
        end={{ x: 0, y: 1 }}
        style={{ flex: 1 }}
      >
        <View className="px-4 pb-12 pt-6">
          <Text className="text-[28px] leading-[32px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
            {greeting}, {firstName}!
          </Text>
          <Text className="mt-2 text-[14px] leading-[20px] text-white/85" style={{ fontFamily: "Montserrat" }}>
            {summaryLabel}
          </Text>

          <View className="mt-10">
            <Text className="text-[58px] leading-[60px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
              {isLoadingSummary ? "…" : totalProjects}
            </Text>
            <Text className="mt-1 text-[14px] uppercase tracking-[1.5px] text-white/85" style={{ fontFamily: "Montserrat-SemiBold" }}>
              Total Number of Projects
            </Text>
          </View>
        </View>
      </LinearGradient>
    </Animated.View>
  );
}