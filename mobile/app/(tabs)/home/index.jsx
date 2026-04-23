import { Feather } from "@expo/vector-icons";
import { LinearGradient } from 'expo-linear-gradient';
import { useMemo } from "react";
import { ActivityIndicator, ScrollView, Text, useWindowDimensions, View } from "react-native";
import Animated, {
  Extrapolation,
  interpolate,
  useAnimatedScrollHandler,
  useAnimatedStyle,
  useSharedValue,
} from "react-native-reanimated";
import Svg, { Circle } from "react-native-svg";
import { SafeAreaView } from "react-native-safe-area-context";

import { useFetchLoggedUser } from "../../../hooks/useFetchLoggedUser";
import { useDashboardSummary } from "../../../hooks/useDashboardSummary";
import { APP_COLORS } from "../../../constants/theme";

const STATUS_META = {
  Completed: { icon: "check", iconBackground: "#2f8f4b", color: "#2f8f4b" },
  "On-going": { icon: "loader", iconBackground: "#08348a", color: "#08348a" },
  "Not Yet Started": { icon: "clock", iconBackground: "#444548", color: "#444548" },
  "DED Preparation": { icon: "edit-3", iconBackground: "#6a30c5", color: "#6a30c5" },
  "Bid Evaluation/Opening": { icon: "briefcase", iconBackground: "#b35708", color: "#b35708" },
  "NOA Issuance": { icon: "file-text", iconBackground: "#1f8cc0", color: "#1f8cc0" },
  Cancelled: { icon: "x", iconBackground: "#c81448", color: "#c81448" },
  "ITB/AD Posted": { icon: "radio", iconBackground: "#bf470f", color: "#bf470f" },
  Terminated: { icon: "slash", iconBackground: "#b03030", color: "#b03030" },
};

const FUND_SOURCE_META = {
  SBDP: {
    icon: "shield",
    label: "SBDP",
    backgroundColor: "#c8d7f5",
    borderColor: "#7e9fd8",
    accentColor: "#173e8c",
  },
  FALGU: {
    icon: "shield",
    label: "FALGU",
    backgroundColor: "#cadbf2",
    borderColor: "#7f9dcf",
    accentColor: "#173e8c",
  },
  CMGP: {
    icon: "shield",
    label: "CMGP",
    backgroundColor: "#cbd7f2",
    borderColor: "#7d99d1",
    accentColor: "#173e8c",
  },
  GEF: {
    icon: "shield",
    label: "GEF",
    backgroundColor: "#c8d7f5",
    borderColor: "#7e9fd8",
    accentColor: "#173e8c",
  },
  SAFPB: {
    icon: "shield",
    label: "SAFPB",
    backgroundColor: "#c8d7f5",
    borderColor: "#7e9fd8",
    accentColor: "#173e8c",
  },
};

const FINANCIAL_METRIC_CARDS = {
  allocation: {
    label: "LGSF Allocation",
    icon: "dollar-sign",
    backgroundColor: "#ffefe6",
    borderColor: "#f97316",
    accentColor: "#ea580c",
    valueColor: "#ea580c",
  },
  percentage: {
    label: "Percentage",
    icon: "pie-chart",
    backgroundColor: "#dce8ff",
    borderColor: "#1d4ed8",
    accentColor: "#143f93",
    valueColor: "#143f93",
  },
  obligation: {
    label: "Obligation",
    icon: "archive",
    backgroundColor: "#f3f7cf",
    borderColor: "#a3a300",
    accentColor: "#8d8d00",
    valueColor: "#8d8d00",
  },
  disbursement: {
    label: "Disbursement",
    icon: "credit-card",
    backgroundColor: "#c9f0d3",
    borderColor: "#15803d",
    accentColor: "#0f6b31",
    valueColor: "#0f6b31",
  },
  balance: {
    label: "Balance",
    icon: "box",
    backgroundColor: "#efdcf0",
    borderColor: "#86198f",
    accentColor: "#70127a",
    valueColor: "#70127a",
  },
};

const PROJECT_RISK_DONUT_ORDER = ["Ahead", "No Risk", "On Schedule", "High Risk", "Moderate Risk", "Low Risk"];
const PROJECT_RISK_STYLES = {
  "On Schedule": { bg: "#a3a3a3", text: "#f8fafc" },
  Ahead: { bg: "#3f9142", text: "#f8fafc" },
  "No Risk": { bg: "#2f84cf", text: "#f8fafc" },
  "Low Risk": { bg: "#f6c000", text: "#f8fafc" },
  "Moderate Risk": { bg: "#fb6f41", text: "#f8fafc" },
  "High Risk": { bg: "#c81d1d", text: "#f8fafc" },
};

