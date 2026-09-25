<script setup>
import { reactive, ref, onMounted } from "vue";
import QRCode from "qrcode";
import { adminApi } from "../api";
import { useAuthStore } from "../stores/auth";
import { formatDate } from "../format";
import { passwordProblem } from "../passwordStrength";
import PasswordField from "../components/PasswordField.vue";

const auth = useAuthStore();

// ─── Password ───
const pw = reactive({ current_password: "", new_password: "", new_password_confirmation: "" });
const pwErrors = ref({});
const pwMessage = ref({ type: "", text: "" });
const pwSaving = ref(false);

async function changePassword() {
  if (pwSaving.value) return;
  pwMessage.value = { type: "", text: "" };
  const local = {};
  if (!pw.current_password) local.current_password = "Joriy parolni kiriting";
  const problem = passwordProblem(pw.new_password, auth.admin?.username);
  if (problem) local.new_password = problem;
  if (pw.new_password !== pw.new_password_confirmation) local.new_password_confirmation = "Parollar mos kelmadi";
  pwErrors.value = local;
  if (Object.keys(local).length) return;

  pwSaving.value = true;
  try {
    const res = await adminApi("/api/auth/password", { method: "PUT", body: { ...pw } });
    pwMessage.value = { type: "success", text: res.message };
    pw.current_password = pw.new_password = pw.new_password_confirmation = "";
    loadSessions();
    loadLogs();
  } catch (err) {
    pwMessage.value = { type: "error", text: err.message };
    pwErrors.value = err.errors || {};
  } finally {
    pwSaving.value = false;
  }
}

// ─── Two-factor ───
// tfa.step: "idle" | "password" (confirm password) | "scan" (QR + code) | "disable"
const tfa = reactive({ step: "idle", password: "", code: "", secret: "", qr: "", error: "", busy: false });

function tfaReset(step = "idle") {
  Object.assign(tfa, { step, password: "", code: "", secret: "", qr: "", error: "", busy: false });
}

async function tfaStart() {
  tfa.busy = true;
  tfa.error = "";
  try {
    const res = await adminApi("/api/auth/2fa/setup", { method: "POST", body: { password: tfa.password } });
    tfa.secret = res.secret;
    tfa.qr = await QRCode.toDataURL(res.uri, { width: 220, margin: 1 });
    tfa.password = "";
    tfa.step = "scan";
  } catch (err) {
    tfa.error = err.errors?.password || err.message;
  } finally {
    tfa.busy = false;
  }
}

async function tfaEnable() {
  tfa.busy = true;
  tfa.error = "";
  try {
    await adminApi("/api/auth/2fa/enable", { method: "POST", body: { code: tfa.code.replace(/\s/g, "") } });
    auth.setTwoFactor(true);
    tfaReset();
    loadLogs();
  } catch (err) {
    tfa.error = err.errors?.code || err.message;
  } finally {
    tfa.busy = false;
  }
}

async function tfaDisable() {
  tfa.busy = true;
  tfa.error = "";
  try {
    await adminApi("/api/auth/2fa/disable", { method: "POST", body: { password: tfa.password, code: tfa.code.replace(/\s/g, "") } });
    auth.setTwoFactor(false);
    tfaReset();
    loadLogs();
  } catch (err) {
    tfa.error = err.errors?.password || err.errors?.code || err.message;
  } finally {
    tfa.busy = false;
  }
}

// ─── Sessions and log ───
const sessions = ref([]);
const logs = ref([]);
const listError = ref("");

async function loadSessions() {
  try {
    sessions.value = (await adminApi("/api/auth/sessions")).data;
  } catch (err) {
    listError.value = err.message;
  }
}

async function loadLogs() {
  try {
    logs.value = (await adminApi("/api/auth/logs")).data;
  } catch (err) {
    listError.value = err.message;
  }
}

async function revoke(session) {
  if (!confirm("Bu qurilmadagi sessiya yopilsinmi?")) return;
  try {
    await adminApi(`/api/auth/sessions/${session.id}`, { method: "DELETE" });
    sessions.value = sessions.value.filter((s) => s.id !== session.id);
  } catch (err) {
    listError.value = err.message;
  }
}

async function revokeOthers() {
  if (!confirm("Shu qurilmadan boshqa barcha sessiyalar yopilsinmi?")) return;
  try {
    await adminApi("/api/auth/sessions", { method: "DELETE" });
    sessions.value = sessions.value.filter((s) => s.current);
    loadLogs();
  } catch (err) {
    listError.value = err.message;
  }
}

