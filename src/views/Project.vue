<script setup>
import { computed, ref, watch, nextTick } from "vue";
import { useSettingsStore } from "../stores/settings";
import { translations } from "../constants/translations";
import { GITHUB_PROFILE } from "../constants/site";
import { useReveal } from "../composables/useReveal";
import { useProjects } from "../composables/useProjects";

const settings = useSettingsStore();
const t = computed(() => translations[settings.lang]?.projects || { items: {} });
const root = ref(null);
const { refresh } = useReveal(root, ".animate-up, .project-card");
const projects = useProjects();

// Projects from the API bring their own texts ({ uz, en, ru }); built-in ones use translations.js
const translatedProjects = computed(() =>
  projects.value.map((p) => ({
    ...p,
    title: p.title?.[settings.lang] || p.title?.uz || t.value.items?.[p.key]?.title || p.key,
    desc: p.desc?.[settings.lang] ?? p.desc?.uz ?? t.value.items?.[p.key]?.desc ?? "",
  }))
);

// Cards rendered after the API answers need to be observed too, or they'd stay hidden
watch(projects, () => nextTick(refresh));
</script>

<template>
  <div class="projects-section" ref="root">
    <div class="container">
      <!-- EDITORIAL HEADER -->
      <div class="project-header animate-up mb-5">
        <div class="row align-items-end">
          <div class="col-lg-8">
            <h2 class="editorial-title">
              {{ t.title }} <span class="text-accent">{{ t.subtitle }}</span>
            </h2>
          </div>
          <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
            <a :href="GITHUB_PROFILE" target="_blank" rel="noopener noreferrer" class="view-all-link" v-magnetic="10">
              {{ t.view_all }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </div>
      <!-- EDITORIAL PROJECT GRID -->
      <div class="editorial-grid">
        <article
          v-for="(project, index) in translatedProjects"
          :key="project.key"
          class="project-card"
          :class="`card-${project.type}`"
          :style="{ transitionDelay: `${(index % 2) * 0.1}s` }"
        >
          <!-- Image -->
          <div class="project-img-wrapper">
            <img v-if="project.image" :src="project.image" :alt="project.title" loading="lazy" decoding="async"
              :class="{ 'img-contain': project.fit === 'contain' }" />
            <div v-else class="img-placeholder" aria-hidden="true">{{ project.title.charAt(0) }}</div>
            <div class="img-overlay"></div>
          </div>

          <!-- Content -->
          <div class="project-content">
            <div class="content-left">
              <div class="project-number">{{ String(index + 1).padStart(2, '0') }}</div>
              <h3 class="project-title">{{ project.title }}</h3>
              <p class="project-desc">{{ project.desc }}</p>
            </div>

            <div class="project-actions">
              <a v-if="project.github" :href="project.github" target="_blank" rel="noopener noreferrer"
                class="action-btn" :aria-label="`${project.title} — ${t.btn_github}`" :title="t.btn_github">
                <i class="bi bi-github" aria-hidden="true"></i>
              </a>
              <a v-if="project.live" :href="project.live" target="_blank" rel="noopener noreferrer"
                class="action-btn" :aria-label="`${project.title} — ${t.btn_live}`" :title="t.btn_live">
                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </article>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* ─── SECTION ─── */
.projects-section {
  padding: 120px 0;
  background: transparent;
}

/* ─── HEADER ─── */
.editorial-title {
  font-size: clamp(2.5rem, 5vw, 4.5rem);
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.02em;
  text-transform: uppercase;
  color: var(--text-color);
  margin: 0;
}

.view-all-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 2px;
  color: var(--text-muted);
  transition: color 0.3s ease;
}

.view-all-link:hover {
  color: var(--accent);
}

/* ─── GRID LAYOUT ─── */
.editorial-grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 32px;
  grid-auto-flow: dense;
}

.project-card {
  position: relative;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  overflow: hidden;
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), 
              transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
  will-change: transform, opacity;
  display: flex;
  flex-direction: column;
}

.project-card.in-view {
  opacity: 1;
  transform: translateY(0);
}

/* Specific Layout Rules */
.card-featured {
  grid-column: span 12;
}

.card-regular {
  grid-column: span 6;
}

.card-wide {
  grid-column: span 12;
}

@media (max-width: 991px) {
  .card-regular, .card-featured, .card-wide {
    grid-column: span 12;
  }
}

/* ─── IMAGE ─── */
.project-img-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  background: var(--bg-secondary);
}

.card-featured .project-img-wrapper, 
.card-wide .project-img-wrapper {
  aspect-ratio: 21 / 9;
}

@media (max-width: 991px) {
  .card-featured .project-img-wrapper, 
  .card-wide .project-img-wrapper {
    aspect-ratio: 16 / 9;
  }
}

.project-img-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top center;
  transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Logo-style images are shown whole on a light plate instead of being cropped */
.project-img-wrapper img.img-contain {
  object-fit: contain;
  object-position: center;
  background: #fff;
  padding: 24px;
}

/* Project saved without an image */
.img-placeholder {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  font-size: clamp(3rem, 8vw, 6rem);
  font-weight: 800;
  color: var(--accent);
  background: radial-gradient(circle at 30% 20%, var(--accent-dim), transparent 60%), var(--bg-secondary);
}

.img-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent 60%, rgba(4, 5, 9, 0.8) 100%);
  opacity: 0.5;
  transition: opacity 0.5s ease;
  pointer-events: none;
}

.project-card:hover .project-img-wrapper img {
  transform: scale(1.05);
}

.project-card:hover .img-overlay {
  opacity: 0.2;
}

/* ─── CONTENT ─── */
.project-content {
  padding: 32px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
  flex: 1;
  background: var(--bg-primary);
  transition: background 0.3s ease;
}

.project-card:hover .project-content {
  background: var(--bg-secondary);
}

.content-left {
  flex: 1;
}

.project-number {
  font-size: 0.75rem;
  font-family: monospace;
  letter-spacing: 2px;
  color: var(--accent);
  margin-bottom: 12px;
}

.project-title {
  font-size: clamp(1.2rem, 2vw, 1.8rem);
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 12px;
  line-height: 1.2;
  transition: color 0.3s ease;
}

.project-card:hover .project-title {
  color: var(--accent);
}

.project-desc {
  font-size: 0.9rem;
  color: var(--text-muted);
  line-height: 1.6;
  margin: 0;
  max-width: 600px;
}

/* ─── ACTIONS ─── */
.project-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  color: var(--text-color);
  font-size: 1.2rem;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.action-btn:hover {
  background: var(--accent);
  border-color: var(--accent);
  color: var(--bg-primary);
  transform: translateY(-4px);
}

/* ─── ANIMATIONS ─── */
.animate-up {
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), 
              transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.animate-up.in-view {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 768px) {
  .projects-section {
    padding: 60px 0;
  }

  .editorial-title {
    font-size: clamp(1.8rem, 8vw, 2.8rem);
  }

  .view-all-link {
    font-size: 0.8rem;
    letter-spacing: 1px;
  }

  .project-content {
    flex-direction: column;
    align-items: flex-start;
    padding: 20px;
  }

  .editorial-grid {
    gap: 16px;
  }
}
</style>
