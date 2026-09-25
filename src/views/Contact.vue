<script setup>
import { ref, computed, onUnmounted } from "vue";
import { useSettingsStore } from "../stores/settings";
import { translations } from "../constants/translations";
import { CONTACT, SOCIALS } from "../constants/site";
import { sendContactMessage } from "../services/contact";
import { useReveal } from "../composables/useReveal";

const settings = useSettingsStore();
const t = computed(() => translations[settings.lang]?.contact || translations.uz.contact);
const contactSocials = SOCIALS.filter((s) => s.name !== "Instagram");
const root = ref(null);
useReveal(root);

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const COOLDOWN_MS = 30_000;

const name = ref("");
const email = ref("");
const subject = ref("");
const message = ref("");
const website = ref(""); // honeypot: real visitors never see or fill this field

// status: "idle" | "sending" | "sent" | "error"
const status = ref("idle");
const errorText = ref("");
let lastSentAt = 0;
let resetTimer = null;

function showError(text) {
  status.value = "error";
  errorText.value = text;
}

async function sendMessage() {
  if (status.value === "sending") return;

  if (!name.value.trim() || !email.value.trim() || !message.value.trim()) {
    return showError(t.value.error);
  }
  if (!EMAIL_RE.test(email.value.trim())) {
    return showError(t.value.error_email);
  }

  // Bots fill every field; pretend success so they move on
  if (website.value || Date.now() - lastSentAt < COOLDOWN_MS) {
    status.value = "sent";
    return;
  }

  status.value = "sending";
  errorText.value = "";
  try {
    await sendContactMessage({
      name: name.value.trim(),
      email: email.value.trim(),
      subject: subject.value.trim(),
      message: message.value.trim(),
      lang: settings.lang,
    });
    lastSentAt = Date.now();
    status.value = "sent";
    name.value = email.value = subject.value = message.value = "";
    clearTimeout(resetTimer);
    resetTimer = setTimeout(() => {
      if (status.value === "sent") status.value = "idle";
    }, 6000);
  } catch (err) {
    console.error("Contact form:", err);
    showError(t.value.error_send);
  }
}

onUnmounted(() => clearTimeout(resetTimer));
</script>

<template>
  <div class="contact-section" ref="root">
    <div class="container">

      <!-- EDITORIAL HEADER -->
      <div class="contact-header animate-up">
        <h2 class="editorial-title">
          {{ t.title }} 
          <span class="text-accent">{{ t.subtitle }}</span> {{ t.together }}
        </h2>
      </div>

      <div class="row g-5 align-items-start mt-5">

        <!-- ── CONTACT INFO ── -->
        <div class="col-lg-5 animate-up" style="--delay: 0.2s">
          <div class="info-block">
            <h3 class="info-block-title">{{ t.get_in_touch }}</h3>
            <p class="info-block-desc">
              {{ t.desc }}
            </p>

            <div class="info-items mt-5">
              <div class="info-item">
                <span class="info-label">{{ t.email }}</span>
                <a :href="`mailto:${CONTACT.email}`" class="info-value hover-underline">{{ CONTACT.email }}</a>
              </div>
              <div class="info-item mt-4">
                <span class="info-label">{{ t.phone }}</span>
                <a :href="`tel:${CONTACT.phone}`" class="info-value hover-underline">{{ CONTACT.phoneDisplay }}</a>
              </div>
              <div class="info-item mt-4">
                <span class="info-label">{{ t.location }}</span>
                <span class="info-value">{{ t.location_val }}</span>
              </div>
            </div>

            <!-- SOCIALS -->
            <div class="social-row mt-5">
              <template v-for="(social, i) in contactSocials" :key="social.name">
                <span v-if="i > 0" class="separator" aria-hidden="true">/</span>
                <a :href="social.url" class="social-link" target="_blank" rel="noopener noreferrer" v-magnetic="10">
                  {{ social.name }}
                </a>
              </template>
            </div>
          </div>
        </div>

        <!-- ── FORM ── -->
        <div class="col-lg-7 animate-up" style="--delay: 0.4s">
          <form class="minimal-form" @submit.prevent="sendMessage" novalidate>
            <!-- Status banners -->
            <div class="form-banner success-banner" v-if="status === 'sent'" role="status">
              <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
              {{ t.success }}
            </div>
            <div class="form-banner error-banner" v-if="status === 'error'" role="alert">
              <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
              {{ errorText }}
            </div>

            <!-- Honeypot field for spam bots — hidden from people and screen readers -->
            <div class="hp-field" aria-hidden="true">
              <label for="cf-website">Website</label>
              <input id="cf-website" type="text" v-model="website" tabindex="-1" autocomplete="off" />
            </div>

            <div class="row g-4">
              <div class="col-md-6">
                <div class="input-wrapper">
                  <input id="cf-name" name="name" type="text" class="minimal-input" v-model="name" placeholder=" "
                    autocomplete="name" maxlength="100" required />
                  <label for="cf-name" class="floating-label">{{ t.form.name }}</label>
                  <div class="input-line"></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="input-wrapper">
                  <input id="cf-email" name="email" type="email" class="minimal-input" v-model="email" placeholder=" "
                    autocomplete="email" maxlength="150" required />
                  <label for="cf-email" class="floating-label">{{ t.form.email }}</label>
                  <div class="input-line"></div>
                </div>
              </div>
              <div class="col-12">
                <div class="input-wrapper">
                  <input id="cf-subject" name="subject" type="text" class="minimal-input" v-model="subject"
                    placeholder=" " maxlength="150" />
                  <label for="cf-subject" class="floating-label">{{ t.form.subject }}</label>
                  <div class="input-line"></div>
                </div>
              </div>
              <div class="col-12">
                <div class="input-wrapper">
                  <textarea id="cf-message" name="message" class="minimal-input" rows="4" v-model="message"
                    placeholder=" " maxlength="3000" required></textarea>
                  <label for="cf-message" class="floating-label">{{ t.form.message }}</label>
                  <div class="input-line"></div>
                </div>
              </div>
              <div class="col-12 mt-5">
                <button type="submit" class="btn-primary-custom w-100 py-3" :disabled="status === 'sending'"
                  v-magnetic="5">
                  <template v-if="status === 'sending'">
                    {{ t.form.sending }} <span class="spinner" aria-hidden="true"></span>
                  </template>
                  <template v-else>
                    {{ t.form.submit }} <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                  </template>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── SECTION ─── */
