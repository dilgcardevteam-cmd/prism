import { useCallback, useEffect, useMemo, useState } from "react";

import { formatUpdatedAt } from "./useLocallyFundedProjects";
import { useWebAppRequest } from "./useWebAppRequest";

const PROJECT_RISK_SUMMARY_ORDER = ["On Schedule", "Ahead", "No Risk", "Low Risk", "Moderate Risk", "High Risk"];
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

export function useDashboardSummary() {
  const { fetchJsonWithFallback } = useWebAppRequest();
  const [totalProjects, setTotalProjects] = useState(0);
  const [fundSourceCounts, setFundSourceCounts] = useState([]);
  const [statusSubaybayanRows, setStatusSubaybayanRows] = useState([]);
  const [statusSubaybayanTotal, setStatusSubaybayanTotal] = useState(0);
  const [statusSubaybayanMax, setStatusSubaybayanMax] = useState(0);
  const [financialSummary, setFinancialSummary] = useState(DEFAULT_FINANCIAL);
  const [projectAtRiskSlippageRows, setProjectAtRiskSlippageRows] = useState(
    PROJECT_RISK_SUMMARY_ORDER.map((label) => ({ label, count: 0 }))
  );
  const [latestUpdatedAt, setLatestUpdatedAt] = useState(null);
  const [isLoadingSummary, setIsLoadingSummary] = useState(true);
  const [summaryError, setSummaryError] = useState("");

  const loadDashboardSummary = useCallback(async () => {
    setIsLoadingSummary(true);
    setSummaryError("");

    try {
      const [summaryPayload, projectAtRiskSummary] = await Promise.all([
        fetchJsonWithFallback("/api/mobile/locally-funded/dashboard-summary"),
        fetchJsonWithFallback("/api/mobile/project-at-risk/slippage-summary"),
      ]);

      const summaryData = summaryPayload?.data && typeof summaryPayload.data === "object"
        ? summaryPayload.data
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

      const riskRows = Array.isArray(projectAtRiskSummary?.data)
        ? projectAtRiskSummary.data
            .map((row) => ({
              label: String(row?.label || "").trim(),
              count: Number(row?.count || 0),
            }))
            .filter((row) => row.label && PROJECT_RISK_SUMMARY_ORDER.includes(row.label))
        : [];

      if (riskRows.length > 0) {
        const countsByLabel = new Map(riskRows.map((row) => [row.label, Number.isFinite(row.count) ? row.count : 0]));
        setProjectAtRiskSlippageRows(
          PROJECT_RISK_SUMMARY_ORDER.map((label) => ({
            label,
            count: countsByLabel.get(label) || 0,
          }))
        );
      } else {
        setProjectAtRiskSlippageRows(PROJECT_RISK_SUMMARY_ORDER.map((label) => ({ label, count: 0 })));
      }
    } catch (error) {
      setTotalProjects(0);
      setFundSourceCounts([]);
      setStatusSubaybayanRows([]);
      setStatusSubaybayanTotal(0);
      setStatusSubaybayanMax(0);
      setFinancialSummary(DEFAULT_FINANCIAL);
      setProjectAtRiskSlippageRows(PROJECT_RISK_SUMMARY_ORDER.map((label) => ({ label, count: 0 })));
      setLatestUpdatedAt(null);
      setSummaryError(error?.message || "Unable to load dashboard summary.");
    } finally {
      setIsLoadingSummary(false);
    }
  }, [fetchJsonWithFallback]);

  useEffect(() => {
    loadDashboardSummary();
  }, [loadDashboardSummary]);

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

  const summaryLabel = latestUpdatedAt
    ? `Project Status Summary as of ${formatUpdatedAt(latestUpdatedAt)}`
    : "Project Status Summary";

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
    loadDashboardSummary,
  };
}
