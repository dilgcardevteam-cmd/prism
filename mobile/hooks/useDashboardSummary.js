import { useCallback, useEffect, useMemo, useState, useRef } from "react";

import { formatUpdatedAt } from "./useLocallyFundedProjects";
import { useWebAppRequest } from "./useWebAppRequest";
import { useDashboardCache } from "./useDashboardCache";

const PROJECT_RISK_SUMMARY_ORDER = ["On Schedule", "Ahead", "No Risk", "Low Risk", "Moderate Risk", "High Risk"];
const PROJECT_RISK_AGING_ORDER = ["High Risk", "Low Risk", "No Risk"];
const PROJECT_UPDATE_STATUS_ORDER = ["High Risk", "Low Risk", "No Risk"];
const DEFAULT_FINANCIAL = {
  allocation: 0,
  obligation: 0,
  disbursement: 0,
  reverted: 0,
  balance: 0,
  utilizationRate: 0,
};

function toNumber(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function formatPercentage(value) {
  return `${Number(value || 0).toFixed(2)}%`;
}

function formatCurrency(value) {
  return `P ${new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(toNumber(value))}`;
}

function normalizeRiskRows(rows, order) {
  const mappedRows = Array.isArray(rows)
    ? rows
        .map((row) => ({
          label: String(row?.label || "").trim(),
          count: Number(row?.count || 0),
        }))
        .filter((row) => row.label && order.includes(row.label))
    : [];

  const countsByLabel = new Map(
    mappedRows.map((row) => [row.label, Number.isFinite(row.count) ? row.count : 0])
  );

  return order.map((label) => ({
    label,
    count: countsByLabel.get(label) || 0,
  }));
}

export function useDashboardSummary() {
  const { fetchJsonWithFallback } = useWebAppRequest();
  const { getCachedData, setCachedData, clearCache, getStaleCachedData } = useDashboardCache();

  // State for summary data
  const [totalProjects, setTotalProjects] = useState(0);
  const [fundSourceCounts, setFundSourceCounts] = useState([]);
  const [statusSubaybayanRows, setStatusSubaybayanRows] = useState([]);
  const [statusSubaybayanTotal, setStatusSubaybayanTotal] = useState(0);
  const [statusSubaybayanMax, setStatusSubaybayanMax] = useState(0);
  const [financialSummary, setFinancialSummary] = useState(DEFAULT_FINANCIAL);
  const [projectAtRiskSlippageRows, setProjectAtRiskSlippageRows] = useState(
    PROJECT_RISK_SUMMARY_ORDER.map((label) => ({ label, count: 0 }))
  );
  const [projectAtRiskAgingRows, setProjectAtRiskAgingRows] = useState(
    PROJECT_RISK_AGING_ORDER.map((label) => ({ label, count: 0 }))
  );
  const [projectUpdateStatusRows, setProjectUpdateStatusRows] = useState(
    PROJECT_UPDATE_STATUS_ORDER.map((label) => ({ label, count: 0 }))
  );
  const [projectsExpectedCompletionThisMonth, setProjectsExpectedCompletionThisMonth] = useState([]);
  const [expectedCompletionMonthLabel, setExpectedCompletionMonthLabel] = useState(
    new Intl.DateTimeFormat("en-US", { month: "long", year: "numeric" }).format(new Date())
  );
  const [latestUpdatedAt, setLatestUpdatedAt] = useState(null);
  const [isLoadingSummary, setIsLoadingSummary] = useState(true);
  const [summaryError, setSummaryError] = useState("");

  // Track if we've loaded from cache to avoid redundant API calls
  const hasTriedCacheRef = useRef(false);
  const isRefreshingRef = useRef(false);

  const processAggregateData = useCallback((aggregatePayload) => {
    try {
      // Extract summary data
      const summaryData = aggregatePayload?.summary && typeof aggregatePayload.summary === "object"
        ? aggregatePayload.summary
        : {};

      const normalizedFundSourceCounts = Array.isArray(summaryData.fund_source_counts)
        ? summaryData.fund_source_counts
            .map((entry) => ({
              fundSource: String(entry?.fund_source || "").trim(),
              count: toNumber(entry?.count),
            }))
            .filter((entry) => entry.fundSource && entry.count > 0)
        : [];

      const normalizedStatusRows = Array.isArray(summaryData.status_subaybayan_rows)
        ? summaryData.status_subaybayan_rows
            .map((entry) => ({
              status: String(entry?.status || "").trim(),
              count: toNumber(entry?.count),
            }))
            .filter((entry) => entry.status && entry.count > 0)
        : [];

      const summaryFinancial = summaryData.financial && typeof summaryData.financial === "object"
        ? summaryData.financial
        : {};

      const allocation = toNumber(summaryFinancial.allocation);
      const obligation = toNumber(summaryFinancial.obligation);
      const disbursement = toNumber(summaryFinancial.disbursement);
      const reverted = toNumber(summaryFinancial.reverted);
      const fallbackBalance = allocation - (disbursement + reverted);
      const utilizationRate = toNumber(summaryFinancial.utilization_rate);
      const hasExplicitBalance = summaryFinancial.balance !== null && summaryFinancial.balance !== undefined && summaryFinancial.balance !== "";

      setTotalProjects(toNumber(summaryData.total_projects));
      setFundSourceCounts(normalizedFundSourceCounts);
      setStatusSubaybayanRows(normalizedStatusRows);
      setStatusSubaybayanTotal(toNumber(summaryData.status_subaybayan_total));
      setStatusSubaybayanMax(toNumber(summaryData.status_subaybayan_max));
      setFinancialSummary({
        allocation,
        obligation,
        disbursement,
        reverted,
        balance: hasExplicitBalance ? toNumber(summaryFinancial.balance) : fallbackBalance,
        utilizationRate,
      });
      setLatestUpdatedAt(summaryData.latest_updated_at || null);

      // Extract slippage data
      const slippageData = aggregatePayload?.slippage?.data || [];
      setProjectAtRiskSlippageRows(normalizeRiskRows(slippageData, PROJECT_RISK_SUMMARY_ORDER));

      // Extract aging data
      const agingData = aggregatePayload?.aging?.data || [];
      setProjectAtRiskAgingRows(normalizeRiskRows(agingData, PROJECT_RISK_AGING_ORDER));

      // Extract update status data
      const updateStatusData = aggregatePayload?.update_status?.data || [];
      setProjectUpdateStatusRows(normalizeRiskRows(updateStatusData, PROJECT_UPDATE_STATUS_ORDER));

      // Extract expected completion data
      const expectedCompletionPayload = aggregatePayload?.expected_completion || {};
      const expectedCompletionData = Array.isArray(expectedCompletionPayload.data)
        ? expectedCompletionPayload.data
            .map((entry) => ({
              projectCode: String(entry?.project_code || "").trim(),
              projectTitle: String(entry?.project_title || "").trim(),
              province: String(entry?.province || "").trim(),
              cityMunicipality: String(entry?.city_municipality || "").trim(),
              expectedCompletionDate: String(entry?.expected_completion_date || "").trim(),
            }))
            .filter((entry) => entry.projectCode && entry.projectTitle && entry.expectedCompletionDate)
        : [];

      setProjectsExpectedCompletionThisMonth(expectedCompletionData);
      setExpectedCompletionMonthLabel(
        String(expectedCompletionPayload.meta?.month_label || "").trim() ||
          new Intl.DateTimeFormat("en-US", { month: "long", year: "numeric" }).format(new Date())
      );

      return true;
    } catch (error) {
      console.error("Error processing aggregate data:", error);
      return false;
    }
  }, []);

  const resetToDefaults = useCallback(() => {
    setTotalProjects(0);
    setFundSourceCounts([]);
    setStatusSubaybayanRows([]);
    setStatusSubaybayanTotal(0);
    setStatusSubaybayanMax(0);
    setFinancialSummary(DEFAULT_FINANCIAL);
    setProjectAtRiskSlippageRows(PROJECT_RISK_SUMMARY_ORDER.map((label) => ({ label, count: 0 })));
    setProjectAtRiskAgingRows(PROJECT_RISK_AGING_ORDER.map((label) => ({ label, count: 0 })));
    setProjectUpdateStatusRows(PROJECT_UPDATE_STATUS_ORDER.map((label) => ({ label, count: 0 })));
    setProjectsExpectedCompletionThisMonth([]);
    setExpectedCompletionMonthLabel(
      new Intl.DateTimeFormat("en-US", { month: "long", year: "numeric" }).format(new Date())
    );
    setLatestUpdatedAt(null);
  }, []);

  const loadDashboardSummary = useCallback(async () => {
    // Prevent simultaneous refreshes
    if (isRefreshingRef.current) {
      return;
    }

    setIsLoadingSummary(true);
    setSummaryError("");
    isRefreshingRef.current = true;

    try {
      // Try to fetch from the aggregated endpoint
      const aggregatedData = await fetchJsonWithFallback("/api/mobile/dashboard/aggregate");

      if (aggregatedData) {
        // Process and cache the data
        const success = processAggregateData(aggregatedData);
        if (success) {
          await setCachedData(aggregatedData);
        } else {
          setSummaryError("Failed to process dashboard data.");
        }
      }
    } catch (error) {
      console.error("Error loading dashboard summary:", error);
      setSummaryError(error?.message || "Unable to load dashboard summary.");

      // Attempt to fall back to stale cache for offline support
      try {
        const staleCacheData = await getStaleCachedData();
        if (staleCacheData) {
          processAggregateData(staleCacheData);
          setSummaryError(""); // Clear error if we can display stale data
        } else {
          resetToDefaults();
        }
      } catch (cacheError) {
        console.error("Error accessing stale cache:", cacheError);
        resetToDefaults();
      }
    } finally {
      setIsLoadingSummary(false);
      isRefreshingRef.current = false;
    }
  }, [fetchJsonWithFallback, processAggregateData, setCachedData, getStaleCachedData, resetToDefaults]);

  // Initial load: try cache first, then fetch
  useEffect(() => {
    if (hasTriedCacheRef.current) {
      return;
    }

    hasTriedCacheRef.current = true;

    const initializeData = async () => {
      setIsLoadingSummary(true);

      try {
        // Try to get cached data (cache expires after 10 minutes)
        const cachedData = await getCachedData(10);
        if (cachedData) {
          // Use cached data
          const success = processAggregateData(cachedData);
          if (success) {
            setIsLoadingSummary(false);
            return;
          }
        }
      } catch (error) {
        console.error("Error checking cache:", error);
      }

      // If no valid cache, fetch fresh data
      await loadDashboardSummary();
    };

    initializeData();
  }, [getCachedData, processAggregateData, loadDashboardSummary]);

  const financialMetrics = useMemo(() => {
    const allocation = toNumber(financialSummary.allocation);
    const obligation = toNumber(financialSummary.obligation);
    const disbursement = toNumber(financialSummary.disbursement);
    const balance = toNumber(financialSummary.balance);
    const utilizationRate = toNumber(financialSummary.utilizationRate);

    return [
      { key: "allocation", value: formatCurrency(allocation) },
      { key: "percentage", value: formatPercentage(utilizationRate) },
      { key: "obligation", value: formatCurrency(obligation) },
      { key: "disbursement", value: formatCurrency(disbursement) },
      { key: "balance", value: formatCurrency(balance) },
    ];
  }, [financialSummary]);

  const projectAtRiskSlippageTotal = useMemo(
    () => projectAtRiskSlippageRows.reduce((sum, row) => sum + row.count, 0),
    [projectAtRiskSlippageRows]
  );

  const projectAtRiskAgingTotal = useMemo(
    () => projectAtRiskAgingRows.reduce((sum, row) => sum + row.count, 0),
    [projectAtRiskAgingRows]
  );

  const projectUpdateStatusTotal = useMemo(
    () => projectUpdateStatusRows.reduce((sum, row) => sum + row.count, 0),
    [projectUpdateStatusRows]
  );

  const summaryLabel = latestUpdatedAt
    ? `Project Status Summary as of ${formatUpdatedAt(latestUpdatedAt)}`
    : "Project Status Summary";

  const refreshDashboard = useCallback(async () => {
    // Clear cache and force refresh
    await clearCache();
    await loadDashboardSummary();
  }, [clearCache, loadDashboardSummary]);

  return {
    isLoadingSummary,
    summaryError,
    summaryLabel,
    totalProjects,
    projects: [],
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
    loadDashboardSummary,
    refreshDashboard,
  };
}
