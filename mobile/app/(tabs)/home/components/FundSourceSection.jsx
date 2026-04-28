import { Feather } from "@expo/vector-icons";
import { useMemo, useState } from "react";
import { FlatList, Modal, Pressable, ScrollView, Text, View } from "react-native";
import Animated, { Easing, FadeIn, FadeOut, SlideInDown, SlideOutDown } from "react-native-reanimated";

import { APP_COLORS } from "../../../../constants/theme";
import SkeletonLoader from "../../../../components/common/SkeletonLoader";

const FUND_SOURCE_ICON_BY_LABEL = {
  SBDP: "shield",
  FALGU: "shield",
  CMGP: "shield",
  GEF: "shield",
  SAFPB: "shield",
  "AM-LA": "package",
  "ADM-LA": "briefcase",
  "AM-DRR": "alert-triangle",
  "ADM-OT": "folder",
  "AM-PW": "tool",
  "DILG-BLDGS": "home",
  DRRAP: "activity",
  DTEAP: "zap",
  LGSF: "dollar-sign",
  LA: "map-pin",
  LO: "compass",
  LR: "map",
  OT: "help-circle",
  PA: "archive",
  PM: "package",
  PW: "box",
  SA: "sun",
  SF: "star",
  "FINANCIAL ASSISTANCE TO LOCAL GOVERNMENT UNIT PROGRAM": "credit-card",
  "GROWTH EQUITY FUND": "trending-up",
  "SUPPORT TO THE BARANGAY DEVELOPMENT PROGRAM": "users",
};

const FUND_SOURCE_LABEL_ABBREVIATIONS = {
  "GROWTH EQUITY FUND": "GEF",
  "FINANCIAL ASSISTANCE TO LOCAL GOVERNMENT UNIT": "FALGU",
  "FINANCIAL ASSISTANCE TO LOCAL GOVERNMENT UNIT PROGRAM": "FALGU",
  "SUPPORT TO THE BARANGAY DEVELOPMENT PROGRAM": "SBDP",
};

function abbreviateFundSourceLabel(label) {
  const normalizedLabel = String(label || "").trim().toUpperCase();
  return FUND_SOURCE_LABEL_ABBREVIATIONS[normalizedLabel] || label;
}

function getFundSourceIcon(label) {
  const abbreviatedLabel = abbreviateFundSourceLabel(label);
  return FUND_SOURCE_ICON_BY_LABEL[abbreviatedLabel] || FUND_SOURCE_ICON_BY_LABEL[label] || "shield";
}

function FundSourceTile({ label, count, icon, backgroundColor, borderColor, accentColor, tileWidth }) {
  return (
    <View
      className="overflow-hidden rounded-[20px] border px-3 py-3"
      style={{ backgroundColor, borderColor, width: tileWidth, minHeight: 86 }}
    >
      <View className="absolute inset-0 items-center justify-center pointer-events-none">
        <Feather name={icon} size={42} color={accentColor} style={{ opacity: 0.12 }} />
      </View>

      <View className="flex-1 justify-between">
        <View className="items-start">
          <Text
            className="uppercase text-[#173e8c]"
            style={{
              fontFamily: "Montserrat-SemiBold",
              fontSize: String(label || "").length > 12 ? 10 : 11,
              lineHeight: 12,
              letterSpacing: 0.4,
            }}
            numberOfLines={1}
            adjustsFontSizeToFit
            minimumFontScale={0.7}
          >
            {label}
          </Text>
        </View>

        <Text
          className="text-[#173e8c]"
          style={{
            fontFamily: "Montserrat-SemiBold",
            fontSize: 32,
            lineHeight: 34,
            letterSpacing: -0.5,
          }}
          numberOfLines={1}
          adjustsFontSizeToFit
          minimumFontScale={0.6}
        >
          {count}
        </Text>
      </View>
    </View>
  );
}

