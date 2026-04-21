import { Feather } from "@expo/vector-icons";
import { Text, View } from "react-native";

function DetailField({ label, value, icon = "info" }) {
  return (
    <View className="mt-3 flex-row items-start">
      <Feather name={icon} size={18} color="#0f2f7a" style={{ marginTop: 2 }} />
      <View className="ml-3 flex-1">
        <Text className="text-[12px] text-[#5a6b8e]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          {label}
        </Text>
        <Text
          className="mt-1 text-[14px] text-[#0f2f7a]"
          style={{ fontFamily: "Montserrat" }}
        >
          {value || "N/A"}
        </Text>
      </View>
    </View>
  );
}

function DetailSection({ title, children }) {
  return (
    <View className="mt-4 pt-3">
      <Text className="text-[13px] text-[#7a8ab0]" style={{ fontFamily: "Montserrat-SemiBold" }}>
        {title}
      </Text>
      {children}
    </View>
  );
}

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

function formatCurrency(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "N/A";
  }

  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 2,
  }).format(Number(value));
}

export default function ContractInformation({ project }) {
  const modeOfProcurement = String(project?.procurementType ?? "N/A");
  const datePostingItb = formatDate(project?.datePostingItb);
  const dateBidOpening = formatDate(project?.dateBidOpening);
  const dateNoa = formatDate(project?.dateNoa);
  const dateNtp = formatDate(project?.dateNtp);
  const contractor = String(project?.contractor ?? "N/A");
  const contractAmount = formatCurrency(project?.contractAmount);
  const projectDuration = String(project?.projectDuration ?? "N/A");
  const actualStartDate = formatDate(project?.actualStartDate);
  const targetDateCompletion = formatDate(project?.targetDateCompletion);
  const revisedTargetDate = formatDate(project?.revisedTargetDate);

  return (
    <View className="mt-3 rounded-2xl border border-[#d7e2f5] bg-white px-4 py-4">
      <Text
        className="text-[16px] text-[#0f2f7a]"
        style={{ fontFamily: "Montserrat-SemiBold" }}
      >
        Contract Information
      </Text>

      <DetailSection title="CONTRACT DETAILS">
        <DetailField label="Contractor" value={contractor} icon="user" />
        <DetailField label="Contract Amount" value={contractAmount} icon="dollar-sign" />
        <DetailField label="Project Duration" value={projectDuration} icon="clock" />
      </DetailSection>

      <DetailSection title="PROCUREMENT DETAILS">
        <DetailField label="Mode of Procurement" value={modeOfProcurement} icon="briefcase" />
        <DetailField label="Date of Posting (ITB)" value={datePostingItb} icon="calendar" />
        <DetailField label="Date of Bid Opening" value={dateBidOpening} icon="calendar" />
        <DetailField label="Date of NOA" value={dateNoa} icon="file-text" />
        <DetailField label="Date of NTP" value={dateNtp} icon="file-text" />
      </DetailSection>

      <DetailSection title="TIMELINE">
        <DetailField label="Actual Start Date" value={actualStartDate} icon="play-circle" />
        <DetailField label="Target Date of Completion" value={targetDateCompletion} icon="flag" />
        <DetailField label="Revised Target Date" value={revisedTargetDate} icon="refresh-cw" />
      </DetailSection>
    </View>
  );
}
