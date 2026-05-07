import {
	View,
	Text,
	ScrollView,
	Pressable
} from "react-native";
import { APP_COLORS } from "../../../constants/theme";
import { TYPOGRAPHY_DEFAULTS } from "../../../constants/typography";

import UtilityCard from "./components/UtilityCard";

export default function UtilitiesScreen() {
  return (
		<ScrollView
			style={{ flex: 1, backgroundColor: APP_COLORS.background }}
		>
			<View className="p-4">
				<Text style={{ color: APP_COLORS.primary, fontSize: 16, fontFamily: TYPOGRAPHY_DEFAULTS.fontFamily.semiBold }}>
					Configuration Overview
				</Text>
				<Text
					style={{fontFamily: TYPOGRAPHY_DEFAULTS.fontFamily.italic}}
					className="text-sm text-gray-600">
					Open a dedicated configuration page for each system area instead of managing everything from one screen.
				</Text>
			</View>

			{/* UTILITIES CARDS */}
			<UtilityCard/>
		</ScrollView>
	);
}
