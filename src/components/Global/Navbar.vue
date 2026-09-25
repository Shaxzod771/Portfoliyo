<script setup>
import { useSettingsStore } from "../../stores/settings";
import { translations } from "../../constants/translations";
import { SOCIALS } from "../../constants/site";
import { computed, ref, onMounted, onUnmounted } from "vue";

const settings = useSettingsStore();
const t = computed(() => translations[settings.lang]?.nav || {});
const languages = ["uz", "en", "ru"];
const menuSocials = SOCIALS.slice(0, 3);

const activeSection = ref("home");
const isMenuOpen = ref(false);
const isScrolled = ref(false);
const isLangOpen = ref(false);
const langDropdown = ref(null);

const navLinks = computed(() =>
  ["home", "about", "services", "project", "contact"].map((id, i) => ({
    id,
    href: `#${id}`,
    label: t.value[id],
    num: String(i + 1).padStart(2, "0"),
  }))
);

function chooseLanguage(lang) {
  settings.setLanguage(lang);
  isLangOpen.value = false;
}

function onDocumentClick(e) {
  if (isLangOpen.value && langDropdown.value && !langDropdown.value.contains(e.target)) {
    isLangOpen.value = false;
  }
}

function onKeydown(e) {
  if (e.key !== "Escape") return;
  isLangOpen.value = false;
  if (isMenuOpen.value) closeMenu();
}

function onScroll() {
  isScrolled.value = window.scrollY > 50;

  const sections = ["home", "about", "services", "project", "contact"];
  let current = "home";
  for (const id of sections) {
    const el = document.getElementById(id);
    if (el && window.scrollY >= el.offsetTop - 150) {
      current = id;
    }
  }
  activeSection.value = current;
}

function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value;
  if (isMenuOpen.value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
}

function closeMenu() {
  isMenuOpen.value = false;
  document.body.style.overflow = '';
}

onMounted(() => {
  window.addEventListener("scroll", onScroll, { passive: true });
  document.addEventListener("click", onDocumentClick);
  document.addEventListener("keydown", onKeydown);
  onScroll();
});

onUnmounted(() => {
  window.removeEventListener("scroll", onScroll);
  document.removeEventListener("click", onDocumentClick);
  document.removeEventListener("keydown", onKeydown);
  document.body.style.overflow = "";
});
</script>

<template>
  <nav class="premium-nav" :class="{ 'scrolled': isScrolled }">
    <div class="nav-container">
      <!-- BRAND -->
      <a href="#home" class="brand" aria-label="Shaxzod Isomiddinov" v-magnetic="20">
        <span class="brand-text">&lt;SHAXZOD<span class="text-accent">.DEV</span>/&gt;</span>
      </a>

      <!-- DESKTOP LINKS -->
      <div class="desktop-links d-none d-lg-flex">
        <a v-for="link in navLinks" :key="link.id" :href="link.href" class="nav-link-item"
          :class="{ 'active': activeSection === link.id }"
          :aria-current="activeSection === link.id ? 'true' : null" v-magnetic="15">
          {{ link.label }}
        </a>
      </div>

      <!-- RIGHT ACTIONS -->
      <div class="nav-actions d-none d-lg-flex">
        <!-- Language Dropdown -->
        <div class="lang-dropdown" ref="langDropdown">
          <button class="lang-btn" type="button" :aria-label="t.language" aria-haspopup="true"
            :aria-expanded="isLangOpen ? 'true' : 'false'" @click="isLangOpen = !isLangOpen" v-magnetic="15">
            {{ settings.lang.toUpperCase() }} <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </button>
          <ul class="glass-dropdown" v-show="isLangOpen">
            <li v-for="lang in languages" :key="lang">
              <button type="button" class="dropdown-item" :class="{ active: settings.lang === lang }"
                @click="chooseLanguage(lang)">{{ lang.toUpperCase() }}</button>
            </li>
          </ul>
        </div>

        <a href="#contact" class="nav-cta" v-magnetic="20">{{ t.cta }} <i class="bi bi-arrow-right"
            aria-hidden="true"></i></a>
      </div>

      <!-- MOBILE TOGGLE -->
      <button type="button" class="menu-toggle d-lg-none" @click="toggleMenu" :class="{ 'open': isMenuOpen }"
        :aria-label="t.menu" :aria-expanded="isMenuOpen ? 'true' : 'false'" aria-controls="mobile-menu">
        <span></span>
        <span></span>
      </button>
    </div>
  </nav>

  <!-- MOBILE OVERLAY MENU -->
  <div id="mobile-menu" class="mobile-overlay" :class="{ 'active': isMenuOpen }" :inert="!isMenuOpen">
    <div class="overlay-bg"></div>
    <div class="overlay-content">
      <div class="overlay-links">
        <a v-for="(link, index) in navLinks" :key="link.id" :href="link.href" class="overlay-link"
          :style="{ 'transition-delay': isMenuOpen ? `${0.1 + (index * 0.1)}s` : '0s' }" @click="closeMenu">
          <span class="overlay-num">{{ link.num }}</span>
          <span class="overlay-text">{{ link.label }}</span>
        </a>
      </div>

      <div class="overlay-footer" :style="{ 'transition-delay': isMenuOpen ? '0.7s' : '0s' }">
        <div class="lang-switcher">
          <button v-for="lang in languages" :key="lang" type="button"
            @click="settings.setLanguage(lang); closeMenu()"
            :class="{ active: settings.lang === lang }">{{ lang.toUpperCase() }}</button>
        </div>
        <div class="overlay-socials">
          <a v-for="social in menuSocials" :key="social.name" :href="social.url" target="_blank"
            rel="noopener noreferrer" :aria-label="social.name">
            <i class="bi" :class="social.icon" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── DESKTOP NAV ─── */
