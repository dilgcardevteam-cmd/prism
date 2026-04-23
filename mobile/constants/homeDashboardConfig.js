export const STATUS_META = {
  Completed: { icon: "check", iconBackground: "#2f8f4b", color: "#2f8f4b" },
  "On-going": { icon: "loader", iconBackground: "#08348a", color: "#08348a" },
  "Not Yet Started": { icon: "clock", iconBackground: "#444548", color: "#444548" },
  "DED Preparation": { icon: "edit-3", iconBackground: "#6a30c5", color: "#6a30c5" },
  "Bid Evaluation/Opening": { icon: "briefcase", iconBackground: "#b35708", color: "#b35708" },
  "NOA Issuance": { icon: "file-text", iconBackground: "#1f8cc0", color: "#1f8cc0" },
  Cancelled: { icon: "x", iconBackground: "#c81448", color: "#c81448" },
  "ITB/AD Posted": { icon: "radio", iconBackground: "#bf470f", color: "#bf470f" },
  Terminated: { icon: "slash", iconBackground: "#b03030", color: "#b03030" },
};

export const FUND_SOURCE_META = {
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

export const FINANCIAL_METRIC_CARDS = {
  allocation: {
    label: "LGSF Allocation",
    icon: "dollar-sign",
    backgroundColor: "#ffefe6",
    borderColor: "#f97316",
    accentColor: "#ea580c",
    valueColor: "#ea580c",
  },
  percentage: {
    label: "Percentage",
    icon: "pie-chart",
    backgroundColor: "#dce8ff",
    borderColor: "#1d4ed8",
    accentColor: "#143f93",
    valueColor: "#143f93",
  },
  obligation: {
    label: "Obligation",
    icon: "archive",
    backgroundColor: "#f3f7cf",
    borderColor: "#a3a300",
    accentColor: "#8d8d00",
    valueColor: "#8d8d00",
  },
  disbursement: {
    label: "Disbursement",
    icon: "credit-card",
    backgroundColor: "#c9f0d3",
    borderColor: "#15803d",
    accentColor: "#0f6b31",
    valueColor: "#0f6b31",
  },
  balance: {
    label: "Balance",
    icon: "box",
    backgroundColor: "#efdcf0",
    borderColor: "#86198f",
    accentColor: "#70127a",
    valueColor: "#70127a",
  },
};

export const PROJECT_RISK_DONUT_ORDER = ["Ahead", "No Risk", "On Schedule", "High Risk", "Moderate Risk", "Low Risk"];

export const PROJECT_RISK_STYLES = {
  "On Schedule": { bg: "#a3a3a3", text: "#f8fafc" },
  Ahead: { bg: "#3f9142", text: "#f8fafc" },
  "No Risk": { bg: "#2f84cf", text: "#f8fafc" },
  "Low Risk": { bg: "#f6c000", text: "#f8fafc" },
  "Moderate Risk": { bg: "#fb6f41", text: "#f8fafc" },
  "High Risk": { bg: "#c81d1d", text: "#f8fafc" },
};

export function formatCount(value) {
  return new Intl.NumberFormat("en-US").format(Number(value || 0));
}

export function formatPercentage(value) {
  return `${Number(value || 0).toFixed(2)}%`;
}