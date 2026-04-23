import { PROJECT_RISK_DONUT_ORDER } from "../../../../constants/homeDashboardConfig";
import RiskDonutSectionWithSheet from "./RiskDonutSectionWithSheet";

export default function ProjectRiskSection({ isLoadingSummary, summaryError, projectAtRiskSlippageRows, projectAtRiskSlippageTotal, donutSize, riskLegendWidth, riskPanelHeight, isNarrowRiskLayout }) {
  return (
    <RiskDonutSectionWithSheet
      isLoadingSummary={isLoadingSummary}
      summaryError={summaryError}
      title="Project At Risk as to Slippage"
      subtitle="Projects with slippages extracted in the SubayBAYAN Portal."
      loadingText="Loading slippage risk summary..."
      errorTitle="Unable to load slippage risk summary."
      emptyText="No slippage risk records available yet."
      rows={projectAtRiskSlippageRows}
      total={projectAtRiskSlippageTotal}
      order={PROJECT_RISK_DONUT_ORDER}
      donutSize={donutSize}
      isNarrowRiskLayout={isNarrowRiskLayout}
    />
  );
}