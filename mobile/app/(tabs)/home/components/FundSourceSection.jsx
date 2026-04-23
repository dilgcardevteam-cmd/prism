import { Feather } from "@expo/vector-icons";
import { ActivityIndicator, Text, View } from "react-native";

import { APP_COLORS } from "../../../../constants/theme";

function FundSourceTile({ label, count, icon, backgroundColor, borderColor, accentColor, tileWidth }) {
  return (
    <View className="mb-3 rounded-[14px] border px-2.5 py-2.5" style={{ backgroundColor, borderColor, width: tileWidth }}>
      <View className="flex-row items-stretch">
        <View className="mr-2 items-center justify-center pr-2" style={{ borderRightWidth: 1, borderRightColor: accentColor }}>
          <Feather name={icon} size={22} color={accentColor} />
        </View>
        <View className="flex-1 justify-center">
          <Text
            className="text-[#173e8c]"
            style={{ fontFamily: "Montserrat-SemiBold", fontSize: 30 / Math.max(String(label || "").length, 4), lineHeight: 18 }}
            numberOfLines={1}
            adjustsFontSizeToFit
            minimumFontScale={0.75}
          >
            {label}
          </Text>
          <Text className="mt-0.5 text-[17px] leading-[20px] text-[#1f4a9a]" style={{ fontFamily: "Montserrat" }}>
            {count}
          </Text>
        </View>
      </View>
    </View>
  );
}

export default function FundSourceSection({ isLoadingSummary, summaryError, fundSourceCards, tileWidth }) {
  return (
    <View className="mt-6">
      <Text className="mb-3 text-[18px] uppercase tracking-[0.8px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        Projects by Fund Source
      </Text>

      {isLoadingSummary ? (
        <View className="items-center justify-center rounded-[20px] border border-[#dbe4f4] bg-[#f8fbff] px-4 py-8">
          <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
          <Text className="mt-3 text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
            Loading dashboard summary...
          </Text>
        </View>
      ) : summaryError ? (
        <View className="rounded-[20px] border border-[#f4c7c7] bg-[#fff5f5] px-4 py-4">
          <Text className="text-[14px] text-[#991b1b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
            Unable to load dashboard summary.
          </Text>
          <Text className="mt-1 text-[12px] text-[#7f1d1d]" style={{ fontFamily: "Montserrat" }}>
            {summaryError}
          </Text>
        </View>
      ) : (
        <View className="flex-row flex-wrap" style={{ columnGap: 8 }}>
          {fundSourceCards.map((card) => (
            <FundSourceTile
              key={card.label}
              label={card.label}
              count={card.count}
              icon={card.icon}
              backgroundColor={card.backgroundColor}
              borderColor={card.borderColor}
              accentColor={card.accentColor}
              tileWidth={tileWidth}
            />
          ))}
        </View>
      )}
    </View>
  );
}