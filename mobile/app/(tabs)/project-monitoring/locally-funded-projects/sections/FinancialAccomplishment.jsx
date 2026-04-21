import { Text, View } from "react-native";

export default function FinancialAccomplishment() {
  return (
    <View className="mt-3 rounded-2xl border border-[#d7e2f5] bg-white px-4 py-4">
      <Text className="text-[15px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        Financial Accomplishment
      </Text>
      <Text className="mt-2 text-[13px] leading-[20px] text-[#5a6b8e]" style={{ fontFamily: "Montserrat" }}>
        This section page is ready. Connect your API fields here to display project details.
      </Text>
    </View>
  );
}
