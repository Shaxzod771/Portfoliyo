import { ref } from "vue";
import { PROJECTS } from "../constants/projects";
import { apiRequest, hasApi } from "../services/api";

// Shared by every component: projects are fetched once per page load.
// Until the API answers (or when there is no API) the built-in list is shown.
const projects = ref(PROJECTS);
let loading = null;

function fromApi(p) {
  return {
    key: `db-${p.id}`,
    image: p.image_url,
    fit: p.image_fit,
    github: p.github_url,
    live: p.live_url,
    type: p.layout,
    // Texts come with the project instead of from translations.js
    title: p.title,
    desc: p.description,
  };
}

export function useProjects() {
  if (hasApi && !loading) {
    loading = apiRequest("/api/projects")
      .then(({ data }) => {
        projects.value = data.map(fromApi);
      })
      .catch((err) => {
        console.warn("Projects API unavailable, using built-in list:", err.message);
      });
  }
  return projects;
}