function formatCount(value) {
  return new Intl.NumberFormat("en-US").format(Number(value || 0));
}

function formatPercentage(value) {
  return `${Number(value || 0).toFixed(2)}%`;
}


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

function FundSourceTile({ label, count, icon, backgroundColor, borderColor, accentColor, tileWidth }) {
  return (
    <View
      className="mb-3 rounded-[14px] border px-2.5 py-2.5"
      style={{ backgroundColor, borderColor, width: tileWidth }}
    >
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
            {formatCount(count)}
          </Text>
        </View>
      </View>
    </View>
  );
}

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
      <View
        className="items-center justify-center rounded-full"
        style={{ backgroundColor: meta.iconBackground, height: iconCircleSize, width: iconCircleSize }}
      >
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

function FinancialMetricTile({ label, value, icon, backgroundColor, borderColor, accentColor, valueColor, width }) {
  const isLongLabel = String(label).length > 11;

  return (
    <View
      className="mr-3 overflow-hidden rounded-[26px] border px-4 py-4"
      style={{ backgroundColor, borderColor, width }}
    >
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

function RiskLegendItem({ label, count, total, compact }) {
  const styleMeta = PROJECT_RISK_STYLES[label] || { bg: "#6b7280", text: "#f8fafc" };
  const percentage = total > 0 ? (count / total) * 100 : 0;
  const labelFontSize = compact ? 12 : 13;
  const valueFontSize = compact ? 20 : 22;
  const percentageFontSize = compact ? 12 : 13;

  return (
    <View className="mb-2 flex-row items-center justify-between rounded-[10px] px-2.5 py-2" style={{ backgroundColor: styleMeta.bg }}>
      <Text
        className="pr-2"
        style={{ fontFamily: "Montserrat", color: styleMeta.text, flex: 1, fontSize: labelFontSize, lineHeight: labelFontSize + 4 }}
        numberOfLines={2}
      >
        {label}
      </Text>

      <View style={{ borderLeftWidth: 2, borderLeftColor: "rgba(248,250,252,0.55)", paddingLeft: 8, alignItems: "flex-end", minWidth: compact ? 72 : 86 }}>
        <Text className="text-right" style={{ fontFamily: "Montserrat-SemiBold", color: styleMeta.text, fontSize: valueFontSize, lineHeight: valueFontSize + 2 }}>
          {formatCount(count)}
        </Text>
        <Text className="text-right" style={{ fontFamily: "Montserrat", color: styleMeta.text, fontSize: percentageFontSize, lineHeight: percentageFontSize + 2 }}>
          {formatPercentage(percentage)}
        </Text>
      </View>
    </View>
  );
}

export default function HomeScreen() {
  const { firstName, greeting } = useFetchLoggedUser();
  const { width: screenWidth } = useWindowDimensions();
  const {
    isLoadingSummary,
    summaryError,
    summaryLabel,
    totalProjects,
    fundSourceCounts,
    statusSubaybayanRows,
    statusSubaybayanTotal,
    statusSubaybayanMax,
    financialMetrics,
    projectAtRiskSlippageRows,
    projectAtRiskSlippageTotal,
  } = useDashboardSummary();
  const scrollY = useSharedValue(0);

  const fundSourceCards = useMemo(() => {
    return fundSourceCounts.map(({ fundSource, count }) => {
      const meta = FUND_SOURCE_META[fundSource] || {
        icon: "box",
        label: fundSource,
        backgroundColor: "#d9e4f7",
        borderColor: "#8ea8d8",
        accentColor: "#173e8c",
      };

      return {
        ...meta,
        count,
      };
    });
  }, [fundSourceCounts]);
  const isCompactScreen = screenWidth < 390;
  const isNarrowRiskLayout = screenWidth < 430;
  const financialTileWidth = Math.max(screenWidth * 0.84, 290);
  const riskPanelHeight = Math.max(250, Math.min(340, screenWidth * 0.76));
  const riskChartWidth = isNarrowRiskLayout
    ? Math.max(118, Math.min(148, screenWidth * 0.34))
    : Math.max(148, Math.min(220, screenWidth * 0.42));
  const riskLegendWidth = Math.max(195, screenWidth - riskChartWidth - 40);
  const donutSize = isNarrowRiskLayout
    ? Math.max(110, Math.min(148, riskPanelHeight - 84))
    : Math.max(132, Math.min(188, riskPanelHeight - 30));
  const fundSourceColumns = screenWidth >= 390 ? 3 : 2;
  const fundSourceGap = 8;
  const horizontalPadding = 16;
  const usableWidth = Math.max(screenWidth - horizontalPadding * 2, 240);
  const tileWidth = (usableWidth - fundSourceGap * (fundSourceColumns - 1)) / fundSourceColumns;

  const handleScroll = useAnimatedScrollHandler({
    onScroll: (event) => {
      scrollY.value = event.contentOffset.y;
    },
  });

  const heroParallaxInputRange = [0, 120];
  const heroParallaxOutputRange = [0, -82];
  const contentParallaxInputRange = [0, 180];
  const contentParallaxOutputRange = [0, -18];

  const heroParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(scrollY.value, heroParallaxInputRange, heroParallaxOutputRange, Extrapolation.CLAMP),
      },
    ],
  }));

  const contentParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(scrollY.value, contentParallaxInputRange, contentParallaxOutputRange, Extrapolation.CLAMP),
      },
    ],
  }));

  const slippageDonutSegments = useMemo(() => {
    if (!projectAtRiskSlippageTotal) {
      return [];
    }

    const rowsByLabel = new Map(projectAtRiskSlippageRows.map((row) => [row.label, row]));
    const baseSegments = PROJECT_RISK_DONUT_ORDER.map((label) => ({
      label,
      count: rowsByLabel.get(label)?.count || 0,
    })).filter((segment) => segment.count > 0);

    const segmentCount = baseSegments.length;
    const gapPercent = segmentCount > 1 ? 0.8 : 0;
    const availablePercent = Math.max(0, 100 - segmentCount * gapPercent);
    let runningPercent = 0;

    return baseSegments.map((segment) => {
      const rawPercent = (segment.count / projectAtRiskSlippageTotal) * 100;
      const lengthPercent = (rawPercent / 100) * availablePercent;
      const normalizedSegment = {
        ...segment,
        percentage: rawPercent,
        startPercent: runningPercent,
        lengthPercent,
      };

      runningPercent += lengthPercent + gapPercent;
      return normalizedSegment;
    });
  }, [projectAtRiskSlippageRows, projectAtRiskSlippageTotal]);

  return (
    <SafeAreaView className="flex-1 font-sans" style={{ backgroundColor: APP_COLORS.primaryBlue }} edges={[]}>
      <Animated.ScrollView
        contentContainerStyle={{ flexGrow: 1 }}
        showsVerticalScrollIndicator={false}
        onScroll={handleScroll}
        scrollEventThrottle={16}
      >
        <Animated.View style={heroParallaxStyle}>

          <LinearGradient
            colors={['#4069ad', APP_COLORS.primaryBlue]}
            locations={[0.2, 1]}
            start={{ x: 1, y: 0 }}   // upper right
            end={{ x: 0, y: 1 }}     // lower left
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
                  {isLoadingSummary ? "…" : formatCount(totalProjects)}
                </Text>
                <Text className="mt-1 text-[14px] uppercase tracking-[1.5px] text-white/85" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  Total Number of Projects
                </Text>
              </View>
            </View>
          </LinearGradient>

        </Animated.View>

        <Animated.View className="-mt-6 flex-1 rounded-t-[28px] bg-white px-4 pt-4" style={contentParallaxStyle}>
          <View className="flex-row flex-wrap justify-between gap-y-3">
            <StatPill icon="filter" label="Filter" />
            <StatPill icon="monitor" label="Monitoring" />
            <StatPill icon="award" label="Expected Completion" />
          </View>

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
              <View className="flex-row flex-wrap" style={{ columnGap: fundSourceGap }}>
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
                  const styleMeta = FINANCIAL_METRIC_CARDS[metric.key];
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

          <View className="mt-6 rounded-[18px] border border-[#dfe3ea] bg-white px-3 py-3">
            <Text className="text-[18px] uppercase tracking-[0.7px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
              Project At Risk as to Slippage
            </Text>
            <Text className="mt-1 text-[13px] text-[#6b7280]" style={{ fontFamily: "Montserrat" }}>
              Projects with slippages extracted in the SubayBAYAN Portal.
            </Text>

            {isLoadingSummary ? (
              <View className="items-center justify-center px-4 py-8">
                <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
                <Text className="mt-3 text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
                  Loading slippage risk summary...
                </Text>
              </View>
            ) : summaryError ? (
              <View className="mt-3 rounded-[14px] border border-[#f4c7c7] bg-[#fff5f5] px-4 py-4">
                <Text className="text-[14px] text-[#991b1b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  Unable to load slippage risk summary.
                </Text>
                <Text className="mt-1 text-[12px] text-[#7f1d1d]" style={{ fontFamily: "Montserrat" }}>
                  {summaryError}
                </Text>
              </View>
            ) : projectAtRiskSlippageTotal > 0 ? (
              <View className="mt-3 flex-col bg-red-400 items-center">
                {/* pie chart */}
                <View
                  className="items-center justify-center rounded-[12px] border border-[#e5e7eb] bg-[#f9fafb]"
                >
                  <Svg width={donutSize} height={donutSize} viewBox={`0 0 ${donutSize} ${donutSize}`}>
                    {(() => {
                      const strokeWidth = Math.max(14, Math.round(donutSize * 0.14));
                      const radius = (donutSize - strokeWidth) / 2;
                      const center = donutSize / 2;
                      const circumference = 2 * Math.PI * radius;

                      return (
                        <>
                          <Circle
                            cx={center}
                            cy={center}
                            r={radius}
                            stroke="#e5e7eb"
                            strokeWidth={strokeWidth}
                            fill="none"
                          />

                          {slippageDonutSegments.map((segment) => {
                            const strokeLength = (segment.lengthPercent / 100) * circumference;
                            const strokeGap = Math.max(circumference - strokeLength, 0);
                            const strokeOffset = -((segment.startPercent / 100) * circumference);
                            const segmentColor = PROJECT_RISK_STYLES[segment.label]?.bg || "#6b7280";

                            return (
                              <Circle
                                key={segment.label}
                                cx={center}
                                cy={center}
                                r={radius}
                                stroke={segmentColor}
                                strokeWidth={strokeWidth}
                                fill="none"
                                strokeDasharray={`${strokeLength} ${strokeGap}`}
                                strokeDashoffset={strokeOffset}
                                rotation="-90"
                                origin={`${center}, ${center}`}
                                strokeLinecap="butt"
                              />
                            );
                          })}
                        </>
                      );
                    })()}
                  </Svg>
                </View>

                {/* legend (parang ako lang -carl) */}
                <View style={{ width: riskLegendWidth, height: riskPanelHeight }}>
                  <ScrollView
                    horizontal
                    nestedScrollEnabled
                    showsHorizontalScrollIndicator
                    style={{ height: riskPanelHeight }}
                    contentContainerStyle={{
                      flexDirection: 'row',
                      paddingRight: 2,
                    }}
                  >
                    {projectAtRiskSlippageRows.map((row) => (
                      <RiskLegendItem
                        key={row.label}
                        label={row.label}
                        count={row.count}
                        total={projectAtRiskSlippageTotal}
                        compact={isNarrowRiskLayout}
                      />
                    ))}
                  </ScrollView>
                </View>
              </View>
            ) : (
              <View className="mt-3 rounded-[14px] border border-[#e2e8f0] bg-[#f8fafc] px-4 py-4">
                <Text className="text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
                  No slippage risk records available yet.
                </Text>
              </View>
            )}
          </View>

          <View
            className="mb-10 mt-6 rounded-[20px] border border-[#e2e8f0] bg-white py-4"
            style={{ paddingHorizontal: isCompactScreen ? 12 : 14 }}
          >
            <Text
              className="uppercase tracking-[0.8px] text-[#173e8c]"
              style={{ fontFamily: "Montserrat-SemiBold", fontSize: isCompactScreen ? 16 : 18, lineHeight: isCompactScreen ? 22 : 24 }}
            >
              Status of Project (Subaybayan Status)
            </Text>

            {isLoadingSummary ? (
              <View className="items-center justify-center px-4 py-8">
                <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
                <Text className="mt-3 text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
                  Loading status summary...
                </Text>
              </View>
            ) : summaryError ? (
              <View className="mt-3 rounded-[14px] border border-[#f4c7c7] bg-[#fff5f5] px-4 py-4">
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
              <View className="mt-3 rounded-[14px] border border-[#e2e8f0] bg-[#f8fafc] px-4 py-4">
                <Text className="text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
                  No SubayBAYAN status records available yet.
                </Text>
              </View>
            )}
          </View>
        </Animated.View>
      </Animated.ScrollView>
    </SafeAreaView>
  );
}

export const meta = {
  title: "Home",
};
