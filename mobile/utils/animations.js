import { Animated, Easing } from "react-native";

/* ==================== ANIMATION CONSTANTS ==================== */

export const ANIMATION_TIMINGS = {
  drawer: {
    open: { duration: 260, easing: Easing.out(Easing.cubic) },
    close: { duration: 220, easing: Easing.in(Easing.cubic) },
  },
  menu: {
    toggle: { duration: 220, easing: Easing.out(Easing.cubic) },
  },
};

export const PROJECT_MONITORING_SUBMENU_HEIGHT = 208;

/* ==================== SCROLL ANIMATION ==================== */

/**
 * Creates scroll event handler for animated values
 * @param {Animated.Value} scrollY - Animated value tracking scroll position
 * @returns {function} Animated.event handler
 */
export const createScrollHandler = (scrollY) => {
  return Animated.event(
    [{ nativeEvent: { contentOffset: { y: scrollY } } }],
    { useNativeDriver: true }
  );
};

/**
 * Creates interpolations for scroll-based header animations
 * @param {Animated.Value} scrollY - Animated value tracking scroll position
 * @returns {object} Object with headerOpacity, headerTranslate, heroOpacity, heroTranslate
 */
export const createScrollInterpolations = (scrollY) => {
  return {
    headerOpacity: scrollY.interpolate({
      inputRange: [60, 120],
      outputRange: [0, 1],
      extrapolate: "clamp",
    }),
    headerTranslate: scrollY.interpolate({
      inputRange: [60, 120],
      outputRange: [10, 0],
      extrapolate: "clamp",
    }),
    heroOpacity: scrollY.interpolate({
      inputRange: [0, 80],
      outputRange: [1, 0],
      extrapolate: "clamp",
    }),
    heroTranslate: scrollY.interpolate({
      inputRange: [0, 80],
      outputRange: [0, -20],
      extrapolate: "clamp",
    }),
  };
};

/* ==================== DRAWER ANIMATION ==================== */

/**
 * Animates drawer open with easing
 * @param {Animated.Value} drawerProgress - Animated value tracking drawer state (0-1)
 * @param {function} onComplete - Callback after animation completes (optional)
 */
export const animateDrawerOpen = (drawerProgress, onComplete) => {
  Animated.timing(drawerProgress, {
    toValue: 1,
    duration: ANIMATION_TIMINGS.drawer.open.duration,
    easing: ANIMATION_TIMINGS.drawer.open.easing,
    useNativeDriver: true,
  }).start(onComplete);
};

/**
 * Animates drawer close with easing
 * @param {Animated.Value} drawerProgress - Animated value tracking drawer state (0-1)
 * @param {function} onComplete - Callback after animation completes (optional)
 */
export const animateDrawerClose = (drawerProgress, onComplete) => {
  Animated.timing(drawerProgress, {
    toValue: 0,
    duration: ANIMATION_TIMINGS.drawer.close.duration,
    easing: ANIMATION_TIMINGS.drawer.close.easing,
    useNativeDriver: true,
  }).start(onComplete);
};

/**
 * Creates interpolations for drawer animations
 * @param {Animated.Value} drawerProgress - Animated value tracking drawer state (0-1)
 * @param {number} drawerWidth - Width of the drawer in pixels
 * @returns {object} Object with translateX interpolation
 */
export const createDrawerInterpolations = (drawerProgress, drawerWidth) => {
  return {
    translateX: drawerProgress.interpolate({
      inputRange: [0, 1],
      outputRange: [-drawerWidth, 0],
    }),
  };
};

/* ==================== MENU ANIMATION ==================== */

/**
 * Animates menu section toggle (e.g., project monitoring submenu)
 * @param {Animated.Value} animationValue - Animated value for the menu section (0-1)
 * @param {boolean} willExpand - Whether expanding (true) or collapsing (false)
 * @param {function} onComplete - Callback after animation completes (optional)
 */
export const animateMenuToggle = (
  animationValue,
  willExpand,
  onComplete
) => {
  Animated.timing(animationValue, {
    toValue: willExpand ? 1 : 0,
    duration: ANIMATION_TIMINGS.menu.toggle.duration,
    easing: ANIMATION_TIMINGS.menu.toggle.easing,
    useNativeDriver: false,
  }).start(onComplete);
};

/**
 * Creates interpolations for submenu animations
 * @param {Animated.Value} animationValue - Animated value for the menu section (0-1)
 * @param {number} submenuHeight - Height of the submenu in pixels
 * @returns {object} Object with height, opacity, and translateY interpolations
 */
export const createSubmenuInterpolations = (
  animationValue,
  submenuHeight
) => {
  return {
    height: animationValue.interpolate({
      inputRange: [0, 1],
      outputRange: [0, submenuHeight],
    }),
    opacity: animationValue,
    translateY: animationValue.interpolate({
      inputRange: [0, 1],
      outputRange: [-8, 0],
    }),
    chevronRotate: animationValue.interpolate({
      inputRange: [0, 1],
      outputRange: ["0deg", "180deg"],
    }),
  };
};
