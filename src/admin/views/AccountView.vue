<script setup>
import { reactive, ref } from "vue";
import { adminApi } from "../api";
import { useAuthStore } from "../stores/auth";

const auth = useAuthStore();
const form = reactive({ current_password: "", new_password: "", new_password_confirmation: "" });
const errors = ref({});
const error = ref("");
const success = ref("");
const saving = ref(false);

async function submit() {
  if (saving.value) return;
  errors.value = {};
  error.value = success.value = "";

  const local = {};
  if (!form.current_password) local.current_password = "Joriy parolni kiriting";
  if (form.new_password.length < 8) local.new_password = "Kamida 8 ta belgi";
  if (form.new_password !== form.new_password_confirmation) local.new_password_confirmation = "Parollar mos kelmadi";
  if (Object.keys(local).length) {
    errors.value = local;
    return;
  }

  saving.value = true;
  try {
    const res = await adminApi("/api/auth/password", { method: "PUT", body: { ...form } });
    success.value = res.message;
    form.current_password = form.new_password = form.new_password_confirmation = "";
  } catch (err) {
    error.value = err.message;
    errors.value = err.errors || {};
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div>
    <div class="page-head"><h1>Profil</h1></div>

    <section class="card narrow">
      <p class="who"><i class="bi bi-person-circle" aria-hidden="true"></i> <strong>{{ auth.admin?.username }}</strong></p>
      <p class="hint">Sessiya muddati: {{ auth.expiresAt }}</p>
    </section>

    <form class="card narrow stack" @submit.prevent="submit" novalidate>
      <h2>Parolni o‘zgartirish</h2>

      <div v-if="error" class="alert alert-error" role="alert">
        <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ error }}</span>
      </div>
      <div v-if="success" class="alert alert-success" role="status">
        <i class="bi bi-check-circle" aria-hidden="true"></i><span>{{ success }}</span>
      </div>

      <label class="field">
        <span>Joriy parol</span>
        <input v-model="form.current_password" class="input" :class="{ invalid: errors.current_password }" type="password" autocomplete="current-password" />
        <span v-if="errors.current_password" class="field-error">{{ errors.current_password }}</span>
      </label>
      <label class="field">
        <span>Yangi parol</span>
        <input v-model="form.new_password" class="input" :class="{ invalid: errors.new_password }" type="password" autocomplete="new-password" maxlength="72" />
        <span v-if="errors.new_password" class="field-error">{{ errors.new_password }}</span>
        <span v-else class="hint">Kamida 8 ta belgi</span>
      </label>
      <label class="field">
        <span>Yangi parolni takrorlang</span>
        <input v-model="form.new_password_confirmation" class="input" :class="{ invalid: errors.new_password_confirmation }" type="password" autocomplete="new-password" maxlength="72" />
        <span v-if="errors.new_password_confirmation" class="field-error">{{ errors.new_password_confirmation }}</span>
      </label>

      <button type="submit" class="btn btn-primary" :disabled="saving">
        <span v-if="saving" class="spinner" aria-hidden="true"></span>
        {{ saving ? "Saqlanmoqda…" : "Parolni yangilash" }}
      </button>
      <p class="hint">Parol o‘zgargach, boshqa qurilmalardagi sessiyalar yopiladi.</p>
    </form>
  </div>
</template>

<style scoped>
.narrow { max-width: 480px; margin-bottom: 16px; }
.stack { display: flex; flex-direction: column; gap: 14px; }
.who { margin: 0 0 4px; display: flex; gap: 8px; align-items: center; font-size: 1.05rem; }
h2 { font-size: 1.1rem; }
p.hint { margin: 0; }
</style>
