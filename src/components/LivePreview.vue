<script setup>
import { ref, onMounted, onUnmounted } from "vue";

// Shows a running website inside a project card: the page is rendered at desktop width
// and scaled down to the card, like a live screenshot. It loads only when the card is near the screen.
const props = defineProps({
  url: { type: String, required: true },
  title: { type: String, default: "" },
  fallbackImage: { type: String, default: null },
});

const VIEWPORT_WIDTH = 1440;

const root = ref(null);
const scale = ref(0.25);
const visible = ref(false);
const loaded = ref(false);

let resizeObserver = null;
let intersectionObserver = null;

onMounted(() => {
  const el = root.value;
  resizeObserver = new ResizeObserver(([entry]) => {
    scale.value = entry.contentRect.width / VIEWPORT_WIDTH;
  });
  resizeObserver.observe(el);

  intersectionObserver = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) {
      visible.value = true;
      intersectionObserver.disconnect();
    }
  }, { rootMargin: "300px" });
  intersectionObserver.observe(el);
});

onUnmounted(() => {
  resizeObserver?.disconnect();
  intersectionObserver?.disconnect();
});
</script>

<template>
  <div ref="root" class="live-preview">
    <!-- Shown until the site has loaded (and if it never does) -->
    <img v-if="fallbackImage" :src="fallbackImage" alt="" class="live-fallback" :class="{ hidden: loaded }" loading="lazy" />
    <div v-else class="live-fallback live-skeleton" :class="{ hidden: loaded }"></div>

    <iframe
      v-if="visible"
      :src="url"
      :title="`${title} — jonli ko‘rinish`"
      class="live-frame"
      :style="{ width: `${VIEWPORT_WIDTH}px`, height: `${100 / scale}%`, transform: `scale(${scale})` }"
      loading="lazy"
      referrerpolicy="no-referrer"
      sandbox="allow-scripts allow-same-origin"
      tabindex="-1"
      aria-hidden="true"
      @load="loaded = true"
    ></iframe>

    <span class="live-badge"><span class="live-dot"></span>LIVE</span>
  </div>
</template>

<style scoped>
.live-preview {
  position: absolute;
  inset: 0;
  overflow: hidden;
  background: var(--bg-secondary);
}

.live-frame {
  position: absolute;
  top: 0;
  left: 0;
  border: 0;
  transform-origin: 0 0;
  background: #fff;
  /* The card stays clickable and the preview can't be scrolled or focused */
  pointer-events: none;
}

.live-fallback {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top center;
  z-index: 1;
  transition: opacity 0.5s ease;
}
.live-fallback.hidden {
  opacity: 0;
  pointer-events: none;
}

.live-skeleton {
  background: linear-gradient(110deg, var(--bg-secondary) 30%, rgba(255, 255, 255, 0.05) 50%, var(--bg-secondary) 70%);
  background-size: 200% 100%;
  animation: shimmer 1.4s linear infinite;
}

@keyframes shimmer {
  to { background-position: -200% 0; }
}

.live-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 3;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 100px;
  background: rgba(4, 5, 9, 0.75);
  backdrop-filter: blur(6px);
  color: var(--text-color);
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.live-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--accent);
  box-shadow: 0 0 8px var(--accent);
  animation: pulse 1.6s ease-in-out infinite;
}

@keyframes pulse {
  50% { opacity: 0.35; }
}
</style>
