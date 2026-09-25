import { createApp } from "vue";
import { createPinia } from "pinia";
import "bootstrap-icons/font/bootstrap-icons.css";
import "./admin.css";
import AdminApp from "./AdminApp.vue";
import router from "./router";

createApp(AdminApp).use(createPinia()).use(router).mount("#admin");
