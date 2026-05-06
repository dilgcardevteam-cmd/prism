import { Feather } from "@expo/vector-icons";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useEffect, useMemo, useState } from "react";
import { FlatList, Image, Linking, Modal, Platform, Pressable, ScrollView, Text, View, useWindowDimensions } from "react-native";
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

function parseProjectParam(rawValue) {
  if (typeof rawValue !== "string" || !rawValue.trim()) {
    return null;
  }

  try {
    return JSON.parse(rawValue);
  } catch (_error) {
    return null;
  }
}

function normalizeStageKey(value) {
  return String(value || "")
    .trim()
    .toLowerCase()
    .replace(/%/g, "")
    .replace(/[^a-z0-9-]+/g, " ")
    .replace(/\s+/g, " ");
}

function normalizeGalleryImages(images) {
  if (!Array.isArray(images)) {
    return [];
  }

  return images
    .map((image) => ({
      id: image?.id,
      category: String(image?.category || image?.gallery_category || "").trim() || "During",
      imageUrl: String(image?.imageUrl || image?.image_url || "").trim(),
      createdAt: image?.createdAt || image?.created_at || null,
      capturedBy:
        image?.capturedBy ||
        image?.captured_by ||
        image?.uploadedByName ||
        image?.uploaded_by_name ||
        image?.uploaded_by ||
        null,
      latitude:
        image?.latitude === null || image?.latitude === undefined || image?.latitude === ""
          ? null
          : Number(image.latitude),
      longitude:
        image?.longitude === null || image?.longitude === undefined || image?.longitude === ""
          ? null
          : Number(image.longitude),
      accuracy:
        image?.accuracy === null || image?.accuracy === undefined || image?.accuracy === ""
          ? null
          : Number(image.accuracy),
    }))
    .filter((image) => image.id && image.imageUrl);
}

