import { apiRequest } from "../services/api";
import { useAuthStore } from "./stores/auth";
import router from "./router";

/** Authenticated request for the admin panel. An ended session sends the user back to the login page. */
export async function adminApi(path, options = {}) {
  const auth = useAuthStore();
  try {
    return await apiRequest(path, { ...options, token: auth.token });
  } catch (err) {
    if (err.status === 401) {
      // The server says why (expired, idle, revoked from another device) — show it on the login page
      auth.clear(err.message);
      router.replace({ name: "login", query: { redirect: router.currentRoute.value.fullPath } });
    }
    throw err;
  }
}
