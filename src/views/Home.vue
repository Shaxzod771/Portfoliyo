<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useSettingsStore } from "../stores/settings";
import { translations } from "../constants/translations";
import { SOCIALS } from "../constants/site";
import { useReveal } from "../composables/useReveal";

const settings = useSettingsStore();
const baseUrl = import.meta.env.BASE_URL;
const root = ref(null);
useReveal(root);

const t = computed(() => translations[settings.lang]?.home || {});
const heroSocials = SOCIALS.slice(0, 3);

// Mouse parallax effect for the hero image.
// The transform is written straight to the element so Vue doesn't re-render 60 times a second.
const visual = ref(null);
let rafId = null;
let targetX = 0;
let targetY = 0;
let currentX = 0;
let currentY = 0;

const onMouseMove = (e) => {
  const { clientX, clientY, innerWidth, innerHeight } = window;
  const xPos = (clientX / innerWidth - 0.5) * 40;
  const yPos = (clientY / innerHeight - 0.5) * 40;
  targetX = xPos;
  targetY = yPos;
};

const animateParallax = () => {
  currentX += (targetX - currentX) * 0.05;
  currentY += (targetY - currentY) * 0.05;

  if (visual.value) {
    visual.value.style.transform =
      `translate3d(${currentX}px, ${currentY}px, 0) rotate(${currentX * 0.5}deg)`;
  }

  rafId = requestAnimationFrame(animateParallax);
};

onMounted(() => {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const hasFinePointer = window.matchMedia('(pointer: fine)').matches;
  if (hasFinePointer && !reduceMotion) {
    window.addEventListener('mousemove', onMouseMove);
    animateParallax();
  }
});

onUnmounted(() => {
  window.removeEventListener('mousemove', onMouseMove);
  if (rafId) cancelAnimationFrame(rafId);
});
</script>

<template>
  <div class="hero-section" ref="root">
    <div class="container hero-container">

      <!-- ABSTRACT VISUAL (Desktop) -->
      <div class="hero-visual d-none d-lg-block" ref="visual">
        <div class="hero-image-wrapper">
          <img :src="`${baseUrl}image/ozim-uchun.jpg`" alt="Shaxzod Isomiddinov" class="hero-image" />
        </div>
      </div>

      <!-- TEXT CONTENT -->
      <div class="hero-content">
        <div class="hero-badge animate-up" style="--delay: 0.1s">
          <span class="badge-dot"></span>
          <span>{{ t.hero_badge }}</span>
        </div>

        <h1 class="hero-title animate-up" style="--delay: 0.2s">
          SHAXZOD <br />
          <span class="text-accent">ISOMIDDINOV</span>
        </h1>

        <h2 class="hero-subtitle animate-up" style="--delay: 0.3s">
          {{ t.hero_subtitle }}
        </h2>

        <p class="hero-desc animate-up" style="--delay: 0.4s">
          {{ t.hero_desc }}
        </p>

        <div class="hero-actions animate-up" style="--delay: 0.5s">
          <a href="#project" class="btn-primary-custom" v-magnetic="20">
            {{ t.hero_btn }} <i class="bi bi-arrow-down-right ms-2" aria-hidden="true"></i>
          </a>
          <div class="hero-socials">
            <a v-for="social in heroSocials" :key="social.name" :href="social.url" target="_blank"
              rel="noopener noreferrer" :aria-label="social.name" v-magnetic="15">
              <i class="bi" :class="social.icon" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* ─── SECTION ─── */
.hero-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  padding-top: 80px;
  overflow: hidden;
}

.hero-container {
  position: relative;
  z-index: 2;
  width: 100%;
}

/* ─── CONTENT ─── */
.hero-content {
  max-width: 900px;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 8px 16px;
  border: 1px solid var(--border-color);
  border-radius: 100px;
  font-size: 0.75rem;
  letter-spacing: 2px;
  color: var(--text-muted);
  margin-bottom: 2rem;
  backdrop-filter: blur(10px);
}

.badge-dot {
  width: 6px;
  height: 6px;
  background: var(--accent);
  border-radius: 50%;
  box-shadow: 0 0 10px var(--accent-glow);
  animation: pulse 2s infinite;
}

@keyframes pulse {

  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    opacity: 0.5;
    transform: scale(0.8);
  }
}

.hero-title {
  font-size: clamp(2.5rem, 8vw, 7.5rem);
  font-weight: 800;
  line-height: 1.05;
  letter-spacing: -0.03em;
  margin: 24px 0;
  text-transform: uppercase;
}

.hero-subtitle {
  font-size: clamp(1rem, 2vw, 1.2rem);
  font-weight: 600;
  letter-spacing: 4px;
  color: var(--text-color);
  margin-bottom: 24px;
}

.hero-desc {
  font-size: clamp(1rem, 1.8vw, 1.2rem);
  color: var(--text-muted);
  max-width: 600px;
  line-height: 1.6;
  margin-bottom: 40px;
}

/* ─── ACTIONS ─── */
.hero-actions {
  display: flex;
  align-items: center;
  gap: 32px;
  flex-wrap: wrap;
}

.hero-socials {
  display: flex;
  gap: 16px;
}

.hero-socials a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: 1px solid var(--border-color);
  color: var(--text-muted);
  font-size: 1.2rem;
  transition: all 0.3s ease;
}

.hero-socials a:hover {
  border-color: var(--accent);
  color: var(--accent);
  background: rgba(204, 255, 0, 0.05);
}

/* ─── ABSTRACT VISUAL ─── */
/* ─── HERO IMAGE ─── */

.hero-visual {
  position: absolute;
  right: 0;
  top: 50%;
  margin-top: -280px;
  width: 520px;
  height: 560px;
  pointer-events: none;
  z-index: -1;
  opacity: 1;
}

.hero-image-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-image {
  width: 500px;
  height: 500px;
  object-fit: cover;
  object-position: center;
  border-radius: 50%;
  /* agar fonini olib tashlangan PNG bo'lsa */
  mix-blend-mode: normal;
  filter: drop-shadow(0 20px 50px rgba(0, 0, 0, 0.4));
  transition: transform 0.3s ease;
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

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
  .hero-section {
    padding-top: 100px;
    padding-bottom: 60px;
  }

  .hero-actions {
    flex-direction: column;
    align-items: flex-start;
    gap: 24px;
  }
}
</style>
