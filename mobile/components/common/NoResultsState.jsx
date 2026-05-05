import LottieView from "lottie-react-native";
import { Text, View } from "react-native";

export default function NoResultsState({
  title = "No results found",
  description = "Try another keyword or adjust your filters.",
  animationStyle,
  containerClassName = "rounded-2xl border border-[#dbe3f0] bg-white px-4 py-5",
  titleClassName = "text-[15px] font-semibold text-[#1e3a8a]",
  descriptionClassName = "mt-1 text-[12px] leading-[18px] text-[#64748b]",
}) {
  return (
    <View className={containerClassName}>
      <View className="items-center justify-center">
        <LottieView
          source={require("../../assets/animations/no-result.json")}
          autoPlay
          loop
          style={[{ width: 300, height: 300 }, animationStyle]}
        />
      </View>

      <Text className={titleClassName}>{title}</Text>
      {description ? <Text className={descriptionClassName}>{description}</Text> : null}
    </View>
  );
}