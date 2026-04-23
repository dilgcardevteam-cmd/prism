import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useMemo, useState } from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { APP_ROUTES } from "../../../../constants/routes";
import ProjectProfile from "./sections/ProjectProfile";
import ContractInformation from "./sections/ContractInformation";
import PhysicalAccomplishment from "./sections/PhysicalAccomplishment";
import FinancialAccomplishment from "./sections/FinancialAccomplishment";
import MonitoringInspectionActivities from "./sections/MonitoringInspectionActivities";
import PostImplementation from "./sections/PostImplementation";
import Gallery from "./sections/Gallery";

const SECTION_TABS = [
  { key: "project-profile", label: "Project Profile" },
  { key: "contract-information", label: "Contract Information" },
  { key: "physical-accomplishment", label: "Physical Accomplishment" },
  { key: "financial-accomplishment", label: "Financial Accomplishment" },
  { key: "monitoring-inspection", label: "Monitoring/Inspection Activities" },
  { key: "post-implementation", label: "Post Implementation" },
  { key: "gallery", label: "Gallery" },
];

function parseProjectParam(rawValue) {
  if (typeof rawValue !== "string" || !rawValue.trim()) {
    return null;
  }

  try {
    return JSON.parse(rawValue);
  } catch (_error) {
    return null;
  }
}

function SectionPill({ label, isActive, onPress }) {
  return (
    <Pressable
      onPress={onPress}
      className={`mr-2 rounded-full border px-4 py-2 ${
        isActive ? "border-[#0b3d91] bg-[#0b3d91]" : "border-[#b7c7e6] bg-[#f1f5fb]"
      }`}
      accessibilityRole="button"
      accessibilityLabel={`Open ${label}`}
    >
      <Text
        className={`text-[12px] ${isActive ? "text-white" : "text-[#0b3d91]"}`}
        style={{ fontFamily: "Montserrat-SemiBold" }}
      >
        {label}
      </Text>
    </Pressable>
  );
}



export default function ViewLocallyFundedProjectsScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const initialSectionKey =
    typeof params.section === "string" && SECTION_TABS.some((section) => section.key === params.section)
      ? params.section
      : "project-profile";
  const [activeSectionKey, setActiveSectionKey] = useState(initialSectionKey);

  const project = useMemo(() => parseProjectParam(params.project), [params.project]);

  const projectTitle = String(project?.title ?? "Unknown Project");

  const activeSection = useMemo(
    () => SECTION_TABS.find((section) => section.key === activeSectionKey) || SECTION_TABS[0],
    [activeSectionKey]
  );

  const renderActiveSection = () => {
    switch (activeSectionKey) {
      case "project-profile":
        return <ProjectProfile project={project} />;
      case "contract-information":
        return <ContractInformation project={project} />;
      case "physical-accomplishment":
        return <PhysicalAccomplishment project={project} />;
      case "financial-accomplishment":
        return <FinancialAccomplishment />;
      case "monitoring-inspection":
        return <MonitoringInspectionActivities />;
      case "post-implementation":
        return <PostImplementation />;
      case "gallery":
        return <Gallery project={project} />;
      default:
        return <ProjectProfile project={project} />;
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-[#f1eff5]" edges={["left", "right"]}>
      <ScrollView className="flex-1" contentContainerStyle={{ paddingBottom: 18 }}>
      <View className="px-4 pt-4">
        <View className="flex-row items-start">
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Go back to projects list"
            onPress={() => router.push(APP_ROUTES.projectMonitoring.locallyFundedProjects)}
            className="mr-2 mt-0.5 h-7 w-7 items-center justify-center rounded-full"
            hitSlop={8}
          >
            <Feather name="chevron-left" size={24} color="#0f2f7a" />
          </Pressable>

          <Text
            className="flex-1 text-[16px] leading-[22px] text-[#0f2f7a]"
            style={{ fontFamily: "Montserrat-SemiBold" }}
          >
            {projectTitle}
          </Text>
        </View>

        <View className="mt-3 border-b border-[#b8bdc9]" />

        <View className="mt-3">
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={{ minHeight: 44 }}
            contentContainerStyle={{ paddingRight: 12, alignItems: "center" }}
          >
            {SECTION_TABS.map((section) => (
              <SectionPill
                key={section.key}
                label={section.label}
                isActive={activeSection.key === section.key}
                onPress={() => setActiveSectionKey(section.key)}
              />
            ))}
          </ScrollView>
        </View>

        {renderActiveSection()}
      </View>
      </ScrollView>
    </SafeAreaView>
  );
}

export const meta = {
  title: "View Locally Funded Project",
};
