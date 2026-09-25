import { createApp } from "vue";
import { createPinia } from "pinia";
// Only the parts of Bootstrap the site uses: base reset, grid and utility classes
import "bootstrap/dist/css/bootstrap-reboot.min.css";
import "bootstrap/dist/css/bootstrap-grid.min.css";
import "bootstrap/dist/css/bootstrap-utilities.min.css";
import "bootstrap-icons/font/bootstrap-icons.css";
import App from "./App.vue";
import magnetic from "./directives/magnetic";

const app = createApp(App);

app.use(createPinia());

app.directive('magnetic', magnetic);

app.mount("#app");
