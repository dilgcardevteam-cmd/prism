import { useMemo } from "react";

import { useAuth } from "../contexts/AuthContext";
import { useTime } from "./useTime";

function getTimeBasedGreeting(hour) {

  if (hour < 12) {
    return "Good morning";
  }

  if (hour < 18) {
    return "Good afternoon";
  }

  return "Good evening";
}

export function useFetchLoggedUser() {
  const { session, isHydrating } = useAuth();
  const { hour } = useTime({ timeZone: "Asia/Manila", locale: "en-PH" });

  const firstName = useMemo(() => {
    const firstNameSource =
      session?.first_name ||
      session?.fname ||
      session?.firstName ||
      session?.username ||
      "User";

    return String(firstNameSource).trim().split(/[\s._-]+/)[0] || "User";
  }, [session]);

  const greeting = useMemo(() => getTimeBasedGreeting(hour), [hour]);

  return {
    userInfo: session,
    firstName,
    greeting,
    isHydrating,
  };
}
