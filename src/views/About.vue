<script setup>
import { computed, ref } from "vue";
import { useSettingsStore } from "../stores/settings";
import { translations } from "../constants/translations";
import { PROJECTS } from "../constants/projects";
import { useReveal } from "../composables/useReveal";

const settings = useSettingsStore();
const t = computed(() => translations[settings.lang]?.about || {});
const baseUrl = import.meta.env.BASE_URL;
const root = ref(null);
useReveal(root, ".animate-up, .stat-line");

const stats = computed(() => [
  { label: t.value.stats_exp, value: '1+' },
  { label: t.value.stats_proj, value: `${PROJECTS.length}+` },
  { label: t.value.stats_client, value: '100%' }
]);
</script>

<template>
  <div class="about-section" ref="root">
    <div class="container">

      <!-- EDITORIAL HEADER -->
      <div class="about-header animate-up">
        <div class="row align-items-center">
          <div class="col-lg-10">
            <h2 class="editorial-title">
              {{ t.editorial_title_1 }} <br />
              <span class="text-accent">{{ t.editorial_title_2 }}</span>
            </h2>
          </div>
        </div>
      </div>

      <div class="row mt-5 pt-4 gy-5">
        <!-- LEFT: IMAGE & METADATA -->
        <div class="col-lg-4 animate-up" style="--delay: 0.2s">
          <div class="about-image-wrapper mb-5">
            <img :src="`${baseUrl}image/ozim-uchun1.jpg`" alt="Shaxzod Isomiddinov" class="profile-img"
              loading="lazy" />
          </div>

          <div class="metadata-block">
            <div class="meta-item">
              <span class="meta-label">{{ t.meta_role }}</span>
              <span class="meta-value">{{ t.role }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label">{{ t.meta_location }}</span>
              <span class="meta-value">{{ t.location_val }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label">{{ t.meta_focus }}</span>
              <span class="meta-value">{{ t.meta_focus_val }}</span>
            </div>
          </div>
        </div>

        <!-- RIGHT: DESCRIPTION & STATS -->
        <div class="col-lg-7 offset-lg-1">
          <p class="about-desc animate-up" style="--delay: 0.3s">
            {{ t.desc }}
          </p>

          <div class="stats-container mt-5">
            <div class="stat-line" v-for="(stat, i) in stats" :key="i" :style="{ '--delay': `${0.4 + (i * 0.1)}s` }">
              <span class="stat-label">{{ stat.label }}</span>
              <span class="stat-value">{{ stat.value }}</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* ─── SECTION ─── */
.about-section {
  padding: 80px 0;
  background: transparent;
}

/* ─── EDITORIAL HEADER ─── */
.editorial-title {
  font-size: clamp(2.5rem, 5vw, 4.5rem);
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.02em;
  text-transform: uppercase;
  color: var(--text-color);
  margin: 0;
}

.text-accent {
  color: var(--accent);
  display: inline-block;
  position: relative;
}

/* ─── IMAGE ─── */
.about-image-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  max-width: 400px;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.profile-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  transition: transform 0.5s;
}

.about-image-wrapper:hover .profile-img {
  transform: scale(1.05);
}

/* ─── METADATA ─── */
.metadata-block {
  display: flex;
  flex-direction: column;
  gap: 32px;
  border-left: 1px solid var(--border-color);
  padding-left: 24px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.meta-label {
  font-size: 0.7rem;
  letter-spacing: 2px;
  color: var(--text-muted);
}

.meta-value {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-color);
}

/* ─── DESCRIPTION ─── */
.about-desc {
  font-size: clamp(1.1rem, 2vw, 1.4rem);
  line-height: 1.6;
  color: var(--text-muted);
  margin: 0;
}

/* ─── STATS ─── */
.stats-container {
  display: flex;
  flex-direction: column;
}

.stat-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 0;
  border-bottom: 1px solid var(--border-color);
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
  transition-delay: var(--delay);
}

.stat-line.in-view {
  opacity: 1;
  transform: translateY(0);
}

.stat-label {
  font-size: 0.85rem;
  letter-spacing: 2px;
  color: var(--text-muted);
  text-transform: uppercase;
  max-width: 60%;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--accent);
  font-family: monospace;
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
  .about-image-wrapper {
    margin: 0 auto 32px;
  }

  .metadata-block {
    border-left: none;
    border-top: 1px solid var(--border-color);
    padding-left: 0;
    padding-top: 24px;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 24px;
  }

  .meta-item {
    flex: 1;
    min-width: 140px;
  }
}

@media (max-width: 768px) {
  .about-section {
    padding: 60px 0;
  }

  .editorial-title {
    font-size: clamp(1.8rem, 7vw, 2.8rem);
    line-height: 1.15;
  }

  .about-desc {
    font-size: 1rem;
    line-height: 1.65;
  }

  .stat-line {
    padding: 16px 0;
  }

  .stat-label {
    font-size: 0.75rem;
    letter-spacing: 1px;
    max-width: 55%;
  }

  .stat-value {
    font-size: 1.8rem;
  }

  .about-image-wrapper {
    max-width: 280px;
  }
}
</style>
