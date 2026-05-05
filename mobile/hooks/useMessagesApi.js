import { useCallback } from "react";

import { buildApiUrl } from "../constants/api";
import { useAuth } from "../contexts/AuthContext";
import { useWebAppRequest } from "./useWebAppRequest";

export function useMessagesApi() {
  const { session } = useAuth();
  const { activeBaseUrl, fetchJsonWithFallback } = useWebAppRequest();

  const fetchMessages = useCallback(async ({ threadId = 0 } = {}) => {
    const queryParams = new URLSearchParams();

    if (Number(threadId || 0) > 0) {
      queryParams.set("thread", String(threadId));
    }

    if (session?.id) {
      queryParams.set("user_id", String(session.id));
    }

    const queryString = queryParams.toString();
    const endpoint = queryString ? `/api/mobile/messages?${queryString}` : "/api/mobile/messages";

    return fetchJsonWithFallback(endpoint, {
      method: "GET",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
    });
  }, [fetchJsonWithFallback, session?.id]);

  const sendMessage = useCallback(async ({ threadId = 0, recipientIds = [], message = "", image = null }) => {
    const formData = new FormData();

    if (session?.id) {
      formData.append("user_id", String(session.id));
    }

    if (Number(threadId || 0) > 0) {
      formData.append("thread_id", String(threadId));
    }

    recipientIds.forEach((recipientId) => {
      formData.append("recipient_ids[]", String(recipientId));
    });

    formData.append("message", String(message || ""));

    if (image?.uri) {
      formData.append("image", {
        uri: image.uri,
        name: image.name || `message-${Date.now()}.jpg`,
        type: image.type || "image/jpeg",
      });
    }

    const response = await fetch(buildApiUrl("/api/mobile/messages", activeBaseUrl), {
      method: "POST",
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
      body: formData,
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      throw new Error(payload?.message || "Unable to send the message.");
    }

    return payload || {};
  }, [activeBaseUrl, session?.id]);

  return {
    fetchMessages,
    sendMessage,
  };
}
