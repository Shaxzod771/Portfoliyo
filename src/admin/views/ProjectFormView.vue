<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { adminApi } from "../api";
import { projectFormData } from "../projectForm";
import { LANG_LABELS } from "../format";

const props = defineProps({ id: { type: String, default: null } });
const router = useRouter();
const isEdit = computed(() => props.id !== null);

const MAX_MB = 3;
const LAYOUTS = [
  { value: "featured", label: "Katta", hint: "Keng kartochka, ro‘yxat boshida" },
  { value: "regular", label: "Oddiy", hint: "Yarim kenglikdagi kartochka" },
  { value: "wide", label: "Keng", hint: "To‘liq kenglikdagi kartochka" },
];

const form = reactive({
  title_uz: "", title_en: "", title_ru: "",
  desc_uz: "", desc_en: "", desc_ru: "",
  github_url: "", live_url: "",
  layout: "regular",
  image_fit: "cover",
  preview_mode: "image",
  is_published: true,
});
const activeLang = ref("uz");
const currentImage = ref(null);
const newImage = ref(null);
const previewUrl = ref(null);
const removeImage = ref(false);

const loading = ref(false);
const saving = ref(false);
const error = ref("");
const errors = ref({});
const saved = ref(false);

// A live preview needs a link; drop back to the image when the link is cleared
watch(() => form.live_url, (url) => {
  if (!url.trim() && form.preview_mode === "live") form.preview_mode = "image";
});

const shownImage = computed(() => previewUrl.value || (removeImage.value ? null : currentImage.value));

// Point to the language tab that holds a validation error
const langHasError = (lang) => Boolean(errors.value[`title_${lang}`] || errors.value[`desc_${lang}`]);

