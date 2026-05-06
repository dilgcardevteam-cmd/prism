import { Feather } from "@expo/vector-icons";
import { useRouter } from "expo-router";
import { memo, useCallback, useEffect, useMemo, useState } from "react";
import { FlatList, Image, Modal, Platform, Pressable, ScrollView, Text, View, useWindowDimensions } from "react-native";
import { Gesture, GestureDetector, GestureHandlerRootView } from "react-native-gesture-handler";
import Animated, { useAnimatedStyle, useSharedValue } from "react-native-reanimated";
import * as ImagePicker from "expo-image-picker";
import * as Location from "expo-location";
import ConfirmationModal from "../../../../../components/common/ConfirmationModal";
import FloatingToast from "../../../../../components/common/FloatingToast";
import { APP_ROUTES } from "../../../../../constants/routes";
import { useAuth } from "../../../../../contexts/AuthContext";
import { useWebAppRequest } from "../../../../../hooks/useWebAppRequest";

const GALLERY_FILTER_OPTIONS = [
  "All",
  "Before",
  "Project Billboard",
  "Community Billboard",
  "20-40%",
  "50-70%",
  "90%",
  "Completed",
  "During",
];

const GALLERY_SECTION_ORDER = GALLERY_FILTER_OPTIONS.filter((option) => option !== "All");

const BRAND = {
  primary: "#0f2f7a",
  primarySoft: "#e8f0ff",
  border: "#d7e2f5",
  borderSoft: "#c9d8f4",
  textMuted: "#6077a4",
};