/** Short, readable device name from a user-agent string */
function device(ua) {
  if (!ua) return "Noma’lum qurilma";
  const browser = /Edg\//.test(ua) ? "Edge" : /OPR\//.test(ua) ? "Opera" : /Firefox\//.test(ua) ? "Firefox"
    : /Chrome\//.test(ua) ? "Chrome" : /Safari\//.test(ua) ? "Safari" : /curl/i.test(ua) ? "curl" : "Brauzer";
  const os = /Windows/.test(ua) ? "Windows" : /Android/.test(ua) ? "Android" : /iPhone|iPad/.test(ua) ? "iOS"
    : /Mac OS/.test(ua) ? "macOS" : /Linux/.test(ua) ? "Linux" : "";
  return os ? `${browser} · ${os}` : browser;
}

const WARN_EVENTS = ["login_failed", "login_locked", "2fa_failed"];

onMounted(() => {
  loadSessions();
  loadLogs();
});
</script>

<template>
  <div>
    <div class="page-head"><h1>Profil va xavfsizlik</h1></div>

    <div class="grid">
      <!-- Password -->
      <form class="card stack" @submit.prevent="changePassword" novalidate>
        <h2><i class="bi bi-key" aria-hidden="true"></i> Parolni o‘zgartirish</h2>
        <div v-if="pwMessage.text" class="alert" :class="`alert-${pwMessage.type}`" role="status">
          <i class="bi" :class="pwMessage.type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle'" aria-hidden="true"></i>
          <span>{{ pwMessage.text }}</span>
        </div>
        <PasswordField v-model="pw.current_password" label="Joriy parol" :error="pwErrors.current_password" />
        <PasswordField v-model="pw.new_password" label="Yangi parol" autocomplete="new-password" strength
          :username="auth.admin?.username" :error="pwErrors.new_password" />
        <PasswordField v-model="pw.new_password_confirmation" label="Yangi parolni takrorlang" autocomplete="new-password"
          :error="pwErrors.new_password_confirmation" />
        <button type="submit" class="btn btn-primary" :disabled="pwSaving">
          <span v-if="pwSaving" class="spinner" aria-hidden="true"></span>
          {{ pwSaving ? "Saqlanmoqda…" : "Parolni yangilash" }}
        </button>
        <p class="hint">Parol o‘zgargach, boshqa qurilmalardagi sessiyalar yopiladi.</p>
      </form>

      <!-- Two-factor -->
      <section class="card stack">
        <h2>
          <i class="bi bi-shield-lock" aria-hidden="true"></i> Ikki bosqichli tekshiruv (2FA)
          <span class="badge" :class="auth.admin?.totp_enabled ? 'badge-success' : ''">{{ auth.admin?.totp_enabled ? "Yoqilgan" : "O‘chiq" }}</span>
        </h2>
        <p class="hint">Yoqilsa, kirishda paroldan tashqari telefondagi ilova (Google Authenticator, Authy…) bergan kod ham so‘raladi. Parol o‘g‘irlansa ham akkaunt himoyalangan bo‘ladi.</p>

        <div v-if="tfa.error" class="alert alert-error" role="alert">
          <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ tfa.error }}</span>
        </div>

        <template v-if="!auth.admin?.totp_enabled">
          <button v-if="tfa.step === 'idle'" type="button" class="btn btn-primary" @click="tfaReset('password')">
            <i class="bi bi-shield-plus" aria-hidden="true"></i> 2FA ni yoqish
          </button>

          <form v-else-if="tfa.step === 'password'" class="stack" @submit.prevent="tfaStart">
            <PasswordField v-model="tfa.password" label="Davom etish uchun parolingiz" />
            <div class="row-btns">
              <button type="submit" class="btn btn-primary" :disabled="tfa.busy || !tfa.password">Davom etish</button>
              <button type="button" class="btn" @click="tfaReset()">Bekor qilish</button>
            </div>
          </form>

          <form v-else-if="tfa.step === 'scan'" class="stack" @submit.prevent="tfaEnable">
            <ol class="steps">
              <li>Telefonda Google Authenticator ilovasini oching va QR kodni skanerlang.</li>
              <li>Ilova ko‘rsatgan 6 xonali kodni kiriting.</li>
            </ol>
            <img :src="tfa.qr" alt="2FA QR kodi" class="qr" width="220" height="220" />
            <details class="hint">
              <summary>QR skanerlanmayaptimi? Kalitni qo‘lda kiriting</summary>
              <code class="secret">{{ tfa.secret.match(/.{1,4}/g).join(" ") }}</code>
            </details>
            <label class="field">
              <span>Ilovadagi kod</span>
              <input v-model="tfa.code" class="input code-input" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="123 456" />
            </label>
            <div class="row-btns">
              <button type="submit" class="btn btn-primary" :disabled="tfa.busy">Tasdiqlash va yoqish</button>
              <button type="button" class="btn" @click="tfaReset()">Bekor qilish</button>
            </div>
          </form>
        </template>

        <template v-else>
          <button v-if="tfa.step === 'idle'" type="button" class="btn btn-danger" @click="tfaReset('disable')">
            <i class="bi bi-shield-x" aria-hidden="true"></i> 2FA ni o‘chirish
          </button>
          <form v-else class="stack" @submit.prevent="tfaDisable">
            <PasswordField v-model="tfa.password" label="Parol" />
            <label class="field">
              <span>Ilovadagi kod</span>
              <input v-model="tfa.code" class="input code-input" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="123 456" />
            </label>
            <div class="row-btns">
              <button type="submit" class="btn btn-danger" :disabled="tfa.busy">O‘chirish</button>
              <button type="button" class="btn" @click="tfaReset()">Bekor qilish</button>
            </div>
          </form>
          <p class="hint">Telefon yo‘qolsa: <code>php backend/bin/create-admin.php {{ auth.admin?.username }} --disable-2fa</code></p>
        </template>
      </section>
    </div>

    <div v-if="listError" class="alert alert-error mt" role="alert">
      <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ listError }}</span>
    </div>

    <!-- Sessions -->
    <section class="card mt">
      <div class="section-head">
        <h2><i class="bi bi-laptop" aria-hidden="true"></i> Faol sessiyalar</h2>
        <button v-if="sessions.length > 1" type="button" class="btn btn-sm btn-danger" @click="revokeOthers">Boshqalarini yopish</button>
      </div>
      <ul class="rows">
        <li v-for="s in sessions" :key="s.id" class="row">
          <i class="bi" :class="/Android|iPhone/.test(s.user_agent) ? 'bi-phone' : 'bi-laptop'" aria-hidden="true"></i>
          <div class="grow">
            <strong>{{ device(s.user_agent) }}</strong>
            <span v-if="s.current" class="badge badge-accent">Shu qurilma</span>
            <span v-if="s.remember" class="badge">Eslab qolingan</span>
            <div class="hint">IP {{ s.ip }} · kirgan: {{ formatDate(s.created_at) }} · oxirgi faollik: {{ formatDate(s.last_used_at) }}</div>
          </div>
          <button v-if="!s.current" type="button" class="btn btn-sm" @click="revoke(s)">Yopish</button>
        </li>
      </ul>
    </section>

    <!-- Security log -->
    <section class="card mt">
      <div class="section-head">
        <h2><i class="bi bi-clock-history" aria-hidden="true"></i> Xavfsizlik tarixi</h2>
        <span class="hint">oxirgi 30 ta hodisa</span>
      </div>
      <ul class="rows">
        <li v-for="(l, i) in logs" :key="i" class="row">
          <i class="bi" :class="WARN_EVENTS.includes(l.event) ? 'bi-exclamation-triangle warn' : 'bi-check2-circle ok'" aria-hidden="true"></i>
          <div class="grow">
            <strong>{{ l.label }}</strong>
            <div class="hint">{{ device(l.user_agent) }} · IP {{ l.ip }}</div>
          </div>
          <span class="hint nowrap">{{ formatDate(l.created_at) }}</span>
        </li>
      </ul>
    </section>
  </div>
