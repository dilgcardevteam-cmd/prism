import { useMemo, useState } from "react";
import { ActivityIndicator, Modal, Pressable, ScrollView, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import Svg, { Circle } from "react-native-svg";
import Animated, { Easing, FadeIn, FadeOut, SlideInDown, SlideOutDown } from "react-native-reanimated";
import { Feather } from "@expo/vector-icons";

import { APP_COLORS } from "../../../../constants/theme";
import { formatCount, formatPercentage, PROJECT_RISK_STYLES } from "../../../../constants/homeDashboardConfig";

function CompactLegendItem({ label, compact }) {
  const styleMeta = PROJECT_RISK_STYLES[label] || { bg: "#6b7280" };
  const fontSize = compact ? 11 : 12;

  return (
    <View className="flex-row items-center rounded-full border border-[#d8deea] bg-white px-3 py-1.5">
      <View className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: styleMeta.bg }} />
      <Text className="ml-2 text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold", fontSize }} numberOfLines={1}>
        {label}
      </Text>
    </View>
  );
}

function DetailedLegendItem({ label, count, total }) {
  const styleMeta = PROJECT_RISK_STYLES[label] || { bg: "#6b7280", text: "#f8fafc" };
  const percentage = total > 0 ? (count / total) * 100 : 0;

  return (
    <View className="mb-2 rounded-[12px] border border-[#e2e8f0] bg-[#f8fbff] px-3 py-2">
      <View className="flex-row items-center justify-between gap-3">
        <View className="flex-row items-center flex-1">
          <View className="h-3 w-3 rounded-full" style={{ backgroundColor: styleMeta.bg }} />
          <Text className="ml-2 text-[13px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }} numberOfLines={1}>
            {label}
          </Text>
        </View>

        <Text className="text-[13px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }} numberOfLines={1}>
          {formatCount(count)} | {formatPercentage(percentage)}
        </Text>
      </View>
    </View>
  );
}

