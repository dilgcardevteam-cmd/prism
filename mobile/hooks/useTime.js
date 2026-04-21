import { useEffect, useMemo, useState } from "react";

function getHourInTimeZone(dateValue, timeZone, locale) {
  const formatter = new Intl.DateTimeFormat(locale, {
    hour: "numeric",
    hour12: false,
    timeZone,
  });

  const hourValue = Number.parseInt(formatter.format(dateValue), 10);
  if (!Number.isFinite(hourValue)) {
    return 0;
  }

  return ((hourValue % 24) + 24) % 24;
}

export function useTime(options = {}) {
  const {
    timeZone = "Asia/Manila",
    locale = "en-PH",
    refreshIntervalMs = 60_000,
  } = options;

  const [now, setNow] = useState(() => new Date());

  useEffect(() => {
    const intervalId = setInterval(() => {
      setNow(new Date());
    }, refreshIntervalMs);

    return () => {
      clearInterval(intervalId);
    };
  }, [refreshIntervalMs]);

  const hour = useMemo(
    () => getHourInTimeZone(now, timeZone, locale),
    [now, timeZone, locale]
  );

  return {
    now,
    hour,
    timeZone,
  };
}
