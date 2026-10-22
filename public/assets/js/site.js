// Progressive enhancements: metrics, the theme toggle, the contact-form image check,
// the capability pins and the project videos. Without this file the page still works: each pin links to
// its evidence list, the form offers a link that loads the image check, and a demo opens on YouTube.
(() => {
  'use strict';

  const root = document.documentElement;
  const query = (q) => (window.matchMedia ? window.matchMedia(q) : null);
  const prefersDark = query('(prefers-color-scheme: dark)');
  const reducedMotion = query('(prefers-reduced-motion: reduce)');
  const singleColumn = query('(max-width: 61.99rem)');

  // ---- Metrics: anonymous, cookie-free events to /e, relayed server-side (src/Metrics.php) ----
  const metricsOn = document.body.dataset.metrics === 'on';
  const send = (event, props = {}) => {
    if (!metricsOn) return;
    const body = JSON.stringify({ event, props: { $pathname: location.pathname, ...props } });
    try {
      if (navigator.sendBeacon && navigator.sendBeacon('/e', new Blob([body], { type: 'application/json' }))) return;
    } catch (error) {
      // fall through to fetch
    }
    fetch('/e', { method: 'POST', body, headers: { 'Content-Type': 'application/json' }, keepalive: true }).catch(() => {});
  };

  if (metricsOn) {
    let referrer = '';
    try {
      const from = new URL(document.referrer);
      if (from.host !== location.host) referrer = from.host;
    } catch (error) {
      // no referrer
    }
    const width = window.innerWidth;
    send('$pageview', {
      lang: root.lang,
      theme: root.getAttribute('data-theme') || 'system',
      viewport: width < 640 ? 'phone' : width < 1024 ? 'tablet' : 'desktop',
      $referring_domain: referrer,
      status: document.body.dataset.status || '200',
    });

    // Core Web Vitals, sent once when the page is hidden.
    const vitals = {};
    const observe = (type, callback, options = {}) => {
      try {
        new PerformanceObserver((list) => callback(list.getEntries())).observe({ type, buffered: true, ...options });
      } catch (error) {
        // entry type not supported by this browser
      }
    };
    observe('largest-contentful-paint', (entries) => { vitals.LCP = Math.round(entries[entries.length - 1].startTime); });
    observe('paint', (entries) => entries.forEach((e) => { if (e.name === 'first-contentful-paint') vitals.FCP = Math.round(e.startTime); }));
    observe('layout-shift', (entries) => entries.forEach((e) => { if (!e.hadRecentInput) vitals.CLS = (vitals.CLS || 0) + e.value; }));
    observe('event', (entries) => entries.forEach((e) => { if (e.interactionId) vitals.INP = Math.max(vitals.INP || 0, Math.round(e.duration)); }), { durationThreshold: 40 });
    let vitalsSent = false;
    const sendVitals = () => {
      if (vitalsSent || Object.keys(vitals).length === 0) return;
      vitalsSent = true;
      const props = {};
      Object.keys(vitals).forEach((name) => { props[`$web_vitals_${name}_value`] = name === 'CLS' ? Number(vitals.CLS.toFixed(4)) : vitals[name]; });
      send('$web_vitals', props);
    };
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') sendVitals(); });
    window.addEventListener('pagehide', sendVitals); // navigations that skip visibilitychange

    // JavaScript errors, at most five per page.
    let errorsSent = 0;
    const reportError = (type, message, source, line) => {
      if (errorsSent++ >= 5) return;
      send('$exception', {
        $exception_type: type,
        $exception_message: String(message || '').slice(0, 200),
        $exception_source: String(source || '').replace(location.origin, ''),
        $exception_lineno: Number(line) || 0,
      });
    };
    window.addEventListener('error', (e) => reportError('Error', e.message, e.filename, e.lineno));
    window.addEventListener('unhandledrejection', (e) => reportError('UnhandledRejection', e.reason && e.reason.message ? e.reason.message : e.reason));

    // Which external links people follow (host and path only).
    document.addEventListener('click', (event) => {
      const link = event.target.closest ? event.target.closest('a[href^="http"]') : null;
      if (!link || link.host === location.host) return;
      send('outbound_click', { host: link.host, path: link.pathname });
    });
  }

  // ---- Contact form: image check, loaded when the form comes into view ----
  const captcha = document.querySelector('[data-captcha]');
  if (captcha) {
    const image = captcha.querySelector('[data-captcha-image]');
    const token = captcha.querySelector('[data-captcha-token]');
    const fresh = captcha.querySelector('[data-captcha-new]');
    const show = captcha.querySelector('[data-captcha-show]');
    const answer = captcha.querySelector('input[name="captcha_answer"]');
    const load = () => {
      show.textContent = show.dataset.loading;
      return fetch('/contact/challenge', { headers: { Accept: 'application/json' } })
        .then((response) => (response.ok ? response.json() : Promise.reject(response.status)))
        .then((challenge) => {
          image.src = challenge.image;
          image.hidden = false;
          token.value = challenge.token;
          fresh.hidden = false;
          show.hidden = true;
        })
        .catch(() => { show.hidden = false; }); // the link still reloads the page with a check
    };
    fresh.hidden = token.value === '';
    fresh.addEventListener('click', () => {
      answer.value = '';
      load().then(() => answer.focus());
      send('captcha_refreshed');
    });
    if (token.value === '') {
      const form = captcha.closest('form');
      if ('IntersectionObserver' in window) {
        const watcher = new IntersectionObserver((entries) => {
          if (entries.some((entry) => entry.isIntersecting)) {
            watcher.disconnect();
            load();
          }
        }, { rootMargin: '300px' });
        watcher.observe(form);
      } else {
        form.addEventListener('focusin', load, { once: true });
      }
    }
  }

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
      send('theme_changed', { theme: next });
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
    send('pin_selected', { pin: pin.dataset.pin });
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

  // ---- Short screen recordings: play muted while on screen, never with reduced motion ----
  const clips = document.querySelectorAll('video[data-autoplay]');
  if (clips.length && 'IntersectionObserver' in window && !(reducedMotion && reducedMotion.matches)) {
    const watcher = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        const clip = entry.target;
        if (entry.isIntersecting) {
          clip.preload = 'auto';
          clip.play().catch(() => {});
        } else {
          clip.pause();
        }
      });
    }, { threshold: 0.5 });
    clips.forEach((clip) => watcher.observe(clip));
  }

  // ---- Demo videos: the YouTube player loads only when the visitor presses play ----
  document.querySelectorAll('[data-youtube]').forEach((frame) => {
    const play = frame.querySelector('.demo__play');
    if (!play) return;
    play.addEventListener('click', (event) => {
      event.preventDefault();
      const player = document.createElement('iframe');
      player.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(frame.dataset.youtube)}?autoplay=1&rel=0&playsinline=1`;
      player.title = frame.dataset.title;
      player.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
      player.referrerPolicy = 'strict-origin-when-cross-origin';
      player.allowFullscreen = true;
      frame.replaceChildren(player);
      player.focus();
    });
  });
})();
