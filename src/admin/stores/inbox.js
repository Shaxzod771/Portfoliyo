import { defineStore } from "pinia";

// Unread message count shown in the sidebar; updated by the dashboard and messages pages
export const useInboxStore = defineStore("inbox", {
  state: () => ({ unread: 0 }),
});