onMounted(async () => {
  if (!isEdit.value) return;
  loading.value = true;
  try {
    const { data } = await adminApi(`/api/admin/projects/${props.id}`);
    Object.assign(form, data.fields, {
      github_url: data.github_url || "",
      live_url: data.live_url || "",
      layout: data.layout,
      image_fit: data.image_fit,
      preview_mode: data.preview_mode,
      is_published: data.is_published,
    });
    currentImage.value = data.image_url;
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
});

onBeforeUnmount(() => previewUrl.value && URL.revokeObjectURL(previewUrl.value));

function onFile(event) {
  const file = event.target.files?.[0];
  event.target.value = ""; // allow choosing the same file again
  if (!file) return;

  const next = { ...errors.value };
  delete next.image;
  if (!/^image\/(jpeg|png|webp|avif|gif)$/.test(file.type)) {
    errors.value = { ...next, image: "Faqat JPG, PNG, WEBP, AVIF yoki GIF rasm" };
    return;
  }
  if (file.size > MAX_MB * 1024 * 1024) {
    errors.value = { ...next, image: `Rasm ${MAX_MB} MB dan oshmasin` };
    return;
  }
  errors.value = next;

  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  newImage.value = file;
  previewUrl.value = URL.createObjectURL(file);
  removeImage.value = false;
}

function clearImage() {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = null;
  newImage.value = null;
  removeImage.value = Boolean(currentImage.value);
}

async function submit() {
  if (saving.value) return;
  error.value = "";
  errors.value = {};
  saved.value = false;

  if (!form.title_uz.trim()) {
    errors.value = { title_uz: "O‘zbekcha nom majburiy" };
    activeLang.value = "uz";
    return;
  }

  saving.value = true;
  try {
    const body = projectFormData(form, { image: newImage.value, removeImage: removeImage.value });
    const path = isEdit.value ? `/api/admin/projects/${props.id}` : "/api/admin/projects";
    const { data } = await adminApi(path, { method: "POST", body });

    if (!isEdit.value) {
      router.replace({ name: "projects" });
      return;
    }
    currentImage.value = data.image_url;
    clearImage();
    removeImage.value = false;
    saved.value = true;
  } catch (err) {
    error.value = err.message;
    errors.value = err.errors || {};
    const langWithError = ["uz", "en", "ru"].find(langHasError);
    if (langWithError) activeLang.value = langWithError;
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div>
    <div class="page-head">
      <div>
        <RouterLink :to="{ name: 'projects' }" class="back hint"><i class="bi bi-arrow-left" aria-hidden="true"></i> Loyihalar</RouterLink>
        <h1>{{ isEdit ? "Loyihani tahrirlash" : "Yangi loyiha" }}</h1>
      </div>
    </div>

    <div v-if="loading" class="empty"><span class="spinner" aria-label="Yuklanmoqda"></span></div>

    <form v-else class="grid" @submit.prevent="submit" novalidate>
      <div class="main-col">
        <div v-if="error" class="alert alert-error" role="alert">
          <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ error }}</span>
        </div>
        <div v-if="saved" class="alert alert-success" role="status">
          <i class="bi bi-check-circle" aria-hidden="true"></i><span>Saqlandi</span>
        </div>

        <!-- Texts per language -->
        <section class="card stack">
          <div class="tabs" role="tablist" aria-label="Til">
            <button v-for="(label, lang) in LANG_LABELS" :key="lang" type="button" role="tab" class="tab"
              :class="{ active: activeLang === lang, 'has-error': langHasError(lang) }"
              :aria-selected="activeLang === lang ? 'true' : 'false'" @click="activeLang = lang">
              {{ label }}<span v-if="lang === 'uz'" class="req" aria-hidden="true">*</span>
            </button>
          </div>

          <template v-for="(label, lang) in LANG_LABELS" :key="lang">
            <div v-show="activeLang === lang" class="stack" role="tabpanel">
              <label class="field">
                <span>Nomi ({{ label }}){{ lang === "uz" ? " *" : "" }}</span>
                <input v-model="form[`title_${lang}`]" class="input" :class="{ invalid: errors[`title_${lang}`] }"
                  maxlength="150" :placeholder="lang !== 'uz' ? 'Bo‘sh qolsa, o‘zbekchasi ko‘rsatiladi' : ''" />
                <span v-if="errors[`title_${lang}`]" class="field-error">{{ errors[`title_${lang}`] }}</span>
              </label>
              <label class="field">
                <span>Tavsif ({{ label }})</span>
                <textarea v-model="form[`desc_${lang}`]" class="input" :class="{ invalid: errors[`desc_${lang}`] }"
                  rows="4" maxlength="1000"></textarea>
                <span class="hint">{{ form[`desc_${lang}`].length }}/1000</span>
                <span v-if="errors[`desc_${lang}`]" class="field-error">{{ errors[`desc_${lang}`] }}</span>
              </label>
            </div>
          </template>
        </section>

        <!-- Links -->
        <section class="card stack">
          <label class="field">
            <span>GitHub havolasi</span>
            <input v-model="form.github_url" class="input" :class="{ invalid: errors.github_url }" type="url"
              placeholder="https://github.com/…" maxlength="255" />
            <span v-if="errors.github_url" class="field-error">{{ errors.github_url }}</span>
          </label>
          <label class="field">
            <span>Jonli versiya (demo) havolasi</span>
            <input v-model="form.live_url" class="input" :class="{ invalid: errors.live_url }" type="url"
              placeholder="https://…" maxlength="255" />
            <span v-if="errors.live_url" class="field-error">{{ errors.live_url }}</span>
          </label>
          <p class="hint">Bo‘sh qoldirilsa, saytda tugmasi ko‘rinmaydi.</p>
        </section>
      </div>

      <aside class="side-col">
        <!-- What the card shows -->
        <section class="card stack">
          <fieldset class="field">
            <legend class="field-label">Kartochkada nima ko‘rinsin</legend>
            <label class="radio"><input v-model="form.preview_mode" type="radio" value="image" /> Rasm</label>
            <label class="radio">
              <input v-model="form.preview_mode" type="radio" value="live" :disabled="!form.live_url" />
              <span>Jonli sayt <span class="hint">— demo havoladagi sahifa kartaning ichida ochilib turadi</span></span>
            </label>
            <span v-if="!form.live_url" class="hint">Jonli ko‘rinish uchun avval demo havolasini kiriting.</span>
            <span v-if="errors.preview_mode" class="field-error">{{ errors.preview_mode }}</span>
          </fieldset>
          <iframe v-if="form.preview_mode === 'live' && form.live_url && !errors.live_url" :src="form.live_url"
            class="live-check" title="Jonli ko‘rinish" sandbox="allow-scripts allow-same-origin" loading="lazy"></iframe>
          <p v-if="form.preview_mode === 'live'" class="hint">
            Yuqorida sayt ko‘rinmasa, u boshqa saytlarga joylashtirishni taqiqlagan — unda «Rasm»ni tanlang.
            Rasm sayt yuklanguncha ko‘rsatiladi.
          </p>
        </section>

        <!-- Image -->
        <section class="card stack">
          <span class="field-label">Rasm</span>
          <div class="preview" :class="{ contain: form.image_fit === 'contain' }">
            <img v-if="shownImage" :src="shownImage" alt="Loyiha rasmi" />
            <span v-else class="hint"><i class="bi bi-image" aria-hidden="true"></i> Rasm yo‘q</span>
          </div>
          <div class="row-btns">
            <label class="btn btn-sm file-btn">
              <i class="bi bi-upload" aria-hidden="true"></i> {{ shownImage ? "Almashtirish" : "Yuklash" }}
              <input type="file" accept="image/jpeg,image/png,image/webp,image/avif,image/gif" class="sr-only" @change="onFile" />
            </label>
            <button v-if="shownImage" type="button" class="btn btn-sm btn-danger" @click="clearImage">
              <i class="bi bi-x-lg" aria-hidden="true"></i> Olib tashlash
            </button>
          </div>
          <span v-if="errors.image" class="field-error">{{ errors.image }}</span>
          <span class="hint">JPG, PNG, WEBP, AVIF · {{ MAX_MB }} MB gacha. WEBP tavsiya etiladi.</span>

          <fieldset class="field">
            <legend class="field-label">Rasm ko‘rinishi</legend>
            <label class="radio"><input v-model="form.image_fit" type="radio" value="cover" /> To‘ldirish (skrinshot uchun)</label>
            <label class="radio"><input v-model="form.image_fit" type="radio" value="contain" /> To‘liq ko‘rsatish (logo uchun)</label>
          </fieldset>
        </section>

        <!-- Display -->
        <section class="card stack">
          <fieldset class="field">
            <legend class="field-label">Kartochka o‘lchami</legend>
            <label v-for="l in LAYOUTS" :key="l.value" class="radio">
              <input v-model="form.layout" type="radio" :value="l.value" />
              <span>{{ l.label }} <span class="hint">— {{ l.hint }}</span></span>
            </label>
            <span v-if="errors.layout" class="field-error">{{ errors.layout }}</span>
          </fieldset>

          <label class="switch">
            <input v-model="form.is_published" type="checkbox" />
            <span>Saytda ko‘rsatish</span>
          </label>
        </section>

        <button type="submit" class="btn btn-primary save" :disabled="saving">
          <span v-if="saving" class="spinner" aria-hidden="true"></span>
          {{ saving ? "Saqlanmoqda…" : isEdit ? "O‘zgarishlarni saqlash" : "Loyihani qo‘shish" }}
        </button>
      </aside>
    </form>
  </div>
</template>

<style scoped>
.back { display: inline-flex; gap: 6px; text-decoration: none; margin-bottom: 6px; }
.back:hover { color: var(--text); }

.grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 20px;
  align-items: start;
}
.main-col, .side-col { display: flex; flex-direction: column; gap: 16px; }
.stack { display: flex; flex-direction: column; gap: 14px; }

.tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); }
.tab {
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  padding: 8px 12px;
  margin-bottom: -1px;
  color: var(--muted);
  font-weight: 600;
  cursor: pointer;
}
.tab.active { color: var(--accent); border-bottom-color: var(--accent); }
.tab.has-error { color: var(--danger); }
.req { color: var(--accent); margin-left: 2px; }

.preview {
  aspect-ratio: 16 / 10;
  border-radius: 10px;
  overflow: hidden;
  background: var(--bg-hover);
  display: grid;
  place-items: center;
}
.preview img { width: 100%; height: 100%; object-fit: cover; object-position: top; }
.preview.contain { background: #fff; }
.preview.contain img { object-fit: contain; padding: 12px; }

.row-btns { display: flex; gap: 8px; flex-wrap: wrap; }
.live-check { width: 100%; aspect-ratio: 16 / 10; border: 1px solid var(--border); border-radius: 10px; background: #fff; }
.file-btn { position: relative; }
.file-btn:focus-within { outline: 2px solid var(--accent); outline-offset: 2px; }

fieldset { border: none; padding: 0; margin: 0; }
legend { margin-bottom: 6px; padding: 0; }
.radio, .switch { display: flex; gap: 8px; align-items: flex-start; cursor: pointer; font-size: 0.9rem; }
.radio input, .switch input { accent-color: var(--accent); margin-top: 3px; }
.switch { font-weight: 600; }

.save { width: 100%; padding: 12px; }

@media (max-width: 1000px) {
  .grid { grid-template-columns: 1fr; }
}
</style>
