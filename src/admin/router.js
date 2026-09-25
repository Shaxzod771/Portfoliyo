import { createRouter, createWebHashHistory } from "vue-router";
import { useAuthStore } from "./stores/auth";
import AdminLayout from "./components/AdminLayout.vue";
import LoginView from "./views/LoginView.vue";
import SetupView from "./views/SetupView.vue";

// Hash history: admin.html is a static file, so /admin.html#/projects works on any host without rewrites
const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: "/login", name: "login", component: LoginView, meta: { title: "Kirish", guest: true } },
    { path: "/setup", name: "setup", component: SetupView, meta: { title: "Birinchi sozlash", guest: true } },
    {
      path: "/",
      component: AdminLayout,
      children: [
        { path: "", name: "dashboard", component: () => import("./views/DashboardView.vue"), meta: { title: "Dashboard" } },
        { path: "projects", name: "projects", component: () => import("./views/ProjectsView.vue"), meta: { title: "Loyihalar" } },
        { path: "projects/new", name: "project-new", component: () => import("./views/ProjectFormView.vue"), meta: { title: "Yangi loyiha" } },
        { path: "projects/:id(\\d+)", name: "project-edit", component: () => import("./views/ProjectFormView.vue"), props: true, meta: { title: "Loyihani tahrirlash" } },
        { path: "messages", name: "messages", component: () => import("./views/MessagesView.vue"), meta: { title: "Xabarlar" } },
        { path: "account", name: "account", component: () => import("./views/AccountView.vue"), meta: { title: "Profil" } },
      ],
    },
    { path: "/:pathMatch(.*)*", redirect: "/" },
  ],
});

router.beforeEach((to) => {
  const auth = useAuthStore();
  if (!to.meta.guest && !auth.isLoggedIn) {
    return { name: "login", query: to.fullPath !== "/" ? { redirect: to.fullPath } : {} };
  }
  if (to.meta.guest && auth.isLoggedIn) {
    return { name: "dashboard" };
  }
});

router.afterEach((to) => {
  document.title = `${to.meta.title || "Admin"} — Shaxzod.dev admin`;
});

export default router;
