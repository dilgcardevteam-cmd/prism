import { Feather } from "@expo/vector-icons";
import { ActivityIndicator, ScrollView, Text, View } from "react-native";

import { APP_COLORS } from "../../../../constants/theme";

function FinancialMetricTile({ label, value, icon, backgroundColor, borderColor, accentColor, valueColor, width }) {
  const isLongLabel = String(label).length > 11;

  return (
    <View className="mr-3 overflow-hidden rounded-[26px] border px-4 py-4" style={{ backgroundColor, borderColor, width }}>
      <View className="flex-row items-center">
        <View className="flex-1 pr-4" style={{ borderRightWidth: 2, borderRightColor: accentColor }}>
          <Text
            className="uppercase"
            style={{
              fontFamily: "Montserrat-SemiBold",
              color: accentColor,
              fontSize: isLongLabel ? 14 : 16,
              lineHeight: isLongLabel ? 19 : 21,
              letterSpacing: 0.4,
            }}
            numberOfLines={2}
          >
            {label}
          </Text>
        </View>

        <View className="pl-4">
          <Text className="text-[40px]" style={{ color: accentColor, opacity: 0.16 }}>
            <Feather name={icon} size={40} color={accentColor} />
          </Text>
        </View>
      </View>

      <Text className="mt-2 text-right text-[33px]" style={{ fontFamily: "Montserrat", color: valueColor }} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.55}>
        {value}
      </Text>
    </View>
  );
}

export default function FinancialAccomplishmentSection({ isLoadingSummary, summaryError, financialMetrics, financialTileWidth, metricCards }) {
  return (
    <View className="mt-6">
      <Text className="mb-3 text-[18px] uppercase tracking-[0.8px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        Financial Accomplishment Status
      </Text>

      {isLoadingSummary ? (
        <View className="items-center justify-center rounded-[20px] border border-[#dbe4f4] bg-[#f8fbff] px-4 py-8">
          <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
          <Text className="mt-3 text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
            Loading financial summary...
          </Text>
        </View>
      ) : summaryError ? (
        <View className="rounded-[20px] border border-[#f4c7c7] bg-[#fff5f5] px-4 py-4">
          <Text className="text-[14px] text-[#991b1b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
            Unable to load financial summary.
          </Text>
          <Text className="mt-1 text-[12px] text-[#7f1d1d]" style={{ fontFamily: "Montserrat" }}>
            {summaryError}
          </Text>
        </View>
      ) : (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingRight: 4 }}>
          {financialMetrics.map((metric) => {
            const styleMeta = metricCards[metric.key];

            return (
              <FinancialMetricTile
                key={metric.key}
                label={styleMeta.label}
                value={metric.value}
                icon={styleMeta.icon}
                backgroundColor={styleMeta.backgroundColor}
                borderColor={styleMeta.borderColor}
                accentColor={styleMeta.accentColor}
                valueColor={styleMeta.valueColor}
                width={financialTileWidth}
              />
            );
          })}
        </ScrollView>
      )}
    </View>
  );
}