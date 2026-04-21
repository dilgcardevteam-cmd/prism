import { Feather } from "@expo/vector-icons";
import { useMemo } from "react";
import { Text, View } from "react-native";

function formatDate(value) {
  if (!value) {
    return "N/A";
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return String(value);
  }

  return parsed.toLocaleDateString("en-PH", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "N/A";
  }

  return `${Number(value).toFixed(2)}%`;
}

function StatusPill({ value }) {
  const raw = String(value ?? "").trim();
  const normalized = raw.toLowerCase();

  let colors = {
    background: "#e2e8f0",
    text: "#334155",
  };

  if (!raw || normalized === "n/a" || normalized === "-") {
    colors = {
      background: "#f1f5f9",
      text: "#64748b",
    };
  } else if (normalized.includes("complete") || normalized.includes("completed")) {
    colors = {
      background: "#dcfce7",
      text: "#166534",
    };
  } else if (normalized.includes("on-track") || normalized.includes("on track")) {
    colors = {
      background: "#dbeafe",
      text: "#1d4ed8",
    };
  } else if (normalized.includes("delay") || normalized.includes("risk") || normalized.includes("critical")) {
    colors = {
      background: "#fee2e2",
      text: "#991b1b",
    };
  }

  return (
    <View className="self-start rounded-full px-2.5 py-1" style={{ backgroundColor: colors.background }}>
      <Text className="text-[11px]" style={{ color: colors.text, fontFamily: "Montserrat-SemiBold" }}>
        {raw || "N/A"}
      </Text>
    </View>
  );
}

function SummaryCard({ label, value, isStatus = false }) {
  return (
    <View className="mb-2.5 w-[48.8%] rounded-xl border border-[#d7e2f5] bg-[#f8fbff] px-3 py-2.5">
      <Text className="text-[11px] text-[#6c7ea7]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        {label}
      </Text>
      {isStatus ? (
        <View className="mt-2">
          <StatusPill value={value} />
        </View>
      ) : (
        <Text className="mt-1 text-[13px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          {value || "N/A"}
        </Text>
      )}
    </View>
  );
}

function TrendValue({ current, previous, type = "percent" }) {
  const hasCurrent = current !== null && current !== undefined && !Number.isNaN(Number(current));
  if (!hasCurrent) {
    return (
      <Text className="text-[12px] text-[#64748b]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        N/A
      </Text>
    );
  }

  const currentNumber = Number(current);
  const previousNumber = previous !== null && previous !== undefined && !Number.isNaN(Number(previous))
    ? Number(previous)
    : null;

  let icon = "minus";
  let color = "#64748b";

  if (previousNumber !== null) {
    if (currentNumber > previousNumber) {
      icon = "trending-up";
      color = "#15803d";
    } else if (currentNumber < previousNumber) {
      icon = "trending-down";
      color = "#b91c1c";
    }
  }

  const displayValue = type === "percent" ? formatPercent(currentNumber) : String(currentNumber);

  return (
    <View className="flex-row items-center">
      <Feather name={icon} size={13} color={color} />
      <Text className="ml-1 text-[12px]" style={{ color, fontFamily: "Montserrat-SemiBold" }}>
        {displayValue}
      </Text>
    </View>
  );
}

function MetricRow({ label, value, previous, isStatus = false }) {
  return (
    <View className="mb-3">
      <Text className="text-[11px] text-[#6b7ea6]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        {label}
      </Text>
      <View className="mt-1">
        {isStatus ? <StatusPill value={value} /> : <TrendValue current={value} previous={previous} />}
      </View>
    </View>
  );
}

