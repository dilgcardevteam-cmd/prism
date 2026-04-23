import { Feather } from "@expo/vector-icons";
import { useMemo } from "react";
import { FlatList, Modal, Pressable, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import Animated, { Easing, FadeIn, FadeOut, SlideInDown, SlideOutDown } from "react-native-reanimated";

function CompletionProjectCard({ item }) {
  const locationParts = [item.province, item.cityMunicipality].filter(Boolean);
  const locationLabel = locationParts.length > 0 ? locationParts.join(" | ") : "Location unavailable";

  return (
    <View className="mb-3 rounded-[16px] border border-[#d9e6ff] bg-[#f8fbff] px-4 py-3">
      <View className="flex-row items-start justify-between gap-3">
        <Text
          className="flex-1 uppercase text-[#173e8c]"
          style={{ fontFamily: "Montserrat-SemiBold", fontSize: 12, lineHeight: 16, letterSpacing: 0.2 }}
          numberOfLines={1}
        >
          {item.projectCode}
        </Text>

        <Text
          className="text-right text-[#0f766e]"
          style={{ fontFamily: "Montserrat-SemiBold", fontSize: 12, lineHeight: 16 }}
          numberOfLines={1}
        >
          {item.expectedCompletionDate}
        </Text>
      </View>

      <Text
        className="mt-1 text-[#111827]"
        style={{ fontFamily: "Montserrat-SemiBold", fontSize: 15, lineHeight: 20 }}
        numberOfLines={2}
      >
        {item.projectTitle}
      </Text>

      <View className="mt-1 flex-row items-center">
        <Feather name="map-pin" size={12} color="#64748b" />
        <Text
          className="ml-1 uppercase text-[#64748b]"
          style={{ fontFamily: "Montserrat", fontSize: 11, lineHeight: 15, letterSpacing: 0.2 }}
          numberOfLines={1}
        >
          {locationLabel}
        </Text>
      </View>
    </View>
  );
}

export default function ProjectsExpectedCompletionSheet({ visible, onClose, projects, monthLabel }) {
  const insets = useSafeAreaInsets();

  const sortedProjects = useMemo(() => {
    return [...projects].sort((left, right) => {
      const leftDate = left.expectedCompletionDate || "";
      const rightDate = right.expectedCompletionDate || "";
      if (leftDate !== rightDate) {
        return leftDate.localeCompare(rightDate);
      }

      return String(left.projectCode).localeCompare(String(right.projectCode));
    });
  }, [projects]);

  if (!visible) {
    return null;
  }

  return (
    <Modal transparent visible={visible} animationType="none" onRequestClose={onClose} statusBarTranslucent>
      <Animated.View entering={FadeIn.duration(160)} exiting={FadeOut.duration(140)} className="flex-1 justify-end bg-black/35">
        <Pressable className="flex-1" onPress={onClose} />

        <Animated.View
          entering={SlideInDown.duration(220).easing(Easing.out(Easing.cubic))}
          exiting={SlideOutDown.duration(180).easing(Easing.in(Easing.cubic))}
          className="overflow-hidden rounded-t-[28px] bg-white"
          style={{ maxHeight: "86%", paddingBottom: Math.max(insets.bottom, 12) }}
        >
          <View className="items-center pt-3">
            <View className="h-1.5 w-12 rounded-full bg-[#d7dfea]" />
          </View>

          <View className="flex-row items-start justify-between px-4 pt-4">
            <View className="flex-1 pr-3 flex-row items-center gap-2">
              <View className="h-9 w-9 items-center justify-center rounded-full bg-[#dbeafe]">
                <Feather name="calendar" size={18} color="#2563eb" />
              </View>
              <View className="flex-1">
                <Text className="uppercase tracking-[0.8px] text-[#173e8c]" style={{ fontFamily: "Montserrat-SemiBold", fontSize: 16, lineHeight: 20 }}>
                  Projects Expected to be Completed ({monthLabel})
                </Text>
              </View>
            </View>

            <Pressable onPress={onClose} hitSlop={10} className="pt-0.5">
              <Feather name="x" size={22} color="#173e8c" />
            </Pressable>
          </View>

          <Text className="px-4 pt-1 text-[12px] text-[#64748b]" style={{ fontFamily: "Montserrat" }}>
            Projects with target completion dates falling within the selected month.
          </Text>

          <FlatList
            className="px-4 pt-4"
            data={sortedProjects}
            keyExtractor={(item, index) => `${item.projectCode || "project"}-${item.expectedCompletionDate || "date"}-${index}`}
            showsVerticalScrollIndicator={false}
            contentContainerStyle={{ paddingBottom: 20 }}
            renderItem={({ item }) => <CompletionProjectCard item={item} />}
            ListEmptyComponent={
              <View className="rounded-[16px] border border-dashed border-[#cbd5e1] bg-[#f8fafc] px-4 py-6">
                <Text className="text-[13px] text-[#475569]" style={{ fontFamily: "Montserrat" }}>
                  No projects are scheduled for completion this month.
                </Text>
              </View>
            }
          />
        </Animated.View>
      </Animated.View>
    </Modal>
  );
}
