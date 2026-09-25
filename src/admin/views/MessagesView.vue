<script setup>
import { ref, watch, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { adminApi } from "../api";
import { useInboxStore } from "../stores/inbox";
import { formatDate, LANG_LABELS } from "../format";

const route = useRoute();
const router = useRouter();
const inbox = useInboxStore();

const FILTERS = [
  { value: "all", label: "Barchasi" },
  { value: "unread", label: "O‘qilmagan" },
  { value: "read", label: "O‘qilgan" },
];

const messages = ref([]);
const meta = ref({ page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref("");
const openId = ref(null);
const search = ref(typeof route.query.q === "string" ? route.query.q : "");

let controller = null;
let searchTimer = null;

// Filter, page and search live in the URL so reloads and the back button keep them
async function load() {
  controller?.abort();
  controller = new AbortController();
  loading.value = true;
  error.value = "";

  const params = new URLSearchParams({
    page: String(route.query.page || 1),
    status: String(route.query.status || "all"),
    q: String(route.query.q || ""),
  });
  try {
    const res = await adminApi(`/api/admin/messages?${params}`, { signal: controller.signal });
    messages.value = res.data;
    meta.value = res.meta;
    inbox.unread = res.meta.unread;

    // ?open=<id> (links from the dashboard) expands that message
    const requested = messages.value.find((m) => m.id === Number(route.query.open));
    if (requested && openId.value !== requested.id) toggle(requested);
  } catch (err) {
    if (err.name !== "AbortError") error.value = err.message;
  } finally {
    loading.value = false;
  }
}

watch(() => [route.query.page, route.query.status, route.query.q], load, { immediate: true });

watch(search, (value) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => setQuery({ q: value.trim() || undefined, page: undefined }), 350);
});

onBeforeUnmount(() => {
  controller?.abort();
  clearTimeout(searchTimer);
});

function setQuery(patch) {
  const query = { ...route.query, ...patch };
  delete query.open;
  Object.keys(query).forEach((k) => query[k] === undefined && delete query[k]);
  router.replace({ query });
}

async function setRead(message, isRead) {
  try {
    const { data } = await adminApi(`/api/admin/messages/${message.id}`, { method: "PATCH", body: { is_read: isRead } });
    if (message.is_read !== data.is_read) inbox.unread += data.is_read ? -1 : 1;
    Object.assign(message, data);
  } catch (err) {
    error.value = err.message;
  }
}

function toggle(message) {
  openId.value = openId.value === message.id ? null : message.id;
  // Opening an unread message marks it as read
  if (openId.value === message.id && !message.is_read) setRead(message, true);
}

async function markAllRead() {
  try {
    await adminApi("/api/admin/messages/read-all", { method: "POST" });
    messages.value.forEach((m) => (m.is_read = true));
    inbox.unread = 0;
    if (route.query.status === "unread") load();
  } catch (err) {
    error.value = err.message;
  }
}

async function remove(message) {
  if (!confirm(`${message.name} yuborgan xabarni o‘chirasizmi?`)) return;
  try {
    await adminApi(`/api/admin/messages/${message.id}`, { method: "DELETE" });
    if (!message.is_read) inbox.unread--;
    // Reload so the page and totals stay correct; step back if the page became empty
    if (messages.value.length === 1 && meta.value.page > 1) setQuery({ page: meta.value.page - 1 });
    else load();
  } catch (err) {
    error.value = err.message;
  }
}

function replyLink(message) {
  const subject = encodeURIComponent(`Re: ${message.subject || "Portfolio"}`);
  return `mailto:${message.email}?subject=${subject}`;
}
</script>

<template>
  <div>
    <div class="page-head">
      <h1>Xabarlar <span v-if="inbox.unread" class="badge badge-accent">{{ inbox.unread }} yangi</span></h1>
      <button type="button" class="btn btn-sm" :disabled="!inbox.unread" @click="markAllRead">
        <i class="bi bi-check2-all" aria-hidden="true"></i> Hammasini o‘qilgan qilish
      </button>
    </div>

    <div class="toolbar">
      <div class="filters" role="group" aria-label="Filtr">
        <button v-for="f in FILTERS" :key="f.value" type="button" class="btn btn-sm"
          :class="{ active: (route.query.status || 'all') === f.value }"
          @click="setQuery({ status: f.value === 'all' ? undefined : f.value, page: undefined })">
          {{ f.label }}
        </button>
      </div>
      <label class="search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <span class="sr-only">Qidirish</span>
        <input v-model="search" type="search" class="input" placeholder="Ism, email yoki matn bo‘yicha qidirish" />
      </label>
    </div>

    <div v-if="error" class="alert alert-error mb" role="alert">
      <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ error }}</span>
    </div>

    <div v-if="loading && !messages.length" class="empty"><span class="spinner" aria-label="Yuklanmoqda"></span></div>

    <div v-else-if="!messages.length" class="card empty">
      <i class="bi bi-inbox" aria-hidden="true"></i>
      {{ route.query.q || route.query.status ? "Hech narsa topilmadi" : "Hozircha xabar yo‘q" }}
    </div>

    <ul v-else class="list" :class="{ faded: loading }">
      <li v-for="m in messages" :key="m.id" class="card msg" :class="{ unread: !m.is_read, open: openId === m.id }">
        <button type="button" class="msg-head" :aria-expanded="openId === m.id ? 'true' : 'false'" @click="toggle(m)">
          <span class="dot" aria-hidden="true"></span>
          <span class="who">
            <strong>{{ m.name }}</strong>
            <span class="hint">{{ m.email }}</span>
          </span>
          <span class="preview">{{ m.subject || m.message }}</span>
          <span class="hint date">{{ formatDate(m.created_at) }}</span>
          <i class="bi bi-chevron-down chev" aria-hidden="true"></i>
        </button>

        <div v-if="openId === m.id" class="msg-body">
          <p v-if="m.subject" class="subject">{{ m.subject }}</p>
          <p class="text">{{ m.message }}</p>
          <div class="msg-meta">
            <span class="badge">{{ LANG_LABELS[m.lang] || m.lang }}</span>
            <span class="badge" :class="m.email_sent ? 'badge-success' : ''">
              <i class="bi" :class="m.email_sent ? 'bi-envelope-check' : 'bi-envelope-slash'" aria-hidden="true"></i>
              {{ m.email_sent ? "Emailga yuborilgan" : "Emailga yuborilmagan" }}
            </span>
            <span v-if="m.ip" class="hint">IP: {{ m.ip }}</span>
          </div>
          <div class="msg-actions">
            <a :href="replyLink(m)" class="btn btn-sm btn-primary"><i class="bi bi-reply" aria-hidden="true"></i> Javob yozish</a>
            <button type="button" class="btn btn-sm" @click="setRead(m, !m.is_read)">
              {{ m.is_read ? "O‘qilmagan qilish" : "O‘qilgan qilish" }}
            </button>
            <button type="button" class="btn btn-sm btn-danger" @click="remove(m)">
              <i class="bi bi-trash" aria-hidden="true"></i> O‘chirish
            </button>
          </div>
        </div>
      </li>
    </ul>

    <nav v-if="meta.last_page > 1" class="pager" aria-label="Sahifalar">
      <button type="button" class="btn btn-sm" :disabled="meta.page <= 1" @click="setQuery({ page: meta.page - 1 })">
        <i class="bi bi-chevron-left" aria-hidden="true"></i> Oldingi
      </button>
      <span class="hint">{{ meta.page }} / {{ meta.last_page }} · jami {{ meta.total }}</span>
      <button type="button" class="btn btn-sm" :disabled="meta.page >= meta.last_page" @click="setQuery({ page: meta.page + 1 })">
        Keyingi <i class="bi bi-chevron-right" aria-hidden="true"></i>
      </button>
    </nav>
  </div>