function TimelineItem({ entry, previousEntry }) {
  return (
    <View className="mb-4 flex-row items-stretch">
      <View className="items-center pt-1">
        <View className="h-9 w-9 items-center justify-center rounded-full border border-[#aac2ef] bg-[#e9f1ff]">
          <Text className="text-[10px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
            {entry.month_short || "---"}
          </Text>
        </View>
        <View className="mt-1 h-full w-[1px] bg-[#d3dff5]" />
      </View>

      <View className="ml-3 flex-1 rounded-2xl border border-[#d7e2f5] bg-white px-3.5 py-3">
        <View className="flex-row items-center justify-between">
          <View>
            <Text className="text-[11px] text-[#6b7ea6]" style={{ fontFamily: "Montserrat" }}>
              Timeline Point
            </Text>
            <Text className="text-[14px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
              {entry.month_label} {entry.year}
            </Text>
          </View>
          <View className="rounded-full bg-[#ecf3ff] px-2.5 py-1">
            <Text className="text-[11px] text-[#2a4f93]" style={{ fontFamily: "Montserrat-SemiBold" }}>
              {String(entry.month_number ?? "").padStart(2, "0")}
            </Text>
          </View>
        </View>

        <View className="mt-3 rounded-xl bg-[#f8fbff] px-3 py-2.5">
          <View className="flex-row justify-between">
            <View className="w-[48%]">
              <MetricRow label="Risk" value={entry.risk_aging} isStatus />
              <MetricRow label="NC Letters" value={entry.nc_letters} isStatus />
            </View>
            <View className="w-[48%]">
              <MetricRow label="FOU Status" value={entry.status_project_fou} isStatus />
              <MetricRow
                label="FOU Accomplishment"
                value={entry.accomplishment_pct}
                previous={previousEntry?.accomplishment_pct}
              />
              <MetricRow
                label="FOU Slippage"
                value={entry.slippage}
                previous={previousEntry?.slippage}
              />
            </View>
          </View>

          <View className="mt-1 border-t border-[#deebff] pt-2.5">
            <MetricRow label="RO Status" value={entry.status_project_ro} isStatus />
            <MetricRow
              label="RO Accomplishment"
              value={entry.accomplishment_pct_ro}
              previous={previousEntry?.accomplishment_pct_ro}
            />
            <MetricRow
              label="RO Slippage"
              value={entry.slippage_ro}
              previous={previousEntry?.slippage_ro}
            />
          </View>
        </View>
      </View>
    </View>
  );
}

