import { Feather } from "@expo/vector-icons";
import * as SecureStore from "expo-secure-store";
import { useRouter } from "expo-router";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Animated,
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  TextInput,
  Platform,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { useDebounce } from "../../../../hooks/useDebounce";
import {
  PINNED_PROJECTS_STORAGE_KEY,
  normalizePinnedProjectIds,
  useLocallyFundedProjects,
} from "../../../../hooks/useLocallyFundedProjects";
import { APP_ROUTES } from "../../../../constants/routes";
import FloatingToast from "../../../../components/common/FloatingToast";
import LoadingOverlay from "../../../../components/common/LoadingOverlay";

const FILTER_ALL_VALUE = "All";

async function readPinnedProjectIds() {
  try {
    if (Platform.OS === "web") {
      const rawValue = globalThis?.localStorage?.getItem(PINNED_PROJECTS_STORAGE_KEY);
      return normalizePinnedProjectIds(rawValue ? JSON.parse(rawValue) : []);
    }

    const rawValue = await SecureStore.getItemAsync(PINNED_PROJECTS_STORAGE_KEY);
    return normalizePinnedProjectIds(rawValue ? JSON.parse(rawValue) : []);
  } catch (_error) {
    return [];
  }
}

async function savePinnedProjectIds(projectIds) {
  try {
    const serializedValue = JSON.stringify(normalizePinnedProjectIds(projectIds));

    if (Platform.OS === "web") {
      globalThis?.localStorage?.setItem(PINNED_PROJECTS_STORAGE_KEY, serializedValue);
      return;
    }

    await SecureStore.setItemAsync(PINNED_PROJECTS_STORAGE_KEY, serializedValue);
  } catch (_error) {
    // Ignore persistence failures and keep the in-memory ordering.
  }
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function HighlightedText({
  text,
  query,
  className,
  highlightClassName,
  numberOfLines,
  style,
  highlightStyle,
}) {
  const source = String(text ?? "");
  const keyword = String(query ?? "").trim();

  if (!keyword) {
    return (
      <Text className={className} numberOfLines={numberOfLines} style={style}>
        {source}
      </Text>
    );
  }

  const expression = new RegExp(`(${escapeRegExp(keyword)})`, "ig");
  const segments = source.split(expression);

  return (
    <Text className={className} numberOfLines={numberOfLines} style={style}>
      {segments.map((segment, index) => {
        const isMatch = segment.toLowerCase() === keyword.toLowerCase();

        if (!isMatch) {
          return segment;
        }

        return (
          <Text
            key={`${segment}-${index}`}
            className={highlightClassName}
            style={[style, highlightStyle]}
          >
            {segment}
          </Text>
        );
      })}
    </Text>
  );
}

const FILTER_FIELDS = [
  { key: "fundingYear", label: "Funding Year" },
  { key: "fundSource", label: "Fund Source" },
  { key: "province", label: "Province" },
  { key: "city", label: "City/Mun" },
  { key: "procurementType", label: "Procurement Type" },
  { key: "status", label: "Status" },
];

function FilterPill({ label, value, onPress }) {
  return (
    <Pressable
      onPress={onPress}
      className="ml-2 flex-row items-center rounded-full border border-[#bfccdf] bg-white px-3 py-2"
      accessibilityRole="button"
      accessibilityLabel={`Open ${label} filter`}
    >
      <Text className="text-[12px] text-[#1e293b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        {label}:
      </Text>
      <Text
        numberOfLines={1}
        className="ml-1 max-w-[126px] text-[12px] text-[#334155]"
        style={{ fontFamily: "Montserrat" }}
      >
        {value}
      </Text>
      <Feather name="chevron-down" size={14} color="#64748b" style={{ marginLeft: 6 }} />
    </Pressable>
  );
}

function buildUniqueOptions(projects, selector) {
  const values = projects
    .map(selector)
    .map((value) => String(value ?? "").trim())
    .filter(Boolean);

  const uniqueValues = Array.from(new Set(values));
  uniqueValues.sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));

  return [FILTER_ALL_VALUE, ...uniqueValues];
}

