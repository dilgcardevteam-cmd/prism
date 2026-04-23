import { Feather } from "@expo/vector-icons";
import { Text, View } from "react-native";

import { APP_COLORS } from "../../../../constants/theme";

function StatPill({ icon, label }) {
  return (
    <View className="min-w-[31%] flex-row items-center justify-center rounded-full border border-[#c9cdd5] bg-white px-3 py-2">
      <Feather name={icon} size={16} color={APP_COLORS.primaryBlue} />
      <Text className="ml-2 text-[14px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        {label}
      </Text>
    </View>
  );
}

export default function DashboardQuickStats() {
  return (
    <View className="flex-row flex-wrap justify-between gap-y-3">
      <StatPill icon="filter" label="Filter" />
      <StatPill icon="monitor" label="Monitoring" />
      <StatPill icon="award" label="Expected Completion" />
    </View>
  );
}