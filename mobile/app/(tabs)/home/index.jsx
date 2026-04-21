import { Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { useFetchLoggedUser } from "../../../hooks/useFetchLoggedUser";
import { APP_COLORS } from "../../../constants/theme";

export default function HomeScreen() {
  const { firstName, greeting } = useFetchLoggedUser();

  return (
    <SafeAreaView className="flex-1 font-sans" style={{ backgroundColor: APP_COLORS.background }} edges={[]}>
      <View className="px-4 py-3">
        <Text className="text-base text-[#1f2937]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          {greeting}, {firstName}!
        </Text>
      </View>
    </SafeAreaView>
  );
}

export const meta = {
  title: "Home",
};