export default function LocallyFundedProjectsScreen() {
  const router = useRouter();
  const [searchQuery, setSearchQuery] = useState("");
  const debouncedSearchQuery = useDebounce(searchQuery, 350);
  const {
    activeBaseUrl,
    projects,
    filterOptions: backendFilterOptions,
    isLoading,
    isRefreshing,
    isLoadingMore,
    hasMore,
    errorMessage,
    loadProjects,
    loadMoreProjects,
  } = useLocallyFundedProjects({ searchQuery: debouncedSearchQuery });
  const [isFiltersExpanded, setIsFiltersExpanded] = useState(false);
  const [shouldRenderFilters, setShouldRenderFilters] = useState(false);
  const [pinnedProjectIds, setPinnedProjectIds] = useState([]);
  const [isPinnedReady, setIsPinnedReady] = useState(false);
  const [pinToast, setPinToast] = useState({ visible: false, type: "success", message: "" });
  const [selectedFilters, setSelectedFilters] = useState({
    fundingYear: FILTER_ALL_VALUE,
    fundSource: FILTER_ALL_VALUE,
    province: FILTER_ALL_VALUE,
    city: FILTER_ALL_VALUE,
    procurementType: FILTER_ALL_VALUE,
    status: FILTER_ALL_VALUE,
  });
  const [activeDropdownFilterKey, setActiveDropdownFilterKey] = useState(null);
  const filtersAnimation = useRef(new Animated.Value(0)).current;

  // Load the saved pinned project ids when the screen mounts.
  useEffect(() => {
    let isActive = true;

    readPinnedProjectIds().then((storedProjectIds) => {
      if (!isActive) {
        return;
      }

      setPinnedProjectIds(storedProjectIds);
      setIsPinnedReady(true);
    });

    return () => {
      isActive = false;
    };
  }, []);

  // Persist the pin order whenever the user changes it.
  useEffect(() => {
    if (!isPinnedReady) {
      return;
    }

    savePinnedProjectIds(pinnedProjectIds);
  }, [isPinnedReady, pinnedProjectIds]);

  // Toggle a project's pinned state and keep pinned items at the top of the saved order.
  const togglePinnedProject = useCallback((projectId) => {
    const normalizedProjectId = String(projectId ?? "").trim();
    if (!normalizedProjectId) {
      return;
    }

    setPinnedProjectIds((currentPinnedProjectIds) => {
      const currentIds = normalizePinnedProjectIds(currentPinnedProjectIds);

      if (currentIds.includes(normalizedProjectId)) {
        return currentIds.filter((id) => id !== normalizedProjectId);
      }

      return [normalizedProjectId, ...currentIds];
    });
  }, []);

  const closePinToast = useCallback(() => {
    setPinToast((current) => ({ ...current, visible: false }));
  }, []);

  const showPinToast = useCallback((message) => {
    setPinToast({
      visible: true,
      type: "success",
      message,
    });
  }, []);

  // Animate the filter bar in and out when the user expands or collapses it.
  useEffect(() => {
    if (isFiltersExpanded) {
      setShouldRenderFilters(true);
      Animated.timing(filtersAnimation, {
        toValue: 1,
        duration: 220,
        useNativeDriver: true,
      }).start();
      return;
    }

    Animated.timing(filtersAnimation, {
      toValue: 0,
      duration: 180,
      useNativeDriver: true,
    }).start(({ finished }) => {
      if (finished) {
        setShouldRenderFilters(false);
      }
    });
  }, [filtersAnimation, isFiltersExpanded]);

  const filterOptions = useMemo(
    () => {
      const fallbackFromData = {
        fundingYear: buildUniqueOptions(projects, (project) => project.fundingYear),
        fundSource: buildUniqueOptions(projects, (project) => project.fundSource),
        province: buildUniqueOptions(projects, (project) => project.province),
        city: buildUniqueOptions(projects, (project) => project.city),
        procurementType: buildUniqueOptions(projects, (project) => project.procurementType),
        status: buildUniqueOptions(projects, (project) => project.statusActual),
      };

      const backendFundingYears = Array.isArray(backendFilterOptions?.fundingYears)
        ? backendFilterOptions.fundingYears
        : [];
      const backendFundSources = Array.isArray(backendFilterOptions?.fundSources)
        ? backendFilterOptions.fundSources
        : [];
      const backendProvinces = Array.isArray(backendFilterOptions?.provinces)
        ? backendFilterOptions.provinces
        : [];
      const backendProcurementTypes = Array.isArray(backendFilterOptions?.procurementTypes)
        ? backendFilterOptions.procurementTypes
        : [];
      const backendStatuses = Array.isArray(backendFilterOptions?.statuses)
        ? backendFilterOptions.statuses
        : [];

      const selectedProvince = selectedFilters.province;
      const citiesByProvince =
        backendFilterOptions?.citiesByProvince && typeof backendFilterOptions.citiesByProvince === "object"
          ? backendFilterOptions.citiesByProvince
          : {};

      let backendCities = [];
      if (selectedProvince !== FILTER_ALL_VALUE && Array.isArray(citiesByProvince[selectedProvince])) {
        backendCities = citiesByProvince[selectedProvince];
      } else {
        backendCities = Object.values(citiesByProvince)
          .flatMap((values) => (Array.isArray(values) ? values : []));
      }

      const normalizedBackendCities = Array.from(
        new Set(backendCities.map((city) => String(city).trim()).filter(Boolean))
      ).sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));

      return {
        fundingYear:
          backendFundingYears.length > 0
            ? [FILTER_ALL_VALUE, ...backendFundingYears]
            : fallbackFromData.fundingYear,
        fundSource:
          backendFundSources.length > 0
            ? [FILTER_ALL_VALUE, ...backendFundSources]
            : fallbackFromData.fundSource,
        province:
          backendProvinces.length > 0
            ? [FILTER_ALL_VALUE, ...backendProvinces]
            : fallbackFromData.province,
        city:
          normalizedBackendCities.length > 0
            ? [FILTER_ALL_VALUE, ...normalizedBackendCities]
            : fallbackFromData.city,
        procurementType:
          backendProcurementTypes.length > 0
            ? [FILTER_ALL_VALUE, ...backendProcurementTypes]
            : fallbackFromData.procurementType,
        status:
          backendStatuses.length > 0
            ? [FILTER_ALL_VALUE, ...backendStatuses]
            : fallbackFromData.status,
      };
    },
    [backendFilterOptions, projects, selectedFilters.province]
  );

  // Apply the active filter rules to the project list.
  const filteredProjects = useMemo(() => {
    const normalizeValue = (value) => String(value ?? "").trim().toLowerCase();
    const normalizeComparable = (value) =>
      normalizeValue(value)
        .replace(/[^a-z0-9]+/g, " ")
        .trim();

    return projects.filter((project) => {
      const normalizedFundingYear = String(project.fundingYear ?? "").trim();
      const normalizedFundSource = normalizeValue(project.fundSource);
      const comparableProvince = normalizeComparable(project.province);
      const comparableCity = normalizeComparable(project.city);
      const comparableProcurement = normalizeComparable(project.procurementType);
      const comparableStatusActual = normalizeComparable(project.statusActual);
      const comparableStatusSubaybayan = normalizeComparable(project.statusSubaybayan);

      if (
        selectedFilters.fundingYear !== FILTER_ALL_VALUE &&
        normalizedFundingYear !== selectedFilters.fundingYear
      ) {
        return false;
      }

      if (
        selectedFilters.fundSource !== FILTER_ALL_VALUE &&
        (() => {
          const selectedFundSource = normalizeValue(selectedFilters.fundSource);

          if (selectedFundSource === "falgu") {
            return !normalizedFundSource.includes("falgu");
          }

          return normalizedFundSource !== selectedFundSource;
        })()
      ) {
        return false;
      }

      if (
        selectedFilters.province !== FILTER_ALL_VALUE &&
        comparableProvince !== normalizeComparable(selectedFilters.province)
      ) {
        return false;
      }

      if (
        selectedFilters.city !== FILTER_ALL_VALUE &&
        comparableCity !== normalizeComparable(selectedFilters.city)
      ) {
        return false;
      }

      if (
        selectedFilters.procurementType !== FILTER_ALL_VALUE &&
        comparableProcurement !== normalizeComparable(selectedFilters.procurementType)
      ) {
        return false;
      }

      if (selectedFilters.status !== FILTER_ALL_VALUE) {
        const selectedStatus = normalizeComparable(selectedFilters.status);
        const isPendingSelection = selectedStatus === "pending";

        const hasActualStatus = comparableStatusActual !== "";
        const hasSubaybayanStatus = comparableStatusSubaybayan !== "";

        const matchesPending =
          (!hasActualStatus && !hasSubaybayanStatus) ||
          comparableStatusActual === "pending" ||
          comparableStatusSubaybayan === "pending";

        const matchesExactOrContains =
          comparableStatusActual === selectedStatus ||
          comparableStatusSubaybayan === selectedStatus ||
          comparableStatusActual.includes(selectedStatus) ||
          comparableStatusSubaybayan.includes(selectedStatus);

        const matchesStatus = isPendingSelection ? matchesPending : matchesExactOrContains;

        if (!matchesStatus) {
          return false;
        }
      }

      return true;
    });
  }, [projects, selectedFilters]);

  // Sort pinned projects ahead of the remaining filtered projects.
  const displayedProjects = useMemo(() => {
    const pinnedOrder = new Map(pinnedProjectIds.map((projectId, index) => [String(projectId), index]));

    return filteredProjects
      .map((project, index) => ({ project, index }))
      .sort((left, right) => {
        const leftPinnedOrder = pinnedOrder.has(String(left.project.id))
          ? pinnedOrder.get(String(left.project.id))
          : Number.POSITIVE_INFINITY;
        const rightPinnedOrder = pinnedOrder.has(String(right.project.id))
          ? pinnedOrder.get(String(right.project.id))
          : Number.POSITIVE_INFINITY;

        if (leftPinnedOrder !== rightPinnedOrder) {
          return leftPinnedOrder - rightPinnedOrder;
        }

        return left.index - right.index;
      })
      .map(({ project }) => project);
  }, [filteredProjects, pinnedProjectIds]);

  // Track whether any filter is currently active.
  const hasActiveFilters =
    selectedFilters.fundingYear !== FILTER_ALL_VALUE ||
    selectedFilters.fundSource !== FILTER_ALL_VALUE ||
    selectedFilters.province !== FILTER_ALL_VALUE ||
    selectedFilters.city !== FILTER_ALL_VALUE ||
    selectedFilters.procurementType !== FILTER_ALL_VALUE ||
    selectedFilters.status !== FILTER_ALL_VALUE;

  // Resolve the dropdown field that is currently open.
  const activeDropdownField = FILTER_FIELDS.find((field) => field.key === activeDropdownFilterKey) || null;

  // Build the option list for the open dropdown.
  const activeDropdownOptions = useMemo(() => {
    if (!activeDropdownFilterKey) {
      return [];
    }

    const mapping = {
      fundingYear: filterOptions.fundingYear,
      fundSource: filterOptions.fundSource,
      province: filterOptions.province,
      city: filterOptions.city,
      procurementType: filterOptions.procurementType,
      status: filterOptions.status,
    };

    return mapping[activeDropdownFilterKey] || [];
  }, [activeDropdownFilterKey, filterOptions]);

  // Store the trimmed query for highlight matching.
  const highlightedQuery = debouncedSearchQuery.trim();
  const filtersSlideY = filtersAnimation.interpolate({
    inputRange: [0, 1],
    outputRange: [-12, 0],
  });

  // Build the project card and its navigation payload.
  const renderProjectCard = ({ item }) => {
    const isPinned = pinnedProjectIds.includes(String(item.id));
    const serializedProject = JSON.stringify({
      id: item.id,
      title: item.title,
      code: item.code,
      fundingYear: item.fundingYear,
      fundSource: item.fundSource,
      city: item.city,
      province: item.province,
      barangay: item.barangay,
      procurementType: item.procurementType,
      projectType: item.projectType,
      dateNadai: item.dateNadai,
      numBeneficiaries: item.numBeneficiaries,
      rainwaterSystem: item.rainwaterSystem,
      dateConfirmation: item.dateConfirmation,
      lgsfAllocation: item.lgsfAllocation,
      lguCounterpart: item.lguCounterpart,
      datePostingItb: item.datePostingItb,
      dateBidOpening: item.dateBidOpening,
      dateNoa: item.dateNoa,
      dateNtp: item.dateNtp,
      contractor: item.contractor,
      contractAmount: item.contractAmount,
      projectDuration: item.projectDuration,
      actualStartDate: item.actualStartDate,
      targetDateCompletion: item.targetDateCompletion,
      revisedTargetDate: item.revisedTargetDate,
      actualDateCompletion: item.actualDateCompletion,
      physicalTimeline: item.physicalTimeline,
      currentPhysical: item.currentPhysical,
      galleryImages: item.galleryImages,
      physicalStatus: item.physicalStatus,
      statusActual: item.statusActual,
      statusSubaybayan: item.statusSubaybayan,
    });

    return (
      <Pressable
        className="mb-3"
        accessibilityRole="button"
        accessibilityLabel={`View details for ${String(item.title ?? "project")}`}
        onPress={() => {
          router.push({
            pathname: APP_ROUTES.projectMonitoring.viewLocallyFundedProject,
            params: {
              project: serializedProject,
            },
          });
        }}
      >
        <View className={`rounded-3xl border px-3 py-3 ${isPinned ? "border-[#c0841a] bg-[#fff8e8]" : "border-[#e6eef8] bg-white"}`}>
          <View className="flex-row items-start justify-between gap-2">
            <View className="min-w-0 flex-1 pr-2">
              <HighlightedText
                text={item.title}
                query={highlightedQuery}
                className="min-w-0 flex-1 text-[16px] text-[#0b3a66]"
                highlightClassName=""
                style={{ fontFamily: "Montserrat-Regular" }}
                highlightStyle={{ fontFamily: "Montserrat-SemiBold" }}
                numberOfLines={2}
              />

              <HighlightedText
                text={item.code}
                query={highlightedQuery}
                className="mt-2 text-[13px] text-[#0b3a66]"
                highlightClassName=""
                style={{ fontFamily: "Montserrat-SemiBold" }}
                highlightStyle={{ fontFamily: "Montserrat-SemiBold" }}
                numberOfLines={1}
              />

              <View className="mt-2 border-b" style={{ borderBottomColor: "#e6eef8" }} />

              <Text className="mt-2 text-[12px] text-[#0b3a66]" numberOfLines={1} style={{ fontFamily: "Montserrat-SemiBold" }}>
                {String(item.city ?? "").toUpperCase()}{", "}{String(item.province ?? "").toUpperCase()}
              </Text>
            </View>

            <View className="ml-1 flex-row items-center gap-2 shrink-0">
              <Pressable
                onPress={(event) => {
                  event?.stopPropagation?.();
                  showPinToast(isPinned ? "Project successfully unpinned" : "Project successfully pinned");
                  togglePinnedProject(item.id);
                }}
                className={`h-9 w-9 items-center justify-center rounded-full border ${isPinned ? "border-[#b45309] bg-[#fef3c7]" : "border-[#cbd5e1] bg-white"}`}
                accessibilityRole="button"
                accessibilityLabel={isPinned ? `Unpin ${String(item.title ?? "project")}` : `Pin ${String(item.title ?? "project")}`}
              >
                <Feather name={isPinned ? "bookmark" : "bookmark"} size={14} color={isPinned ? "#b45309" : "#64748b"} />
              </Pressable>
              <Feather name="chevron-right" size={16} color="#64748b" style={{ marginTop: 4 }} />
            </View>
          </View>
        </View>
      </Pressable>
    );
  };

  return (
    <SafeAreaView className="flex-1 bg-[#f1f5f9]" edges={[]}>
      <FloatingToast
        visible={pinToast.visible}
        type={pinToast.type}
        message={pinToast.message}
        onClose={closePinToast}
      />
      {/* <View className="px-4 pt-4 pb-2">
        <Text className="text-[23px] font-bold text-[#002C76]">Locally Funded Projects</Text>
        <Text className="mt-1 text-[12px] text-[#475569]">
          Source: {activeBaseUrl}/api/mobile/locally-funded
        </Text>
      </View> */}

      <LoadingOverlay
        visible={isLoading}
        message="Loading locally funded projects..."
      />
      {!isLoading && (
        <View className="flex-1">
          <View className="w-full flex-row items-center gap-2 px-3 pb-1 pt-3">
            <View className="flex-1 flex-row items-center rounded-2xl border border-[#bfccdf] bg-white px-3 py-2.5">
              <Feather name="search" size={18} color="#64748b" />
              <TextInput
                value={searchQuery}
                onChangeText={setSearchQuery}
                placeholder="Search projects, city, status..."
                placeholderTextColor="#94a3b8"
                className="ml-2 flex-1 text-[14px] text-[#1e293b]"
                autoCapitalize="none"
                autoCorrect={false}
                returnKeyType="search"
              />
              {searchQuery ? (
                <Pressable
                  onPress={() => setSearchQuery("")}
                  className="ml-2 h-6 w-6 items-center justify-center rounded-full bg-[#e2e8f0]"
                  accessibilityRole="button"
                  accessibilityLabel="Clear search"
                >
                  <Feather name="x" size={14} color="#475569" />
                </Pressable>
              ) : null}
            </View>

            <Pressable
              onPress={() => setIsFiltersExpanded((current) => !current)}
              className="h-[46px] w-[46px] items-center justify-center rounded-xl border border-[#bfccdf] bg-white"
              accessibilityRole="button"
              accessibilityLabel="Toggle filters"
            >
              <Feather
                name="sliders"
                size={18}
                color={isFiltersExpanded || hasActiveFilters ? "#1d4ed8" : "#64748b"}
              />
            </Pressable>
          </View>

          {shouldRenderFilters ? (
            <Animated.View
              className="mt-2"
              style={{
                opacity: filtersAnimation,
                transform: [{ translateY: filtersSlideY }],
              }}
            >
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={{ paddingHorizontal: 12, paddingVertical: 2 }}
              >
                <Pressable
                  onPress={() => {
                    setSelectedFilters({
                      fundingYear: FILTER_ALL_VALUE,
                      fundSource: FILTER_ALL_VALUE,
                      province: FILTER_ALL_VALUE,
                      city: FILTER_ALL_VALUE,
                      procurementType: FILTER_ALL_VALUE,
                      status: FILTER_ALL_VALUE,
                    });
                  }}
                  className="rounded-full bg-[#6b7280] px-3 py-2"
                  accessibilityRole="button"
                  accessibilityLabel="Clear all filters"
                >
                  <Text className="text-[12px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Clear
                  </Text>
                </Pressable>
                {FILTER_FIELDS.map((field) => (
                  <FilterPill
                    key={field.key}
                    label={field.label}
                    value={selectedFilters[field.key]}
                    onPress={() => setActiveDropdownFilterKey(field.key)}
                  />
                ))}
              </ScrollView>
            </Animated.View>
          ) : null}

          <FlatList
            contentContainerStyle={{ paddingHorizontal: 12, paddingVertical: 12 }}
            data={displayedProjects}
            keyExtractor={(item, index) => `${item.id}-${index}`}
            renderItem={renderProjectCard}
            onEndReached={() => {
              if (!debouncedSearchQuery.trim() && !hasActiveFilters) {
                loadMoreProjects();
              }
            }}
            onEndReachedThreshold={0.55}
            refreshControl={
              <RefreshControl
                refreshing={isRefreshing}
                onRefresh={() => {
                  loadProjects(true);
                }}
                tintColor="#1d4ed8"
              />
            }
            ListFooterComponent={
              isLoadingMore ? (
                <View className="py-3 items-center justify-center">
                  <ActivityIndicator size="small" color="#1d4ed8" />
                </View>
              ) : !hasMore && displayedProjects.length > 0 && !debouncedSearchQuery.trim() && !hasActiveFilters ? (
                <View className="py-3 items-center justify-center">
                  <Text className="text-[11px] text-[#64748b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    End of list
                  </Text>
                </View>
              ) : null
            }
            ListEmptyComponent={
              <View className="mt-10 rounded-2xl border border-[#dbe3f0] bg-white px-4 py-5">
                <Text className="text-[15px] font-semibold text-[#1e3a8a]">
                  {debouncedSearchQuery.trim() || hasActiveFilters
                    ? "No matching projects"
                    : errorMessage
                    ? "Unable to load projects"
                    : "No projects available"}
                </Text>
                <Text className="mt-1 text-[12px] leading-[18px] text-[#64748b]">
                  {debouncedSearchQuery.trim() || hasActiveFilters
                    ? "Try another keyword or adjust your selected filters."
                    : errorMessage
                    ? errorMessage
                    : "No rows were returned by the endpoint."}
                </Text>
                {errorMessage ? (
                  <Text className="mt-2 text-[11px] text-[#475569]">Current base URL: {activeBaseUrl}</Text>
                ) : null}
                {!debouncedSearchQuery.trim() && !hasActiveFilters ? (
                  <Pressable
                    className="mt-4 self-start rounded-xl bg-[#dbeafe] px-4 py-2"
                    onPress={() => {
                      loadProjects(false);
                    }}
                  >
                    <Text className="text-[12px] font-semibold text-[#1e3a8a]">Retry Fetch</Text>
                  </Pressable>
                ) : null}
              </View>
            }
          />
        </View>
      )}

      <Modal
        transparent
        visible={!!activeDropdownField}
        animationType="fade"
        onRequestClose={() => setActiveDropdownFilterKey(null)}
      >
        <View className="flex-1 justify-end bg-black/30">
          <Pressable
            className="absolute inset-0"
            onPress={() => setActiveDropdownFilterKey(null)}
          />
          <View className="max-h-[68%] rounded-t-3xl bg-white px-4 pb-4 pt-3">
            <View className="mb-3 h-1.5 w-12 self-center rounded-full bg-[#d1d5db]" />
            <Text className="text-[15px] text-[#1e293b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
              {activeDropdownField?.label}
            </Text>

            <ScrollView className="mt-3" showsVerticalScrollIndicator={false}>
              {activeDropdownOptions.map((optionValue) => {
                const isSelected =
                  activeDropdownField && selectedFilters[activeDropdownField.key] === optionValue;

                return (
                  <Pressable
                    key={`${activeDropdownField?.key}-${optionValue}`}
                    className="mb-2 flex-row items-center justify-between rounded-xl border border-[#dbe3f0] bg-[#f8fafc] px-3 py-2.5"
                    onPress={() => {
                      if (!activeDropdownField) {
                        return;
                      }

                      setSelectedFilters((current) => {
                        const nextFilters = {
                          ...current,
                          [activeDropdownField.key]: optionValue,
                        };

                        if (activeDropdownField.key === "province") {
                          const validCities = filterOptions.city;
                          if (!validCities.includes(nextFilters.city)) {
                            nextFilters.city = FILTER_ALL_VALUE;
                          }
                        }

                        return nextFilters;
                      });

                      setActiveDropdownFilterKey(null);
                    }}
                  >
                    <Text
                      className={`text-[14px] ${isSelected ? "text-[#1d4ed8]" : "text-[#334155]"}`}
                      style={{ fontFamily: isSelected ? "Montserrat-SemiBold" : "Montserrat" }}
                    >
                      {optionValue}
                    </Text>
                    {isSelected ? <Feather name="check" size={16} color="#1d4ed8" /> : null}
                  </Pressable>
                );
              })}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

export const meta = {
  title: "Locally Funded Projects",
};
