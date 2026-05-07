import { useCallback, useEffect, useMemo, useState } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import {
  API_CANDIDATE_BASE_URLS,
  API_URL,
  buildApiUrl,
} from "../constants/api";

const STORAGE_KEY = "preferredBaseUrl";
const REQUEST_TIMEOUT = 8000;
const MAX_RETRIES = 2;
const STAGGER_DELAY = 150;

let preferredBaseUrl = API_URL;

function isLoopbackBaseUrl(url) {
  try {
    const hostname = new URL(String(url || "")).hostname;
    return hostname === "127.0.0.1" || hostname === "localhost" || hostname === "10.0.2.2";
  } catch (_error) {
    return false;
  }
}

/**
 * Fetch with timeout
 */
async function requestJson(path, baseUrl, init = {}, timeout = REQUEST_TIMEOUT) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeout);

  try {
    const url = buildApiUrl(path, baseUrl);

    const response = await fetch(url, {
      ...init,
      credentials: init.credentials ?? "include",
      signal: controller.signal,
      headers: {
        Accept: "application/json",
        ...(init.headers || {}),
      },
    });

    if (!response.ok) {
      throw new Error(`Request failed (${response.status}) from ${baseUrl}`);
    }

    const contentType = response.headers.get("content-type") || "";
    if (!contentType.includes("application/json")) {
      throw new Error(`Non-JSON response from ${baseUrl}`);
    }

    return await response.json();
  } catch (err) {
    if (err.name === "AbortError") {
      throw new Error(`Timeout from ${baseUrl}`);
    }
    throw err;
  } finally {
    clearTimeout(timeoutId);
  }
}

/**
 * Retry with exponential backoff
 */
async function requestWithRetry(path, baseUrl, init, retries = MAX_RETRIES, timeout = REQUEST_TIMEOUT) {
  let attempt = 0;

  while (attempt <= retries) {
    try {
      return await requestJson(path, baseUrl, init, timeout);
    } catch (err) {
      if (attempt === retries) throw err;

      const delay = 300 * Math.pow(2, attempt);
      await new Promise((res) => setTimeout(res, delay));
      attempt++;
    }
  }
}

/**
 * Race requests (fastest wins)
 */
async function fetchWithRace(path, candidates, init, timeout = REQUEST_TIMEOUT) {
  return new Promise((resolve, reject) => {
    let settled = false;
    let completed = 0;
    let lastError = null;

    candidates.forEach((baseUrl, index) => {
      setTimeout(() => {
        requestWithRetry(path, baseUrl, init, MAX_RETRIES, timeout)
          .then((res) => {
            if (!settled) {
              settled = true;
              resolve({ res, baseUrl });
            }
          })
          .catch((err) => {
            lastError = err;
            completed++;

            if (completed === candidates.length && !settled) {
              reject(lastError);
            }
          });
      }, index * STAGGER_DELAY);
    });
  });
}

export function useWebAppRequest() {
  const candidateBaseUrls = useMemo(() => API_CANDIDATE_BASE_URLS, []);
  const [activeBaseUrl, setActiveBaseUrl] = useState(preferredBaseUrl);
  const [isStorageLoaded, setIsStorageLoaded] = useState(false);

  /**
   * Load cached base URL (non-blocking)
   */
  useEffect(() => {
    let mounted = true;

    (async () => {
      try {
        const saved = await AsyncStorage.getItem(STORAGE_KEY);

        if (saved && mounted) {
          if (!isLoopbackBaseUrl(saved)) {
            preferredBaseUrl = saved;
            setActiveBaseUrl(saved);
          } else {
            await AsyncStorage.removeItem(STORAGE_KEY);
            setActiveBaseUrl(API_URL);
          }
        }
      } catch (e) {
        // optional: log error
      } finally {
        if (mounted) setIsStorageLoaded(true);
      }
    })();

    return () => {
      mounted = false;
    };
  }, []);

  /**
   * Persist working base URL (skip loopback URLs)
   */
  useEffect(() => {
    if (!activeBaseUrl || !isStorageLoaded || isLoopbackBaseUrl(activeBaseUrl)) return;

    AsyncStorage.setItem(STORAGE_KEY, activeBaseUrl).catch(() => {
      // optional: log error
    });
  }, [activeBaseUrl, isStorageLoaded]);

  /**
   * Main fetch function
   */
  const fetchJsonWithFallback = useCallback(
    async (path, init = {}, options = {}) => {
      const timeout = Number.isFinite(options?.timeout) ? options.timeout : REQUEST_TIMEOUT;
      const dedupedCandidates = Array.from(
        new Set([activeBaseUrl, ...candidateBaseUrls].filter(Boolean))
      );

      const nonLoopbackCandidates = dedupedCandidates.filter((baseUrl) => !isLoopbackBaseUrl(baseUrl));
      const loopbackCandidates = dedupedCandidates.filter((baseUrl) => isLoopbackBaseUrl(baseUrl));
      const candidates = nonLoopbackCandidates.length
        ? [...nonLoopbackCandidates, ...loopbackCandidates]
        : dedupedCandidates;

      const { res, baseUrl } = await fetchWithRace(path, candidates, init, timeout);

      if (baseUrl !== activeBaseUrl) {
        preferredBaseUrl = baseUrl;
        setActiveBaseUrl(baseUrl);
      }

      return res;
    },
    [activeBaseUrl, candidateBaseUrls]
  );

  return {
    activeBaseUrl,
    candidateBaseUrls,
    fetchJsonWithFallback,
    isStorageLoaded, // optional: useful for debugging/loading states
  };
}