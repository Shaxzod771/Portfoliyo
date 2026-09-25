<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { hasApi } from "../../services/api";

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const username = ref("");
const password = ref("");
const showPassword = ref(false);
const loading = ref(false);
const error = ref("");

async function submit() {
  if (loading.value) return;
  error.value = "";
  if (!username.value.trim() || !password.value) {
    error.value = "Login va parolni kiriting";
    return;
  }

  loading.value = true;
  try {
    await auth.login(username.value.trim(), password.value);
    const redirect = typeof route.query.redirect === "string" && route.query.redirect.startsWith("/") ? route.query.redirect : "/";
    router.replace(redirect);
  } catch (err) {
    error.value = err.message;
    password.value = "";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="login-page">
    <form class="card login-card" @submit.prevent="submit" novalidate>
      <div class="brand">&lt;SHAXZOD<span class="accent">.DEV</span>/&gt;</div>
      <h1>Admin panelga kirish</h1>

      <div v-if="!hasApi" class="alert alert-error" role="alert">
        <i class="bi bi-plug" aria-hidden="true"></i>
        <span><code>VITE_API_URL</code> sozlanmagan. <code>.env.local</code> faylida backend manzilini yozing va dev serverni qayta ishga tushiring.</span>
      </div>
      <div v-else-if="error" class="alert alert-error" role="alert">
        <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ error }}</span>
      </div>

      <label class="field">
        <span>Login</span>
        <input v-model="username" class="input" type="text" autocomplete="username" autofocus required />
      </label>

      <label class="field">
        <span>Parol</span>
        <div class="password-wrap">
          <input v-model="password" class="input" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required />
          <button type="button" class="btn btn-icon eye" :aria-label="showPassword ? 'Parolni yashirish' : 'Parolni ko‘rsatish'"
            @click="showPassword = !showPassword">
            <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
          </button>
        </div>
      </label>

      <button type="submit" class="btn btn-primary w-full" :disabled="loading || !hasApi">
        <span v-if="loading" class="spinner" aria-hidden="true"></span>
        {{ loading ? "Tekshirilmoqda…" : "Kirish" }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 16px;
  background: radial-gradient(circle at 70% 10%, var(--accent-dim), transparent 50%), var(--bg);
}

.login-card {
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 32px;
}

.brand { font-weight: 800; font-size: 0.95rem; }
.accent { color: var(--accent); }
h1 { font-size: 1.35rem; }

.password-wrap { position: relative; }
.password-wrap .input { padding-right: 44px; }
.eye { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); border: none; }

.w-full { width: 100%; padding: 12px; }
code { font-size: 0.85em; }
</style>
