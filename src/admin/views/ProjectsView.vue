<script setup>
import { ref, onMounted } from "vue";
import { adminApi } from "../api";
import { projectFormData } from "../projectForm";

const projects = ref([]);
const loading = ref(true);
const error = ref("");
const busyId = ref(null);

const LAYOUT_LABELS = { featured: "Katta", regular: "Oddiy", wide: "Keng" };

async function load() {
  loading.value = true;
  error.value = "";
  try {
    projects.value = (await adminApi("/api/admin/projects")).data;
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
}

async function move(index, delta) {
  const target = index + delta;
  if (target < 0 || target >= projects.value.length) return;

  const previous = projects.value;
  const reordered = [...previous];
  [reordered[index], reordered[target]] = [reordered[target], reordered[index]];
  projects.value = reordered; // update right away, roll back if the server refuses

  try {
    projects.value = (await adminApi("/api/admin/projects/reorder", { method: "PUT", body: { ids: reordered.map((p) => p.id) } })).data;
  } catch (err) {
    projects.value = previous;
    error.value = err.message;
  }
}

async function togglePublished(project) {
  busyId.value = project.id;
  error.value = "";
  try {
    const body = projectFormData({ ...project.fields, ...project, is_published: !project.is_published });
    const { data } = await adminApi(`/api/admin/projects/${project.id}`, { method: "POST", body });
    projects.value = projects.value.map((p) => (p.id === data.id ? data : p));
  } catch (err) {
    error.value = err.message;
  } finally {
    busyId.value = null;
  }
}

async function remove(project) {
  if (!confirm(`"${project.title.uz}" loyihasini o‘chirasizmi? Bu amalni qaytarib bo‘lmaydi.`)) return;
  busyId.value = project.id;
  error.value = "";
  try {
    await adminApi(`/api/admin/projects/${project.id}`, { method: "DELETE" });
    projects.value = projects.value.filter((p) => p.id !== project.id);
  } catch (err) {
    error.value = err.message;
  } finally {
    busyId.value = null;
  }
}

onMounted(load);
</script>

<template>
  <div>
    <div class="page-head">
      <h1>Loyihalar</h1>
      <RouterLink :to="{ name: 'project-new' }" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Yangi loyiha
      </RouterLink>
    </div>

    <div v-if="error" class="alert alert-error mb" role="alert">
      <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ error }}</span>
    </div>

    <div v-if="loading" class="empty"><span class="spinner" aria-label="Yuklanmoqda"></span></div>

    <div v-else-if="!projects.length" class="card empty">
      <i class="bi bi-collection" aria-hidden="true"></i>
      Hali loyiha yo‘q. Birinchisini qo‘shing.
    </div>

    <ul v-else class="list">
      <li v-for="(project, index) in projects" :key="project.id" class="card row" :class="{ muted: !project.is_published }">
        <div class="order">
          <button type="button" class="btn btn-icon btn-sm" :disabled="index === 0" aria-label="Yuqoriga" @click="move(index, -1)">
            <i class="bi bi-chevron-up" aria-hidden="true"></i>
          </button>
          <button type="button" class="btn btn-icon btn-sm" :disabled="index === projects.length - 1" aria-label="Pastga" @click="move(index, 1)">
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </button>
        </div>

        <div class="thumb" :class="{ contain: project.image_fit === 'contain' }">
          <img v-if="project.image_url" :src="project.image_url" alt="" loading="lazy" />
          <i v-else class="bi bi-image" aria-hidden="true"></i>
        </div>

        <div class="info">
          <RouterLink :to="{ name: 'project-edit', params: { id: project.id } }" class="title">{{ project.title.uz }}</RouterLink>
          <div class="meta">
            <span class="badge" :class="project.is_published ? 'badge-success' : ''">
              {{ project.is_published ? "Saytda" : "Yashirilgan" }}
            </span>
            <span class="badge">{{ LAYOUT_LABELS[project.layout] }}</span>
            <a v-if="project.github_url" :href="project.github_url" target="_blank" rel="noopener" class="hint link">
              <i class="bi bi-github" aria-hidden="true"></i> GitHub
            </a>
            <a v-if="project.live_url" :href="project.live_url" target="_blank" rel="noopener" class="hint link">
              <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Demo
            </a>
          </div>
        </div>

        <div class="actions">
          <button type="button" class="btn btn-sm" :disabled="busyId === project.id" @click="togglePublished(project)">
            <i class="bi" :class="project.is_published ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
            {{ project.is_published ? "Yashirish" : "Ko‘rsatish" }}
          </button>
          <RouterLink :to="{ name: 'project-edit', params: { id: project.id } }" class="btn btn-sm">
            <i class="bi bi-pencil" aria-hidden="true"></i> Tahrirlash
          </RouterLink>
          <button type="button" class="btn btn-sm btn-danger btn-icon" :disabled="busyId === project.id"
            :aria-label="`${project.title.uz} — o‘chirish`" @click="remove(project)">
            <i class="bi bi-trash" aria-hidden="true"></i>
          </button>
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.mb { margin-bottom: 16px; }
.list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }

.row { display: flex; align-items: center; gap: 16px; padding: 14px; }
.row.muted .thumb, .row.muted .title { opacity: 0.55; }

.order { display: flex; flex-direction: column; gap: 4px; }

.thumb {
  width: 120px;
  aspect-ratio: 16 / 10;
  border-radius: 8px;
  overflow: hidden;
  background: var(--bg-hover);
  display: grid;
  place-items: center;
  color: var(--muted);
  flex-shrink: 0;
}
.thumb img { width: 100%; height: 100%; object-fit: cover; object-position: top; }
.thumb.contain { background: #fff; }
.thumb.contain img { object-fit: contain; padding: 6px; }

.info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
.title { font-weight: 700; text-decoration: none; }
.title:hover { color: var(--accent); }
.meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.link { text-decoration: none; }
.link:hover { color: var(--text); }

.actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }

@media (max-width: 700px) {
  .row { flex-wrap: wrap; }
  .thumb { width: 88px; }
  .actions { width: 100%; justify-content: flex-start; }
}
</style>
