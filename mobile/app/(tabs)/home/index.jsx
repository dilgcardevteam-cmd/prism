import { useMemo } from "react";
import { useWindowDimensions } from "react-native";
import Animated, {
  Extrapolation,
  interpolate,
  useAnimatedScrollHandler,
  useAnimatedStyle,
  useSharedValue,
} from "react-native-reanimated";
import { SafeAreaView } from "react-native-safe-area-context";

import { useFetchLoggedUser } from "../../../hooks/useFetchLoggedUser";
import { useDashboardSummary } from "../../../hooks/useDashboardSummary";
import { APP_COLORS } from "../../../constants/theme";
import HomeHeroSection from "./components/HomeHeroSection";
import DashboardQuickStats from "./components/DashboardQuickStats";
import FundSourceSection from "./components/FundSourceSection";
import FinancialAccomplishmentSection from "./components/FinancialAccomplishmentSection";
import ProjectRiskSection from "./components/ProjectRiskSection";
import ProjectStatusSection from "./components/ProjectStatusSection";
import { FUND_SOURCE_META, FINANCIAL_METRIC_CARDS, formatCount } from "./components/dashboardConfig";

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

  return (
    <SafeAreaView className="flex-1 font-sans" style={{ backgroundColor: APP_COLORS.primaryBlue }} edges={[]}>
      <Animated.ScrollView
        contentContainerStyle={{ flexGrow: 1 }}
        showsVerticalScrollIndicator={false}
        onScroll={handleScroll}
        scrollEventThrottle={16}
      >
        <Animated.View style={heroParallaxStyle}>

          <HomeHeroSection
            greeting={greeting}
            firstName={firstName}
            summaryLabel={summaryLabel}
            totalProjects={formatCount(totalProjects)}
            isLoadingSummary={isLoadingSummary}
            style={{ flex: 1 }}
          />

        </Animated.View>

        <Animated.View className="-mt-6 flex-1 rounded-t-[28px] bg-white px-4 pt-4" style={contentParallaxStyle}>
          <DashboardQuickStats />
          <FundSourceSection
            isLoadingSummary={isLoadingSummary}
            summaryError={summaryError}
            fundSourceCards={fundSourceCards}
            tileWidth={tileWidth}
          />
          <FinancialAccomplishmentSection
            isLoadingSummary={isLoadingSummary}
            summaryError={summaryError}
            financialMetrics={financialMetrics}
            financialTileWidth={financialTileWidth}
            metricCards={FINANCIAL_METRIC_CARDS}
          />
          <ProjectRiskSection
            isLoadingSummary={isLoadingSummary}
            summaryError={summaryError}
            projectAtRiskSlippageRows={projectAtRiskSlippageRows}
            projectAtRiskSlippageTotal={projectAtRiskSlippageTotal}
            donutSize={donutSize}
            riskLegendWidth={riskLegendWidth}
            riskPanelHeight={riskPanelHeight}
            isNarrowRiskLayout={isNarrowRiskLayout}
          />
          <ProjectStatusSection
            isLoadingSummary={isLoadingSummary}
            summaryError={summaryError}
            statusSubaybayanRows={statusSubaybayanRows}
            statusSubaybayanTotal={statusSubaybayanTotal}
            statusSubaybayanMax={statusSubaybayanMax}
            screenWidth={screenWidth}
            isCompactScreen={isCompactScreen}
          />
        </Animated.View>
      </Animated.ScrollView>
    </SafeAreaView>
  );
}

export const meta = {
  title: "Home",
};
