document.querySelectorAll('.alert').forEach((el) => {
  setTimeout(() => el.classList.add('fade'), 3800);
});

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.querySelectorAll('[data-tilt-card]').forEach((card) => {
  if (prefersReducedMotion) {
    return;
  }

  const strength = 10;
  let frame = null;
  let pointer = null;
  const focusableChild = card.querySelector('a, button, input, select, textarea');

  if (!focusableChild) {
    return;
  }

  const renderTilt = () => {
    if (!pointer) {
      frame = null;
      return;
    }

    const rect = card.getBoundingClientRect();
    const px = (pointer.x - rect.left) / rect.width;
    const py = (pointer.y - rect.top) / rect.height;
    const rotateY = (px - 0.5) * strength;
    const rotateX = (0.5 - py) * strength;

    card.style.transform = `perspective(900px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-3px)`;
    frame = null;
  };

  card.addEventListener('mousemove', (event) => {
    pointer = { x: event.clientX, y: event.clientY };

    if (frame === null) {
      frame = window.requestAnimationFrame(renderTilt);
    }
  });

  card.addEventListener('mouseleave', () => {
    pointer = null;
    if (frame !== null) {
      window.cancelAnimationFrame(frame);
      frame = null;
    }
    card.style.transform = '';
  });

  card.addEventListener('focusin', () => {
    card.style.transform = 'perspective(900px) rotateX(2deg) rotateY(-2deg) translateY(-2px)';
  });

  card.addEventListener('focusout', (event) => {
    if (!card.contains(event.relatedTarget)) {
      card.style.transform = '';
    }
  });
});

document.querySelectorAll('.amount-grid').forEach((grid) => {
  const updateSelection = () => {
    grid.querySelectorAll('.amount-input').forEach((input) => {
      const card = input.nextElementSibling;

      if (card?.classList.contains('amount-card')) {
        card.classList.toggle('is-selected', input.checked);
      }
    });
  };

  grid.addEventListener('change', updateSelection);
  updateSelection();
});
