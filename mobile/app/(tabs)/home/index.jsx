import { Feather } from "@expo/vector-icons";
import { useEffect, useMemo, useState } from "react";
import { ActivityIndicator, Text, useWindowDimensions, View } from "react-native";
import Animated, {
  Extrapolation,
  interpolate,
  useAnimatedScrollHandler,
  useAnimatedStyle,
  useSharedValue,
} from "react-native-reanimated";
import { SafeAreaView } from "react-native-safe-area-context";

import { useFetchLoggedUser } from "../../../hooks/useFetchLoggedUser";
import { formatUpdatedAt } from "../../../hooks/useLocallyFundedProjects";
import { useWebAppRequest } from "../../../hooks/useWebAppRequest";
import { APP_COLORS } from "../../../constants/theme";

const FUND_SOURCE_ORDER = ["SBDP", "FALGU", "CMGP", "GEF", "SAFPB"];

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

function formatCount(value) {
  return new Intl.NumberFormat("en-US").format(Number(value || 0));
}

function normalizeFundSource(value) {
  const normalized = String(value ?? "").trim().toUpperCase() || "UNSPECIFIED";

  if (normalized.startsWith("FALGU")) {
    return "FALGU";
  }

  if (normalized.startsWith("CMGP")) {
    return "CMGP";
  }

  if (normalized.startsWith("SBDP")) {
    return "SBDP";
  }

  if (normalized.startsWith("GEF")) {
    return "GEF";
  }

  if (normalized.startsWith("SAFPB")) {
    return "SAFPB";
  }

  return normalized;
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

export default function HomeScreen() {
  const { firstName, greeting } = useFetchLoggedUser();
  const { fetchJsonWithFallback } = useWebAppRequest();
  const { width: screenWidth } = useWindowDimensions();
  const [projects, setProjects] = useState([]);
  const [latestUpdatedAt, setLatestUpdatedAt] = useState(null);
  const [isLoadingSummary, setIsLoadingSummary] = useState(true);
  const [summaryError, setSummaryError] = useState("");
  const scrollY = useSharedValue(0);

  useEffect(() => {
    let isActive = true;

    const loadDashboardSummary = async () => {
      setIsLoadingSummary(true);
      setSummaryError("");

      try {
        const firstPage = await fetchJsonWithFallback("/api/mobile/locally-funded?per_page=100&page=1");
        const totalPages = Math.max(1, Number(firstPage?.meta?.last_page || 1));
        const collectedRows = Array.isArray(firstPage?.data) ? [...firstPage.data] : [];

        if (totalPages > 1) {
          const remainingPages = await Promise.all(
            Array.from({ length: totalPages - 1 }, (_value, index) => {
              const pageNumber = index + 2;
              return fetchJsonWithFallback(`/api/mobile/locally-funded?per_page=100&page=${pageNumber}`);
            })
          );

          remainingPages.forEach((pagePayload) => {
            if (Array.isArray(pagePayload?.data)) {
              collectedRows.push(...pagePayload.data);
            }
          });
        }

        if (!isActive) {
          return;
        }

        setProjects(collectedRows);

        const latestRow = collectedRows.reduce((currentLatest, row) => {
          const candidateDate = new Date(row?.updated_at || 0);

          if (Number.isNaN(candidateDate.getTime())) {
            return currentLatest;
          }

          if (!currentLatest) {
            return row;
          }

          const currentLatestDate = new Date(currentLatest?.updated_at || 0);
          return candidateDate > currentLatestDate ? row : currentLatest;
        }, null);

        setLatestUpdatedAt(latestRow?.updated_at || firstPage?.meta?.latest_updated_at || null);
      } catch (error) {
        if (isActive) {
          setProjects([]);
          setLatestUpdatedAt(null);
          setSummaryError(error?.message || "Unable to load dashboard summary.");
        }
      } finally {
        if (isActive) {
          setIsLoadingSummary(false);
        }
      }
    };

    loadDashboardSummary();

    return () => {
      isActive = false;
    };
  }, [fetchJsonWithFallback]);

  const totalProjects = projects.length;

  const fundSourceCards = useMemo(() => {
    const counts = new Map();

    projects.forEach((project) => {
      const fundSource = normalizeFundSource(project?.fund_source);
      counts.set(fundSource, (counts.get(fundSource) || 0) + 1);
    });

    const orderedSources = FUND_SOURCE_ORDER.map((fundSource) => ({
      fundSource,
      count: counts.get(fundSource) || 0,
    })).filter((entry) => entry.count > 0);

    const remainingSources = Array.from(counts.entries())
      .filter(([fundSource, count]) => count > 0 && !FUND_SOURCE_ORDER.includes(fundSource))
      .sort(([leftSource], [rightSource]) => leftSource.localeCompare(rightSource))
      .map(([fundSource, count]) => ({ fundSource, count }));

    return [...orderedSources, ...remainingSources].slice(0, 5).map(({ fundSource, count }) => {
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
  }, [projects]);

  const summaryLabel = latestUpdatedAt ? `Project Status Summary as of ${formatUpdatedAt(latestUpdatedAt)}` : "Project Status Summary";
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

  const heroParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(scrollY.value, [0, 320], [0, -36], Extrapolation.CLAMP),
      },
    ],
  }));

  const contentParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(scrollY.value, [0, 320], [0, -10], Extrapolation.CLAMP),
      },
    ],
  }));

  return (
    <SafeAreaView className="flex-1 font-sans" style={{ backgroundColor: APP_COLORS.background }} edges={[]}>
      <Animated.ScrollView
        contentContainerStyle={{ flexGrow: 1 }}
        showsVerticalScrollIndicator={false}
        onScroll={handleScroll}
        scrollEventThrottle={16}
      >
        <Animated.View style={[{ backgroundColor: APP_COLORS.primaryBlue }, heroParallaxStyle]}>
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
        </Animated.View>
      </Animated.ScrollView>
    </SafeAreaView>
  );
}

export const meta = {
  title: "Home",
};
