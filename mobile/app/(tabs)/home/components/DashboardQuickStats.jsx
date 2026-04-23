import { Feather } from "@expo/vector-icons";
import { useState } from "react";
import { Pressable, Text, View } from "react-native";

import { APP_COLORS } from "../../../../constants/theme";
import ProjectsExpectedCompletionSheet from "./ProjectsExpectedCompletionSheet";

function StatPill({ icon, label, onPress }) {
  return (
    <Pressable className="min-w-[31%] flex-row items-center justify-center rounded-full border border-[#c9cdd5] bg-white px-3 py-2" onPress={onPress} hitSlop={8}>
      <Feather name={icon} size={16} color={APP_COLORS.primaryBlue} />
      <Text className="ml-2 text-[14px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        {label}
      </Text>
    </Pressable>
  );
}

export default function DashboardQuickStats({ projectsExpectedCompletionThisMonth, expectedCompletionMonthLabel }) {
  const [isCompletionSheetVisible, setIsCompletionSheetVisible] = useState(false);

  return (
    <View className="flex-row flex-wrap justify-around gap-y-3">
      <StatPill icon="filter" label="Filter" />
      {/* <StatPill icon="monitor" label="Monitoring" /> */}
      <StatPill icon="award" label="Completion" onPress={() => setIsCompletionSheetVisible(true)} />

      <ProjectsExpectedCompletionSheet
        visible={isCompletionSheetVisible}
        onClose={() => setIsCompletionSheetVisible(false)}
        projects={projectsExpectedCompletionThisMonth}
        monthLabel={expectedCompletionMonthLabel}
      />
    </View>
  );
}