.premium-nav {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  padding: 32px 0;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  z-index: 100;
}

.premium-nav.scrolled {
  padding: 16px 0;
  background: rgba(4, 5, 9, 0.8);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border-color);
}

.nav-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

@media (max-width: 768px) {
  .nav-container {
    padding: 0 24px;
  }
}

/* BRAND */
.brand {
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: 2px;
  color: var(--text-color);
  text-decoration: none;
  display: inline-block;
}

/* LINKS */
.desktop-links {
  display: flex;
  gap: 40px;
}

.nav-link-item {
  font-size: 0.85rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--text-muted);
  text-decoration: none;
  position: relative;
  padding: 8px 0;
  transition: color 0.3s ease;
}

.nav-link-item::after {
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

.nav-link-item:hover,
.nav-link-item.active {
  color: var(--text-color);
}

.nav-link-item:hover::after,
.nav-link-item.active::after {
  transform: scaleX(1);
  transform-origin: left;
}

/* ACTIONS */
.nav-actions {
  display: flex;
  align-items: center;
  gap: 24px;
}

.lang-btn {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 1px;
  padding: 8px;
  transition: color 0.3s;
}

.lang-btn:hover {
  color: var(--text-color);
}

.lang-btn i {
  font-size: 0.65rem;
  margin-left: 4px;
}

.lang-dropdown {
  position: relative;
}

.glass-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  list-style: none;
  margin: 0;
  background: rgba(10, 11, 18, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  min-width: 80px;
  padding: 8px;
  z-index: 10;
}

.glass-dropdown .dropdown-item {
  display: block;
  width: 100%;
  background: transparent;
  border: none;
  text-align: left;
  color: var(--text-muted);
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: 6px;
  padding: 8px 16px;
  transition: all 0.2s;
}

.glass-dropdown .dropdown-item.active {
  color: var(--text-color);
}

.glass-dropdown .dropdown-item:hover {
  background: var(--accent-dim);
  color: var(--accent);
}

.nav-cta {
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 1px;
  color: var(--bg-primary);
  background: var(--accent);
  padding: 10px 20px;
  border-radius: 100px;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.3s;
}

.nav-cta i {
  transition: transform 0.3s;
}

.nav-cta:hover {
  transform: scale(1.05);
}

.nav-cta:hover i {
  transform: translateX(4px);
}

/* ─── MOBILE MENU TOGGLE ─── */
.menu-toggle {
  background: transparent;
  border: none;
  width: 44px;
  height: 44px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-end;
  gap: 6px;
  z-index: 1000;
  cursor: pointer;
}

.menu-toggle span {
  display: block;
  height: 2px;
  background: var(--text-color);
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.menu-toggle span:nth-child(1) {
  width: 28px;
}

.menu-toggle span:nth-child(2) {
  width: 20px;
}

.menu-toggle.open span:nth-child(1) {
  width: 28px;
  transform: translateY(4px) rotate(45deg);
}

.menu-toggle.open span:nth-child(2) {
  width: 28px;
  transform: translateY(-4px) rotate(-45deg);
}

/* ─── FULL SCREEN OVERLAY ─── */
.mobile-overlay {
  position: fixed;
  inset: 0;
  z-index: 99;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.mobile-overlay.active {
  pointer-events: all;
}

.overlay-bg {
  position: absolute;
  top: 0;
  right: 0;
  width: 100%;
  height: 100vh;
  background: var(--bg-primary);
  clip-path: circle(0px at calc(100% - 40px) 40px);
  transition: clip-path 0.8s cubic-bezier(0.77, 0, 0.175, 1);
}

.mobile-overlay.active .overlay-bg {
  clip-path: circle(150vh at calc(100% - 40px) 40px);
}

.overlay-content {
  position: relative;
  z-index: 2;
  width: 100%;
  padding: 0 40px;
  display: flex;
  flex-direction: column;
  height: 100%;
  justify-content: center;
}

.overlay-links {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.overlay-link {
  display: flex;
  align-items: baseline;
  gap: 16px;
  text-decoration: none;
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.mobile-overlay.active .overlay-link {
  opacity: 1;
  transform: translateY(0);
}

.overlay-num {
  font-size: 1rem;
  font-weight: 500;
  color: var(--accent);
  font-family: monospace;
}

.overlay-text {
  font-size: 3rem;
  font-weight: 700;
  letter-spacing: -1px;
  color: var(--text-color);
  text-transform: uppercase;
}

.overlay-footer {
  margin-top: 60px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.mobile-overlay.active .overlay-footer {
  opacity: 1;
  transform: translateY(0);
}

.lang-switcher {
  display: flex;
  gap: 16px;
}

.lang-switcher button {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 1.1rem;
  font-weight: 600;
  padding: 0;
}

.lang-switcher button.active {
  color: var(--text-color);
  text-decoration: underline;
  text-decoration-color: var(--accent);
  text-underline-offset: 6px;
}

.overlay-socials {
  display: flex;
  gap: 20px;
}

.overlay-socials a {
  color: var(--text-color);
  font-size: 1.5rem;
  transition: color 0.3s;
}

.overlay-socials a:hover {
  color: var(--accent);
}
</style>
