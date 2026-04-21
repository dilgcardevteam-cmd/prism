import { useCallback, useEffect, useState } from "react";
import { API_URL } from "../constants/api";
import { useWebAppRequest } from "./useWebAppRequest";

const pesoFormatter = new Intl.NumberFormat("en-PH", {
  style: "currency",
  currency: "PHP",
  maximumFractionDigits: 2,
});

function normalizeProjectRow(row) {
  const galleryImages = Array.isArray(row.gallery_images)
    ? row.gallery_images
        .map((image) => ({
          id: image?.id,
          category: String(image?.category || "").trim() || "During",
          imageUrl: String(image?.image_url || "").trim(),
          createdAt: image?.created_at || null,
          latitude:
            image?.latitude === null || image?.latitude === undefined || image?.latitude === ""
              ? null
              : Number(image.latitude),
          longitude:
            image?.longitude === null || image?.longitude === undefined || image?.longitude === ""
              ? null
              : Number(image.longitude),
          accuracy:
            image?.accuracy === null || image?.accuracy === undefined || image?.accuracy === ""
              ? null
              : Number(image.accuracy),
        }))
        .filter((image) => image.id && image.imageUrl)
    : [];

  return {
    id: row.lfp_id || row.subaybayan_project_code,
    code: row.subaybayan_project_code || "-",
    title: row.project_name || row.subaybayan_project_code || "Untitled Project",
    province: row.province || "-",
    city: row.city_municipality || "-",
    barangay: row.barangay || "-",
    fundingYear: row.funding_year || "-",
    fundSource: row.fund_source || "-",
    procurementType: row.mode_of_procurement || "-",
    projectType: row.project_type || "-",
    dateNadai: row.date_nadai || null,
    numBeneficiaries: row.no_of_beneficiaries || 0,
    rainwaterSystem: row.rainwater_collection_system || "No",
    dateConfirmation: row.date_confirmation_fund_receipt || null,
    lgsfAllocation: row.lgsf_allocation,
    lguCounterpart: row.lgu_counterpart,
    datePostingItb: row.date_posting_itb || null,
    dateBidOpening: row.date_bid_opening || null,
    dateNoa: row.date_noa || null,
    dateNtp: row.date_ntp || null,
    contractor: row.contractor || "-",
    contractAmount: row.contract_amount,
    projectDuration: row.project_duration || "-",
    actualStartDate: row.actual_start_date || null,
    targetDateCompletion: row.target_date_completion || null,
    revisedTargetDate: row.revised_target_date_completion || null,
    actualDateCompletion: row.actual_date_completion || null,
    physicalTimeline: Array.isArray(row.physical_timeline) ? row.physical_timeline : [],
    currentPhysical: row.current_physical && typeof row.current_physical === "object"
      ? row.current_physical
      : null,
    galleryImages,
    obligation: row.obligation,
    utilizationRate: Number(row.utilization_rate ?? 0),
    physicalStatus: Number(row.subay_accomplishment_pct ?? row.accomplishment_pct_ro ?? 0),
    statusActual: row.status_actual || "-",
    statusSubaybayan: row.status_subaybayan_current || row.status_subaybayan || "-",
    lastUpdatedAt: row.updated_at,
  };
}

export function formatMoney(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "-";
  }

  return pesoFormatter.format(Number(value));
}

export function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "0.00%";
  }

  return `${Number(value).toFixed(2)}%`;
}

export function formatUpdatedAt(value) {
  if (!value) {
    return "-";
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return "-";
  }

  const month = parsed.toLocaleString("en-US", { month: "short" });
  const day = String(parsed.getDate()).padStart(2, "0");
  const year = parsed.getFullYear();
  const time = parsed
    .toLocaleString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
      hour12: true,
    })
    .replace(/\s/g, "");

  return `${month} ${day}, ${year} ${time}`;
}

export function useLocallyFundedProjects() {
  const { activeBaseUrl, fetchJsonWithFallback } = useWebAppRequest();
  const [projects, setProjects] = useState([]);
  const [filterOptions, setFilterOptions] = useState({
    fundingYears: [],
    fundSources: [],
    provinces: [],
    citiesByProvince: {},
    procurementTypes: [],
    statuses: [],
  });
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [errorMessage, setErrorMessage] = useState("");

  const loadProjects = useCallback(
    async (isPullToRefresh = false) => {
      if (isPullToRefresh) {
        setIsRefreshing(true);
      } else {
        setIsLoading(true);
      }

      try {
        setErrorMessage("");

        const payload = await fetchJsonWithFallback(
          "/api/mobile/locally-funded?per_page=50"
        );
        const rows = Array.isArray(payload?.data) ? payload.data : [];
        const filters = payload?.meta?.filters || {};

        setFilterOptions({
          fundingYears: Array.isArray(filters.funding_years)
            ? filters.funding_years.map((year) => String(year).trim()).filter(Boolean)
            : [],
          fundSources: Array.isArray(filters.fund_sources)
            ? filters.fund_sources.map((source) => String(source).trim()).filter(Boolean)
            : [],
          provinces: Array.isArray(filters.provinces)
            ? filters.provinces.map((province) => String(province).trim()).filter(Boolean)
            : [],
          citiesByProvince:
            filters.cities_by_province && typeof filters.cities_by_province === "object"
              ? filters.cities_by_province
              : {},
          procurementTypes: Array.isArray(filters.procurement_types)
            ? filters.procurement_types.map((type) => String(type).trim()).filter(Boolean)
            : [],
          statuses: Array.isArray(filters.statuses)
            ? filters.statuses.map((status) => String(status).trim()).filter(Boolean)
            : [],
        });

        setProjects(rows.map(normalizeProjectRow));
      } catch (error) {
        setProjects([]);
        setFilterOptions({
          fundingYears: [],
          fundSources: [],
          provinces: [],
          citiesByProvince: {},
          procurementTypes: [],
          statuses: [],
        });

        const hint =
          `Make sure Laravel is running and your phone can reach your computer on the same network. Current base URL: ${API_URL}. You can set EXPO_PUBLIC_API_URL to your PC IP, for example http://192.168.x.x:8000.`;
        setErrorMessage(`${error?.message || "Unable to load projects."}. ${hint}`);
      } finally {
        setIsLoading(false);
        setIsRefreshing(false);
      }
    },
    [fetchJsonWithFallback]
  );

  useEffect(() => {
    loadProjects(false);
  }, [loadProjects]);

  return {
    activeBaseUrl,
    projects,
    filterOptions,
    isLoading,
    isRefreshing,
    errorMessage,
    loadProjects,
  };
}
