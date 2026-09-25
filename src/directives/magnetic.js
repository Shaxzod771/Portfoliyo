export default {
  mounted(el, binding) {
    // Magnetic hover only makes sense with a mouse
    if (!window.matchMedia('(pointer: fine)').matches) return;

    // Transforms don't apply to plain inline elements; leave flex/block layouts untouched
    if (getComputedStyle(el).display === 'inline') {
      el.style.display = 'inline-block';
    }

    const strength = binding.value || 30; // Maximum distance to move
    
    let boundingClientRect = null;
    let isHovered = false;

    const handleMouseMove = (e) => {
      if (!isHovered) return;
      if (!boundingClientRect) boundingClientRect = el.getBoundingClientRect();
      
      const x = e.clientX - boundingClientRect.left - boundingClientRect.width / 2;
      const y = e.clientY - boundingClientRect.top - boundingClientRect.height / 2;
      
      const pullX = (x / boundingClientRect.width) * strength;
      const pullY = (y / boundingClientRect.height) * strength;
      
      el.style.transform = `translate(${pullX}px, ${pullY}px)`;
      // Add slight transition for smooth following
      el.style.transition = 'transform 0.1s ease-out';
    };

    const handleMouseEnter = () => {
      isHovered = true;
      boundingClientRect = el.getBoundingClientRect();
      el.style.transition = 'transform 0.1s ease-out';
    };

    const handleMouseLeave = () => {
      isHovered = false;
      el.style.transform = 'translate(0px, 0px)';
      el.style.transition = 'transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
      boundingClientRect = null;
    };

    el.addEventListener('mousemove', handleMouseMove);
    el.addEventListener('mouseenter', handleMouseEnter);
    el.addEventListener('mouseleave', handleMouseLeave);
    
    // Store cleanup on element
    el._magneticCleanup = () => {
      el.removeEventListener('mousemove', handleMouseMove);
      el.removeEventListener('mouseenter', handleMouseEnter);
      el.removeEventListener('mouseleave', handleMouseLeave);
    };
  },
  unmounted(el) {
    if (el._magneticCleanup) {
      el._magneticCleanup();
    }
  }
};
