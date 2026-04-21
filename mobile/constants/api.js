import Constants from "expo-constants";
import { NativeModules, Platform } from "react-native";

const FALLBACK_API_URL = Platform.select({
  android: "http://10.0.2.2:8000",
  default: "http://127.0.0.1:8000",
});

const ENV_API_URL =
  process.env.EXPO_PUBLIC_API_URL?.trim() ||
  process.env.EXPO_PUBLIC_WEB_APP_URL?.trim() ||
  "";

export function normalizeBaseUrl(url) {
  return String(url || "").replace(/\/+$/, "").trim();
}

function getHostFromUrl(url) {
  try {
    const parsed = new URL(url);
    return parsed.hostname || "";
  } catch (_error) {
    return "";
  }
}

function getExpoDevHost() {
  const hostUri =
    Constants.expoConfig?.hostUri ||
    Constants.manifest2?.extra?.expoClient?.hostUri ||
    "";

  if (!hostUri) {
    return "";
  }

  return String(hostUri).split(":")[0] || "";
}

function getScriptHost() {
  const scriptUrl = NativeModules?.SourceCode?.scriptURL || "";

  if (!scriptUrl) {
    return "";
  }

  try {
    return new URL(scriptUrl).hostname || "";
  } catch (_error) {
    return "";
  }
}

function buildCandidateBaseUrls() {
  const detectedHosts = [getExpoDevHost(), getScriptHost()].filter(Boolean);
  const hostDerivedCandidates = detectedHosts.map((host) => `http://${host}:8000`);

  const envHost = getHostFromUrl(ENV_API_URL);
  const envIsLoopback = envHost === "127.0.0.1" || envHost === "localhost";

  const candidates = [
    ENV_API_URL,
    ...(envIsLoopback ? hostDerivedCandidates : []),
    ...hostDerivedCandidates,
    FALLBACK_API_URL,
    "http://127.0.0.1:8000",
    "http://localhost:8000",
  ]
    .map((candidate) => normalizeBaseUrl(candidate))
    .filter(Boolean);

  return Array.from(new Set(candidates));
}

export const API_CANDIDATE_BASE_URLS = buildCandidateBaseUrls();
export const API_URL = API_CANDIDATE_BASE_URLS[0] || normalizeBaseUrl(FALLBACK_API_URL);

export function buildApiUrl(path, baseUrl = API_URL) {
  const normalizedPath = String(path || "").startsWith("/") ? path : `/${path}`;
  return `${normalizeBaseUrl(baseUrl)}${normalizedPath}`;
}