function FundSourceSheet({ visible, onClose, fundSourceCards, screenWidth }) {
  if (!visible) {
    return null;
  }

  const sheetPadding = 16;
  const columnGap = 12;
  const sheetCardWidth = Math.max((screenWidth - sheetPadding * 2 - columnGap) / 2, 140);

  return (
    <Modal transparent visible={visible} animationType="none" onRequestClose={onClose} statusBarTranslucent>
      <Animated.View entering={FadeIn.duration(160)} exiting={FadeOut.duration(140)} className="flex-1 justify-end bg-black/35">
        <Pressable className="flex-1" onPress={onClose} />

        <Animated.View
          entering={SlideInDown.duration(220).easing(Easing.out(Easing.cubic))}
          exiting={SlideOutDown.duration(180).easing(Easing.in(Easing.cubic))}
          className="overflow-hidden rounded-t-[28px] bg-white"
          style={{ maxHeight: "78%" }}
        >
          <View className="items-center pt-3">
            <View className="h-1.5 w-12 rounded-full bg-[#d7dfea]" />
          </View>

          <View className="flex-row items-start justify-between px-4 pt-4">
            <Text
              className="flex-1 pr-3 uppercase tracking-[0.8px] text-[#173e8c]"
              style={{ fontFamily: "Montserrat-SemiBold", fontSize: 17, lineHeight: 21 }}
            >
              Fund Source Breakdown
            </Text>

            <Pressable onPress={onClose} hitSlop={10} className="pt-0.5">
              <Feather name="x" size={22} color="#173e8c" />
            </Pressable>
          </View>

          <Text className="px-4 pt-1 text-[12px] text-[#64748b]" style={{ fontFamily: "Montserrat" }}>
            Sorted from highest to lowest project count.
          </Text>

          <FlatList
            className="px-4 pt-4"
            data={fundSourceCards}
            keyExtractor={(item) => item.label}
            numColumns={2}
            showsVerticalScrollIndicator={false}
            columnWrapperStyle={{ justifyContent: "space-between", marginBottom: 12 }}
            contentContainerStyle={{ paddingBottom: 24 }}
            renderItem={({ item }) => (
              <FundSourceTile
                label={item.label}
                count={item.count}
                icon={item.icon}
                backgroundColor={item.backgroundColor}
                borderColor={item.borderColor}
                accentColor={item.accentColor}
                tileWidth={sheetCardWidth}
              />
            )}
          />
        </Animated.View>
      </Animated.View>
    </Modal>
  );
}

export default function FundSourceSection({ isLoadingSummary, summaryError, fundSourceCards, tileWidth, screenWidth }) {
  const [isSheetVisible, setIsSheetVisible] = useState(false);

  const sortedFundSourceCards = useMemo(() => {
    return [...fundSourceCards].sort((left, right) => {
      if (right.count !== left.count) {
        return right.count - left.count;
      }

      return String(left.label).localeCompare(String(right.label));
    });
  }, [fundSourceCards]);

  const topThreeCards = useMemo(() => sortedFundSourceCards.slice(0, 3), [sortedFundSourceCards]);

  const displayCards = useMemo(() => {
    return topThreeCards.map((card) => ({
      ...card,
      label: abbreviateFundSourceLabel(card.label),
      icon: getFundSourceIcon(card.label),
    }));
  }, [topThreeCards]);

  const sheetCards = useMemo(() => {
    return sortedFundSourceCards.map((card) => ({
      ...card,
      label: abbreviateFundSourceLabel(card.label),
      icon: getFundSourceIcon(card.label),
    }));
  }, [sortedFundSourceCards]);

  const isCompactHeader = screenWidth < 430;

  return (
    <View className="mt-6">
      <View className={isCompactHeader ? "mb-3 gap-2" : "mb-3 flex-row items-center justify-between gap-3"}>
        <Text
          className="uppercase tracking-[0.8px] text-[#173e8c]"
          style={{
            fontFamily: "Montserrat-SemiBold",
            fontSize: screenWidth < 390 ? 15 : 18,
            lineHeight: screenWidth < 390 ? 18 : 22,
            maxWidth: isCompactHeader ? "100%" : "78%",
          }}
        >
          Projects by Fund Source (Top 3)
        </Text>

        <Pressable onPress={() => setIsSheetVisible(true)} hitSlop={10} style={isCompactHeader ? { alignSelf: "flex-end" } : undefined}>
          <View className="flex-row items-center">
            <Text className="mr-1 text-[12px] uppercase tracking-[0.6px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold" }}>
              See More
            </Text>
            <Feather name="chevron-right" size={16} color="#173e8c" />
          </View>
        </Pressable>
      </View>

      {isLoadingSummary ? (
        <View className="overflow-hidden rounded-[20px] border border-[#dbe4f4] bg-[#f8fbff] px-4 py-8">
          <SkeletonLoader width="100%" height={24} borderRadius={12} count={3} gap={12} />
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
        <View className="flex-row flex-wrap justify-between gap-y-3">
          {displayCards.map((card) => (
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

      <FundSourceSheet visible={isSheetVisible} onClose={() => setIsSheetVisible(false)} fundSourceCards={sheetCards} screenWidth={screenWidth} />
    </View>
  );
}