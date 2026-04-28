import { Text, View, Pressable } from "react-native";
import { MaterialCommunityIcons } from "@expo/vector-icons";
import Animated from "react-native-reanimated";
import { LinearGradient } from "expo-linear-gradient";

import { APP_COLORS } from "../../../../constants/theme";
import BouncingDots from "../../../../components/common/BouncingDots";

export default function HomeHeroSection({ greeting, firstName, summaryLabel, totalProjects, isLoadingSummary, style, onRefresh, isRefreshing }) {
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
          <View className="flex-row items-center justify-between">
            <View className="flex-1">
              <Text className="text-[28px] leading-[32px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                {greeting}, {firstName}!
              </Text>
              <Text className="mt-2 text-[14px] leading-[20px] text-white/85" style={{ fontFamily: "Montserrat" }}>
                {summaryLabel}
              </Text>
            </View>

            {onRefresh && (
              <Pressable
                onPress={onRefresh}
                disabled={isRefreshing || isLoadingSummary}
                hitSlop={12}
                className="ml-4"
              >
                <MaterialCommunityIcons
                  name="refresh"
                  size={24}
                  color="#ffffff"
                  style={{
                    opacity: isRefreshing || isLoadingSummary ? 0.6 : 1,
                    transform: [{ rotate: isRefreshing ? "360deg" : "0deg" }],
                  }}
                />
              </Pressable>
            )}
          </View>

          <View className="mt-10">
            {isLoadingSummary ? (
              <BouncingDots size={16} color="#ffffff" style={{ marginBottom: 8 }} />
            ) : (
              <Text className="text-[58px] leading-[60px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                {totalProjects}
              </Text>
            )}
            <Text className="mt-1 text-[14px] uppercase tracking-[1.5px] text-white/85" style={{ fontFamily: "Montserrat-SemiBold" }}>
              Total Number of Projects
            </Text>
          </View>
        </View>
      </LinearGradient>
    </Animated.View>
  );
}