function normalizeGalleryImages(images) {
  if (!Array.isArray(images)) {
    return [];
  }

  return images
    .map((image) => ({
      id: image?.id,
      category: String(image?.category || "").trim() || "During",
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

function FilterDropdown({ value, options, onChange }) {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <View className="z-20">
      <View className="flex-row items-center justify-between">
        <Text className="text-[12px] text-[#6c7ea7]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          Filter by stage
        </Text>
        <Feather name="sliders" size={14} color="#6c7ea7" />
      </View>

      <View className="relative mt-2">
        <Pressable
          onPress={() => setIsOpen((previous) => !previous)}
          className="flex-row items-center justify-between rounded-2xl border border-[#c8d6f1] bg-[#f5f9ff] px-4 py-3"
          accessibilityRole="button"
          accessibilityLabel="Toggle gallery filter dropdown"
        >
          <Text className="text-[13px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
            {value}
          </Text>
          <Feather name={isOpen ? "chevron-up" : "chevron-down"} size={16} color="#2c4f96" />
        </Pressable>

        {isOpen ? (
          <View
            className="absolute left-0 right-0 top-full mt-2 rounded-2xl border border-[#d5e0f4] bg-white px-2 py-2"
            style={{ zIndex: 50, elevation: 8 }}
          >
            {options.map((option) => {
              const isActive = option === value;
              return (
                <Pressable
                  key={option}
                  onPress={() => {
                    onChange(option);
                    setIsOpen(false);
                  }}
                  className={`mb-1 rounded-lg px-3 py-2 ${isActive ? "bg-[#e8f0ff]" : "bg-transparent"}`}
                  accessibilityRole="button"
                  accessibilityLabel={`Select ${option} filter`}
                >
                  <Text
                    className={`text-[13px] ${isActive ? "text-[#0f2f7a]" : "text-[#4f648f]"}`}
                    style={{ fontFamily: isActive ? "Montserrat-SemiBold" : "Montserrat" }}
                  >
                    {option}
                  </Text>
                </Pressable>
              );
            })}
          </View>
        ) : null}
      </View>
    </View>
  );
}

const GalleryImageTile = memo(function GalleryImageTile({ image, onOpenViewer, onOpenLocation, onDelete, tileWidth }) {
  return (
    <Pressable
      onPress={() => onOpenLocation(image)}
      accessibilityRole="button"
      accessibilityLabel="Open image location"
      className="relative mb-3 overflow-hidden rounded-2xl border border-[#d3dff3] bg-[#f8fbff]"
      style={{ width: tileWidth }}
    >
      <Pressable
        onPress={(event) => {
          event.stopPropagation?.();
          onOpenViewer(image);
        }}
        className="absolute right-2 top-2 z-20 h-8 w-8 items-center justify-center rounded-full border border-[#d1ddf3] bg-white/95"
        accessibilityRole="button"
        accessibilityLabel="Preview full image"
      >
        <Feather name="eye" size={14} color="#0f2f7a" />
      </Pressable>
      <Pressable
        onPress={(event) => {
          event.stopPropagation?.();
          onDelete(image);
        }}
        className="absolute left-2 top-2 z-20 h-8 w-8 items-center justify-center rounded-full border border-[#f5d4d4] bg-white/95"
        accessibilityRole="button"
        accessibilityLabel="Delete image"
      >
        <Feather name="trash-2" size={14} color="#dc2626" />
      </Pressable>
      <Image
        source={{ uri: image.imageUrl }}
        className="h-32 w-full bg-[#e2e8f0]"
        resizeMode="cover"
        resizeMethod={Platform.OS === "android" ? "resize" : "auto"}
        fadeDuration={0}
      />
      <View className="px-3 pb-3 pt-2">
        <Text className="text-[11px] text-[#21437f]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          {image.category || "During"}
        </Text>
      </View>
    </Pressable>
  );
});

const GalleryStageSection = memo(function GalleryStageSection({ title, images, onOpenViewer, onOpenLocation, onDelete, tileWidth }) {
  if (!Array.isArray(images) || images.length === 0) {
    return null;
  }

  return (
    <View className="mb-5">
      <View className="mb-3 flex-row items-center">
        <View className="h-px flex-1 bg-[#d7e2f5]" />
        <Text className="mx-3 text-[11px] uppercase tracking-[0.9px] text-[#5b719a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          {title}
        </Text>
        <View className="h-px flex-1 bg-[#d7e2f5]" />
      </View>

      <View className="flex-row flex-wrap justify-between">
        {images.map((image) => (
          <GalleryImageTile
            key={String(image.id)}
            image={image}
            onOpenViewer={onOpenViewer}
            onOpenLocation={onOpenLocation}
            onDelete={onDelete}
            tileWidth={tileWidth}
          />
        ))}
      </View>
    </View>
  );
});

const GalleryAllHeader = memo(function GalleryAllHeader({ count }) {
  return (
    <View className="mb-5">
      <View className="mb-3 flex-row items-center">
        <View className="h-px flex-1 bg-[#d7e2f5]" />
        <Text className="mx-3 text-[11px] uppercase tracking-[0.9px] text-[#5b719a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          All ({count})
        </Text>
        <View className="h-px flex-1 bg-[#d7e2f5]" />
      </View>
    </View>
  );
});

export default function Gallery({ project }) {
  const { width } = useWindowDimensions();
  const router = useRouter();
  const { session } = useAuth();
  const { fetchJsonWithFallback } = useWebAppRequest();
  const filterOptions = useMemo(() => GALLERY_FILTER_OPTIONS, []);
  const [selectedFilter, setSelectedFilter] = useState(filterOptions[0]);
  const [selectedImageUrl, setSelectedImageUrl] = useState("");
  const [selectedImageLabel, setSelectedImageLabel] = useState("");
  const [isViewerOpen, setIsViewerOpen] = useState(false);
  const [isAddImageSheetOpen, setIsAddImageSheetOpen] = useState(false);
  const [addImageSheetStep, setAddImageSheetStep] = useState("menu");
  const [uploadPhotoUri, setUploadPhotoUri] = useState("");
  const [uploadPhotoStage, setUploadPhotoStage] = useState("");
  const [uploadFormErrors, setUploadFormErrors] = useState({ photo: "", stage: "" });
  const [isUploadConfirmationOpen, setIsUploadConfirmationOpen] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [toast, setToast] = useState({ visible: false, type: "info", message: "" });
  const [serverImages, setServerImages] = useState(() => normalizeGalleryImages(project?.galleryImages));
  const [deviceLocation, setDeviceLocation] = useState({ latitude: null, longitude: null, accuracy: null });
  const [deleteConfirmationOpen, setDeleteConfirmationOpen] = useState(false);
  const [imageToDelete, setImageToDelete] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const scale = useSharedValue(1);
  const savedScale = useSharedValue(1);
  const translateX = useSharedValue(0);
  const translateY = useSharedValue(0);
  const savedTranslateX = useSharedValue(0);
  const savedTranslateY = useSharedValue(0);
  const projectId = Number(project?.id || 0);
  const stageOptions = useMemo(
    () => GALLERY_SECTION_ORDER,
    []
  );
  const normalizedProjectGallery = useMemo(
    () => normalizeGalleryImages(project?.galleryImages),
    [project?.galleryImages]
  );
  const galleryImages = useMemo(() => serverImages, [serverImages]);
  const isCompactScreen = width < 360;
  const imageTileWidth = isCompactScreen ? "100%" : "48.5%";

  const showToast = (type, message) => {
    setToast({ visible: true, type, message: String(message || "") });
  };

  useEffect(() => {
    setServerImages(normalizedProjectGallery);
  }, [project?.id, normalizedProjectGallery]);

  useEffect(() => {
    const requestLocationPermission = async () => {
      try {
        const { status } = await Location.requestForegroundPermissionsAsync();
        if (status === "granted") {
          const location = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
          setDeviceLocation({
            latitude: location.coords.latitude,
            longitude: location.coords.longitude,
            accuracy: location.coords.accuracy,
          });
        }
      } catch (error) {
        console.error("Error requesting location:", error);
      }
    };

    requestLocationPermission();
  }, []);

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

  const filteredImages = useMemo(() => {
    if (selectedFilter === "All") {
      return galleryImages;
    }

    return galleryImages.filter((image) => image.category === selectedFilter);
  }, [galleryImages, selectedFilter]);

  const groupedGallerySections = useMemo(() => {
    if (selectedFilter !== "All") {
      return [];
    }

    return GALLERY_SECTION_ORDER.map((stage) => ({
      stage,
      images: galleryImages.filter((image) => image.category === stage),
    })).filter((section) => section.images.length > 0);
  }, [galleryImages, selectedFilter]);

  const openViewer = (image) => {
    setSelectedImageUrl(String(image?.imageUrl || ""));
    setSelectedImageLabel(String(image?.category || "Image"));
    scale.value = 1;
    savedScale.value = 1;
    translateX.value = 0;
    translateY.value = 0;
    savedTranslateX.value = 0;
    savedTranslateY.value = 0;
    setIsViewerOpen(true);
  };

  const openImageLocation = (image) => {
    const serializedProject = JSON.stringify(project || {});

    router.push({
      pathname: APP_ROUTES.projectMonitoring.locallyFundedGalleryLocation,
      params: {
        project: serializedProject,
        projectTitle: String(project?.title || "Locally Funded Project"),
        projectCode: String(project?.code || ""),
        imageId: String(image?.id || ""),
        imageUrl: String(image?.imageUrl || ""),
        stage: String(image?.category || "During"),
        imageCreatedAt: image?.createdAt ? String(image.createdAt) : "",
        imageCapturedBy: image?.capturedBy ? String(image.capturedBy) : "",
        latitude: image?.latitude === null || image?.latitude === undefined ? "" : String(image.latitude),
        longitude: image?.longitude === null || image?.longitude === undefined ? "" : String(image.longitude),
        accuracy: image?.accuracy === null || image?.accuracy === undefined ? "" : String(image.accuracy),
      },
    });
  };

  const closeViewer = () => {
    setIsViewerOpen(false);
  };

  const appendServerImage = (image) => {
    const normalized = normalizeGalleryImages([image]);
    if (normalized.length === 0) {
      return;
    }

    setServerImages((current) => {
      const existingIds = new Set(current.map((item) => String(item.id)));
      const next = normalized.filter((item) => !existingIds.has(String(item.id)));
      return [...next, ...current];
    });
  };

  const resolveStageFromCurrentFilter = () => {
    return selectedFilter !== "All" ? selectedFilter : "During";
  };

  const getMimeTypeFromUri = (uri) => {
    const safeUri = String(uri || "").toLowerCase();
    if (safeUri.endsWith(".png")) {
      return "image/png";
    }
    if (safeUri.endsWith(".webp")) {
      return "image/webp";
    }
    if (safeUri.endsWith(".gif")) {
      return "image/gif";
    }
    if (safeUri.endsWith(".bmp")) {
      return "image/bmp";
    }

    return "image/jpeg";
  };

  const uploadImageToServer = async ({ uri, stage }) => {
    const safeUri = String(uri || "").trim();
    const safeStage = String(stage || "").trim() || "During";

    if (!projectId) {
      throw new Error("Unable to upload because project ID is missing.");
    }

    if (!safeUri) {
      throw new Error("No image selected for upload.");
    }

    const mimeType = getMimeTypeFromUri(safeUri);
    const extension = mimeType.split("/")[1] || "jpg";
    const fileName = `gallery-${Date.now()}.${extension}`;

    const formData = new FormData();
    formData.append("gallery_category", safeStage);
    formData.append("gallery_image", {
      uri: safeUri,
      name: fileName,
      type: mimeType,
    });

    const uploaderId = Number(session?.id || 0);
    if (Number.isFinite(uploaderId) && uploaderId > 0) {
      formData.append("uploaded_by", String(uploaderId));
    }

    if (deviceLocation.latitude !== null && deviceLocation.longitude !== null) {
      formData.append("latitude", String(deviceLocation.latitude));
      formData.append("longitude", String(deviceLocation.longitude));
      if (deviceLocation.accuracy !== null) {
        formData.append("accuracy", String(deviceLocation.accuracy));
      }
    }

    const payload = await fetchJsonWithFallback(`/api/mobile/locally-funded/${projectId}/gallery`, {
      method: "POST",
      body: formData,
    });

    const uploaded = payload?.data;
    if (!uploaded?.id || !uploaded?.image_url) {
      throw new Error("Upload succeeded but response was invalid.");
    }

    appendServerImage({
      id: uploaded.id,
      category: uploaded.category || safeStage,
      image_url: uploaded.image_url,
      uploaded_by: uploaded.uploaded_by ?? null,
      uploaded_by_name: uploaded.uploaded_by_name ?? null,
      latitude: uploaded.latitude ?? null,
      longitude: uploaded.longitude ?? null,
      accuracy: uploaded.accuracy ?? null,
      created_at: uploaded.created_at || null,
    });

    return payload;
  };

  const resetAddImageFlow = () => {
    setAddImageSheetStep("menu");
    setUploadPhotoUri("");
    setUploadPhotoStage("");
    setUploadFormErrors({ photo: "", stage: "" });
    setIsUploadConfirmationOpen(false);
  };

  const closeAddImageSheet = (shouldReset = true) => {
    setIsAddImageSheetOpen(false);
    if (shouldReset) {
      resetAddImageFlow();
    }
  };

  const cancelAddImageSheet = () => {
    closeAddImageSheet();
  };

  const openAddImageSheet = () => {
    setIsAddImageSheetOpen(true);
    resetAddImageFlow();
  };

  const goToUploadFormStep = () => {
    setAddImageSheetStep("upload-form");
    setUploadFormErrors({ photo: "", stage: "" });
    setUploadPhotoStage(resolveStageFromCurrentFilter());
  };

  const pickPhotoForUploadForm = async () => {
    try {
      const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!permission.granted) {
        showToast("warning", "Please allow gallery access to select a photo.");
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ["images"],
        allowsEditing: false,
        quality: 1,
      });

      if (!result.canceled && result.assets?.[0]?.uri) {
        setUploadPhotoUri(result.assets[0].uri);
        setUploadFormErrors((current) => ({ ...current, photo: "" }));
        showToast("success", "Photo selected.");
        return;
      }

      // showToast("warning", "No photo selected.");
    } catch (_error) {
      showToast("error", "Unable to open photo library.");
    }
  };

  const validateUploadForm = () => {
    const nextErrors = {
      photo: uploadPhotoUri ? "" : "Please select a photo.",
      stage: uploadPhotoStage ? "" : "Please select a stage.",
    };

    setUploadFormErrors(nextErrors);

    if (nextErrors.photo || nextErrors.stage) {
      return false;
    }

    return true;
  };

  const submitUploadForm = () => {
    const isValid = validateUploadForm();
    if (!isValid) {
      showToast("warning", "Photo and stage are both required.");
      return;
    }

    setIsAddImageSheetOpen(false);
    setIsUploadConfirmationOpen(true);
    // showToast("info", "Please confirm upload.");
  };

  const confirmUploadForm = async () => {
    if (isUploading) {
      return;
    }

    setIsUploading(true);

    try {
      await uploadImageToServer({
        uri: uploadPhotoUri,
        stage: uploadPhotoStage,
      });

      setIsUploadConfirmationOpen(false);
      closeAddImageSheet();
      showToast("success", "Image uploaded successfully.");
    } catch (error) {
      setIsUploadConfirmationOpen(false);
      setIsAddImageSheetOpen(true);
      showToast("error", error?.message || "Upload failed. Please try again.");
    } finally {
      setIsUploading(false);
    }
  };

  const handleUploadPhoto = async () => {
    goToUploadFormStep();
  };

  const handleTakePhoto = async () => {
    try {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (!permission.granted) {
        showToast("warning", "Please allow camera access to take a photo.");
        return;
      }

      const result = await ImagePicker.launchCameraAsync({
        allowsEditing: false,
        quality: 1,
      });

      if (result.canceled || !result.assets?.[0]?.uri) {
        showToast("warning", "No photo captured.");
        return;
      }

      setIsUploading(true);
      await uploadImageToServer({
        uri: result.assets[0].uri,
        stage: resolveStageFromCurrentFilter(),
      });

      closeAddImageSheet();
      showToast("success", "Photo captured and uploaded successfully.");
    } catch (error) {
      showToast("error", error?.message || "Unable to capture and upload photo.");
    } finally {
      setIsUploading(false);
    }
  };

  const deleteGalleryImageFromServer = async (imageId) => {
    if (!projectId || !imageId) {
      throw new Error("Missing project or image ID for deletion.");
    }

    const payload = await fetchJsonWithFallback(
      `/api/mobile/locally-funded/${projectId}/gallery/${imageId}`,
      {
        method: "DELETE",
      }
    );

    return payload;
  };

  const handleDeleteImage = (image) => {
    setImageToDelete(image);
    setDeleteConfirmationOpen(true);
  };

  const confirmDeleteImage = async () => {
    if (!imageToDelete || isDeleting) {
      return;
    }

    setIsDeleting(true);

    try {
      await deleteGalleryImageFromServer(imageToDelete.id);

      // Remove image from local state
      setServerImages((current) =>
        current.filter((img) => String(img.id) !== String(imageToDelete.id))
      );

      setDeleteConfirmationOpen(false);
      setImageToDelete(null);
      showToast("success", "Image deleted successfully.");
    } catch (error) {
      showToast("error", error?.message || "Failed to delete image. Please try again.");
    } finally {
      setIsDeleting(false);
    }
  };

  const cancelDeleteImage = () => {
    setDeleteConfirmationOpen(false);
    setImageToDelete(null);
  };

  const renderGalleryImage = useCallback(
    ({ item }) => (
      <GalleryImageTile
        image={item}
        onOpenViewer={openViewer}
        onOpenLocation={openImageLocation}
        onDelete={handleDeleteImage}
        tileWidth={imageTileWidth}
      />
    ),
    [handleDeleteImage, imageTileWidth, openImageLocation, openViewer]
  );

  const keyExtractor = useCallback((item) => String(item.id), []);

  const listHeader = useMemo(
    () => (
      <Pressable
        onPress={openAddImageSheet}
        className="mb-3 items-center justify-center rounded-2xl border border-[#b9cdf0] bg-[#edf4ff] px-3 py-4"
        style={{ width: imageTileWidth, minHeight: 156 }}
        accessibilityRole="button"
        accessibilityLabel="Add image"
      >
        <View className="h-11 w-11 items-center justify-center rounded-full border border-[#adc2ec] bg-white">
          <Feather name="plus" size={22} color="#0f2f7a" />
        </View>
        <Text className="mt-3 text-[13px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
          Add image
        </Text>
        <Text className="mt-1 text-center text-[11px] text-[#6077a4]" style={{ fontFamily: "Montserrat" }}>
          Upload or capture site progress
        </Text>
      </Pressable>
    ),
    [imageTileWidth]
  );

  const listEmpty = useMemo(
    () => (
      <View className="items-center rounded-2xl border border-dashed border-[#c7d8f2] bg-[#f8fbff] px-4 py-5">
        <View className="mb-2 h-10 w-10 items-center justify-center rounded-full bg-[#e9f1ff]">
          <Feather name="image" size={18} color="#41619e" />
        </View>
        <Text className="text-[12px] text-[#5c719b]" style={{ fontFamily: "Montserrat" }}>
          No images found for {selectedFilter}.
        </Text>
      </View>
    ),
    [selectedFilter]
  );

  const isAllFilter = selectedFilter === "All";

  return (
    <View className="mt-3 overflow-hidden rounded-3xl border border-[#d7e2f5] bg-white">
      <View className="px-4 pt-4" style={{ backgroundColor: BRAND.primarySoft }}>
        <View className="flex-row items-start justify-between">
          <View className="flex-1 pr-3">
            <Text className="text-[16px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
              Gallery
            </Text>
            <Text className="mt-1 text-[12px] text-[#486192]" style={{ fontFamily: "Montserrat" }}>
              Visual progress updates and site documentation.
            </Text>
          </View>

          <View className="rounded-full border border-[#c3d3f2] bg-white px-3 py-1.5">
            <Text className="text-[11px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
              {filteredImages.length} item{filteredImages.length === 1 ? "" : "s"}
            </Text>
          </View>
        </View>

        <View className="mt-3 pb-4">
          <FilterDropdown value={selectedFilter} options={filterOptions} onChange={setSelectedFilter} />
        </View>
      </View>

      <View className="px-4 pb-4 pt-4">
      <FloatingToast
        visible={toast.visible}
        type={toast.type}
        message={toast.message}
        duration={2600}
        onClose={() => setToast((current) => ({ ...current, visible: false }))}
      />

      {isAllFilter ? (
        <View>
          {listHeader}
          <GalleryAllHeader count={filteredImages.length} />
          {groupedGallerySections.length > 0 ? (
            groupedGallerySections.map((section) => (
              <GalleryStageSection
                key={section.stage}
                title={section.stage}
                images={section.images}
                onOpenViewer={openViewer}
                onOpenLocation={openImageLocation}
                onDelete={handleDeleteImage}
                tileWidth={imageTileWidth}
              />
            ))
          ) : (
            listEmpty
          )}
        </View>
      ) : (
        <FlatList
          data={filteredImages}
          keyExtractor={keyExtractor}
          renderItem={renderGalleryImage}
          numColumns={isCompactScreen ? 1 : 2}
          columnWrapperStyle={isCompactScreen ? undefined : { justifyContent: "space-between" }}
          ListHeaderComponent={listHeader}
          ListEmptyComponent={listEmpty}
          scrollEnabled={false}
          removeClippedSubviews
          initialNumToRender={6}
          maxToRenderPerBatch={6}
          windowSize={5}
          updateCellsBatchingPeriod={50}
        />
      )}
      </View>

      <Modal
        visible={isViewerOpen}
        transparent
        animationType="fade"
        onRequestClose={closeViewer}
      >
        <GestureHandlerRootView style={{ flex: 1 }}>
          <View className="flex-1 bg-black/90">
            <View className="flex-row items-center justify-between px-4 pb-3 pt-12">
              <Text className="text-[13px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                {selectedImageLabel}
              </Text>
              <Pressable
                onPress={closeViewer}
                className="h-8 w-8 items-center justify-center rounded-full border border-white/40 bg-white/10"
                accessibilityRole="button"
                accessibilityLabel="Close image viewer"
              >
                <Feather name="x" size={18} color="#ffffff" />
              </Pressable>
            </View>

            {selectedImageUrl ? (
              Platform.OS === "android" ? (
                <View className="flex-1 items-center justify-center">
                  <GestureDetector gesture={zoomPanGesture}>
                    <Animated.View style={{ width: "100%", height: "78%", justifyContent: "center", alignItems: "center" }}>
                      <Animated.Image
                        source={{ uri: selectedImageUrl }}
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
                    source={{ uri: selectedImageUrl }}
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
        visible={isAddImageSheetOpen}
        transparent
        animationType="fade"
        onRequestClose={cancelAddImageSheet}
      >
        <View className="flex-1 bg-black/35">
          <Pressable
            onPress={cancelAddImageSheet}
            className="absolute inset-0"
            accessibilityRole="button"
            accessibilityLabel="Close add image options"
          />

          <View className="absolute bottom-0 left-0 right-0 rounded-t-3xl border border-[#d7e2f5] bg-white px-4 pb-8 pt-4">
            <View className="mb-4 self-center rounded-full bg-[#d6deef]" style={{ width: 40, height: 4 }} />

            {addImageSheetStep === "menu" ? (
              <>
                <Text className="text-[15px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                  Add image
                </Text>
                <Text className="mt-1 text-[12px] text-[#6077a4]" style={{ fontFamily: "Montserrat" }}>
                  Choose how you want to add your photo.
                </Text>

                <Pressable
                  onPress={handleUploadPhoto}
                  className="mt-4 flex-row items-center rounded-2xl border border-[#c7d8f2] bg-[#f7faff] px-4 py-3.5"
                  accessibilityRole="button"
                  accessibilityLabel="Upload photo"
                >
                  <Feather name="image" size={18} color="#0f2f7a" />
                  <Text className="ml-3 text-[14px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Upload photo
                  </Text>
                </Pressable>

                <Pressable
                  onPress={handleTakePhoto}
                  className="mt-3 flex-row items-center rounded-2xl border border-[#c7d8f2] bg-[#f7faff] px-4 py-3.5"
                  accessibilityRole="button"
                  accessibilityLabel="Take photo"
                >
                  <Feather name="camera" size={18} color="#0f2f7a" />
                  <Text className="ml-3 text-[14px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Take photo
                  </Text>
                </Pressable>

                <Pressable
                  onPress={cancelAddImageSheet}
                  className="mt-3 items-center rounded-2xl border border-[#d3dceb] bg-[#ffffff] px-4 py-3"
                  accessibilityRole="button"
                  accessibilityLabel="Cancel add image"
                >
                  <Text className="text-[14px] text-[#6077a4]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Cancel
                  </Text>
                </Pressable>
              </>
            ) : (
              <>
                <View className="flex-row items-center justify-between">
                  <Text className="text-[15px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Upload photo details
                  </Text>
                  <Pressable
                    onPress={() => setAddImageSheetStep("menu")}
                    accessibilityRole="button"
                    accessibilityLabel="Back to add image options"
                  >
                    <Text className="text-[12px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                      Back
                    </Text>
                  </Pressable>
                </View>

                <View className="mt-3">
                  <Text className="text-[12px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Photo <Text style={{ color: "#dc2626" }}>*</Text>
                  </Text>
                  <Pressable
                    onPress={pickPhotoForUploadForm}
                    className="mt-2 flex-row items-center rounded-2xl border border-[#c7d8f2] bg-[#f7faff] px-4 py-3"
                    accessibilityRole="button"
                    accessibilityLabel="Select photo to upload"
                  >
                    <Feather name="image" size={18} color="#0f2f7a" />
                    <Text className="ml-3 text-[13px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                      {uploadPhotoUri ? "Change selected photo" : "Select photo"}
                    </Text>
                  </Pressable>
                  {uploadPhotoUri ? (
                    <Image
                      source={{ uri: uploadPhotoUri }}
                      className="mt-2 h-24 w-full rounded-lg bg-[#e2e8f0]"
                      resizeMode="cover"
                    />
                  ) : null}
                  {uploadFormErrors.photo ? (
                    <Text className="mt-1 text-[11px]" style={{ color: "#dc2626", fontFamily: "Montserrat" }}>
                      {uploadFormErrors.photo}
                    </Text>
                  ) : null}
                </View>

                <View className="mt-3">
                  <Text className="text-[12px] text-[#0f2f7a]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Stage <Text style={{ color: "#dc2626" }}>*</Text>
                  </Text>
                  <View className="mt-2 flex-row flex-wrap">
                    {stageOptions.map((stage) => {
                      const isSelected = uploadPhotoStage === stage;
                      return (
                        <Pressable
                          key={stage}
                          onPress={() => {
                            setUploadPhotoStage(stage);
                            setUploadFormErrors((current) => ({ ...current, stage: "" }));
                          }}
                          className={`mb-2 mr-2 rounded-full border px-3 py-2 ${isSelected ? "border-[#0f2f7a] bg-[#e8f0ff]" : "border-[#c7d8f2] bg-[#f7faff]"}`}
                          accessibilityRole="button"
                          accessibilityLabel={`Select stage ${stage}`}
                        >
                          <Text className={`text-[12px] ${isSelected ? "text-[#0f2f7a]" : "text-[#4f648f]"}`} style={{ fontFamily: "Montserrat-SemiBold" }}>
                            {stage}
                          </Text>
                        </Pressable>
                      );
                    })}
                  </View>
                  {uploadFormErrors.stage ? (
                    <Text className="mt-1 text-[11px]" style={{ color: "#dc2626", fontFamily: "Montserrat" }}>
                      {uploadFormErrors.stage}
                    </Text>
                  ) : null}
                </View>

                <Pressable
                  onPress={submitUploadForm}
                  className="mt-4 items-center rounded-2xl border border-[#0f2f7a] bg-[#0f2f7a] px-4 py-3"
                  accessibilityRole="button"
                  accessibilityLabel="Upload selected photo"
                >
                  <Text className="text-[14px] text-white" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Upload
                  </Text>
                </Pressable>

                <Pressable
                  onPress={cancelAddImageSheet}
                  className="mt-3 items-center rounded-2xl border border-[#d3dceb] bg-[#ffffff] px-4 py-3"
                  accessibilityRole="button"
                  accessibilityLabel="Cancel add image"
                >
                  <Text className="text-[14px] text-[#6077a4]" style={{ fontFamily: "Montserrat-SemiBold" }}>
                    Cancel
                  </Text>
                </Pressable>
              </>
            )}
          </View>
        </View>
      </Modal>

      <ConfirmationModal
        visible={isUploadConfirmationOpen}
        title="Confirm upload"
        message="Are you sure you want to upload this photo to the selected stage?"
        confirmLabel="Upload"
        cancelLabel="Cancel"
        onConfirm={confirmUploadForm}
        onCancel={() => {
          setIsUploadConfirmationOpen(false);
          setIsAddImageSheetOpen(true);
          showToast("warning", "Upload cancelled.");
        }}
        loading={isUploading}
      />

      <ConfirmationModal
        visible={deleteConfirmationOpen}
        title="Delete image"
        message={`Are you sure you want to delete this ${imageToDelete?.category || "gallery"} image? This action cannot be undone.`}
        confirmLabel="Delete"
        cancelLabel="Cancel"
        onConfirm={confirmDeleteImage}
        onCancel={cancelDeleteImage}
        loading={isDeleting}
        isDangerous={true}
      />
    </View>
  );
}
