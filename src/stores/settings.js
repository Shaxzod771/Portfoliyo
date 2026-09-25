import { defineStore } from 'pinia';

const SUPPORTED_LANGS = ['uz', 'en', 'ru'];

function readStoredLang() {
  try {
    const lang = localStorage.getItem('lang');
    return SUPPORTED_LANGS.includes(lang) ? lang : 'uz';
  } catch {
    return 'uz';
  }
}

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    lang: readStoredLang(),
  }),
  actions: {
    setLanguage(lang) {
      if (!SUPPORTED_LANGS.includes(lang)) return;
      this.lang = lang;
      try {
        localStorage.setItem('lang', lang);
      } catch {
        // Storage can be unavailable (private mode); the choice still applies for this visit
      }
    },
  }
});