export default function GalleryImageLocationScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const { width: windowWidth } = useWindowDimensions();

  const project = useMemo(() => parseProjectParam(params.project), [params.project]);

  const projectTitle = String(params.projectTitle || "Locally Funded Project");
  const projectCode = String(params.projectCode || "-");
  const stage = String(params.stage || "During");
  const markerTitle = String(projectTitle || "Locally Funded Project").trim() || "Locally Funded Project";
  const projectImages = useMemo(() => normalizeGalleryImages(project?.galleryImages), [project?.galleryImages]);
  const currentStageKey = useMemo(() => normalizeStageKey(stage), [stage]);
  const initialImageId = String(params.imageId || "").trim();
  const fallbackImage = useMemo(() => {
    return (
      normalizeGalleryImages([
        {
          id: initialImageId || params.imageUrl || "current-image",
          category: stage,
          imageUrl: params.imageUrl,
          createdAt: params.imageCreatedAt,
          capturedBy: params.imageCapturedBy,
          latitude: params.latitude,
          longitude: params.longitude,
          accuracy: params.accuracy,
        },
      ])[0] || null
    );
  }, [initialImageId, params.accuracy, params.imageCapturedBy, params.imageCreatedAt, params.imageUrl, params.latitude, params.longitude, stage]);
  const stageImages = useMemo(() => {
    const matched = projectImages.filter((image) => normalizeStageKey(image.category) === currentStageKey);

    if (matched.length > 0) {
      return matched;
    }

    return fallbackImage ? [fallbackImage] : [];
  }, [currentStageKey, fallbackImage, projectImages]);
  const [activeImageIndex, setActiveImageIndex] = useState(() => {
    if (stageImages.length === 0) {
      return 0;
    }

    const initialIndex = stageImages.findIndex(
      (image) => String(image.id) === initialImageId || String(image.imageUrl) === String(params.imageUrl || "")
    );

    return initialIndex >= 0 ? initialIndex : 0;
  });
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

  const activeImage = stageImages[activeImageIndex] || stageImages[0] || fallbackImage;
  const imageUrl = String(activeImage?.imageUrl || params.imageUrl || "");
  const imageCreatedAt = String(activeImage?.createdAt || params.imageCreatedAt || "");
  const imageCapturedBy = String(activeImage?.capturedBy || params.imageCapturedBy || "").trim();
  const markerDescription = String(activeImage?.category || stage || "During").trim() || "During";
  const latitude = useMemo(() => parseCoordinate(activeImage?.latitude), [activeImage?.latitude]);
  const longitude = useMemo(() => parseCoordinate(activeImage?.longitude), [activeImage?.longitude]);
  const accuracy = useMemo(() => parseAccuracy(activeImage?.accuracy), [activeImage?.accuracy]);

  const hasCoordinates = latitude !== null && longitude !== null;
  const hasMultipleStageImages = stageImages.length > 1;
  const carouselWidth = Math.max(windowWidth, 1);

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
    if (stageImages.length === 0) {
      setActiveImageIndex(0);
      return;
    }

    const nextIndex = stageImages.findIndex(
      (image) => String(image.id) === initialImageId || String(image.imageUrl) === String(params.imageUrl || "")
    );

    setActiveImageIndex(nextIndex >= 0 ? nextIndex : 0);
  }, [initialImageId, params.imageUrl, stageImages]);

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

  const handleCarouselMomentumEnd = (event) => {
    if (!hasMultipleStageImages) {
      return;
    }

    const nextIndex = Math.round(event.nativeEvent.contentOffset.x / carouselWidth);
    const boundedIndex = Math.max(0, Math.min(nextIndex, stageImages.length - 1));
    setActiveImageIndex(boundedIndex);
  };

  const openImageViewer = (image = activeImage) => {
    if (!image?.imageUrl) {
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

  const renderStagePage = ({ item, index }) => {
    const pageImageUrl = String(item?.imageUrl || "");
    const pageCreatedAt = String(item?.createdAt || "");
    const pageCapturedBy = String(item?.capturedBy || "").trim();
    const pageMarkerDescription = String(item?.category || stage || "During").trim() || "During";
    const pageLatitude = parseCoordinate(item?.latitude);
    const pageLongitude = parseCoordinate(item?.longitude);
    const pageAccuracy = parseAccuracy(item?.accuracy);
    const pageHasCoordinates = pageLatitude !== null && pageLongitude !== null;
    const pageInitialRegion = pageHasCoordinates
      ? {
          latitude: pageLatitude,
          longitude: pageLongitude,
          latitudeDelta: 0.012,
          longitudeDelta: 0.012,
        }
      : null;

    return (
      <View style={{ width: carouselWidth }} className="flex-1">
        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 28 }}>
          <View className="relative">
            <Pressable
              onPress={() => openImageViewer(item)}
              accessibilityRole="button"
              accessibilityLabel={`Open ${String(item?.category || stage || "image")}`}
              className="overflow-hidden"
            >
              {pageImageUrl ? (
                <Image source={{ uri: pageImageUrl }} className="h-[290px] w-full bg-[#dce6f8]" resizeMode="cover" />
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
            </View>

            <View className="absolute bottom-[15%] left-4 flex-row items-center gap-2">
              <View className="rounded-full bg-black/55 px-3 py-1.5">
                <Text className="text-[11px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  {pageMarkerDescription}
                </Text>
              </View>

              {hasMultipleStageImages ? (
                <View className="rounded-full bg-black/40 px-3 py-1.5">
                  <Text className="text-[11px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    {index + 1} of {stageImages.length}
                  </Text>
                </View>
              ) : null}
            </View>
          </View>

          <View className="-mt-7 rounded-[30px] border border-[#e2e8f0] bg-white px-5 py-5 shadow-xl shadow-black/10">
            <Text className="text-[22px] leading-[30px] text-[#002C76]" numberOfLines={3} style={{ fontFamily: "Montserrat-SemiBold" }}>
              {projectTitle}
            </Text>

            <View className="mt-3 flex-col items-start gap-2">
              <View className="max-w-full flex-row items-center rounded-full bg-[#f3f4f6] px-2.5 py-1.5">
                <View className="h-3 w-3 rounded-full bg-[#65a30d]" />
                <Text className="ml-2 text-[12px] text-[#374151]" numberOfLines={1} style={{ fontFamily: "Montserrat-SemiBold" }}>
                  {pageHasCoordinates ? "Coordinates captured" : "Location unavailable"}
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

            {hasMultipleStageImages ? (
              <Text className="mt-2 text-[12px] text-[#6b7280]" style={{ fontFamily: "Montserrat" }}>
                Swipe left or right to view the other {pageMarkerDescription} images in this project.
              </Text>
            ) : null}

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

                {pageHasCoordinates ? (
                  <>
                    <Text className="mt-4 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                      Uploaded At: {formatCapturedAt(pageCreatedAt)}
                    </Text>
                    <Text className="mt-1.5 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                      Uploaded By: {pageCapturedBy || "Not available"}
                    </Text>
                    <Text className="mt-4 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                      Latitude: {formatCoordinate(pageLatitude)}
                    </Text>
                    <Text className="mt-1.5 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                      Longitude: {formatCoordinate(pageLongitude)}
                    </Text>
                    <Text className="mt-1.5 text-[13px] text-[#4b5563]" style={{ fontFamily: "Montserrat" }}>
                      Accuracy: {pageAccuracy !== null ? `${pageAccuracy.toFixed(2)} m` : "Not available"}
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
                {pageHasCoordinates ? (
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

                {pageHasCoordinates ? (
                  <MapView
                    style={{ flex: 1 }}
                    initialRegion={pageInitialRegion}
                    showsCompass={false}
                    showsScale={false}
                    rotateEnabled={false}
                    pitchEnabled={false}
                    onMapReady={() => setLocationState(LOCATION_STATE.READY_ONLINE)}
                  >
                    <Marker
                      coordinate={{ latitude: pageLatitude, longitude: pageLongitude }}
                      tappable
                      pinColor="#002C76"
                      title={markerTitle}
                      description={pageMarkerDescription}
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
          </View>
        </ScrollView>
      </View>
    );
  };

  const keyExtractor = (item) => String(item.id);

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

      <FlatList
        data={stageImages}
        keyExtractor={keyExtractor}
        renderItem={renderStagePage}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onMomentumScrollEnd={handleCarouselMomentumEnd}
        initialScrollIndex={Math.min(activeImageIndex, Math.max(stageImages.length - 1, 0))}
        getItemLayout={(_, index) => ({
          length: carouselWidth,
          offset: carouselWidth * index,
          index,
        })}
        onScrollToIndexFailed={() => null}
        nestedScrollEnabled
      />

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
                {markerDescription}
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
