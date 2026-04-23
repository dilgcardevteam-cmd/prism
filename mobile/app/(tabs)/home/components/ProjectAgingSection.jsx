import RiskDonutSectionWithSheet from "./RiskDonutSectionWithSheet";

const PROJECT_AGING_ORDER = ["High Risk", "Low Risk", "No Risk"];

export default function ProjectAgingSection({
  isLoadingSummary,
  summaryError,
  projectAtRiskAgingRows,
  projectAtRiskAgingTotal,
  donutSize,
  riskLegendWidth,
  isNarrowRiskLayout,
}) {
  return (
    <RiskDonutSectionWithSheet
      isLoadingSummary={isLoadingSummary}
      summaryError={summaryError}
      title="Aging of the Projects with Slippage"
      subtitle="Project aging grouped by risk thresholds from latest extracted records."
      loadingText="Loading project aging summary..."
      errorTitle="Unable to load project aging summary."
      emptyText="No project aging records available yet."
      rows={projectAtRiskAgingRows}
      total={projectAtRiskAgingTotal}
      order={PROJECT_AGING_ORDER}
      donutSize={donutSize}
      isNarrowRiskLayout={isNarrowRiskLayout}
    />
  );
}
