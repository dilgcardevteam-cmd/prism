import RiskDonutSectionWithSheet from "./RiskDonutSectionWithSheet";

const PROJECT_UPDATE_STATUS_ORDER = ["High Risk", "Low Risk", "No Risk"];

export default function ProjectUpdateStatusSection({
  isLoadingSummary,
  summaryError,
  projectUpdateStatusRows,
  projectUpdateStatusTotal,
  donutSize,
  riskLegendWidth,
  isNarrowRiskLayout,
}) {
  return (
    <RiskDonutSectionWithSheet
      isLoadingSummary={isLoadingSummary}
      summaryError={summaryError}
      title="Project Update Status Dashboard"
      subtitle="Risk bands based on latest project update date from SubayBAYAN records."
      loadingText="Loading project update status summary..."
      errorTitle="Unable to load project update status summary."
      emptyText="No project update status records available yet."
      rows={projectUpdateStatusRows}
      total={projectUpdateStatusTotal}
      order={PROJECT_UPDATE_STATUS_ORDER}
      donutSize={donutSize}
      isNarrowRiskLayout={isNarrowRiskLayout}
    />
  );
}
