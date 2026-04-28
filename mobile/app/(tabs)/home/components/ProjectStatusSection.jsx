import { Feather } from "@expo/vector-icons";
import { Text, View } from "react-native";

import { APP_COLORS } from "../../../../constants/theme";
import { formatCount, formatPercentage, STATUS_META } from "../../../../constants/homeDashboardConfig";
import SkeletonLoader from "../../../../components/common/SkeletonLoader";

function StatusSubaybayanRow({ status, count, total, maxCount, screenWidth }) {
  const meta = STATUS_META[status] || {
    icon: "activity",
    iconBackground: APP_COLORS.primaryBlue,
    color: APP_COLORS.primaryBlue,
  };
  const isCompact = screenWidth < 390;
  const iconCircleSize = isCompact ? 42 : 48;
  const iconSize = isCompact ? 20 : 22;
  const statusFontSize = isCompact ? 14 : 16;
  const countFontSize = isCompact ? 15 : 16;
  const percentageFontSize = isCompact ? 14 : 15;
  const statusPercentage = total > 0 ? (count / total) * 100 : 0;
  const barWidth = maxCount > 0 ? (count / maxCount) * 100 : 0;

  const countText = (
    <Text style={{ fontFamily: "Montserrat-SemiBold", color: meta.color, fontSize: countFontSize }}>
      {formatCount(count)}
      <Text style={{ fontFamily: "Montserrat", color: meta.color, fontSize: percentageFontSize }}>
        {` (${formatPercentage(statusPercentage)})`}
      </Text>
    </Text>
  );

  return (
    <View className="mb-2.5 flex-row items-center">
      <View className="items-center justify-center rounded-full" style={{ backgroundColor: meta.iconBackground, height: iconCircleSize, width: iconCircleSize }}>
        <Feather name={meta.icon} size={iconSize} color="#ffffff" />
      </View>

      <View className="ml-3 flex-1">
        <View
          style={{
            flexDirection: isCompact ? "column" : "row",
            alignItems: isCompact ? "flex-start" : "center",
            justifyContent: "space-between",
            width: "100%",
          }}
        >
          <Text
            numberOfLines={1}
            adjustsFontSizeToFit
            minimumFontScale={0.82}
            style={{
              fontFamily: "Montserrat-SemiBold",
              color: meta.color,
              fontSize: statusFontSize,
              paddingRight: isCompact ? 0 : 8,
              flexShrink: 1,
              width: isCompact ? "100%" : undefined,
            }}
          >
            {status}
          </Text>

          <View style={{ marginTop: isCompact ? 2 : 0, width: isCompact ? "100%" : undefined, alignItems: isCompact ? "flex-end" : "flex-start" }}>
            {countText}
          </View>
        </View>

        <View className="mt-1.5 h-2 overflow-hidden rounded-full bg-[#e5e7eb]">
          <View className="h-full rounded-full" style={{ width: `${barWidth}%`, backgroundColor: meta.color }} />
        </View>
      </View>
    </View>
  );
}

export default function ProjectStatusSection({ isLoadingSummary, summaryError, statusSubaybayanRows, statusSubaybayanTotal, statusSubaybayanMax, screenWidth, isCompactScreen }) {
  return (
    <View className="mt-6">
      <Text className="mb-2 text-[18px] uppercase tracking-[0.8px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        Status of Project (SUBAYBAYAN Status)
      </Text>

      {isLoadingSummary ? (
        <View className="overflow-hidden px-4 py-8">
          <SkeletonLoader width="100%" height={60} borderRadius={12} count={4} gap={12} />
        </View>
      ) : summaryError ? (
        <View className="mt-3 rounded-[14px] bg-[#fff5f5] px-4 py-4">
          <Text className="text-[14px] text-[#991b1b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
            Unable to load status summary.
          </Text>
          <Text className="mt-1 text-[12px] text-[#7f1d1d]" style={{ fontFamily: "Montserrat" }}>
            {summaryError}
          </Text>
        </View>
      ) : statusSubaybayanRows.length ? (
        <View className="mt-3">
          {statusSubaybayanRows.map((row) => (
            <StatusSubaybayanRow
              key={row.status}
              status={row.status}
              count={row.count}
              total={statusSubaybayanTotal}
              maxCount={statusSubaybayanMax}
              screenWidth={screenWidth}
            />
          ))}
        </View>
      ) : (
        <View className="mt-3 rounded-[14px] bg-[#f8fafc] px-4 py-4">
          <Text className="text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
            No SubayBAYAN status records available yet.
          </Text>
        </View>
      )}
    </View>
  );
}