</template>

<style scoped>
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px; align-items: start; }
.stack { display: flex; flex-direction: column; gap: 14px; }
.mt { margin-top: 16px; }
h2 { font-size: 1.05rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
p.hint { margin: 0; }
.row-btns { display: flex; gap: 8px; flex-wrap: wrap; }

.steps { margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 4px; font-size: 0.9rem; }
.qr { border-radius: 8px; background: #fff; align-self: flex-start; }
.secret { display: block; margin-top: 6px; font-size: 0.95rem; letter-spacing: 1px; word-break: break-all; color: var(--text); }
.code-input { font-size: 1.2rem; letter-spacing: 5px; text-align: center; max-width: 220px; }

.section-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 8px; }
.rows { list-style: none; margin: 0; padding: 0; }
.row { display: flex; gap: 12px; align-items: center; padding: 12px 4px; }
.row + .row { border-top: 1px solid var(--border); }
.row > .bi { font-size: 1.15rem; color: var(--muted); }
.row > .bi.warn { color: #ffb020; }
.row > .bi.ok { color: var(--success); }
.grow { flex: 1; min-width: 0; display: flex; flex-wrap: wrap; gap: 4px 8px; align-items: center; }
.grow .hint { width: 100%; }
.nowrap { white-space: nowrap; }
code { font-size: 0.85em; }

@media (max-width: 420px) {
  .grid { grid-template-columns: 1fr; }
}
</style>
