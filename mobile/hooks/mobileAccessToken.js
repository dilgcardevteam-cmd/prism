import * as SecureStore from "expo-secure-store";

const AUTH_STORAGE_KEY = "pdmuoms.mobile.auth.session";

export async function getMobileAuthSession() {
  const rawSession = await SecureStore.getItemAsync(AUTH_STORAGE_KEY);
  if (!rawSession) {
    return null;
  }

  try {
    const session = JSON.parse(rawSession);
    if (typeof session?.access_token !== "string" || typeof session?.api_base_url !== "string") {
      return null;
    }

    return {
      accessToken: session.access_token,
      baseUrl: session.api_base_url,
    };
  } catch {
    return null;
  }
}
