import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useEffect, useMemo, useRef, useState } from "react";
import { Image, Linking, Modal, Platform, Pressable, ScrollView, Text, View } from "react-native";
import { Gesture, GestureDetector, GestureHandlerRootView } from "react-native-gesture-handler";
import Animated, { useAnimatedStyle, useSharedValue } from "react-native-reanimated";
import MapView, { Marker } from "react-native-maps";
import { SafeAreaView } from "react-native-safe-area-context";
import { APP_ROUTES } from "../../../../constants/routes";

const VIEW_MODE = {
  LIST: "list",
  MAP: "map",
};

const LOCATION_STATE = {
  READY_ONLINE: "LOCATION_READY_ONLINE",
  READY_OFFLINE_TILES: "LOCATION_READY_OFFLINE_TILES",
  READY_NO_TILES: "LOCATION_READY_NO_TILES",
  MISSING: "LOCATION_MISSING",
  ERROR: "LOCATION_ERROR",
};

const TOAST_STYLES = {
  success: {
    container: "border-[#15a34a] bg-[#f0fdf4]",
    text: "#166534",
  },
  error: {
    container: "border-[#dc2626] bg-[#fef2f2]",
    text: "#991b1b",
  },
  warning: {
    container: "border-[#d97706] bg-[#fffbeb]",
    text: "#92400e",
  },
  info: {
    container: "border-[#1d4ed8] bg-[#eff6ff]",
    text: "#1e3a8a",
  },
};

function ActionToast({ visible, type = "info", message = "" }) {
  if (!visible || !message) {
    return null;
  }

  const style = TOAST_STYLES[type] || TOAST_STYLES.info;

  return (
    <View pointerEvents="none" className="absolute right-4 top-3 z-[300] max-w-[78%]">
      <View className={`rounded-xl border px-4 py-3 ${style.container}`}>
        <Text className="text-[12px]" style={{ color: style.text, fontFamily: "Montserrat-SemiBold" }}>
          {message}
        </Text>
      </View>
    </View>
  );
}

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

