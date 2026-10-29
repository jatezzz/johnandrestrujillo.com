// Under the hood: fills the live readings from the Performance APIs and feature checks, in this browser, as the
// visitor reads. Labels come from the page (data-*), so this file holds no copy in either language.
(() => {
  'use strict';

  const box = document.querySelector('[data-hood]');
  if (!box) return;
  const t = box.dataset;
  const arrival = JSON.parse(t.arrival || '{}');
  const prefs = JSON.parse(t.prefs || '{}');
  const set = (attr, key, value, hint = false) => {
    const el = box.querySelector(`[${attr}="${key}"]`);
    if (!el) return;
    el.textContent = value;
    el.classList.toggle('is-hint', hint);
  };
  const reading = (key, value, hint = false) => set('data-reading', key, value, hint);
  const ms = (value) => `${Math.round(value).toLocaleString()} ms`;
  const kb = (bytes) => `${(bytes / 1024).toFixed(1)} KB`;
  const observe = (type, callback, options = {}) => {
    try {
      new PerformanceObserver((list) => callback(list.getEntries())).observe({ type, buffered: true, ...options });
      return true;
    } catch (error) {
      return false; // entry type not supported here
    }
  };

  // ---- the visit ----
  ['lcp', 'cls', 'inp'].forEach((key) => reading(key, t.waiting, true));
  if (!observe('largest-contentful-paint', (entries) => reading('lcp', ms(entries[entries.length - 1].startTime)))) reading('lcp', t.no, true);
  let cls = 0;
  if (observe('layout-shift', (entries) => {
    entries.forEach((entry) => { if (!entry.hadRecentInput) cls += entry.value; });
    reading('cls', cls.toFixed(3));
  })) reading('cls', '0.000');
  let inp = 0;
  if (observe('event', (entries) => {
    entries.forEach((entry) => { if (entry.interactionId) inp = Math.max(inp, entry.duration); });
    if (inp) reading('inp', ms(inp));
  }, { durationThreshold: 16 })) reading('inp', t.interact, true);
  else reading('inp', t.no, true);

  const nav = performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
  if (nav) {
    const kind = document.prerendering || nav.activationStart > 0 ? 'prerender' : nav.type;
    reading('arrival', arrival[kind] || kind);
  }
  const totals = () => {
    const resources = performance.getEntriesByType('resource');
    const bytes = (nav ? nav.transferSize : 0) + resources.reduce((sum, entry) => sum + (entry.transferSize || 0), 0);
    reading('bytes', bytes > 0 ? kb(bytes) : t.cached);
    reading('requests', String(resources.length + 1));
  };
  if (document.readyState === 'complete') totals();
  else window.addEventListener('load', totals, { once: true });
  observe('resource', totals);

  // ---- what this browser supports ----
  const css = (rule) => !!(window.CSS && CSS.supports && CSS.supports(rule));
  const support = {
    vt: 'onpagereveal' in window,
    anchor: css('position-area: bottom'),
    popover: typeof HTMLElement.prototype.showPopover === 'function',
    spec: !!(HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules')),
    scroll: css('animation-timeline: view()'),
  };
  Object.entries(support).forEach(([key, ok]) => set('data-support', key, ok ? t.yes : t.no));

  // ---- the visitor's settings, live ----
  const media = (q) => (window.matchMedia ? window.matchMedia(q) : { matches: false, addEventListener() {} });
  const queries = {
    scheme: [media('(prefers-color-scheme: dark)'), 'dark', 'light'],
    motion: [media('(prefers-reduced-motion: reduce)'), 'reduce', 'full'],
    contrast: [media('(prefers-contrast: more)'), 'more', 'normal'],
    transparency: [media('(prefers-reduced-transparency: reduce)'), 'solid', 'glass'],
  };
  Object.entries(queries).forEach(([key, [list, on, off]]) => {
    const show = () => {
      // the theme button overrides the system scheme on this site
      const forced = key === 'scheme' ? document.documentElement.getAttribute('data-theme') : null;
      set('data-pref', key, prefs[forced || (list.matches ? on : off)]);
    };
    show();
    if (list.addEventListener) list.addEventListener('change', show);
    if (key === 'scheme') new MutationObserver(show).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
  });
})();