.contact-section {
  background: transparent;
  padding: 120px 0;
  border-top: 1px solid var(--border-color);
}

/* ─── HEADER ─── */
.editorial-title {
  font-size: clamp(3rem, 6vw, 6rem);
  font-weight: 800;
  line-height: 1.05;
  letter-spacing: -0.02em;
  text-transform: uppercase;
  color: var(--text-color);
  margin: 0;
  max-width: 900px;
}

.text-accent {
  color: var(--accent);
}

/* ─── INFO BLOCK ─── */
.info-block {
  padding-right: 40px;
}

.info-block-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-color);
  margin-bottom: 16px;
}

.info-block-desc {
  font-size: 1rem;
  line-height: 1.6;
  color: var(--text-muted);
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-label {
  font-size: 0.75rem;
  letter-spacing: 2px;
  color: var(--text-muted);
  text-transform: uppercase;
}

.info-value {
  font-size: 1.2rem;
  font-weight: 500;
  color: var(--text-color);
  text-decoration: none;
}

.hover-underline {
  position: relative;
  display: inline-block;
  width: fit-content;
}

.hover-underline::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 1px;
  background: var(--accent);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform 0.4s cubic-bezier(0.19, 1, 0.22, 1);
}

.hover-underline:hover::after {
  transform: scaleX(1);
  transform-origin: left;
}

/* ─── SOCIALS ─── */
.social-row {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.social-link {
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--text-color);
  text-decoration: none;
  transition: color 0.3s ease;
  display: inline-block;
}

.social-link:hover {
  color: var(--accent);
}

.separator {
  color: var(--border-color);
}

/* ─── MINIMAL FORM ─── */
.minimal-form {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 40px;
  backdrop-filter: blur(10px);
}

.form-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  border-radius: 12px;
  padding: 16px;
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 24px;
}

.success-banner {
  background: var(--accent-dim);
  border: 1px solid var(--accent);
  color: var(--accent);
}

.error-banner {
  background: rgba(255, 77, 109, 0.1);
  border: 1px solid #ff4d6d;
  color: #ff8095;
}

.hp-field {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

.btn-primary-custom:disabled {
  opacity: 0.7;
  cursor: wait;
}

.spinner {
  width: 16px;
  height: 16px;
  margin-left: 10px;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ─── INPUTS ─── */
.input-wrapper {
  position: relative;
  padding-top: 12px;
}

.minimal-input {
  width: 100%;
  background: transparent;
  border: none;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  padding: 12px 0;
  color: var(--text-color);
  font-size: 1rem;
  font-family: inherit;
  outline: none;
  resize: none;
}

.floating-label {
  position: absolute;
  left: 0;
  top: 24px;
  font-size: 0.9rem;
  color: var(--text-muted);
  pointer-events: none;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.minimal-input:focus ~ .floating-label,
.minimal-input:not(:placeholder-shown) ~ .floating-label {
  top: -4px;
  font-size: 0.7rem;
  color: var(--accent);
}

.input-line {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 1px;
  background: var(--accent);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.minimal-input:focus ~ .input-line {
  transform: scaleX(1);
  transform-origin: left;
}

/* ─── ANIMATIONS ─── */
.animate-up {
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), 
              transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
  transition-delay: var(--delay, 0s);
}

.animate-up.in-view {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 991px) {
  .info-block {
    padding-right: 0;
  }
  
  .minimal-form {
    padding: 24px;
  }
}

@media (max-width: 768px) {
  .contact-section {
    padding: 60px 0;
  }

  .editorial-title {
    font-size: clamp(1.8rem, 7vw, 2.8rem);
    line-height: 1.1;
  }

  .info-block-title {
    font-size: 1.2rem;
  }

  .info-value {
    font-size: 1rem;
    word-break: break-all;
  }

  .minimal-form {
    padding: 20px;
  }

  .social-row {
    gap: 12px;
  }
}
</style>
