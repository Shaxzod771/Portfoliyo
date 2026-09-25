<script setup>
import { ref, reactive, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { apiRequest } from "../../services/api";
import { passwordProblem } from "../passwordStrength";
import PasswordField from "../components/PasswordField.vue";

const auth = useAuthStore();
const router = useRouter();

const status = ref(null);
const form = reactive({ username: "", password: "", password_confirmation: "", setup_key: "" });
const errors = ref({});
const error = ref("");
const saving = ref(false);

onMounted(async () => {
  try {
    status.value = await apiRequest("/api/auth/setup");
    // An admin already exists: nothing to set up
    if (!status.value.needs_setup) router.replace({ name: "login" });
  } catch (err) {
    error.value = err.message;
  }
});

async function submit() {
  if (saving.value) return;
  error.value = "";

  const local = {};
  if (!/^[A-Za-z0-9_.-]{3,50}$/.test(form.username)) local.username = "3–50 ta lotin harfi, raqam yoki _ . - belgisi";
  const problem = passwordProblem(form.password, form.username);
  if (problem) local.password = problem;
  if (form.password !== form.password_confirmation) local.password_confirmation = "Parollar mos kelmadi";
  if (status.value?.key_required && !form.setup_key) local.setup_key = "O‘rnatish kalitini kiriting";
  errors.value = local;
  if (Object.keys(local).length) return;

  saving.value = true;
  try {
    await auth.setup({ ...form });
    router.replace({ name: "dashboard" });
  } catch (err) {
    error.value = err.message;
    errors.value = err.errors || {};
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <form class="card auth-card stack" @submit.prevent="submit" novalidate>
      <div class="brand">&lt;SHAXZOD<span class="accent">.DEV</span>/&gt;</div>
      <div>
        <h1>Birinchi sozlash</h1>
        <p class="hint">Hali admin yo‘q. Login va parol o‘ylab toping — shu akkaunt admin bo‘ladi. Bu sahifa faqat bir marta ochiladi.</p>
      </div>

      <div v-if="error" class="alert alert-error" role="alert">
        <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ error }}</span>
      </div>

      <label v-if="status?.key_required" class="field">
        <span>O‘rnatish kaliti</span>
        <input v-model="form.setup_key" class="input" :class="{ invalid: errors.setup_key }" type="password" autocomplete="off" />
        <span v-if="errors.setup_key" class="field-error">{{ errors.setup_key }}</span>
        <span v-else class="hint">
          {{ status.key_configured
            ? "backend/config.php → setup_key dagi qiymat"
            : "Server kompyuterida oching yoki backend/config.php da setup_key belgilang" }}
        </span>
      </label>

      <label class="field">
        <span>Login</span>
        <input v-model="form.username" class="input" :class="{ invalid: errors.username }" type="text" autocomplete="username" autofocus />
        <span v-if="errors.username" class="field-error">{{ errors.username }}</span>
      </label>

      <PasswordField v-model="form.password" label="Parol" autocomplete="new-password" strength :username="form.username" :error="errors.password" />
      <PasswordField v-model="form.password_confirmation" label="Parolni takrorlang" autocomplete="new-password" :error="errors.password_confirmation" />

      <button type="submit" class="btn btn-primary w-full" :disabled="saving || !status">
        <span v-if="saving" class="spinner" aria-hidden="true"></span>
        {{ saving ? "Yaratilmoqda…" : "Admin yaratish va kirish" }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.stack { display: flex; flex-direction: column; gap: 16px; }
h1 { font-size: 1.35rem; margin-bottom: 6px; }
p.hint { margin: 0; }
.w-full { width: 100%; padding: 12px; }
</style>
