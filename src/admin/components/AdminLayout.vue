<script setup>
import { ref, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useInboxStore } from "../stores/inbox";
import { adminApi } from "../api";

const auth = useAuthStore();
const inbox = useInboxStore();
const route = useRoute();
const router = useRouter();
const menuOpen = ref(false);
const siteUrl = import.meta.env.BASE_URL;

const links = [
  { to: { name: "dashboard" }, icon: "bi-grid-1x2", label: "Dashboard" },
  { to: { name: "projects" }, icon: "bi-collection", label: "Loyihalar" },
  { to: { name: "messages" }, icon: "bi-envelope", label: "Xabarlar", badge: true },
  { to: { name: "account" }, icon: "bi-person-gear", label: "Profil" },
];

// Close the mobile menu after navigating
watch(() => route.fullPath, () => (menuOpen.value = false));

onMounted(async () => {
  try {
    const stats = await adminApi("/api/admin/stats");
    inbox.unread = stats.messages.unread;
  } catch {
    // The page itself shows connection errors
  }
});

async function logout() {
  await auth.logout();
  router.replace({ name: "login" });
}
</script>

<template>
  <div class="layout">
    <header class="topbar">
      <button type="button" class="btn btn-icon menu-btn" :aria-expanded="menuOpen ? 'true' : 'false'"
        aria-controls="admin-sidebar" aria-label="Menyu" @click="menuOpen = !menuOpen">
        <i class="bi" :class="menuOpen ? 'bi-x-lg' : 'bi-list'" aria-hidden="true"></i>
      </button>
      <span class="brand">&lt;SHAXZOD<span class="accent">.DEV</span>/&gt;</span>
    </header>

    <aside id="admin-sidebar" class="sidebar" :class="{ open: menuOpen }">
      <div class="brand sidebar-brand">&lt;SHAXZOD<span class="accent">.DEV</span>/&gt; <span class="badge">admin</span></div>

      <nav class="nav">
        <RouterLink v-for="link in links" :key="link.label" :to="link.to" class="nav-link"
          :class="{ active: link.to.name === 'projects' ? route.name?.startsWith('project') : route.name === link.to.name }">
          <i class="bi" :class="link.icon" aria-hidden="true"></i>
          <span>{{ link.label }}</span>
          <span v-if="link.badge && inbox.unread" class="badge badge-accent ms-auto">{{ inbox.unread }}</span>
        </RouterLink>
      </nav>

      <div class="sidebar-foot">
        <a :href="siteUrl" target="_blank" rel="noopener" class="nav-link">
          <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span>Saytni ochish</span>
        </a>
        <div class="user">
          <i class="bi bi-person-circle" aria-hidden="true"></i>
          <span class="user-name">{{ auth.admin?.username }}</span>
          <button type="button" class="btn btn-sm" @click="logout">Chiqish</button>
        </div>
      </div>
    </aside>

    <div v-if="menuOpen" class="backdrop" @click="menuOpen = false"></div>

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.layout {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 250px 1fr;
}

.brand { font-weight: 800; letter-spacing: 0.5px; font-size: 0.95rem; }
.accent { color: var(--accent); }

.topbar { display: none; }

.sidebar {
  position: sticky;
  top: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px 16px;
  border-right: 1px solid var(--border);
  background: var(--bg-raised);
}

.sidebar-brand { display: flex; align-items: center; gap: 8px; padding: 0 8px; }

.nav { display: flex; flex-direction: column; gap: 4px; }

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  color: var(--muted);
  text-decoration: none;
  font-weight: 500;
  transition: background-color 0.15s, color 0.15s;
}
.nav-link:hover { background: var(--bg-hover); color: var(--text); }
.nav-link.active { background: var(--accent-dim); color: var(--accent); }
.ms-auto { margin-left: auto; }

.sidebar-foot { margin-top: auto; display: flex; flex-direction: column; gap: 8px; }

.user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-top: 1px solid var(--border);
}
.user-name { flex: 1; overflow: hidden; text-overflow: ellipsis; font-weight: 600; }

.content {
  padding: 32px clamp(16px, 4vw, 48px);
  min-width: 0;
  max-width: 1200px;
}

.backdrop { display: none; }

@media (max-width: 900px) {
  .layout { grid-template-columns: 1fr; }

  .topbar {
    position: sticky;
    top: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    background: var(--bg-raised);
    border-bottom: 1px solid var(--border);
  }

  .sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 260px;
    z-index: 40;
    transform: translateX(-100%);
    transition: transform 0.25s ease;
  }
  .sidebar.open { transform: none; }

  .backdrop {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 30;
    background: rgba(0, 0, 0, 0.5);
  }

  .content { padding: 20px 16px; }
}
</style>
