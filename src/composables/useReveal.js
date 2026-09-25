import { onMounted, onUnmounted } from "vue";

/**
 * Adds the `in-view` class to elements inside `rootRef` once they scroll into view.
 * Only this component's own elements are observed, and the observer is released on unmount.
 */
export function useReveal(rootRef, selector = ".animate-up") {
  let observer = null;

  onMounted(() => {
    const root = rootRef.value;
    if (!root) return;
    const targets = root.querySelectorAll(selector);

    if (!("IntersectionObserver" in window)) {
      targets.forEach((el) => el.classList.add("in-view"));
      return;
    }

    observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("in-view");
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    targets.forEach((el) => observer.observe(el));
  });

  onUnmounted(() => {
    observer?.disconnect();
    observer = null;
  });
}
