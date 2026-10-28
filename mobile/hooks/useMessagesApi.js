import { useCallback } from "react";

import { useWebAppRequest } from "./useWebAppRequest";
import { getMobileAuthSession } from "./mobileAccessToken";

export function useMessagesApi() {
  const { fetchJsonWithFallback } = useWebAppRequest();

  const fetchMessages = useCallback(async ({ threadId = 0 } = {}) => {
    const queryParams = new URLSearchParams();

    if (Number(threadId || 0) > 0) {
      queryParams.set("thread", String(threadId));
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
  }, [fetchJsonWithFallback]);

  const sendMessage = useCallback(async ({ threadId = 0, recipientIds = [], message = "", image = null, images = [] }) => {
    const formData = new FormData();

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

    if (Array.isArray(images) && images.length) {
      images.forEach((attachment) => {
        if (!attachment?.uri) {
          return;
        }

        formData.append("images[]", {
          uri: attachment.uri,
          name: attachment.name || `message-${Date.now()}.jpg`,
          type: attachment.type || "image/jpeg",
        });
      });
    }

    const authSession = await getMobileAuthSession();
    if (!authSession) {
      throw new Error("Please log in again before sending messages.");
    }

    const response = await fetch(`${authSession.baseUrl}/api/mobile/messages`, {
      method: "POST",
      credentials: "include",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${authSession.accessToken}`,
      },
      body: formData,
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      throw new Error(payload?.message || "Unable to send the message.");
    }

    return payload || {};
  }, []);

  return {
    fetchMessages,
    sendMessage,
  };
}
