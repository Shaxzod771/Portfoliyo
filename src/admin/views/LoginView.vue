<script setup>
import { ref, nextTick, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { apiRequest, hasApi } from "../../services/api";
import PasswordField from "../components/PasswordField.vue";

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

// step: "credentials" → (if 2FA is on) "code"
const step = ref("credentials");
const username = ref("");
const password = ref("");
const remember = ref(false);
const code = ref("");
const codeInput = ref(null);
let challenge = null;

const loading = ref(false);
const error = ref(auth.notice);
const attemptsLeft = ref(null);
const locked = ref(false);

onMounted(async () => {
  if (!hasApi) return;
  // No admin yet → first-run setup page
  try {
    const status = await apiRequest("/api/auth/setup");
    if (status.needs_setup) router.replace({ name: "setup" });
  } catch (err) {
    error.value = err.message;
  }
});

function goToApp() {
  const redirect = route.query.redirect;
  router.replace(typeof redirect === "string" && redirect.startsWith("/") && !redirect.startsWith("//") ? redirect : "/");
}

function showFailure(err) {
  error.value = err.message;
  const left = err.errors?.attempts_left;
  attemptsLeft.value = left !== undefined ? Number(left) : null;
  locked.value = err.status === 429;
}

async function submitCredentials() {
  if (loading.value) return;
  error.value = "";
  if (!username.value.trim() || !password.value) {
    error.value = "Login va parolni kiriting";
    return;
  }

  loading.value = true;
  try {
    const result = await auth.login(username.value.trim(), password.value, remember.value);
    password.value = "";
    if (result.twoFactor) {
      challenge = result.challenge;
      step.value = "code";
      attemptsLeft.value = null;
      await nextTick();
      codeInput.value?.focus();
    } else {
      goToApp();
    }
  } catch (err) {
    showFailure(err);
    password.value = "";
  } finally {
    loading.value = false;
  }
}

async function submitCode() {
  if (loading.value) return;
  error.value = "";
  const clean = code.value.replace(/\s/g, "");
  if (!/^\d{6}$/.test(clean)) {
    error.value = "6 xonali kodni kiriting";
    return;
  }

  loading.value = true;
  try {
    await auth.verifyTwoFactor(challenge, clean, remember.value);
    goToApp();
  } catch (err) {
    showFailure(err);
    code.value = "";
    // The ticket expired or ran out of tries: start again from the password
    if (err.status === 401 && !err.errors?.attempts_left) backToCredentials(err.message);
  } finally {
    loading.value = false;
  }
}

function backToCredentials(message = "") {
  step.value = "credentials";
  challenge = null;
  code.value = "";
  error.value = message;
}
</script>

<template>
  <div class="auth-page">
    <div class="card auth-card">
      <div class="brand">&lt;SHAXZOD<span class="accent">.DEV</span>/&gt;</div>

      <div v-if="!hasApi" class="alert alert-error" role="alert">
        <i class="bi bi-plug" aria-hidden="true"></i>
        <span><code>VITE_API_URL</code> sozlanmagan. <code>.env.local</code> faylida backend manzilini yozing va dev serverni qayta ishga tushiring.</span>
      </div>

      <!-- Step 1: login + password -->
      <form v-if="step === 'credentials'" class="stack" @submit.prevent="submitCredentials" novalidate>
        <h1>Admin panelga kirish</h1>

        <div v-if="error" class="alert" :class="locked ? 'alert-error' : 'alert-warn'" role="alert">
          <i class="bi" :class="locked ? 'bi-lock' : 'bi-exclamation-circle'" aria-hidden="true"></i>
          <span>{{ error }}</span>
        </div>

        <label class="field">
          <span>Login</span>
          <input v-model="username" class="input" type="text" autocomplete="username" autofocus required />
        </label>

        <PasswordField v-model="password" label="Parol" />

        <div v-if="attemptsLeft !== null && attemptsLeft > 0" class="attempts" aria-hidden="true">
          <span v-for="i in 5" :key="i" :class="{ used: i > attemptsLeft }"></span>
        </div>

        <label class="check">
          <input v-model="remember" type="checkbox" />
          <span>Meni eslab qol <span class="hint">(faqat shaxsiy kompyuterda)</span></span>
        </label>

        <button type="submit" class="btn btn-primary w-full" :disabled="loading || !hasApi || locked">
          <span v-if="loading" class="spinner" aria-hidden="true"></span>
          {{ loading ? "Tekshirilmoqda…" : "Kirish" }}
        </button>
        <button v-if="locked" type="button" class="btn btn-sm" @click="locked = false">Qayta urinish</button>
      </form>

      <!-- Step 2: code from the authenticator app -->
      <form v-else class="stack" @submit.prevent="submitCode" novalidate>
        <h1>Ikki bosqichli tekshiruv</h1>
        <p class="hint">Google Authenticator (yoki shunga o‘xshash) ilovadagi 6 xonali kodni kiriting.</p>

        <div v-if="error" class="alert" :class="locked ? 'alert-error' : 'alert-warn'" role="alert">
          <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ error }}</span>
        </div>

        <label class="field">
          <span>Tasdiqlash kodi</span>
          <input ref="codeInput" v-model="code" class="input code-input" type="text" inputmode="numeric"
            autocomplete="one-time-code" maxlength="7" placeholder="123 456" />
        </label>

        <button type="submit" class="btn btn-primary w-full" :disabled="loading || locked">
          <span v-if="loading" class="spinner" aria-hidden="true"></span>
          {{ loading ? "Tekshirilmoqda…" : "Tasdiqlash" }}
        </button>
        <button type="button" class="btn btn-sm" @click="backToCredentials()">
          <i class="bi bi-arrow-left" aria-hidden="true"></i> Orqaga
        </button>
      </form>

      <p class="foot hint"><i class="bi bi-shield-lock" aria-hidden="true"></i> 5 ta noto‘g‘ri urinishdan keyin kirish vaqtincha bloklanadi</p>
    </div>
  </div>
</template>

<style scoped>
.stack { display: flex; flex-direction: column; gap: 16px; }
h1 { font-size: 1.35rem; }
.w-full { width: 100%; padding: 12px; }
code { font-size: 0.85em; }

.alert-warn { background: rgba(255, 176, 32, 0.12); color: #ffd48a; }

.check { display: flex; gap: 8px; align-items: center; font-size: 0.9rem; cursor: pointer; }
.check input { accent-color: var(--accent); }

.attempts { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-top: -6px; }
.attempts span { height: 4px; border-radius: 2px; background: var(--accent); }
.attempts span.used { background: var(--danger); opacity: 0.6; }

.code-input { font-size: 1.4rem; letter-spacing: 6px; text-align: center; font-variant-numeric: tabular-nums; }

.foot { margin: 0; display: flex; gap: 6px; align-items: center; justify-content: center; }
</style>