</template>

<style scoped>
.mb { margin-bottom: 16px; }
.page-head h1 { display: flex; align-items: center; gap: 10px; }

.toolbar { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; margin-bottom: 16px; }
.filters { display: flex; gap: 6px; }
.filters .active { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
.search { position: relative; flex: 1; max-width: 360px; min-width: 220px; }
.search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
.search .input { padding-left: 36px; }

.list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; transition: opacity 0.15s; }
.list.faded { opacity: 0.6; }

.msg { padding: 0; overflow: hidden; }
.msg.unread { border-color: rgba(204, 255, 0, 0.3); }

.msg-head {
  width: 100%;
  display: grid;
  grid-template-columns: 10px minmax(140px, 220px) minmax(0, 1fr) auto 16px;
  gap: 14px;
  align-items: center;
  padding: 14px 16px;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
}
.msg-head:hover { background: var(--bg-hover); }

.dot { width: 8px; height: 8px; border-radius: 50%; }
.unread .dot { background: var(--accent); }
.who { display: flex; flex-direction: column; min-width: 0; }
.who > * { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.unread .who strong { color: var(--accent); }
.preview { color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.date { white-space: nowrap; }
.chev { color: var(--muted); transition: transform 0.2s; }
.open .chev { transform: rotate(180deg); }

.msg-body { padding: 4px 16px 16px 40px; display: flex; flex-direction: column; gap: 12px; }
.subject { margin: 0; font-weight: 700; }
.text { margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.6; }
.msg-meta, .msg-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }

.pager { display: flex; justify-content: center; align-items: center; gap: 16px; margin-top: 20px; }

@media (max-width: 700px) {
  .msg-head { grid-template-columns: 10px minmax(0, 1fr) 16px; }
  .preview, .date { display: none; }
  .msg-body { padding-left: 16px; }
}
</style>
