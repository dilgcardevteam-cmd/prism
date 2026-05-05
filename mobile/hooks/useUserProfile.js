import { useCallback, useEffect, useState } from "react";
import { useAuth } from "../contexts/AuthContext";
import { useWebAppRequest } from "./useWebAppRequest";

export function useUserProfile() {
  const { session, isHydrating } = useAuth();
  const { fetchJsonWithFallback } = useWebAppRequest();
  const [profile, setProfile] = useState(session?.id ? session : null);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState(null);

  const loadProfile = useCallback(async ({ silent = false } = {}) => {
    try {
      if (!silent) {
        setIsLoading(true);
      }
      setErrorMessage(null);

      const data = await fetchJsonWithFallback("/api/mobile/user/profile", {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
      });

      if (data?.user) {
        setProfile(data.user);
      } else {
        throw new Error("Failed to load profile data");
      }
    } catch (error) {
      if (session?.id) {
        setProfile((currentProfile) => currentProfile || session);
        setErrorMessage(null);
      } else {
        setErrorMessage(
          error?.message || "Unable to load profile. Please try again."
        );
      }
    } finally {
      if (!silent) {
        setIsLoading(false);
      }
    }
  }, [fetchJsonWithFallback, session]);

  useEffect(() => {
    if (isHydrating) {
      return;
    }

    if (session?.id) {
      setProfile(session);
      setErrorMessage(null);
      setIsLoading(false);
      loadProfile({ silent: true });
      return;
    }

    setIsLoading(false);
  }, [isHydrating, session, loadProfile]);

  return {
    profile: profile || session, // Fallback to session data
    isLoading,
    errorMessage,
    refreshProfile: loadProfile,
  };
}
