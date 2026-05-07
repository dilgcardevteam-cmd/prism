import { View, Text, Pressable } from "react-native";
import { APP_COLORS } from "../../../../constants/theme";
import { TYPOGRAPHY_DEFAULTS } from "../../../../constants/typography";

import { Feather } from "@expo/vector-icons";

// UTILITIES VALUES
const UTILITIES = [
    {
        id: 1,
        title: "Role Configuration",
        description: "Configure CRUD access by hierarchy role and apply it to every assigned user from one place.",
        icon: "users",
    },
    {
        id: 2,
        title: "Activity Logs",
        description: "Review immutable audit events for authentication, CRUD actions, role changes, and other sensitive system activity.",
        icon: "activity",
    },
    {
        id: 3,
        title: "Location Configuration",
        description: "Review and manage the location-related configuration used accross the application.",
        icon: "map-pin",
    },
    {
        id: 4,
        title: "Deadlines Configuration",
        description: "Review and maintain deadline settings used across project monitoring and reportorial workflows.",
        icon: "calendar",
    },
    {
        id: 5,
        title: "Bulk Notification",
        description: "Send announcement emails to role-based audiences and review the notification workspace.",
        icon: "bell",
    },
    {
        id: 6,
        title: "Database and Backups",
        description: "Download SQL backups, restore data, and maintain automated backup routines.",
        icon: "database",
    },
    {
        id: 7,
        title: "System Maintenance",
        description: "Maintenance tools and controls will be available here in an upcoming release.",
        icon: "tool",
    },
]

function UtilityCard({title, description, icon}) {
  return (
    <Pressable
      className="mx-4 mb-4 flex-row items-center rounded-2xl bg-white p-4"
      style={{
        shadowColor: "#000",
        shadowOpacity: 0.05,
        shadowRadius: 10,
        shadowOffset: { width: 0, height: 4 },
        elevation: 3,
      }}
    >

      <View
        className="mr-4 h-14 w-14 items-center justify-center rounded-2xl"
        style={{ backgroundColor: `${APP_COLORS.primary}15` }}
      >
        <Feather name={icon} size={24} color={APP_COLORS.primary} />
      </View>
        
      {/* TEXTS */}
      <View className="flex-1">
        <Text
          style={{
            color: APP_COLORS.primary,
            fontFamily: TYPOGRAPHY_DEFAULTS.fontFamily.semiBold,
            fontSize: 16,
          }}
        >
          {title}
        </Text>

        <Text
          className="mt-1 text-sm text-gray-600"
          style={{
            fontFamily: TYPOGRAPHY_DEFAULTS.fontFamily.regular,
          }}
        >
          {description}
        </Text>
      </View>


    </Pressable>
  )
}

export default function () {
  return (
    <View>
      {UTILITIES.map((item) => (
        <UtilityCard
          key={item.id}
          title={item.title}
          description={item.description}
          icon={item.icon}
        />
      ))}
    </View>
  )
}