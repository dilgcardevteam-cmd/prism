import { useMemo, useState, useCallback } from "react";
import { useWindowDimensions, RefreshControl } from "react-native";
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
import ProjectAgingSection from "./components/ProjectAgingSection";
import ProjectUpdateStatusSection from "./components/ProjectUpdateStatusSection";
import ProjectStatusSection from "./components/ProjectStatusSection";

import {
  FUND_SOURCE_META,
  FINANCIAL_METRIC_CARDS,
  formatCount,
} from "../../../constants/homeDashboardConfig";

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
    projectAtRiskAgingRows,
    projectAtRiskAgingTotal,
    projectUpdateStatusRows,
    projectUpdateStatusTotal,
    projectsExpectedCompletionThisMonth,
    expectedCompletionMonthLabel,
    refreshDashboard,
  } = useDashboardSummary();

  const [isRefreshing, setIsRefreshing] = useState(false);
  const scrollY = useSharedValue(0);

  /**
   * =============================
   * Derived Data
   * =============================
   */
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

  /**
   * =============================
   * Layout Calculations
   * =============================
   */
  const isCompactScreen = screenWidth < 390;
  const isNarrowRiskLayout = screenWidth < 430;

  const financialTileWidth = Math.max(screenWidth * 0.84, 290);
  const riskPanelHeight = Math.max(240, Math.min(320, screenWidth * 0.72));
  const riskLegendWidth = Math.max(280, Math.min(screenWidth - 8, 420));

  const donutSize = isNarrowRiskLayout
    ? Math.max(136, Math.min(182, screenWidth * 0.46))
    : Math.max(156, Math.min(220, screenWidth * 0.5));

  const fundSourceColumns = screenWidth >= 390 ? 3 : 2;
  const fundSourceGap = 8;
  const horizontalPadding = 16;

  const usableWidth = Math.max(screenWidth - horizontalPadding * 2, 240);

  const tileWidth =
    (usableWidth - fundSourceGap * (fundSourceColumns - 1)) /
    fundSourceColumns;

  /**
   * =============================
   * Scroll + Parallax
   * =============================
   */
  const handleScroll = useAnimatedScrollHandler({
    onScroll: (event) => {
      scrollY.value = event.contentOffset.y;
    },
  });

  const heroParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(
          scrollY.value,
          [0, 120],
          [0, 30],
          Extrapolation.CLAMP
        ),
      },
    ],
  }));

  const contentParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(
          scrollY.value,
          [0, 180],
          [0, -18],
          Extrapolation.CLAMP
        ),
      },
    ],
  }));

  /**
   * =============================
   * Refresh Logic
   * =============================
   */
  const handleRefresh = useCallback(async () => {
    if (isRefreshing) return;

    setIsRefreshing(true);
    try {
      await refreshDashboard();
    } catch (error) {
      console.error("Error refreshing dashboard:", error);
    } finally {
      setIsRefreshing(false);
    }
  }, [isRefreshing, refreshDashboard]);

  /**
   * =============================
   * Render
   * =============================
   */
  return (
    <SafeAreaView
      className="flex-1 font-sans"
      style={{ backgroundColor: APP_COLORS.primaryBlue }}
      edges={[]}
    >
      <Animated.ScrollView
        contentContainerStyle={{ flexGrow: 1 }}
        showsVerticalScrollIndicator={false}
        onScroll={handleScroll}
        scrollEventThrottle={16}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={handleRefresh}
            tintColor="#ffffff"
            colors={[APP_COLORS.primaryBlue]}
            progressViewOffset={80}
          />
        }
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

        <Animated.View
          className="-mt-6 flex-1 rounded-t-[28px] bg-white px-4 pt-4"
          style={contentParallaxStyle}
        >
          <DashboardQuickStats
            projectsExpectedCompletionThisMonth={
              projectsExpectedCompletionThisMonth
            }
            expectedCompletionMonthLabel={expectedCompletionMonthLabel}
          />

          <FundSourceSection
            isLoadingSummary={isLoadingSummary}
            summaryError={summaryError}
            fundSourceCards={fundSourceCards}
            tileWidth={tileWidth}
            screenWidth={screenWidth}
          />

          <FinancialAccomplishmentSection
            isLoadingSummary={isLoadingSummary}
            summaryError={summaryError}
            financialMetrics={financialMetrics}
            financialTileWidth={financialTileWidth}
            metricCards={FINANCIAL_METRIC_CARDS}
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

          <ProjectAgingSection
            isLoadingSummary={isLoadingSummary}
            summaryError={summaryError}
            projectAtRiskAgingRows={projectAtRiskAgingRows}
            projectAtRiskAgingTotal={projectAtRiskAgingTotal}
            donutSize={donutSize}
            riskLegendWidth={riskLegendWidth}
            isNarrowRiskLayout={isNarrowRiskLayout}
          />

          <ProjectUpdateStatusSection
            isLoadingSummary={isLoadingSummary}
            summaryError={summaryError}
            projectUpdateStatusRows={projectUpdateStatusRows}
            projectUpdateStatusTotal={projectUpdateStatusTotal}
            donutSize={donutSize}
            riskLegendWidth={riskLegendWidth}
            isNarrowRiskLayout={isNarrowRiskLayout}
          />
        </Animated.View>
      </Animated.ScrollView>
    </SafeAreaView>
  );
}

export const meta = {
  title: "Home",
};