import { useCallback, useEffect, useMemo, useState } from "react";

import { formatUpdatedAt } from "./useLocallyFundedProjects";
import { useWebAppRequest } from "./useWebAppRequest";

const FUND_SOURCE_ORDER = ["SBDP", "FALGU", "CMGP", "GEF", "SAFPB"];
const STATUS_DISPLAY_ORDER = [
  "Completed",
  "On-going",
  "Bid Evaluation/Opening",
  "NOA Issuance",
  "DED Preparation",
  "Not Yet Started",
  "ITB/AD Posted",
  "Terminated",
  "Cancelled",
];

const STATUS_LABEL_BY_NORMALIZED = {
  COMPLETED: "Completed",
  ONGOING: "On-going",
  "BID EVALUATION/OPENING": "Bid Evaluation/Opening",
  "NOA ISSUANCE": "NOA Issuance",
  "DED PREPARATION": "DED Preparation",
  "NOT YET STARTED": "Not Yet Started",
  "ITB/AD POSTED": "ITB/AD Posted",
  TERMINATED: "Terminated",
  CANCELLED: "Cancelled",
};

const STATUS_ALIASES = {
  "ON-GOING": "ONGOING",
  "NOT STARTED": "NOT YET STARTED",
};

const PROJECT_RISK_CHART_ORDER = ["Ahead", "No Risk", "On Schedule", "High Risk", "Moderate Risk", "Low Risk"];
const PROJECT_RISK_SUMMARY_ORDER = ["On Schedule", "Ahead", "No Risk", "Low Risk", "Moderate Risk", "High Risk"];

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

function normalizeProjectStatus(value) {
  const raw = String(value ?? "").trim();
  if (!raw) {
    return null;
  }

  const uppercase = raw.toUpperCase();
  const normalized = STATUS_ALIASES[uppercase] || uppercase;
  return STATUS_LABEL_BY_NORMALIZED[normalized] || null;
}

function classifySlippageRisk(value) {
  const slippage = Number(value);

  if (!Number.isFinite(slippage)) {
    return null;
  }

  if (slippage > 0) {
    return "Ahead";
  }

  if (slippage === 0) {
    return "On Schedule";
  }

  if (slippage <= -15) {
    return "High Risk";
  }

  if (slippage <= -10) {
    return "Moderate Risk";
  }

  if (slippage <= -5) {
    return "Low Risk";
  }

  if (slippage < 0) {
    return "No Risk";
  }

  return null;
}

export function useDashboardSummary() {
  const { fetchJsonWithFallback } = useWebAppRequest();
  const [projects, setProjects] = useState([]);
  const [latestUpdatedAt, setLatestUpdatedAt] = useState(null);
  const [isLoadingSummary, setIsLoadingSummary] = useState(true);
  const [summaryError, setSummaryError] = useState("");

  const loadDashboardSummary = useCallback(async () => {
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
      setProjects([]);
      setLatestUpdatedAt(null);
      setSummaryError(error?.message || "Unable to load dashboard summary.");
    } finally {
      setIsLoadingSummary(false);
    }
  }, [fetchJsonWithFallback]);

  useEffect(() => {
    loadDashboardSummary();
  }, [loadDashboardSummary]);

  const totalProjects = projects.length;

  const fundSourceCounts = useMemo(() => {
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

    return [...orderedSources, ...remainingSources].slice(0, 5);
  }, [projects]);

  const statusSubaybayanRows = useMemo(() => {
    const counts = new Map(STATUS_DISPLAY_ORDER.map((statusLabel) => [statusLabel, 0]));

    projects.forEach((project) => {
      const statusLabel = normalizeProjectStatus(project?.status_subaybayan || project?.status_subaybayan_current);
      if (statusLabel) {
        counts.set(statusLabel, (counts.get(statusLabel) || 0) + 1);
      }
    });

    return Array.from(counts.entries())
      .map(([status, count]) => ({ status, count }))
      .filter((entry) => entry.count > 0)
      .sort((left, right) => {
        if (right.count !== left.count) {
          return right.count - left.count;
        }

        return STATUS_DISPLAY_ORDER.indexOf(left.status) - STATUS_DISPLAY_ORDER.indexOf(right.status);
      });
  }, [projects]);

  const statusSubaybayanTotal = useMemo(
    () => statusSubaybayanRows.reduce((sum, row) => sum + row.count, 0),
    [statusSubaybayanRows]
  );

  const statusSubaybayanMax = useMemo(
    () => statusSubaybayanRows.reduce((max, row) => Math.max(max, row.count), 0),
    [statusSubaybayanRows]
  );

  const financialMetrics = useMemo(() => {
    const allocation = projects.reduce((sum, project) => sum + toNumber(project?.lgsf_allocation), 0);
    const obligation = projects.reduce((sum, project) => sum + toNumber(project?.obligation), 0);
    const disbursement = projects.reduce((sum, project) => sum + toNumber(project?.disbursed_amount), 0);
    const reverted = projects.reduce((sum, project) => sum + toNumber(project?.reverted_amount), 0);
    const balance = allocation - (disbursement + reverted);
    const utilizationRate = allocation > 0 ? ((disbursement + reverted) / allocation) * 100 : 0;

    return [
      { key: "allocation", value: formatCurrency(allocation) },
      { key: "percentage", value: formatPercentage(utilizationRate) },
      { key: "obligation", value: formatCurrency(obligation) },
      { key: "disbursement", value: formatCurrency(disbursement) },
      { key: "balance", value: formatCurrency(balance) },
    ];
  }, [projects]);

  const projectAtRiskSlippageRows = useMemo(() => {
    const counts = new Map(PROJECT_RISK_CHART_ORDER.map((riskLabel) => [riskLabel, 0]));

    projects.forEach((project) => {
      const slippageValue = project?.current_physical?.slippage_ro;
      const riskLabel = classifySlippageRisk(slippageValue);

      if (riskLabel) {
        counts.set(riskLabel, (counts.get(riskLabel) || 0) + 1);
      }
    });

    return PROJECT_RISK_SUMMARY_ORDER.map((riskLabel) => ({
      label: riskLabel,
      count: counts.get(riskLabel) || 0,
    }));
  }, [projects]);

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
    projects,
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
