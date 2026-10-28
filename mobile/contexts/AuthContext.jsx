import * as SecureStore from "expo-secure-store";
import { createContext, useContext, useEffect, useMemo, useState } from "react";

import { API_CANDIDATE_BASE_URLS, buildApiUrl } from "../constants/api";

const AUTH_STORAGE_KEY = "pdmuoms.mobile.auth.session";
const AUTH_STORAGE_VERSION = 3;

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
  const [isHydrating, setIsHydrating] = useState(true);

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

        setSession(parsedSession);
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

  const signIn = async (payload) => {
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

        if (!response.ok) {
          const message = payloadJson?.message || "The username or password is incorrect.";
          const authError = new Error(message);
          authError.isAuthError = true;
          throw authError;
        }

        const user = payloadJson?.user || {};
        const accessToken = payloadJson?.access_token;
        if (typeof accessToken !== "string" || accessToken.length === 0) {
          throw new Error("The server did not issue a mobile access token.");
        }

        const nextSession = {
          authVersion: AUTH_STORAGE_VERSION,
          authenticatedAt: Date.now(),
          access_token: accessToken,
          api_base_url: baseUrl,
          id: user.id ?? null,
          username: user.username || username,
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

        await SecureStore.setItemAsync(
          AUTH_STORAGE_KEY,
          JSON.stringify(nextSession)
        );
        setSession(nextSession);
        return nextSession;
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
  };

  const signOut = async () => {
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
  };

  const value = useMemo(
    () => ({
      session,
      isHydrating,
      isAuthenticated: Boolean(session),
      signIn,
      signOut,
    }),
    [session, isHydrating]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error("useAuth must be used within AuthProvider");
  }

  return context;
}
