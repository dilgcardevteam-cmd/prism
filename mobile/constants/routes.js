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
  utilities: "/(tabs)/utilities",
};

export const TAB_ROUTES = [
  {
    route: "home/index",
    title: "Home",
    icon: "grid"
  },
  
  // UTILITITTIES ============================================
  {
    route: "utilities/index",
    title: "Utilities",
    icon: "cog"
  },
  {
    route: "utilities/pages/role-configuration",
    title: "Role Configuration",
  },
  {
    route: "utilities/pages/activity-logs",
    title: "Activity Logs",
  },
  {
    route: "utilities/pages/location-configuration",
    title: "Location Configuration",
  },
  {
    route: "utilities/pages/deadlines-configuration",
    title: "Deadlines Configuration",
  },
  {
    route: "utilities/pages/bulk-notification",
    title: "Bulk Notification",
  },
  {
    route: "utilities/pages/database-and-backups",
    title: "Database and Backups",
  },
  {
    route: "utilities/pages/system-maintenance",
    title: "System Maintenance",
  },
];

export const PROJECT_MONITORING_ROUTES = [
  {
    route: "project-monitoring/locally-funded-projects/index",
    title: "LFP",
  },
  {
    route: "project-monitoring/locally-funded-projects/view-locally-funded-projects",
    title: "LFP Details",
  },
  {
    route: "project-monitoring/locally-funded-projects/gallery-image-location",
    title: "",
  },
  {
    route: "project-monitoring/rlip-lime-20-development-fund/index",
    title: "RLIP/LIME-20% Development Fund",
  },
  {
    route: "project-monitoring/project-at-risk/index",
    title: "Project At Risk",
  },
  {
    route: "project-monitoring/sglgif-portal/index",
    title: "SGLGIF Portal",
  },
  {
    route: "profile/index",
    title: "Profile",
  },
  {
    route: "message/new",
    title: "",
  },
];
