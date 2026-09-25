import { defineStore } from "pinia";
import { apiRequest } from "../../services/api";

const STORAGE_KEY = "admin_session";

function readSession() {
  try {
    const session = JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
    // Drop sessions the server would reject anyway
    if (session?.token && new Date(session.expires_at.replace(" ", "T")) > new Date()) return session;
  } catch {
    // Storage blocked or corrupted: start logged out
  }
  return null;
}

export const useAuthStore = defineStore("auth", {
  state: () => {
    const session = readSession();
    return {
      token: session?.token || null,
      expiresAt: session?.expires_at || null,
      admin: session?.admin || null,
    };
  },
  getters: {
    isLoggedIn: (state) => Boolean(state.token),
  },
  actions: {
    async login(username, password) {
      const data = await apiRequest("/api/auth/login", { method: "POST", body: { username, password } });
      this.token = data.token;
      this.expiresAt = data.expires_at;
      this.admin = data.admin;
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ token: data.token, expires_at: data.expires_at, admin: data.admin }));
      } catch {
        // Not persisted: the session lasts until the tab is closed
      }
    },
    async logout() {
      if (this.token) {
        // Best effort: the local session is cleared even if the server is unreachable
        await apiRequest("/api/auth/logout", { method: "POST", token: this.token }).catch(() => {});
      }
      this.clear();
    },
    clear() {
      this.token = this.expiresAt = this.admin = null;
      try {
        localStorage.removeItem(STORAGE_KEY);
      } catch {
        // ignore
      }
    },
  },
});