export default function GalleryImageLocationScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();

  const projectTitle = String(params.projectTitle || "Locally Funded Project");
  const projectCode = String(params.projectCode || "-");
  const stage = String(params.stage || "During");
  const imageUrl = String(params.imageUrl || "");
  const markerTitle = String(projectTitle || "Locally Funded Project").trim() || "Locally Funded Project";
  const markerDescription = String(stage || "During").trim() || "During";

  const latitude = useMemo(() => parseCoordinate(params.latitude), [params.latitude]);
  const longitude = useMemo(() => parseCoordinate(params.longitude), [params.longitude]);
  const accuracy = useMemo(() => parseAccuracy(params.accuracy), [params.accuracy]);
  const [locationState, setLocationState] = useState(LOCATION_STATE.READY_ONLINE);
  const [toast, setToast] = useState({ visible: false, type: "info", message: "" });
  const [viewMode, setViewMode] = useState(VIEW_MODE.LIST);
  const [showMapCoordinate, setShowMapCoordinate] = useState(false);
  const [isViewerOpen, setIsViewerOpen] = useState(false);
  const markerRef = useRef(null);

  const scale = useSharedValue(1);
  const savedScale = useSharedValue(1);
  const translateX = useSharedValue(0);
  const translateY = useSharedValue(0);
  const savedTranslateX = useSharedValue(0);
  const savedTranslateY = useSharedValue(0);

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

  const handleMarkerSelect = () => {
    setShowMapCoordinate(true);
    markerRef.current?.showCallout?.();
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

  return (
    <SafeAreaView className="flex-1 bg-[#f4f7fc]" edges={["left", "right"]}>
      <View className="flex-1 px-4 pb-4 pt-4">
        <ActionToast visible={toast.visible} type={toast.type} message={toast.message} />
        <View className="flex-row items-center justify-between">
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Go back"
            onPress={handleBackToGallery}
            className="h-9 w-9 items-center justify-center rounded-full border border-[#c7d6ef] bg-white"
          >
            <Feather name="chevron-left" size={20} color="#0f2f7a" />
          </Pressable>

          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Back to project"
            onPress={() => router.push(APP_ROUTES.projectMonitoring.locallyFundedProjects)}
            className="rounded-full border border-[#c7d6ef] bg-white px-3 py-2"
          >
            <Text className="text-[11px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
              Project List
            </Text>
          </Pressable>
        </View>

        <View className="mt-4 flex-row rounded-xl border border-[#c9d8f0] bg-white p-1">
          <Pressable
            onPress={() => setViewMode(VIEW_MODE.LIST)}
            className={`flex-1 items-center rounded-lg px-3 py-2.5 ${
              viewMode === VIEW_MODE.LIST ? "bg-[#0f2f7a]" : "bg-transparent"
            }`}
            accessibilityRole="button"
            accessibilityLabel="Switch to list mode"
          >
            <Text
              className={`text-[12px] ${viewMode === VIEW_MODE.LIST ? "text-white" : "text-[#0f2f7a]"}`}
              style={{ fontFamily: "Montserrat-SemiBold" }}
            >
              List mode
            </Text>
          </Pressable>

          <Pressable
            onPress={() => setViewMode(VIEW_MODE.MAP)}
            className={`ml-1 flex-1 items-center rounded-lg px-3 py-2.5 ${
              viewMode === VIEW_MODE.MAP ? "bg-[#0f2f7a]" : "bg-transparent"
            }`}
            accessibilityRole="button"
            accessibilityLabel="Switch to map mode"
          >
            <Text
              className={`text-[12px] ${viewMode === VIEW_MODE.MAP ? "text-white" : "text-[#0f2f7a]"}`}
              style={{ fontFamily: "Montserrat-SemiBold" }}
            >
              Map mode
            </Text>
          </Pressable>
        </View>

        {viewMode === VIEW_MODE.MAP ? (
          <View className="mt-4 flex-1 overflow-hidden rounded-2xl border border-[#d6e1f4] bg-[#dfe7f5]">
            {hasCoordinates ? (
              <View style={{ flex: 1 }}>
                <MapView
                  style={{ flex: 1 }}
                  initialRegion={initialRegion}
                  showsCompass={false}
                  showsScale={false}
                  rotateEnabled={false}
                  pitchEnabled={false}
                  onPress={() => setShowMapCoordinate(false)}
                  onMapReady={() => setLocationState(LOCATION_STATE.READY_ONLINE)}
                >
                  <Marker
                    ref={markerRef}
                    coordinate={{ latitude, longitude }}
                    onPress={handleMarkerSelect}
                    onSelect={handleMarkerSelect}
                    tappable
                    pinColor="#0f2f7a"
                    title={markerTitle}
                    description={markerDescription}
                  />
                </MapView>

                {showMapCoordinate ? (
                  <View className="absolute bottom-4 left-4 right-4 rounded-xl border border-[#bfccdf] bg-white/95 px-3 py-3">
                    <Text className="text-[12px] text-[#1f3f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                      Pin Coordinates
                    </Text>
                    <Text className="mt-1 text-[11px] text-[#3c5687]" style={{ fontFamily: "Montserrat" }}>
                      Latitude: {formatCoordinate(latitude)}
                    </Text>
                    <Text className="mt-1 text-[11px] text-[#3c5687]" style={{ fontFamily: "Montserrat" }}>
                      Longitude: {formatCoordinate(longitude)}
                    </Text>
                  </View>
                ) : null}
              </View>
            ) : (
              <View className="h-full items-center justify-center px-6">
                <Feather name="map" size={24} color="#738fbf" />
                <Text className="mt-2 text-center text-[12px] text-[#6077a4]" style={{ fontFamily: "Montserrat" }}>
                  No coordinates available for map preview.
                </Text>
              </View>
            )}
          </View>
        ) : null}

        {viewMode === VIEW_MODE.LIST ? (
          <View className="mt-4 flex-1">
            <View className="rounded-2xl border border-[#d6e1f4] bg-white px-4 py-4">
              <Text className="mt-1 text-[12px] text-[#4f648f] font-bold" style={{ fontFamily: "Montserrat" }}>
                {projectTitle}
              </Text>
              <Text className="mt-1 text-[12px] text-[#4f648f]" style={{ fontFamily: "Montserrat" }}>
                Code: {projectCode}
              </Text>
              <Text className="mt-1 text-[12px] text-[#4f648f]" style={{ fontFamily: "Montserrat" }}>
                Stage: {stage}
              </Text>
            </View>

            <Pressable
              onPress={openImageViewer}
              className="mt-4 overflow-hidden rounded-2xl border border-[#d6e1f4] bg-white"
              accessibilityRole="button"
              accessibilityLabel="Open image preview"
            >
              {imageUrl ? (
                <Image source={{ uri: imageUrl }} className="h-56 w-full bg-[#dce6f8]" resizeMode="cover" />
              ) : (
                <View className="h-56 items-center justify-center bg-[#edf2fb]">
                  <Feather name="image" size={22} color="#7994c7" />
                  <Text className="mt-2 text-[12px] text-[#6d83aa]" style={{ fontFamily: "Montserrat" }}>
                    Image preview unavailable
                  </Text>
                </View>
              )}
            </Pressable>

            <View className="mt-4 rounded-2xl border border-[#d6e1f4] bg-white px-4 py-4">
              <View className="flex-row items-center">
                <Feather name="map-pin" size={16} color="#0f2f7a" />
                <Text className="ml-2 text-[14px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  Captured Coordinates
                </Text>
              </View>

              {hasCoordinates ? (
                <>
                  <Text className="mt-3 text-[12px] text-[#344d7c]" style={{ fontFamily: "Montserrat" }}>
                    Latitude: {formatCoordinate(latitude)}
                  </Text>
                  <Text className="mt-1 text-[12px] text-[#344d7c]" style={{ fontFamily: "Montserrat" }}>
                    Longitude: {formatCoordinate(longitude)}
                  </Text>
                  <Text className="mt-1 text-[12px] text-[#344d7c]" style={{ fontFamily: "Montserrat" }}>
                    Accuracy: {accuracy !== null ? `${accuracy.toFixed(2)} m` : "Not available"}
                  </Text>

                  <Pressable
                    onPress={handleOpenExternalMap}
                    className="mt-4 flex-row items-center justify-center rounded-xl border border-[#0f2f7a] bg-[#0f2f7a] px-4 py-3"
                    accessibilityRole="button"
                    accessibilityLabel="Open location in Google Maps"
                  >
                    <Feather name="navigation" size={14} color="#ffffff" />
                    <Text className="ml-2 text-[13px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                      Open in Google Maps
                    </Text>
                  </Pressable>

                  {(locationState === LOCATION_STATE.READY_NO_TILES || locationState === LOCATION_STATE.ERROR) ? (
                    <Pressable
                      onPress={handleRetryMap}
                      className="mt-3 flex-row items-center justify-center rounded-xl border border-[#c7d6ef] bg-white px-4 py-3"
                      accessibilityRole="button"
                      accessibilityLabel="Retry map"
                    >
                      <Feather name="refresh-cw" size={14} color="#0f2f7a" />
                      <Text className="ml-2 text-[13px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                        Retry Map
                      </Text>
                    </Pressable>
                  ) : null}
                </>
              ) : (
                <View className="mt-3 rounded-xl border border-[#e6d3d3] bg-[#fff5f5] px-3 py-3">
                  <Text className="text-[12px] text-[#9f3b3b]" style={{ fontFamily: "Montserrat" }}>
                    No location was captured for this image.
                  </Text>
                </View>
              )}
            </View>
          </View>
        ) : null}
      </View>

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
    </SafeAreaView>
  );
}

export const meta = {
  title: "Gallery Image Location",
};
