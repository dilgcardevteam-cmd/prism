import { useCallback } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";

const CACHE_KEY = "dashboard_cache";
const TTL_KEY = "dashboard_cache_ttl";

export function useDashboardCache() {
  /**
   * Get cached dashboard data if valid and not expired
   * @param {number} maxAgeMinutes - Maximum age of cache in minutes
   * @returns {object|null} Cached data or null if expired/invalid
   */
  const getCachedData = useCallback(async (maxAgeMinutes = 10) => {
    try {
      const cachedData = await AsyncStorage.getItem(CACHE_KEY);
      const cachedTTL = await AsyncStorage.getItem(TTL_KEY);

      if (!cachedData || !cachedTTL) {
        return null;
      }

      const ttl = parseInt(cachedTTL, 10);
      const now = Date.now();
      const maxAgeMs = maxAgeMinutes * 60 * 1000;

      // Check if cache is expired
      if (now - ttl > maxAgeMs) {
        // Cache is stale, clear it
        await AsyncStorage.removeItem(CACHE_KEY);
        await AsyncStorage.removeItem(TTL_KEY);
        return null;
      }

      return JSON.parse(cachedData);
    } catch (error) {
      console.warn("Dashboard cache read skipped:", error?.message || error);
      return null;
    }
  }, []);

  /**
   * Save dashboard data to cache
   * @param {object} data - Data to cache
   * @returns {boolean} Success status
   */
  const setCachedData = useCallback(async (data) => {
    try {
      const now = Date.now();
      await AsyncStorage.setItem(CACHE_KEY, JSON.stringify(data));
      await AsyncStorage.setItem(TTL_KEY, now.toString());
      return true;
    } catch (error) {
      console.warn("Dashboard cache write skipped:", error?.message || error);
      return false;
    }
  }, []);

  /**
   * Clear dashboard cache
   * @returns {boolean} Success status
   */
  const clearCache = useCallback(async () => {
    try {
      await AsyncStorage.removeItem(CACHE_KEY);
      await AsyncStorage.removeItem(TTL_KEY);
      return true;
    } catch (error) {
      console.warn("Dashboard cache clear skipped:", error?.message || error);
      return false;
    }
  }, []);

  /**
   * Get stale cache data (for offline support)
   * @returns {object|null} Cached data regardless of expiration
   */
  const getStaleCachedData = useCallback(async () => {
    try {
      const cachedData = await AsyncStorage.getItem(CACHE_KEY);
      if (!cachedData) {
        return null;
      }
      return JSON.parse(cachedData);
    } catch (error) {
      console.warn("Dashboard stale cache read skipped:", error?.message || error);
      return null;
    }
  }, []);

  return {
    getCachedData,
    setCachedData,
    clearCache,
    getStaleCachedData,
  };
}
