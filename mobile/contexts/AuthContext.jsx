import * as SecureStore from "expo-secure-store";
import { AppState, View } from "react-native";
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from "react";

import { API_CANDIDATE_BASE_URLS, buildApiUrl } from "../constants/api";

const AUTH_STORAGE_KEY = "pdmuoms.mobile.auth.session";
const AUTH_STORAGE_VERSION = 3;
const IDLE_TIMEOUT_MS = 2 * 60 * 60 * 1000;
const ACTIVITY_PERSIST_INTERVAL_MS = 30 * 1000;

const AuthContext = createContext(null);

function fetchWithTimeout(resource, options = {}, timeoutMs = 5000) {
  if (typeof AbortController === "undefined") {
    return Promise.race([
      fetch(resource, options),
      new Promise((_, reject) => setTimeout(() => reject(new Error("Request timed out")), timeoutMs)),
    ]);
  }

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

  return fetch(resource, { ...options, signal: controller.signal }).finally(() => clearTimeout(timeoutId));
}

export function AuthProvider({ children }) {
  const [session, setSession] = useState(null);
  const [loginChallenge, setLoginChallenge] = useState(null);
  const [isHydrating, setIsHydrating] = useState(true);
  const lastActivityAt = useRef(Date.now());
  const lastActivityPersistedAt = useRef(0);

  useEffect(() => {
    let isMounted = true;

    const hydrate = async () => {
      try {
        const rawSession = await SecureStore.getItemAsync(AUTH_STORAGE_KEY);

        if (!isMounted) {
          return;
        }

        if (!rawSession) {
          setSession(null);
          return;
        }

        const parsedSession = JSON.parse(rawSession);

        if (
          parsedSession?.authVersion !== AUTH_STORAGE_VERSION ||
          !parsedSession?.access_token ||
          !parsedSession?.api_base_url
        ) {
          await SecureStore.deleteItemAsync(AUTH_STORAGE_KEY);
          setSession(null);
          return;
        }

        const activityAt = Number(parsedSession.lastActivityAt || parsedSession.authenticatedAt);
        if (!Number.isFinite(activityAt) || Date.now() - activityAt >= IDLE_TIMEOUT_MS) {
          await SecureStore.deleteItemAsync(AUTH_STORAGE_KEY);
          setSession(null);
          return;
        }

        lastActivityAt.current = activityAt;
        setSession({ ...parsedSession, lastActivityAt: activityAt });
      } catch {
        if (isMounted) {
          await SecureStore.deleteItemAsync(AUTH_STORAGE_KEY);
          setSession(null);
        }
      } finally {
        if (isMounted) {
          setIsHydrating(false);
        }
      }
    };

    hydrate();

    return () => {
      isMounted = false;
    };
  }, []);

  const completeSignIn = useCallback(async (payloadJson, baseUrl, fallbackUsername) => {
    const user = payloadJson?.user || {};
    const accessToken = payloadJson?.access_token;
    if (typeof accessToken !== "string" || accessToken.length === 0) {
      throw new Error("The server did not issue a mobile access token.");
    }

    const activityAt = Date.now();
    lastActivityAt.current = activityAt;
    lastActivityPersistedAt.current = activityAt;

    const nextSession = {
      authVersion: AUTH_STORAGE_VERSION,
      authenticatedAt: activityAt,
      lastActivityAt: activityAt,
      access_token: accessToken,
      api_base_url: baseUrl,
      id: user.id ?? null,
      username: user.username || fallbackUsername,
      first_name: user.first_name ?? null,
      last_name: user.last_name ?? null,
      email: user.email ?? null,
      phone: user.phone ?? null,
      agency: user.agency ?? null,
      position: user.position ?? null,
      region: user.region ?? null,
      province: user.province ?? null,
      office: user.office ?? null,
      role: user.role ?? null,
      status: user.status ?? null,
    };

    await SecureStore.setItemAsync(AUTH_STORAGE_KEY, JSON.stringify(nextSession));
    setSession(nextSession);
    setLoginChallenge(null);

    return nextSession;
  }, []);

  const signIn = useCallback(async (payload) => {
    const username = String(payload?.username ?? "").trim();
    const password = String(payload?.password ?? "");

    if (!username || !password) {
      throw new Error("Please enter your username and password.");
    }

    let lastNetworkError = null;
    let lastAuthError = null;

    for (const baseUrl of API_CANDIDATE_BASE_URLS) {
      try {
        const response = await fetchWithTimeout(
          buildApiUrl("/api/mobile/login", baseUrl),
          {
            method: "POST",
            headers: {
              Accept: "application/json",
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ username, password }),
          },
          5000
        );

        const payloadJson = await response.json().catch(() => null);

        if (response.status === 202 && payloadJson?.requires_otp && payloadJson?.challenge_token) {
          setLoginChallenge({
            challengeToken: payloadJson.challenge_token,
            apiBaseUrl: baseUrl,
            username,
            resendAvailableAt: payloadJson.resend_available_at,
          });

          return {
            requiresOtp: true,
            resendAvailableAt: payloadJson.resend_available_at,
          };
        }

        if (!response.ok) {
          const message = payloadJson?.message || "The username or password is incorrect.";
          const authError = new Error(message);
          authError.isAuthError = true;
          throw authError;
        }

        return completeSignIn(payloadJson, baseUrl, username);
      } catch (error) {
        if (error?.isAuthError) {
          lastAuthError = error;
          continue;
        }

        lastNetworkError = error;
      }
    }

    throw (
      lastAuthError ||
      lastNetworkError ||
      new Error("Unable to verify credentials right now.")
    );
  }, [completeSignIn]);

  const verifyLoginOtp = useCallback(async (otp) => {
    if (!loginChallenge) {
      throw new Error("Your verification request expired. Please sign in again.");
    }

    const response = await fetchWithTimeout(
      buildApiUrl("/api/mobile/login/otp", loginChallenge.apiBaseUrl),
      {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          challenge_token: loginChallenge.challengeToken,
          otp: String(otp ?? "").trim(),
        }),
      },
      5000
    );
    const payloadJson = await response.json().catch(() => null);

    if (!response.ok) {
      throw new Error(payloadJson?.message || "Unable to verify the code. Please try again.");
    }

    return completeSignIn(payloadJson, loginChallenge.apiBaseUrl, loginChallenge.username);
  }, [completeSignIn, loginChallenge]);

  const resendLoginOtp = useCallback(async () => {
    if (!loginChallenge) {
      throw new Error("Your verification request expired. Please sign in again.");
    }

    const response = await fetchWithTimeout(
      buildApiUrl("/api/mobile/login/otp/resend", loginChallenge.apiBaseUrl),
      {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ challenge_token: loginChallenge.challengeToken }),
      },
      5000
    );
    const payloadJson = await response.json().catch(() => null);

    if (payloadJson?.resend_available_at) {
      setLoginChallenge((current) => current
        ? { ...current, resendAvailableAt: payloadJson.resend_available_at }
        : current);
    }

    if (!response.ok) {
      throw new Error(payloadJson?.message || "Unable to resend the verification code.");
    }

    return payloadJson;
  }, [loginChallenge]);

  const cancelLoginOtp = useCallback(() => setLoginChallenge(null), []);

  const signOut = useCallback(async () => {
    let logoutError = null;
    const rawSession = await SecureStore.getItemAsync(AUTH_STORAGE_KEY);
    if (rawSession) {
      const storedSession = JSON.parse(rawSession);
      if (storedSession?.access_token && storedSession?.api_base_url) {
        try {
          const response = await fetchWithTimeout(
            buildApiUrl("/api/mobile/logout", storedSession.api_base_url),
            {
              method: "POST",
              headers: {
                Accept: "application/json",
                Authorization: `Bearer ${storedSession.access_token}`,
              },
            },
            3000
          );
          if (!response.ok && response.status !== 401) {
            throw new Error(`Logout failed with status ${response.status}.`);
          }
        } catch (error) {
          logoutError = error;
        }
      }
    }

    await SecureStore.deleteItemAsync(AUTH_STORAGE_KEY);
    setSession(null);
    if (logoutError) {
      throw new Error("The mobile session was cleared locally, but the server could not revoke its access token.");
    }
  }, []);

  useEffect(() => {
    if (!session || isHydrating) {
      return undefined;
    }

    const enforceIdleTimeout = async () => {
      if (Date.now() - lastActivityAt.current >= IDLE_TIMEOUT_MS) {
        await SecureStore.deleteItemAsync(AUTH_STORAGE_KEY);
        setSession(null);
        return;
      }

      if (Date.now() - lastActivityPersistedAt.current >= ACTIVITY_PERSIST_INTERVAL_MS) {
        const activityAt = lastActivityAt.current;
        await SecureStore.setItemAsync(
          AUTH_STORAGE_KEY,
          JSON.stringify({ ...session, lastActivityAt: activityAt })
        );
        lastActivityPersistedAt.current = Date.now();
      }
    };

    const timer = setInterval(() => {
      void enforceIdleTimeout();
    }, 15 * 1000);
    const appStateSubscription = AppState.addEventListener("change", (state) => {
      if (state === "active") {
        void enforceIdleTimeout();
      }
    });

    return () => {
      clearInterval(timer);
      appStateSubscription.remove();
    };
  }, [session, isHydrating]);

  const recordActivity = useCallback(() => {
    if (session) {
      lastActivityAt.current = Date.now();
    }
  }, [session]);

  const value = useMemo(
    () => ({
      session,
      isHydrating,
      isAuthenticated: Boolean(session),
      signIn,
      verifyLoginOtp,
      resendLoginOtp,
      resendAvailableAt: loginChallenge?.resendAvailableAt ?? null,
      cancelLoginOtp,
      signOut,
    }),
    [session, isHydrating, signIn, verifyLoginOtp, resendLoginOtp, loginChallenge, cancelLoginOtp, signOut]
  );

  return (
    <AuthContext.Provider value={value}>
      <View style={{ flex: 1 }} onTouchStart={recordActivity}>
        {children}
      </View>
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error("useAuth must be used within AuthProvider");
  }

  return context;
}
