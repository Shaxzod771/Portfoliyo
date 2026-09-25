<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const cursor = ref(null);
const cursorDot = ref(null);
const isHovering = ref(false);
const isProjectHover = ref(false);
// Only desktop pointers get the custom cursor; decided on mount so touch devices render nothing
const isEnabled = ref(false);

const INTERACTIVE = 'a, button, input, textarea, .magnetic, .interactive';

let mouseX = 0;
let mouseY = 0;
let cursorX = 0;
let cursorY = 0;
let dotX = 0;
let dotY = 0;

let requestRef;

const animate = () => {
  // Smooth follow for the outer circle
  cursorX += (mouseX - cursorX) * 0.15;
  cursorY += (mouseY - cursorY) * 0.15;

  // Near-instant follow for the dot
  dotX += (mouseX - dotX) * 0.4;
  dotY += (mouseY - dotY) * 0.4;

  if (cursor.value) {
    cursor.value.style.transform = `translate3d(${cursorX}px, ${cursorY}px, 0) translate(-50%, -50%)`;
  }
  if (cursorDot.value) {
    cursorDot.value.style.transform = `translate3d(${dotX}px, ${dotY}px, 0) translate(-50%, -50%)`;
  }

  requestRef = requestAnimationFrame(animate);
};

const onMouseMove = (e) => {
  mouseX = e.clientX;
  mouseY = e.clientY;
};

// One delegated listener instead of attaching handlers to every link:
// works for elements added later (e.g. after a language switch) and never piles up duplicates.
const onMouseOver = (e) => {
  const target = e.target instanceof Element ? e.target : null;
  isProjectHover.value = Boolean(target?.closest('.project-card'));
  isHovering.value = Boolean(target?.closest(INTERACTIVE));
};

const onMouseLeaveWindow = () => {
  isHovering.value = false;
  isProjectHover.value = false;
};

onMounted(() => {
  isEnabled.value =
    window.matchMedia('(pointer: fine)').matches &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!isEnabled.value) return;

  window.addEventListener('mousemove', onMouseMove, { passive: true });
  document.addEventListener('mouseover', onMouseOver, { passive: true });
  document.documentElement.addEventListener('mouseleave', onMouseLeaveWindow);
  requestRef = requestAnimationFrame(animate);
});

onUnmounted(() => {
  if (!isEnabled.value) return;
  window.removeEventListener('mousemove', onMouseMove);
  document.removeEventListener('mouseover', onMouseOver);
  document.documentElement.removeEventListener('mouseleave', onMouseLeaveWindow);
  cancelAnimationFrame(requestRef);
});
</script>

<template>
  <div v-if="isEnabled" class="cursor-wrapper" aria-hidden="true">
    <div ref="cursorDot" class="cursor-dot" :class="{ 'hidden': isProjectHover }"></div>
    <div
      ref="cursor"
      class="cursor-ring"
      :class="{
        'hovering': isHovering && !isProjectHover,
        'project-hover': isProjectHover
      }"
    >
      <span v-if="isProjectHover" class="cursor-text">VIEW</span>
    </div>
  </div>
</template>

<style scoped>
.cursor-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  pointer-events: none;
  z-index: 9999;
}

.cursor-dot {
  position: absolute;
  top: 0;
  left: 0;
  width: 6px;
  height: 6px;
  background-color: var(--text-color, #fff);
  border-radius: 50%;
  pointer-events: none;
  transition: opacity 0.2s;
}

.cursor-ring {
  position: absolute;
  top: 0;
  left: 0;
  width: 32px;
  height: 32px;
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 50%;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: width 0.3s ease, height 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
}

.cursor-ring.hovering {
  width: 48px;
  height: 48px;
  border-color: var(--accent, #ccff00);
  background-color: rgba(204, 255, 0, 0.05);
}

.cursor-ring.project-hover {
  width: 80px;
  height: 80px;
  border-color: transparent;
  background-color: var(--accent, #ccff00);
}

.cursor-text {
  color: #07091e;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1px;
  opacity: 0;
  animation: fadeIn 0.2s forwards;
}

.cursor-dot.hidden {
  opacity: 0;
}

@keyframes fadeIn {
  to {
    opacity: 1;
  }
}
</style>
