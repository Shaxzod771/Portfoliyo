<script setup>
import { ref, watch, onMounted, onUnmounted } from "vue";
import { useSettingsStore } from "./stores/settings";
import Navbar from "./components/Global/Navbar.vue";
import Home from "./views/Home.vue";
import About from "./views/About.vue";
import Contact from "./views/Contact.vue";
import Services from "./views/Services.vue";
import Project from "./views/Project.vue";
import Footer from "./components/Global/Footer.vue";
import CustomCursor from "./components/Global/CustomCursor.vue";

const settings = useSettingsStore();

// Keep <html lang> in sync so screen readers and search engines see the active language
watch(() => settings.lang, (lang) => {
  document.documentElement.lang = lang;
}, { immediate: true });

const scrollProgress = ref("0%");

const handleScroll = () => {
  const totalScroll = document.documentElement.scrollTop;
  const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
  const percent = windowHeight > 0 ? (totalScroll / windowHeight) * 100 : 0;
  scrollProgress.value = `${percent}%`;
};

onMounted(() => {
  window.addEventListener("scroll", handleScroll, { passive: true });
  handleScroll();
});

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
  <CustomCursor />
  
  <div class="scroll-progress-container" aria-hidden="true">
    <div class="scroll-progress-bar" :style="{ width: scrollProgress }"></div>
  </div>

  <Navbar />
  
  <!-- Cinematic Background -->
  <div class="cinematic-bg" aria-hidden="true">
    <div class="grid-overlay"></div>
    <div class="noise-overlay"></div>
    <div class="glow-sphere top-right"></div>
    <div class="glow-sphere bottom-left"></div>
    <div class="glow-sphere center"></div>
  </div>

  <section id="home" class="section-wrapper">
    <Home />
  </section>

  <section id="about" class="section-wrapper">
    <About />
  </section>

  <section id="services" class="section-wrapper">
    <Services />
  </section>

  <section id="project" class="section-wrapper">
    <Project />
  </section>

  <section id="contact" class="section-wrapper">
    <Contact />
  </section>
  
  <Footer />
</template>

<style>
/* ─── GOOGLE FONT ─── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

/* ─── DESIGN TOKENS (PREMIUM DARK) ─── */
:root {
  --bg-primary: #040509;
  --bg-secondary: #0a0b12;
  --bg-surface: rgba(255, 255, 255, 0.02);
  --text-color: #f7f7f8;
  --text-muted: #8b8d98;
  --navbar-bg: rgba(4, 5, 9, 0.7);
  --card-bg: rgba(255, 255, 255, 0.02);
  --border-color: rgba(255, 255, 255, 0.06);
  
  /* Electric Lime Accent */
  --accent: #ccff00;
  --accent-glow: rgba(204, 255, 0, 0.2);
  --accent-dim: rgba(204, 255, 0, 0.08);

  /* Typography */
  --font-main: 'Inter', sans-serif;
}

/* ─── RESET & BASE ─── */
*, *::before, *::after {
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  margin: 0;
  padding: 0;
  color: var(--text-color);
  background: var(--bg-primary);
  font-family: var(--font-main);
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Base interactive resets for custom cursor */
a, button {
  color: inherit;
  text-decoration: none;
  cursor: pointer; /* fallback for touch devices */
}

/* Visible keyboard focus */
a:focus-visible,
button:focus-visible,
input:focus-visible,
textarea:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 3px;
}

/* Respect users who ask the OS for less motion */
@media (prefers-reduced-motion: reduce) {
  html {
    scroll-behavior: auto;
  }

  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* ─── CINEMATIC BACKGROUND EFFECTS ─── */
.cinematic-bg {
  position: fixed;
  inset: 0;
  z-index: -1;
  background: var(--bg-primary);
  overflow: hidden;
}

.grid-overlay {
  position: absolute;
  inset: 0;
  background-image: 
    linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
  /* Fades out the grid at the edges */
  mask-image: radial-gradient(circle at center, black 20%, transparent 80%);
  -webkit-mask-image: radial-gradient(circle at center, black 20%, transparent 80%);
}

.noise-overlay {
  position: absolute;
  inset: 0;
  background: url('data:image/svg+xml;utf8,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.75%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');
  opacity: 0.04;
  mix-blend-mode: overlay;
  pointer-events: none;
}

.glow-sphere {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.15;
  pointer-events: none;
  animation: float 20s infinite alternate ease-in-out;
}

.glow-sphere.top-right {
  top: -10%;
  right: -5%;
  width: 50vw;
  height: 50vw;
  background: radial-gradient(circle, var(--accent), transparent 70%);
  animation-delay: 0s;
}

.glow-sphere.bottom-left {
  bottom: -20%;
  left: -10%;
  width: 60vw;
  height: 60vw;
  background: radial-gradient(circle, #00cfff, transparent 70%);
  animation-delay: -5s;
}

.glow-sphere.center {
  top: 30%;
  left: 20%;
  width: 40vw;
  height: 40vw;
  background: radial-gradient(circle, rgba(204, 255, 0, 0.4), transparent 70%);
  opacity: 0.08;
  animation-delay: -10s;
}

@keyframes float {
  0% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(5%, 5%) scale(1.1); }
  66% { transform: translate(-5%, 8%) scale(0.9); }
  100% { transform: translate(-2%, -5%) scale(1.05); }
}

/* ─── SCROLL PROGRESS ─── */
.scroll-progress-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: transparent;
  z-index: 9999;
}

.scroll-progress-bar {
  height: 100%;
  background: var(--accent);
  width: 0%;
  transition: width 0.1s ease-out;
  box-shadow: 0 0 10px var(--accent-glow);
}

/* ─── SECTION WRAPPER ─── */
.section-wrapper {
  position: relative;
  min-height: 100vh;
  z-index: 1;
}

/* ─── SHARED UTILITIES ─── */
.text-accent {
  color: var(--accent) !important;
}

.section-title {
  font-size: 0.75rem;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--text-muted);
  font-weight: 500;
  margin-bottom: 1rem;
}

.section-heading {
  font-size: clamp(2.5rem, 5vw, 4.5rem);
  font-weight: 700;
  letter-spacing: -0.03em;
  color: var(--text-color);
  line-height: 1.1;
}

.gradient-text {
  color: var(--text-color);
  position: relative;
  display: inline-block;
}

.gradient-text::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: 0.15em;
  width: 100%;
  height: 0.25em;
  background: var(--accent);
  z-index: -1;
  transform: rotate(-1deg);
  opacity: 0.8;
}

/* ─── PREMIUM BUTTONS ─── */
.btn-primary-custom {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--text-color);
  color: var(--bg-primary);
  font-weight: 600;
  border-radius: 100px;
  border: none;
  padding: 14px 28px;
  font-size: 0.95rem;
  letter-spacing: 0.5px;
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), background-color 0.3s ease;
}

.btn-primary-custom:hover {
  background: var(--accent);
  color: var(--bg-primary);
  transform: scale(1.02);
}

.btn-outline-custom {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  color: var(--text-color);
  font-weight: 600;
  border-radius: 100px;
  border: 1px solid var(--border-color);
  padding: 14px 28px;
  font-size: 0.95rem;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
}

.btn-outline-custom:hover {
  border-color: var(--text-color);
  background: rgba(255,255,255,0.05);
}

/* Glass panel utility */
.glass-panel {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 16px;
}
</style>

