import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ command }) => ({
  // GitHub Pages serves the site from https://<user>.github.io/Portfoliyo/
  base: '/Portfoliyo/',
  plugins: [
    vue(),
    // Devtools are only useful while developing; keep them out of the production bundle
    command === 'serve' && vueDevTools(),
  ],
  build: {
    rollupOptions: {
      // Two pages: the portfolio and the admin panel (admin.html)
      input: {
        main: fileURLToPath(new URL('./index.html', import.meta.url)),
        admin: fileURLToPath(new URL('./admin.html', import.meta.url)),
      },
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
}))
