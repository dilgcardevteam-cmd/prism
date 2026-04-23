import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useEffect, useMemo, useState } from "react";
import { Image, Linking, Modal, Platform, Pressable, ScrollView, Text, View } from "react-native";
import { Gesture, GestureDetector, GestureHandlerRootView } from "react-native-gesture-handler";
import Animated, {
  Extrapolation,
  interpolate,
  useAnimatedScrollHandler,
  useAnimatedStyle,
  useSharedValue,
} from "react-native-reanimated";
import MapView, { Marker } from "react-native-maps";
import { SafeAreaView } from "react-native-safe-area-context";
import FloatingToast from "../../../../components/common/FloatingToast";
import { APP_ROUTES } from "../../../../constants/routes";

const VIEW_MODE = {
  OVERVIEW: "overview",
  MAP: "map",
};

const LOCATION_STATE = {
  READY_ONLINE: "LOCATION_READY_ONLINE",
  READY_NO_TILES: "LOCATION_READY_NO_TILES",
  MISSING: "LOCATION_MISSING",
  ERROR: "LOCATION_ERROR",
};

function parseCoordinate(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function parseAccuracy(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
}

function buildGoogleMapsUrl(latitude, longitude) {
  return `https://www.google.com/maps/search/?api=1&query=${latitude},${longitude}`;
}

function formatCoordinate(value) {
  if (!Number.isFinite(value)) {
    return "-";
  }

  return Number(value).toFixed(6);
}

function formatCapturedAt(value) {
  const rawValue = String(value || "").trim();
  if (!rawValue) {
    return "Not available";
  }

  const parsed = new Date(rawValue);
  if (Number.isNaN(parsed.getTime())) {
    return rawValue;
  }

  return parsed.toLocaleString("en-US", {
    month: "short",
    day: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    hour12: true,
  });
}

export default function GalleryImageLocationScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();

  const projectTitle = String(params.projectTitle || "Locally Funded Project");
  const projectCode = String(params.projectCode || "-");
  const stage = String(params.stage || "During");
  const imageUrl = String(params.imageUrl || "");
  const imageCreatedAt = String(params.imageCreatedAt || "");
  const imageCapturedBy = String(params.imageCapturedBy || "").trim();
  const markerTitle = String(projectTitle || "Locally Funded Project").trim() || "Locally Funded Project";
  const markerDescription = String(stage || "During").trim() || "During";

  const latitude = useMemo(() => parseCoordinate(params.latitude), [params.latitude]);
  const longitude = useMemo(() => parseCoordinate(params.longitude), [params.longitude]);
  const accuracy = useMemo(() => parseAccuracy(params.accuracy), [params.accuracy]);
  const [locationState, setLocationState] = useState(LOCATION_STATE.READY_ONLINE);
  const [toast, setToast] = useState({ visible: false, type: "info", message: "" });
  const [viewMode, setViewMode] = useState(VIEW_MODE.OVERVIEW);
  const [isViewerOpen, setIsViewerOpen] = useState(false);
  const [isMapFullscreenOpen, setIsMapFullscreenOpen] = useState(false);

  const scale = useSharedValue(1);
  const savedScale = useSharedValue(1);
  const translateX = useSharedValue(0);
  const translateY = useSharedValue(0);
  const savedTranslateX = useSharedValue(0);
  const savedTranslateY = useSharedValue(0);
  const scrollY = useSharedValue(0);

  const hasCoordinates = latitude !== null && longitude !== null;

  const initialRegion = useMemo(
    () =>
      hasCoordinates
        ? {
            latitude,
            longitude,
            latitudeDelta: 0.012,
            longitudeDelta: 0.012,
          }
        : null,
    [hasCoordinates, latitude, longitude]
  );

  useEffect(() => {
    if (!hasCoordinates) {
      setLocationState(LOCATION_STATE.MISSING);
      return;
    }

    setLocationState(LOCATION_STATE.READY_ONLINE);
  }, [hasCoordinates]);

  useEffect(() => {
    if (!toast.visible) {
      return undefined;
    }

    const timer = setTimeout(() => {
      setToast((current) => ({ ...current, visible: false }));
    }, 2600);

    return () => clearTimeout(timer);
  }, [toast.visible, toast.message]);

  const showToast = (type, message) => {
    setToast({ visible: true, type, message: String(message || "") });
  };

  const handleBackToGallery = () => {
    const serializedProject =
      typeof params.project === "string" && params.project.trim()
        ? params.project
        : "";

    if (serializedProject) {
      router.push({
        pathname: APP_ROUTES.projectMonitoring.viewLocallyFundedProject,
        params: {
          project: serializedProject,
          section: "gallery",
        },
      });
      return;
    }

    router.back();
  };

  const handleOpenExternalMap = async () => {
    if (!hasCoordinates) {
      showToast("warning", "No coordinates available for this image.");
      return;
    }

    try {
      const mapUrl = buildGoogleMapsUrl(latitude, longitude);
      const canOpen = await Linking.canOpenURL(mapUrl);

      if (!canOpen) {
        setLocationState(LOCATION_STATE.READY_NO_TILES);
        showToast("warning", "Internet connection or an external map app is required.");
        return;
      }

      await Linking.openURL(mapUrl);
      setLocationState(LOCATION_STATE.READY_ONLINE);
      showToast("success", "Opening map application.");
    } catch (_error) {
      setLocationState(LOCATION_STATE.ERROR);
      showToast("error", "Map could not be opened right now. Please retry.");
    }
  };

  const handleRetryMap = () => {
    if (!hasCoordinates) {
      return;
    }

    setLocationState(LOCATION_STATE.READY_ONLINE);
    showToast("info", "Map state refreshed.");
  };

  const openImageViewer = () => {
    if (!imageUrl) {
      return;
    }

    scale.value = 1;
    savedScale.value = 1;
    translateX.value = 0;
    translateY.value = 0;
    savedTranslateX.value = 0;
    savedTranslateY.value = 0;
    setIsViewerOpen(true);
  };

  const pinchGesture = Gesture.Pinch()
    .onUpdate((event) => {
      const nextScale = savedScale.value * event.scale;
      scale.value = Math.max(1, Math.min(nextScale, 4));

      if (scale.value <= 1) {
        translateX.value = 0;
        translateY.value = 0;
        savedTranslateX.value = 0;
        savedTranslateY.value = 0;
      }
    })
    .onEnd(() => {
      savedScale.value = scale.value;
    });

  const panGesture = Gesture.Pan()
    .onUpdate((event) => {
      if (scale.value <= 1) {
        return;
      }

      translateX.value = savedTranslateX.value + event.translationX;
      translateY.value = savedTranslateY.value + event.translationY;
    })
    .onEnd(() => {
      if (scale.value <= 1) {
        translateX.value = 0;
        translateY.value = 0;
        savedTranslateX.value = 0;
        savedTranslateY.value = 0;
        return;
      }

      savedTranslateX.value = translateX.value;
      savedTranslateY.value = translateY.value;
    });

  const zoomPanGesture = Gesture.Simultaneous(pinchGesture, panGesture);

  const animatedImageStyle = useAnimatedStyle(() => ({
    transform: [
      { translateX: translateX.value },
      { translateY: translateY.value },
      { scale: scale.value },
    ],
  }));

  const handleScroll = useAnimatedScrollHandler({
    onScroll: (event) => {
      scrollY.value = event.contentOffset.y;
    },
  });

  const heroParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(scrollY.value, [0, 260], [0, 85], Extrapolation.CLAMP),
      },
    ],
  }));

  const infoParallaxStyle = useAnimatedStyle(() => ({
    transform: [
      {
        translateY: interpolate(scrollY.value, [0, 260], [0, -18], Extrapolation.CLAMP),
      },
    ],
  }));

  const hasAccuracy = accuracy !== null;

  const handleOpenMapFullscreen = () => {
    if (!hasCoordinates) {
      showToast("warning", "No coordinates available for this image.");
      return;
    }

    setIsMapFullscreenOpen(true);
  };

  const handleCloseMapFullscreen = () => {
    setIsMapFullscreenOpen(false);
  };

  return (
    <SafeAreaView className="flex-1 bg-[#eceff3]" edges={["left", "right"]}>
      <FloatingToast visible={toast.visible} type={toast.type} message={toast.message} onClose={() => setToast((current) => ({ ...current, visible: false }))} />

      <Animated.ScrollView
        onScroll={handleScroll}
        scrollEventThrottle={16}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: 28 }}
      >
        <Animated.View className="relative" style={heroParallaxStyle}>
          <Pressable
            onPress={openImageViewer}
            accessibilityRole="button"
            accessibilityLabel="Open full image"
            className="overflow-hidden"
          >
            {imageUrl ? (
              <Image source={{ uri: imageUrl }} className="h-[290px] w-full bg-[#dce6f8]" resizeMode="cover" />
            ) : (
              <View className="h-[290px] items-center justify-center bg-[#d8e2f2]">
                <Feather name="image" size={28} color="#6e86ac" />
                <Text className="mt-3 text-[13px] text-[#486391]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  Image unavailable
                </Text>
              </View>
            )}
          </Pressable>

          <View className="absolute left-4 right-4 top-4 flex-row items-center justify-between">
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Go back"
              onPress={handleBackToGallery}
              className="h-11 w-11 items-center justify-center rounded-full bg-white/95"
            >
              <Feather name="chevron-left" size={21} color="#002C76" />
            </Pressable>

            {/* <View className="flex-row items-center gap-2">
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Back to project list"
                onPress={() => router.push(APP_ROUTES.projectMonitoring.locallyFundedProjects)}
                className="h-11 w-11 items-center justify-center rounded-full bg-white/95"
              >
                <Feather name="list" size={18} color="#0f172a" />
              </Pressable>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Open image preview"
                onPress={openImageViewer}
                className="h-11 w-11 items-center justify-center rounded-full bg-white/95"
              >
                <Feather name="eye" size={18} color="#0f172a" />
              </Pressable>
            </View> */}
          </View>

          <View className="absolute bottom-[15%] left-4">
            <View className="rounded-full bg-black/55 px-3 py-1.5">
              <Text className="text-[11px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                {stage}
              </Text>
            </View>
          </View>
        </Animated.View>

        <Animated.View className="-mt-7 rounded-[30px] border border-[#e2e8f0] bg-white px-5 py-5 shadow-xl shadow-black/10" style={infoParallaxStyle}>
          <Text className="text-[22px] leading-[30px] text-[#002C76]" numberOfLines={3} style={{ fontFamily: "Montserrat-SemiBold" }}>
            {projectTitle}
          </Text>

          <View className="mt-3 flex-col items-start gap-2">
            <View className="max-w-full flex-row items-center rounded-full bg-[#f3f4f6] px-2.5 py-1.5">
              <View className="h-3 w-3 rounded-full bg-[#65a30d]" />
              <Text className="ml-2 text-[12px] text-[#374151]" numberOfLines={1} style={{ fontFamily: "Montserrat-SemiBold" }}>
                {hasCoordinates ? "Coordinates captured" : "Location unavailable"}
              </Text>
            </View>

            <Text
              className="w-full text-[12px] text-[#374151] underline"
              numberOfLines={1}
              ellipsizeMode="middle"
              style={{ fontFamily: "Montserrat-SemiBold" }}
            >
              {projectCode}
            </Text>
          </View>

          <Text className="mt-4 text-[14px] leading-6 text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
            This gallery entry records the project stage and field location details for monitoring and verification.
          </Text>

          <View className="mt-5 flex-row rounded-2xl bg-[#f3f4f6] p-1.5">
            <Pressable
              onPress={() => setViewMode(VIEW_MODE.OVERVIEW)}
              className={`flex-1 items-center rounded-xl px-3 py-2.5 ${
                viewMode === VIEW_MODE.OVERVIEW ? "bg-white" : "bg-transparent"
              }`}
              accessibilityRole="button"
              accessibilityLabel="Switch to overview"
            >
              <Text
                className={`text-[12px] ${viewMode === VIEW_MODE.OVERVIEW ? "text-[#002C76]" : "text-[#6b7280]"}`}
                style={{ fontFamily: "Montserrat-SemiBold" }}
              >
                Overview
              </Text>
            </Pressable>

            <Pressable
              onPress={() => setViewMode(VIEW_MODE.MAP)}
              className={`ml-1.5 flex-1 items-center rounded-xl px-3 py-2.5 ${
                viewMode === VIEW_MODE.MAP ? "bg-white" : "bg-transparent"
              }`}
              accessibilityRole="button"
              accessibilityLabel="Switch to map"
            >
              <Text
                className={`text-[12px] ${viewMode === VIEW_MODE.MAP ? "text-[#002C76]" : "text-[#6b7280]"}`}
                style={{ fontFamily: "Montserrat-SemiBold" }}
              >
                Map
              </Text>
            </Pressable>
          </View>

          {viewMode === VIEW_MODE.OVERVIEW ? (
            <View className="mt-5 rounded-2xl border border-[#e5e7eb] bg-[#f9fafb] px-4 py-4">
              <View className="flex-row items-start">
                <Text className="flex-1 pr-2 text-[18px] text-[#002C76]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  Image details
                </Text>
              </View>

              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Open in Google Maps"
                onPress={handleOpenExternalMap}
                className="mt-3 self-start rounded-full bg-[#002C76] px-3.5 py-2"
              >
                <Text className="text-[11px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  Open in Google Maps
                </Text>
              </Pressable>

              {hasCoordinates ? (
                <>
                  <Text className="mt-4 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                    Uploaded At: {formatCapturedAt(imageCreatedAt)}
                  </Text>
                  <Text className="mt-1.5 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                    Uploaded By: {imageCapturedBy || "Not available"}
                  </Text>
                  <Text className="mt-4 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                    Latitude: {formatCoordinate(latitude)}
                  </Text>
                  <Text className="mt-1.5 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                    Longitude: {formatCoordinate(longitude)}
                  </Text>
                  <Text className="mt-1.5 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                    Accuracy: {hasAccuracy ? `${accuracy.toFixed(2)} m` : "Not available"}
                  </Text>

                  {(locationState === LOCATION_STATE.READY_NO_TILES || locationState === LOCATION_STATE.ERROR) ? (
                    <Pressable
                      onPress={handleRetryMap}
                      className="mt-4 flex-row items-center justify-center rounded-xl border border-[#d1d5db] bg-white px-4 py-3"
                      accessibilityRole="button"
                      accessibilityLabel="Retry map"
                    >
                      <Feather name="refresh-cw" size={14} color="#002C76" />
                      <Text className="ml-2 text-[13px] text-[#002C76]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                        Retry map
                      </Text>
                    </Pressable>
                  ) : null}
                </>
              ) : (
                <View className="mt-4 rounded-xl border border-[#fecaca] bg-[#fef2f2] px-3 py-3">
                  <Text className="text-[12px] text-[#b91c1c]" style={{ fontFamily: "Montserrat" }}>
                    No location was captured for this image.
                  </Text>
                </View>
              )}
            </View>
          ) : (
            <View className="mt-5 overflow-hidden rounded-2xl border border-[#e5e7eb] bg-[#dfe7f5]" style={{ height: 300 }}>
              {hasCoordinates ? (
                <Pressable
                  onPress={handleOpenMapFullscreen}
                  className="absolute right-3 top-3 z-20 flex-row items-center rounded-full bg-white/95 px-3 py-1.5"
                  accessibilityRole="button"
                  accessibilityLabel="Open full screen map"
                >
                  <Feather name="maximize-2" size={13} color="#002C76" />
                  <Text className="ml-1.5 text-[11px] text-[#002C76]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Full screen
                  </Text>
                </Pressable>
              ) : null}

              {hasCoordinates ? (
                <MapView
                  style={{ flex: 1 }}
                  initialRegion={initialRegion}
                  showsCompass={false}
                  showsScale={false}
                  rotateEnabled={false}
                  pitchEnabled={false}
                  onMapReady={() => setLocationState(LOCATION_STATE.READY_ONLINE)}
                >
                  <Marker
                    coordinate={{ latitude, longitude }}
                    tappable
                    pinColor="#002C76"
                    title={markerTitle}
                    description={markerDescription}
                  />
                </MapView>
              ) : (
                <View className="h-full items-center justify-center px-6">
                  <Feather name="map" size={24} color="#738fbf" />
                  <Text className="mt-2 text-center text-[12px] text-[#6077a4]" style={{ fontFamily: "Montserrat" }}>
                    No coordinates available for map preview.
                  </Text>
                </View>
              )}
            </View>
          )}
        </Animated.View>
      </Animated.ScrollView>

      <Modal
        visible={isViewerOpen}
        transparent
        animationType="fade"
        onRequestClose={() => setIsViewerOpen(false)}
      >
        <GestureHandlerRootView style={{ flex: 1 }}>
          <View className="flex-1 bg-black/90">
            <View className="flex-row items-center justify-between px-4 pb-3 pt-12">
              <Text className="text-[13px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                {stage}
              </Text>
              <Pressable
                onPress={() => setIsViewerOpen(false)}
                className="h-8 w-8 items-center justify-center rounded-full border border-white/40 bg-white/10"
                accessibilityRole="button"
                accessibilityLabel="Close image viewer"
              >
                <Feather name="x" size={18} color="#ffffff" />
              </Pressable>
            </View>

            {imageUrl ? (
              Platform.OS === "android" ? (
                <View className="flex-1 items-center justify-center">
                  <GestureDetector gesture={zoomPanGesture}>
                    <Animated.View style={{ width: "100%", height: "78%", justifyContent: "center", alignItems: "center" }}>
                      <Animated.Image
                        source={{ uri: imageUrl }}
                        style={[{ width: "100%", height: "100%" }, animatedImageStyle]}
                        resizeMode="contain"
                      />
                    </Animated.View>
                  </GestureDetector>
                </View>
              ) : (
                <ScrollView
                  className="flex-1"
                  contentContainerStyle={{ flexGrow: 1, justifyContent: "center", alignItems: "center" }}
                  minimumZoomScale={1}
                  maximumZoomScale={4}
                  showsHorizontalScrollIndicator={false}
                  showsVerticalScrollIndicator={false}
                  bouncesZoom
                  centerContent
                >
                  <Image
                    source={{ uri: imageUrl }}
                    style={{ width: "100%", height: "78%" }}
                    resizeMode="contain"
                  />
                </ScrollView>
              )
            ) : null}
          </View>
        </GestureHandlerRootView>
      </Modal>

      <Modal
        visible={isMapFullscreenOpen}
        animationType="slide"
        onRequestClose={handleCloseMapFullscreen}
      >
        <SafeAreaView className="flex-1 bg-[#0b1426]" edges={["top", "left", "right", "bottom"]}>
          <View className="flex-row items-center justify-between px-4 pb-3 pt-2">
            <Text className="text-[14px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
              Full Screen Map
            </Text>

            <Pressable
              onPress={handleCloseMapFullscreen}
              className="h-9 w-9 items-center justify-center rounded-full border border-white/30 bg-white/10"
              accessibilityRole="button"
              accessibilityLabel="Close full screen map"
            >
              <Feather name="x" size={18} color="#ffffff" />
            </Pressable>
          </View>

          <View className="flex-1 overflow-hidden border-t border-white/15">
            {hasCoordinates ? (
              <MapView
                style={{ flex: 1 }}
                initialRegion={initialRegion}
                showsCompass
                showsScale
                rotateEnabled={false}
                pitchEnabled={false}
                onMapReady={() => setLocationState(LOCATION_STATE.READY_ONLINE)}
              >
                <Marker
                  coordinate={{ latitude, longitude }}
                  tappable
                  pinColor="#1d4ed8"
                  title={markerTitle}
                  description={markerDescription}
                />
              </MapView>
            ) : (
              <View className="h-full items-center justify-center px-6">
                <Feather name="map" size={26} color="#93a7cf" />
                <Text className="mt-3 text-center text-[13px] text-[#dbe6ff]" style={{ fontFamily: "Montserrat" }}>
                  No coordinates available for map preview.
                </Text>
              </View>
            )}
          </View>
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  );
}

export const meta = {
  title: "Gallery Image Location",
};
