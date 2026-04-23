import { useMemo } from "react";
import { ActivityIndicator, ScrollView, Text, View } from "react-native";
import Svg, { Circle } from "react-native-svg";

import { APP_COLORS } from "../../../../constants/theme";
import { formatCount, formatPercentage, PROJECT_RISK_STYLES } from "../../../../constants/homeDashboardConfig";

const PROJECT_AGING_ORDER = ["High Risk", "Low Risk", "No Risk"];

function AgingLegendItem({ label, count, total, compact }) {
  const styleMeta = PROJECT_RISK_STYLES[label] || { bg: "#6b7280", text: "#f8fafc" };
  const percentage = total > 0 ? (count / total) * 100 : 0;
  const labelFontSize = compact ? 12 : 13;

  return (
    <View className="mb-2 mr-2 flex-col items-center justify-between rounded-[10px] px-3 py-1" style={{ backgroundColor: styleMeta.bg }}>
      <Text className="text-white" style={{ fontFamily: "Montserrat-SemiBold", color: styleMeta.text }}>
        {formatCount(count)} | {formatPercentage(percentage)}
      </Text>

      <Text
        className="pr-2"
        style={{ fontFamily: "Montserrat", color: styleMeta.text, flex: 1, fontSize: labelFontSize, lineHeight: labelFontSize + 4 }}
        numberOfLines={2}
      >
        {label}
      </Text>
    </View>
  );
}

export default function ProjectAgingSection({
  isLoadingSummary,
  summaryError,
  projectAtRiskAgingRows,
  projectAtRiskAgingTotal,
  donutSize,
  riskLegendWidth,
  isNarrowRiskLayout,
}) {
  const agingDonutSegments = useMemo(() => {
    if (!projectAtRiskAgingTotal) {
      return [];
    }

    const rowsByLabel = new Map(projectAtRiskAgingRows.map((row) => [row.label, row]));
    const baseSegments = PROJECT_AGING_ORDER.map((label) => ({
      label,
      count: rowsByLabel.get(label)?.count || 0,
    })).filter((segment) => segment.count > 0);

    const segmentCount = baseSegments.length;
    const gapPercent = segmentCount > 1 ? 0.8 : 0;
    const availablePercent = Math.max(0, 100 - segmentCount * gapPercent);
    let runningPercent = 0;

    return baseSegments.map((segment) => {
      const rawPercent = (segment.count / projectAtRiskAgingTotal) * 100;
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
  }, [projectAtRiskAgingRows, projectAtRiskAgingTotal]);

  return (
    <View className="mt-6 rounded-[18px] border border-[#dfe3ea] bg-white px-3 py-3">
      <Text className="text-[18px] uppercase tracking-[0.7px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        Aging of the Projects with Slippage
      </Text>
      <Text className="mt-1 text-[13px] text-[#6b7280]" style={{ fontFamily: "Montserrat" }}>
        Project aging grouped by risk thresholds from latest extracted records.
      </Text>

      {isLoadingSummary ? (
        <View className="items-center justify-center px-4 py-8">
          <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
          <Text className="mt-3 text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
            Loading project aging summary...
          </Text>
        </View>
      ) : summaryError ? (
        <View className="mt-3 rounded-[14px] border border-[#f4c7c7] bg-[#fff5f5] px-4 py-4">
          <Text className="text-[14px] text-[#991b1b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
            Unable to load project aging summary.
          </Text>
          <Text className="mt-1 text-[12px] text-[#7f1d1d]" style={{ fontFamily: "Montserrat" }}>
            {summaryError}
          </Text>
        </View>
      ) : projectAtRiskAgingTotal > 0 ? (
        <View className="mt-3 w-full items-center">
          <View className="items-center justify-center rounded-[12px] border border-[#e5e7eb] bg-[#f9fafb]">
            <Svg width={donutSize} height={donutSize} viewBox={`0 0 ${donutSize} ${donutSize}`}>
              {(() => {
                const strokeWidth = Math.max(14, Math.round(donutSize * 0.14));
                const radius = (donutSize - strokeWidth) / 2;
                const center = donutSize / 2;
                const circumference = 2 * Math.PI * radius;

                return (
                  <>
                    <Circle cx={center} cy={center} r={radius} stroke="#e5e7eb" strokeWidth={strokeWidth} fill="none" />

                    {agingDonutSegments.map((segment) => {
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

          <View className="mt-3" style={{ width: "100%", minWidth: riskLegendWidth }}>
            <ScrollView
              horizontal
              nestedScrollEnabled
              showsHorizontalScrollIndicator
              style={{ width: "100%" }}
              contentContainerStyle={{
                flexDirection: "row",
                paddingRight: 8,
              }}
            >
              {projectAtRiskAgingRows.map((row) => (
                <AgingLegendItem
                  key={row.label}
                  label={row.label}
                  count={row.count}
                  total={projectAtRiskAgingTotal}
                  compact={isNarrowRiskLayout}
                />
              ))}
            </ScrollView>
          </View>
        </View>
      ) : (
        <View className="mt-3 rounded-[14px] border border-[#e2e8f0] bg-[#f8fafc] px-4 py-4">
          <Text className="text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
            No project aging records available yet.
          </Text>
        </View>
      )}
    </View>
  );
}
