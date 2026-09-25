import { defineStore } from "pinia";
import { apiRequest } from "../../services/api";

const STORAGE_KEY = "admin_session";

// "Remember me" keeps the session in localStorage (survives closing the browser);
// otherwise sessionStorage, which the browser clears when the tab is closed.
function storages() {
  try {
    return [localStorage, sessionStorage];
  } catch {
    return [];
  }
}

function readSession() {
  for (const storage of storages()) {
    try {
      const session = JSON.parse(storage.getItem(STORAGE_KEY) || "null");
      // Drop sessions the server would reject anyway
      if (session?.token && new Date(session.expires_at.replace(" ", "T")) > new Date()) return session;
      storage.removeItem(STORAGE_KEY);
    } catch {
      // Storage blocked or corrupted: ignore it
    }
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
      // Set by the router when the session was ended by the server (expired, idle, revoked)
      notice: "",
    };
  },
  getters: {
    isLoggedIn: (state) => Boolean(state.token),
  },
  actions: {
    /** Returns { twoFactor: true, challenge } when a 2FA code is still needed */
    async login(username, password, remember) {
      const data = await apiRequest("/api/auth/login", { method: "POST", body: { username, password, remember } });
      if (data.two_factor_required) return { twoFactor: true, challenge: data.challenge, remember };
      this.store(data, remember);
      return { twoFactor: false };
    },
    async verifyTwoFactor(challenge, code, remember) {
      const data = await apiRequest("/api/auth/2fa/verify", { method: "POST", body: { challenge, code } });
      this.store(data, remember);
    },
    async setup(payload) {
      const data = await apiRequest("/api/auth/setup", { method: "POST", body: payload });
      this.store(data, false);
    },
    store(data, remember) {
      this.token = data.token;
      this.expiresAt = data.expires_at;
      this.admin = data.admin;
      this.notice = "";
      const session = JSON.stringify({ token: data.token, expires_at: data.expires_at, admin: data.admin });
      const [local, perTab] = storages();
      try {
        (remember ? local : perTab)?.setItem(STORAGE_KEY, session);
        (remember ? perTab : local)?.removeItem(STORAGE_KEY);
      } catch {
        // Not persisted: the session lasts until this page is reloaded
      }
    },
    setTwoFactor(enabled) {
      if (this.admin) this.admin = { ...this.admin, totp_enabled: enabled };
    },
    async logout() {
      if (this.token) {
        // Best effort: the local session is cleared even if the server is unreachable
        await apiRequest("/api/auth/logout", { method: "POST", token: this.token }).catch(() => {});
      }
      this.clear();
    },
    clear(notice = "") {
      this.token = this.expiresAt = this.admin = null;
      this.notice = notice;
      for (const storage of storages()) {
        try {
          storage.removeItem(STORAGE_KEY);
        } catch {
          // ignore
        }
      }
    },
  },
});
