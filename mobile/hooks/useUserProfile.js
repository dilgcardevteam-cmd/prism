import { useCallback, useEffect, useState } from "react";
import { useAuth } from "../contexts/AuthContext";
import { useWebAppRequest } from "./useWebAppRequest";

export function useUserProfile() {
  const { session, isHydrating } = useAuth();
  const { fetchJsonWithFallback } = useWebAppRequest();
  const [profile, setProfile] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState(null);

  const loadProfile = useCallback(async () => {
    try {
      setIsLoading(true);
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
        setErrorMessage("Failed to load profile data");
      }
    } catch (error) {
      setErrorMessage(
        error?.message || "Unable to load profile. Please try again."
      );
    } finally {
      setIsLoading(false);
    }
  }, [fetchJsonWithFallback]);

  useEffect(() => {
    if (!isHydrating && session?.id) {
      loadProfile();
    }
  }, [isHydrating, session?.id, loadProfile]);

  return {
    profile: profile || session, // Fallback to session data
    isLoading,
    errorMessage,
    refreshProfile: loadProfile,
  };
}
