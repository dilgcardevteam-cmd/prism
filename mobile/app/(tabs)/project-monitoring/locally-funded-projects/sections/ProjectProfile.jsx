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

// Format currency values
const formatCurrency = (value) => {
  if (!value || isNaN(value)) return "N/A";
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 2,
  }).format(value);
};

// Format dates
const formatDate = (dateString) => {
  if (!dateString) return "N/A";
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-PH", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  } catch {
    return String(dateString);
  }
};

export default function ProjectProfile({ project }) {
  const projectCode = String(project?.code ?? "N/A");
  const fundingYear = String(project?.fundingYear ?? "N/A");
  const fundSource = String(project?.fundSource ?? "N/A");
  const projectDescription = String(project?.title ?? "N/A");
  const province = String(project?.province ?? "N/A");
  const city = String(project?.city ?? "N/A");
  const barangay = String(project?.barangay ?? "N/A");
  const projectType = String(project?.projectType ?? "N/A");
  const dateNadai = formatDate(project?.dateNadai);
  const numBeneficiaries = String(project?.numBeneficiaries ?? "N/A");
  const rainwaterSystem = String(project?.rainwaterSystem ?? "No");
  const dateConfirmation = formatDate(project?.dateConfirmation);
  const lgsfAllocation = formatCurrency(project?.lgsfAllocation);
  const lguCounterpart = formatCurrency(project?.lguCounterpart);

  return (
    <View className="mt-3 rounded-2xl border border-[#d7e2f5] bg-white px-4 py-4">
      <Text
        className="text-[16px] text-[#0f2f7a]"
        style={{ fontFamily: "Montserrat-SemiBold" }}
      >
        Project Profile
      </Text>

      <DetailSection title="BASIC INFORMATION">
        <DetailField label="Project Code" value={projectCode} icon="paperclip" />
        <DetailField label="Project Description" value={projectDescription} icon="file-text" />
        <DetailField label="Funding Year" value={fundingYear} icon="calendar" />
        <DetailField label="Funding Source" value={fundSource} icon="briefcase" />
      </DetailSection>

      <DetailSection title="LOCATION">
        <DetailField label="Province" value={province} icon="map-pin" />
        <DetailField label="City/Municipality" value={city} icon="map" />
        <DetailField label="Barangay" value={barangay} icon="home" />
      </DetailSection>

      <DetailSection title="PROJECT DETAILS">
        <DetailField label="Project Type" value={projectType} icon="layers" />
        <DetailField label="No. of Beneficiaries" value={numBeneficiaries} icon="users" />
        <DetailField label="Rainwater Collection System" value={rainwaterSystem} icon="droplet" />
      </DetailSection>

      <DetailSection title="FINANCIAL INFORMATION">
        <DetailField label="Date of NADAI" value={dateNadai} icon="calendar" />
        <DetailField label="Date of Confirmation Fund Receipt" value={dateConfirmation} icon="check-circle" />
        <DetailField label="LGSF Allocation" value={lgsfAllocation} icon="dollar-sign" />
        <DetailField label="LGU Counterpart" value={lguCounterpart} icon="dollar-sign" />
      </DetailSection>
    </View>
  );
}
