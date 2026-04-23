import { useCallback, useMemo, useState } from "react";
import {
  API_CANDIDATE_BASE_URLS,
  API_URL,
  buildApiUrl,
} from "../constants/api";

export function useWebAppRequest() {
  const candidateBaseUrls = useMemo(() => API_CANDIDATE_BASE_URLS, []);
  const [activeBaseUrl, setActiveBaseUrl] = useState(API_URL);

  const fetchJsonWithFallback = useCallback(
    async (path, init = {}) => {
      let lastError = null;
      let preferredError = null;
      const requestCandidates = [activeBaseUrl, ...candidateBaseUrls].filter(Boolean);
      const uniqueRequestCandidates = Array.from(new Set(requestCandidates));

      for (const baseUrl of uniqueRequestCandidates) {
        const url = buildApiUrl(path, baseUrl);

        try {
          const response = await fetch(url, {
            ...init,
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
            throw new Error(
              `Endpoint responded with non-JSON content from ${baseUrl}. Check auth/session or endpoint response type.`
            );
          }

          const payload = await response.json();
          setActiveBaseUrl(baseUrl);
          return payload;
        } catch (error) {
          lastError = error;

          const message = String(error?.message || "");
          if (message.startsWith("Request failed") || message.startsWith("Endpoint responded")) {
            preferredError = error;
          }
        }
      }

      throw preferredError || lastError || new Error("Unable to connect to any local web app host.");
    },
    [activeBaseUrl, candidateBaseUrls]
  );

  return {
    activeBaseUrl,
    candidateBaseUrls,
    fetchJsonWithFallback,
  };
}
