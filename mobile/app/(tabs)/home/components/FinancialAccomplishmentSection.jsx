import { Feather } from "@expo/vector-icons";
import { ActivityIndicator, Text, View } from "react-native";

import { APP_COLORS } from "../../../../constants/theme";

function FinancialMetricRow({ label, value, icon, backgroundColor, accentColor, valueColor, isLast }) {
  return (
    <View className={`flex-row items-center py-3 ${isLast ? "" : "border-b border-[#bfc0c4]"}`}>
      <View
        className="h-[66px] w-[66px] items-center justify-center rounded-full"
        style={{ backgroundColor, marginRight: 14 }}
      >
        <Feather name={icon} size={32} color={accentColor} />
      </View>

      <View className="flex-1">
        <Text
          className="uppercase"
          style={{
            fontFamily: "Montserrat-SemiBold",
            color: accentColor,
            fontSize: String(label || "").length > 12 ? 13 : 15,
            lineHeight: String(label || "").length > 12 ? 17 : 19,
            letterSpacing: 0.3,
          }}
          numberOfLines={1}
          adjustsFontSizeToFit
          minimumFontScale={0.75}
        >
          {label}
        </Text>

        <Text
          className="mt-1 text-right"
          style={{
            fontFamily: "Montserrat-SemiBold",
            color: valueColor,
            fontSize: String(value || "").length > 12 ? 20 : 22,
            lineHeight: String(value || "").length > 12 ? 24 : 26,
            letterSpacing: -0.3,
          }}
          numberOfLines={1}
          adjustsFontSizeToFit
          minimumFontScale={0.55}
        >
          {value}
        </Text>
      </View>
    </View>
  );
}

export default function FinancialAccomplishmentSection({ isLoadingSummary, summaryError, financialMetrics, financialTileWidth, metricCards }) {
  return (
    <View className="mt-6">
      <Text className="mb-2 text-[18px] uppercase tracking-[0.8px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
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
        <View className="overflow-hidden">
          <View className="border-t border-[#bfc0c4]">
            {financialMetrics.map((metric, index) => {
              const styleMeta = metricCards[metric.key];

              return (
                <FinancialMetricRow
                  key={metric.key}
                  label={styleMeta.label}
                  value={metric.value}
                  icon={styleMeta.icon}
                  backgroundColor={styleMeta.backgroundColor}
                  accentColor={styleMeta.accentColor}
                  valueColor={styleMeta.valueColor}
                  isLast={index === financialMetrics.length - 1}
                />
              );
            })}
          </View>
        </View>
      )}
    </View>
  );
}