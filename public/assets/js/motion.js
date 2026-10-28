// Motion layer, on top of a page that already works without it: controls that lean toward the pointer, the chip
// that tilts and powers the pins the pointer comes near, a signal along the evidence panel when a pin is picked,
// the masthead trace that follows the section on screen, and on phones a masthead that steps aside while reading.
// Pointer effects run only with a mouse or trackpad and never with reduced motion. Nothing here adds content; screen readers get the same page.
(() => {
  'use strict';

  const query = (q) => (window.matchMedia ? window.matchMedia(q) : { matches: false });
  const reducedMotion = query('(prefers-reduced-motion: reduce)');
  const finePointer = query('(hover: hover) and (pointer: fine)');
  const pointerEffects = () => finePointer.matches && !reducedMotion.matches;
  const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

  // ---- Magnetic controls: inside a field around the control it follows the pointer a little, then springs back ----
  const magnets = Array.from(document.querySelectorAll('[data-magnetic]'), (el) => ({ el, x: 0, y: 0, tx: 0, ty: 0 }));
  const FIELD = 40; // px of field beyond the control's edge
  const PULL = 0.3; // share of the pointer's offset from the centre
  const MAX = 10; // px, so a control never leaves its place
  let frame = 0;

  const settle = () => {
    frame = 0;
    let moving = false;
    magnets.forEach((m) => {
      m.x += (m.tx - m.x) * 0.2;
      m.y += (m.ty - m.y) * 0.2;
      if (Math.abs(m.tx - m.x) < 0.05 && Math.abs(m.ty - m.y) < 0.05) {
        m.x = m.tx;
        m.y = m.ty;
      } else {
        moving = true;
      }
      m.el.style.translate = m.x === 0 && m.y === 0 ? '' : `${m.x.toFixed(2)}px ${m.y.toFixed(2)}px`;
    });
    if (moving) frame = requestAnimationFrame(settle);
  };
  const kick = () => {
    if (!frame) frame = requestAnimationFrame(settle);
  };

  const pullMagnets = (px, py) => {
    magnets.forEach((m) => {
      const box = m.el.getBoundingClientRect();
      // the box already includes our own offset: measure from where the control rests
      const cx = box.left + box.width / 2 - m.x;
      const cy = box.top + box.height / 2 - m.y;
      const dx = px - cx;
      const dy = py - cy;
      const inside = Math.abs(dx) < box.width / 2 + FIELD && Math.abs(dy) < box.height / 2 + FIELD;
      m.tx = inside ? clamp(dx * PULL, -MAX, MAX) : 0;
      m.ty = inside ? clamp(dy * PULL, -MAX, MAX) : 0;
    });
    kick();
  };
  const releaseMagnets = () => {
    magnets.forEach((m) => { m.tx = 0; m.ty = 0; });
    kick();
  };

  // ---- The chip: tilts toward the pointer, and the pins near it light up ----
  const hero = document.querySelector('.hero');
  const body = document.querySelector('.pinout__body');
  const pinLinks = Array.from(document.querySelectorAll('.pin__link'));
  const REACH = 150; // px within which a pin starts to light up
  const TILT = 6; // degrees at the chip's edge

  const powerChip = (px, py) => {
    const box = body.getBoundingClientRect();
    const nx = clamp((px - (box.left + box.width / 2)) / (box.width / 2), -1.5, 1.5);
    const ny = clamp((py - (box.top + box.height / 2)) / (box.height / 2), -1.5, 1.5);
    body.style.setProperty('--tilt-x', `${(-ny * TILT).toFixed(2)}deg`);
    body.style.setProperty('--tilt-y', `${(nx * TILT).toFixed(2)}deg`);
    body.style.setProperty('--glare-x', `${(50 + nx * 50).toFixed(1)}%`);
    body.style.setProperty('--glare-y', `${(50 + ny * 50).toFixed(1)}%`);
    body.classList.add('is-live');
    pinLinks.forEach((pin) => {
      const lead = pin.querySelector('.pin__lead').getBoundingClientRect();
      const distance = Math.hypot(px - (lead.left + lead.width / 2), py - (lead.top + lead.height / 2));
      const charge = Math.round(clamp(1 - distance / REACH, 0, 1) * 20) / 20;
      if (pin.style.getPropertyValue('--charge') !== String(charge)) pin.style.setProperty('--charge', String(charge));
    });
  };
  const restChip = () => {
    if (!body) return;
    body.classList.remove('is-live');
    ['--tilt-x', '--tilt-y', '--glare-x', '--glare-y'].forEach((name) => body.style.removeProperty(name));
    pinLinks.forEach((pin) => pin.style.removeProperty('--charge'));
  };

  // One listener for both effects, at most once per frame.
  let pointer = null;
  let pending = 0;
  document.addEventListener('pointermove', (event) => {
    if (event.pointerType !== 'mouse' || !pointerEffects()) return;
    pointer = event;
    if (pending) return;
    pending = requestAnimationFrame(() => {
      pending = 0;
      if (magnets.length) pullMagnets(pointer.clientX, pointer.clientY);
      if (body && hero && hero.contains(pointer.target)) powerChip(pointer.clientX, pointer.clientY);
      else restChip();
    });
  }, { passive: true });
  document.documentElement.addEventListener('pointerleave', () => { releaseMagnets(); restChip(); });
  window.addEventListener('blur', () => { releaseMagnets(); restChip(); });
  [reducedMotion, finePointer].forEach((list) => {
    if (list.addEventListener) list.addEventListener('change', () => { if (!pointerEffects()) { releaseMagnets(); restChip(); } });
  });

  // ---- Evidence panel: a signal runs along its top edge each time a pin is picked ----
  const panel = document.getElementById('evidence-panel');
  if (panel) {
    pinLinks.forEach((pin) => pin.addEventListener('click', () => {
      panel.classList.remove('is-signal');
      void panel.offsetWidth; // restart the animation
      panel.classList.add('is-signal');
    }));
    panel.addEventListener('animationend', () => panel.classList.remove('is-signal'));
  }

  // ---- Masthead: a trace under the link of the section on screen ----
  const nav = document.querySelector('.masthead__nav ul');
  const links = nav ? Array.from(nav.querySelectorAll('a[href^="#"]')) : [];
  const sections = links.map((link) => document.getElementById(link.hash.slice(1))).filter(Boolean);
  if (nav && sections.length && 'IntersectionObserver' in window) {
    const trace = document.createElement('li');
    trace.className = 'masthead__trace';
    trace.setAttribute('aria-hidden', 'true');
    nav.append(trace);
    let current = null;

    const place = () => {
      if (!current) {
        trace.classList.remove('is-on');
        return;
      }
      trace.style.width = `${current.offsetWidth}px`;
      trace.style.translate = `${current.offsetLeft}px ${current.offsetTop + current.offsetHeight}px`;
      trace.classList.add('is-on');
      // on phones the links are one row that scrolls sideways: keep the current one in it
      if (nav.scrollWidth > nav.clientWidth) {
        nav.scrollTo({ left: current.offsetLeft - 16, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
      }
    };
    const mark = (id) => {
      const next = links.find((link) => link.hash === `#${id}`) || null;
      if (next === current) return;
      links.forEach((link) => link.removeAttribute('aria-current'));
      if (next) next.setAttribute('aria-current', 'location');
      current = next;
      place();
    };

    // A section counts as "on screen" while it crosses the line 40% down the viewport.
    const onScreen = new Set();
    const watcher = new IntersectionObserver((entries) => {
      entries.forEach((entry) => (entry.isIntersecting ? onScreen.add(entry.target) : onScreen.delete(entry.target)));
      const first = sections.find((section) => onScreen.has(section));
      mark(first ? first.id : null);
    }, { rootMargin: '-40% 0px -59% 0px' });
    sections.forEach((section) => watcher.observe(section));
    window.addEventListener('resize', place, { passive: true });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(place);
  }

  // ---- Phones: the masthead steps aside while reading down and comes back on the first scroll up ----
  const masthead = document.querySelector('.masthead');
  const narrow = query('(max-width: 61.99rem)');
  if (masthead) {
    let lastY = window.scrollY;
    let ticking = false;
    const SLACK = 8; // px of scroll ignored, so a shaky thumb does not flicker it
    const check = () => {
      ticking = false;
      const y = window.scrollY;
      const delta = y - lastY;
      if (Math.abs(delta) < SLACK) return;
      const hide = narrow.matches && delta > 0 && y > masthead.offsetHeight && !masthead.contains(document.activeElement);
      masthead.classList.toggle('is-hidden', hide);
      lastY = y;
    };
    window.addEventListener('scroll', () => {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(check);
      }
    }, { passive: true });
    masthead.addEventListener('focusin', () => masthead.classList.remove('is-hidden'));
  }

})();
