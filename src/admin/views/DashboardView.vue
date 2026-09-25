<script setup>
import { ref, onMounted } from "vue";
import { adminApi } from "../api";
import { useInboxStore } from "../stores/inbox";
import { useAuthStore } from "../stores/auth";
import { formatDate } from "../format";

const auth = useAuthStore();
const inbox = useInboxStore();
const stats = ref(null);
const error = ref("");

onMounted(async () => {
  try {
    stats.value = await adminApi("/api/admin/stats");
    inbox.unread = stats.value.messages.unread;
  } catch (err) {
    error.value = err.message;
  }
});
</script>

<template>
  <div>
    <div class="page-head">
      <h1>Salom, {{ auth.admin?.username }} 👋</h1>
      <RouterLink :to="{ name: 'project-new' }" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Yangi loyiha
      </RouterLink>
    </div>

    <div v-if="error" class="alert alert-error" role="alert">
      <i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ error }}</span>
    </div>

    <template v-else-if="stats">
      <div class="stat-grid">
        <RouterLink :to="{ name: 'projects' }" class="card stat">
          <span class="stat-label">Loyihalar</span>
          <span class="stat-value">{{ stats.projects.total }}</span>
          <span class="hint">{{ stats.projects.published }} tasi saytda ko‘rinadi</span>
        </RouterLink>
        <RouterLink :to="{ name: 'messages', query: { status: 'unread' } }" class="card stat">
          <span class="stat-label">O‘qilmagan xabarlar</span>
          <span class="stat-value" :class="{ accent: stats.messages.unread }">{{ stats.messages.unread }}</span>
          <span class="hint">Jami {{ stats.messages.total }} ta xabar</span>
        </RouterLink>
        <div class="card stat">
          <span class="stat-label">So‘nggi 7 kun</span>
          <span class="stat-value">{{ stats.messages.last_7_days }}</span>
          <span class="hint">yangi xabar</span>
        </div>
      </div>

      <section class="card mt">
        <div class="section-head">
          <h2>Oxirgi xabarlar</h2>
          <RouterLink :to="{ name: 'messages' }" class="btn btn-sm">Barchasi</RouterLink>
        </div>
        <div v-if="!stats.latest_messages.length" class="empty">
          <i class="bi bi-inbox" aria-hidden="true"></i>Hozircha xabar yo‘q
        </div>
        <ul v-else class="latest">
          <li v-for="m in stats.latest_messages" :key="m.id">
            <RouterLink :to="{ name: 'messages', query: { open: m.id } }" class="latest-item">
              <span class="dot" :class="{ unread: !m.is_read }" aria-hidden="true"></span>
              <span class="latest-main">
                <strong>{{ m.name }}</strong>
                <span class="hint"> — {{ m.subject || m.message }}</span>
              </span>
              <span class="hint nowrap">{{ formatDate(m.created_at) }}</span>
            </RouterLink>
          </li>
        </ul>
      </section>
    </template>

    <div v-else class="empty"><span class="spinner" aria-label="Yuklanmoqda"></span></div>
  </div>
</template>

<style scoped>
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 4px;
  text-decoration: none;
  transition: border-color 0.15s;
}
a.stat:hover { border-color: var(--border-strong); }
.stat-label { color: var(--muted); font-weight: 600; font-size: 0.85rem; }
.stat-value { font-size: 2.2rem; font-weight: 800; }
.stat-value.accent { color: var(--accent); }

.mt { margin-top: 24px; }
.section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.section-head h2 { font-size: 1.05rem; }

.latest { list-style: none; margin: 0; padding: 0; }
.latest-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 8px;
  border-radius: 8px;
  text-decoration: none;
}
.latest-item:hover { background: var(--bg-hover); }
.latest li + li { border-top: 1px solid var(--border); }
.latest-main { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.nowrap { white-space: nowrap; }

.dot { width: 8px; height: 8px; border-radius: 50%; background: transparent; flex-shrink: 0; }
.dot.unread { background: var(--accent); }
</style>