function DetailSheet({ visible, onClose, title, rows, total, donutSize, segments }) {
  const insets = useSafeAreaInsets();

  if (!visible) {
    return null;
  }

  const largeDonutSize = Math.max(190, donutSize + 44);

  return (
    <Modal transparent visible={visible} animationType="none" onRequestClose={onClose} statusBarTranslucent>
      <Animated.View entering={FadeIn.duration(160)} exiting={FadeOut.duration(140)} className="flex-1 justify-end bg-black/35">
        <Pressable className="flex-1" onPress={onClose} />

        <Animated.View
          entering={SlideInDown.duration(220).easing(Easing.out(Easing.cubic))}
          exiting={SlideOutDown.duration(180).easing(Easing.in(Easing.cubic))}
          className="overflow-hidden rounded-t-[28px] bg-white"
          style={{ maxHeight: "86%", paddingBottom: Math.max(insets.bottom, 12) }}
        >
          <View className="items-center pt-3">
            <View className="h-1.5 w-12 rounded-full bg-[#d7dfea]" />
          </View>

          <View className="flex-row items-start justify-between px-4 pt-4">
            <Text className="flex-1 pr-3 uppercase tracking-[0.8px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold", fontSize: 16, lineHeight: 20 }}>
              {title}
            </Text>

            <Pressable onPress={onClose} hitSlop={10} className="pt-0.5">
              <Feather name="x" size={22} color="#173e8c" />
            </Pressable>
          </View>

          <View className="mt-3 items-center px-4">
            <View className="items-center justify-center rounded-[14px] border border-[#e5e7eb] bg-[#f9fafb] px-3 py-3">
              <Svg width={largeDonutSize} height={largeDonutSize} viewBox={`0 0 ${largeDonutSize} ${largeDonutSize}`}>
                {(() => {
                  const strokeWidth = Math.max(16, Math.round(largeDonutSize * 0.14));
                  const radius = (largeDonutSize - strokeWidth) / 2;
                  const center = largeDonutSize / 2;
                  const circumference = 2 * Math.PI * radius;

                  return (
                    <>
                      <Circle cx={center} cy={center} r={radius} stroke="#e5e7eb" strokeWidth={strokeWidth} fill="none" />

                      {segments.map((segment) => {
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
          </View>

          <ScrollView className="mt-4 px-4" showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 18 }}>
            {rows.map((row, index) => (
              <DetailedLegendItem
                key={`${row.label}-${index}`}
                label={row.label}
                count={row.count}
                total={total}
              />
            ))}
          </ScrollView>
        </Animated.View>
      </Animated.View>
    </Modal>
  );
}

export default function RiskDonutSectionWithSheet({
  isLoadingSummary,
  summaryError,
  title,
  subtitle,
  loadingText,
  errorTitle,
  emptyText,
  rows,
  total,
  order,
  donutSize,
  isNarrowRiskLayout,
}) {
  const [isSheetVisible, setIsSheetVisible] = useState(false);

  const orderedRows = useMemo(() => {
    const rowsByLabel = new Map(rows.map((row) => [row.label, row]));
    return order
      .map((label) => ({ label, count: rowsByLabel.get(label)?.count || 0 }))
      .filter((row) => row.count > 0);
  }, [order, rows]);

  const donutSegments = useMemo(() => {
    if (!total) {
      return [];
    }

    const segmentCount = orderedRows.length;
    const gapPercent = segmentCount > 1 ? 0.8 : 0;
    const availablePercent = Math.max(0, 100 - segmentCount * gapPercent);
    let runningPercent = 0;

    return orderedRows.map((segment) => {
      const rawPercent = (segment.count / total) * 100;
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
  }, [orderedRows, total]);

  const compactRows = orderedRows;

  return (
    <View className="mt-6">
      <Text className="mb-2 text-[18px] uppercase tracking-[0.8px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        {title}
      </Text>
      <Text className="mt-1 text-[13px] text-[#6b7280]" style={{ fontFamily: "Montserrat" }}>
        {subtitle}
      </Text>

      {isLoadingSummary ? (
        <View className="items-center justify-center px-4 py-8">
          <ActivityIndicator size="large" color={APP_COLORS.primaryBlue} />
          <Text className="mt-3 text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
            {loadingText}
          </Text>
        </View>
      ) : summaryError ? (
        <View className="mt-3 rounded-[14px] bg-[#fff5f5] px-4 py-4">
          <Text className="text-[14px] text-[#991b1b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
            {errorTitle}
          </Text>
          <Text className="mt-1 text-[12px] text-[#7f1d1d]" style={{ fontFamily: "Montserrat" }}>
            {summaryError}
          </Text>
        </View>
      ) : total > 0 ? (
        <View className="mt-3 w-full items-center">
          <Pressable className="w-full items-center" onPress={() => setIsSheetVisible(true)}>
            <View className="items-center justify-center rounded-[12px] bg-[#f9fafb]">
              <Svg width={donutSize} height={donutSize} viewBox={`0 0 ${donutSize} ${donutSize}`}>
                {(() => {
                  const strokeWidth = Math.max(14, Math.round(donutSize * 0.14));
                  const radius = (donutSize - strokeWidth) / 2;
                  const center = donutSize / 2;
                  const circumference = 2 * Math.PI * radius;

                  return (
                    <>
                      <Circle cx={center} cy={center} r={radius} stroke="#e5e7eb" strokeWidth={strokeWidth} fill="none" />

                      {donutSegments.map((segment) => {
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

            <View className="mt-3 w-full flex-row flex-wrap items-center justify-center gap-x-2 gap-y-2 px-2">
              {compactRows.map((row) => (
                <CompactLegendItem key={row.label} label={row.label} compact={isNarrowRiskLayout} />
              ))}
            </View>

            <Text className="mt-2 text-[11px] text-[#64748b]" style={{ fontFamily: "Montserrat" }}>
              Tap chart to view details
            </Text>
          </Pressable>

          <DetailSheet
            visible={isSheetVisible}
            onClose={() => setIsSheetVisible(false)}
            title={title}
            rows={orderedRows}
            total={total}
            donutSize={donutSize}
            segments={donutSegments}
          />
        </View>
      ) : (
        <View className="mt-3 rounded-[14px] bg-[#f8fafc] px-4 py-4">
          <Text className="text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
            {emptyText}
          </Text>
        </View>
      )}
    </View>
  );
}
