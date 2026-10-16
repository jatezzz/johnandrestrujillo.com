// Progressive enhancements: the theme toggle and the capability pins.
// Without this file the page still works: each pin links to its evidence list.
(() => {
  'use strict';

  const root = document.documentElement;
  const query = (q) => (window.matchMedia ? window.matchMedia(q) : null);
  const prefersDark = query('(prefers-color-scheme: dark)');
  const reducedMotion = query('(prefers-reduced-motion: reduce)');
  const singleColumn = query('(max-width: 61.99rem)');

  // ---- Theme toggle ----
  const toggle = document.querySelector('[data-theme-toggle]');
  const currentTheme = () => {
    const set = root.getAttribute('data-theme');
    if (set === 'light' || set === 'dark') return set;
    return prefersDark && prefersDark.matches ? 'dark' : 'light';
  };
  if (toggle) {
    const sync = () => toggle.setAttribute('aria-pressed', String(currentTheme() === 'dark'));
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
      const next = currentTheme() === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try {
        localStorage.setItem('theme', next);
      } catch (error) {
        // Not saved; the choice lasts until the page is closed.
      }
      sync();
    });
    if (prefersDark && prefersDark.addEventListener) prefersDark.addEventListener('change', sync);
    sync();
  }

  // ---- Pins: show where each capability was used ----
  const panel = document.getElementById('evidence-panel');
  const pins = Array.from(document.querySelectorAll('.pin__link'));
  if (!panel || pins.length === 0) return;

  const status = panel.querySelector('[data-status]');
  const content = panel.querySelector('[data-content]');
  panel.hidden = false;

  const select = (pin) => {
    const source = document.getElementById('pin-' + pin.dataset.pin);
    const body = source && source.querySelector('.evidence__body');
    if (!body) return false;

    pins.forEach((other) => other.removeAttribute('aria-current'));
    pin.setAttribute('aria-current', 'true');

    const title = document.createElement('p');
    title.className = 'evidence-panel__title';
    const label = document.createElement('span');
    label.className = 'label';
    label.textContent = pin.querySelector('.pin__label').textContent;
    title.append(label, ' ' + pin.dataset.name);
    content.replaceChildren(title, body.cloneNode(true));

    const count = Number(pin.dataset.count);
    const template = count === 1 ? panel.dataset.statusOne : panel.dataset.statusMany;
    status.textContent = template.replace('{name}', pin.dataset.name).replace('{n}', String(count));
    return true;
  };

  // Arrow keys move along a side (up/down) or across the chip (left/right).
  const move = (from, key) => {
    const side = from.dataset.side;
    let target;
    if (key === 'ArrowUp' || key === 'ArrowDown') {
      const column = pins.filter((pin) => pin.dataset.side === side);
      const index = column.indexOf(from) + (key === 'ArrowDown' ? 1 : -1);
      target = column[(index + column.length) % column.length];
    } else {
      const other = side === 'left' ? 'right' : 'left';
      target = pins.find((pin) => pin.dataset.side === other && pin.dataset.row === from.dataset.row);
    }
    if (target) target.focus();
  };

  pins.forEach((pin) => {
    pin.setAttribute('aria-controls', 'evidence-panel');
    pin.addEventListener('click', (event) => {
      if (!select(pin)) return;
      event.preventDefault();
      if (singleColumn && singleColumn.matches) {
        panel.scrollIntoView({ block: 'nearest', behavior: reducedMotion && reducedMotion.matches ? 'auto' : 'smooth' });
      }
    });
    pin.addEventListener('keydown', (event) => {
      if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      event.preventDefault();
      move(pin, event.key);
    });
  });
})();
