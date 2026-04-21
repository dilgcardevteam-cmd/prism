export const APP_ROUTES = {
  login: "/",
  homeTab: "/(tabs)/home",
  message: "/(tabs)/message",
  notifications: "/(tabs)/notifications",
  settings: "/(tabs)/settings",
  projectMonitoring: {
    locallyFundedProjects: "/(tabs)/project-monitoring/locally-funded-projects",
    viewLocallyFundedProject: "/(tabs)/project-monitoring/locally-funded-projects/view-locally-funded-projects",
    locallyFundedGalleryLocation: "/(tabs)/project-monitoring/locally-funded-projects/gallery-image-location",
    rlipLimeDevelopmentFund: "/(tabs)/project-monitoring/rlip-lime-20-development-fund",
    projectAtRisk: "/(tabs)/project-monitoring/project-at-risk",
    sglgifPortal: "/(tabs)/project-monitoring/sglgif-portal",
  },
};

export const TAB_ROUTES = [
  { route: "home/index", title: "Home", icon: "grid" },
];

export const PROJECT_MONITORING_ROUTES = [
  {
    route: "project-monitoring/locally-funded-projects/index",
    title: "Locally Funded Projects",
  },
  {
    route: "project-monitoring/locally-funded-projects/view-locally-funded-projects",
    title: "Locally Funded Project Details",
  },
  {
    route: "project-monitoring/locally-funded-projects/gallery-image-location",
    title: "Image Location",
  },
  {
    route: "project-monitoring/rlip-lime-20-development-fund",
    title: "RLIP/LIME-20% Development Fund",
  },
  {
    route: "project-monitoring/project-at-risk",
    title: "Project At Risk",
  },
  {
    route: "project-monitoring/sglgif-portal",
    title: "SGLGIF Portal",
  },
];