export default function PhysicalAccomplishment({ project }) {
  const fallbackPhysical = useMemo(() => {
    const roStatus = project?.statusSubaybayan;
    const fouStatus = project?.statusActual;
    const roAccomplishment = project?.physicalStatus;

    const hasAnyValue = (
      (roStatus && String(roStatus).trim() && String(roStatus).trim() !== "-")
      || (fouStatus && String(fouStatus).trim() && String(fouStatus).trim() !== "-")
      || (roAccomplishment !== null && roAccomplishment !== undefined && !Number.isNaN(Number(roAccomplishment)))
    );

    if (!hasAnyValue) {
      return null;
    }

    const nowDate = new Date();
    const monthNumber = nowDate.getMonth() + 1;
    const monthLabel = nowDate.toLocaleString("en-PH", { month: "long" });

    return {
      year: nowDate.getFullYear(),
      month_number: monthNumber,
      month_label: monthLabel,
      month_short: monthLabel.slice(0, 3),
      status_project_fou: fouStatus && String(fouStatus).trim() !== "-" ? fouStatus : null,
      status_project_ro: roStatus && String(roStatus).trim() !== "-" ? roStatus : null,
      accomplishment_pct: null,
      accomplishment_pct_ro: roAccomplishment !== null && roAccomplishment !== undefined && !Number.isNaN(Number(roAccomplishment))
        ? Number(roAccomplishment)
        : null,
      slippage: null,
      slippage_ro: null,
      risk_aging: null,
      nc_letters: null,
      has_data: true,
    };
  }, [project?.physicalStatus, project?.statusActual, project?.statusSubaybayan]);

  const timelineEntries = useMemo(() => {
    const source = Array.isArray(project?.physicalTimeline) ? project.physicalTimeline : [];
    const withFallback = source.length === 0 && fallbackPhysical ? [fallbackPhysical] : source;

    return [...withFallback].sort((a, b) => {
      const yearDiff = Number(a?.year ?? 0) - Number(b?.year ?? 0);
      if (yearDiff !== 0) {
        return yearDiff;
      }
      return Number(a?.month_number ?? 0) - Number(b?.month_number ?? 0);
    });
  }, [fallbackPhysical, project?.physicalTimeline]);

  const timelineByYear = useMemo(() => {
    return timelineEntries.reduce((accumulator, entry) => {
      const key = String(entry?.year ?? "Unknown");
      if (!accumulator[key]) {
        accumulator[key] = [];
      }
      accumulator[key].push(entry);
      return accumulator;
    }, {});
  }, [timelineEntries]);

  const yearKeys = useMemo(() => Object.keys(timelineByYear).sort((a, b) => Number(b) - Number(a)), [timelineByYear]);

  const currentPhysical = project?.currentPhysical
    ?? (timelineEntries.length > 0 ? timelineEntries[timelineEntries.length - 1] : null)
    ?? fallbackPhysical;
  const actualDateCompletion = formatDate(project?.actualDateCompletion);

  return (
    <View className="mt-3 rounded-2xl border border-[#d7e2f5] bg-white px-4 py-4">
      <View className="rounded-2xl border border-[#d6e6ff] bg-[#f5f9ff] px-3.5 py-3">
        <Text className="text-[11px] uppercase tracking-[0.6px] text-[#4866a6]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          Progress Snapshot
        </Text>
        <Text className="mt-1 text-[16px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          Physical Accomplishment
        </Text>
        <Text className="mt-1 text-[12px] leading-[18px] text-[#4f648f]" style={{ fontFamily: "Montserrat" }}>
          Monthly delivery trend across FOU and RO updates.
        </Text>
      </View>

      <View className="mt-3 flex-row flex-wrap justify-between">
        <SummaryCard label="Current FOU Status" value={currentPhysical?.status_project_fou} isStatus />
        <SummaryCard label="Current RO Status" value={currentPhysical?.status_project_ro} isStatus />
        <SummaryCard label="FOU Accomplishment" value={formatPercent(currentPhysical?.accomplishment_pct)} />
        <SummaryCard label="RO Accomplishment" value={formatPercent(currentPhysical?.accomplishment_pct_ro)} />
        <SummaryCard label="Risk As To Aging" value={currentPhysical?.risk_aging} isStatus />
        <SummaryCard label="NC Letters" value={currentPhysical?.nc_letters} isStatus />
      </View>

      <View className="mt-1 rounded-xl bg-[#f8fbff] px-3 py-2.5">
        <Text className="text-[11px] text-[#6b7ea6]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          Actual Date of Completion
        </Text>
        <Text className="mt-0.5 text-[13px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          {actualDateCompletion}
        </Text>
      </View>

      <View className="mt-4 border-b border-[#d7e2f5] pb-2">
        <Text className="text-[14px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          Physical Timeline
        </Text>
      </View>

      {timelineEntries.length === 0 ? (
        <View className="mt-4 rounded-xl border border-dashed border-[#c7d8f2] bg-[#f8fbff] px-3 py-3">
          <Text className="text-[12px] text-[#5c719b]" style={{ fontFamily: "Montserrat" }}>
            No physical accomplishment updates have been logged yet.
          </Text>
        </View>
      ) : (
        <View className="mt-4">
          {yearKeys.map((yearKey) => (
            <View key={yearKey} className="mb-3">
              <View className="mb-2 rounded-lg bg-[#eef4ff] px-3 py-2">
                <Text className="text-[12px] text-[#2f4f8f]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  {yearKey}
                </Text>
              </View>
              {timelineByYear[yearKey].map((entry, index) => {
                const previous = index > 0 ? timelineByYear[yearKey][index - 1] : null;
                return (
                  <TimelineItem
                    key={`${yearKey}-${entry.month_number}-${index}`}
                    entry={entry}
                    previousEntry={previous}
                  />
                );
              })}
            </View>
          ))}
        </View>
      )}
    </View>
  );
}
