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
  // A page Chrome prerendered (speculation rules) may never be shown: hold its events until it is.
  const shown = document.prerendering
    ? new Promise((resolve) => document.addEventListener('prerenderingchange', resolve, { once: true }))
    : null;
  // The owner's own visits: open any page once with ?internal=1 (?internal=0 undoes it) and this browser
  // tags its events, so they can be filtered out of the numbers.
  let internal = false;
  try {
    const flag = new URLSearchParams(location.search).get('internal');
    if (flag === '1') localStorage.setItem('internal', '1');
    if (flag === '0') localStorage.removeItem('internal');
    internal = localStorage.getItem('internal') === '1';
  } catch (error) {
    // storage blocked: count the visit as a normal one
  }
  const send = (event, props = {}) => {
    if (!metricsOn) return;
    if (document.prerendering) {
      shown.then(() => send(event, props));
      return;
    }
    const tag = internal ? { internal: 1 } : {};
    const body = JSON.stringify({ event, props: { $pathname: location.pathname, ...props, ...tag } });
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
    const viewport = width < 640 ? 'phone' : width < 1024 ? 'tablet' : 'desktop';
    // how this page arrived: prerendered by the speculation rules (shown on activation), or a plain navigation
    const entry = performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
    const nav = !entry ? '' : (document.prerendering || entry.activationStart > 0) ? 'prerender' : entry.type;
    send('$pageview', {
      nav,
      lang: root.lang,
      theme: root.getAttribute('data-theme') || 'system',
      viewport,
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

    // How far down people read: the deepest point, in screens (comparable when the page length changes).
    let deepest = 0;
    window.addEventListener('scroll', () => { deepest = Math.max(deepest, window.scrollY + window.innerHeight); }, { passive: true });
    let depthSent = false;
    const sendDepth = () => {
      if (depthSent) return;
      depthSent = true;
      const seen = Math.max(deepest, window.innerHeight);
      send('scroll_depth', {
        max_pct: Math.min(100, Math.round((seen / document.documentElement.scrollHeight) * 100)),
        max_screens: Number((seen / window.innerHeight).toFixed(1)),
        viewport,
      });
    };
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') sendDepth(); });
    window.addEventListener('pagehide', sendDepth);

    // Which sections were on screen: once each, when the section crosses the line 40% down the viewport.
    const sections = document.querySelectorAll('main > section[id], main > .hero');
    if (sections.length && 'IntersectionObserver' in window) {
      const reached = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          reached.unobserve(entry.target);
          send('section_viewed', { section: entry.target.id || 'hero', viewport });
        });
      }, { rootMargin: '-40% 0px -59% 0px' });
      sections.forEach((section) => reached.observe(section));
    }

    // Calls to action, by what they are and where they sit (hero, bottom bar, project card, case page).
    document.addEventListener('click', (event) => {
      const cta = event.target.closest ? event.target.closest('[data-cta]') : null;
      if (cta) send('cta_click', { cta: cta.dataset.cta, loc: cta.dataset.loc || '', viewport });
    });

    // The contact form, first touch (the server counts the messages sent).
    const form = document.querySelector('form[action="/contact"]');
    if (form) form.addEventListener('focusin', () => send('contact_started', { viewport }), { once: true });

    // Which folded details people open.
    document.addEventListener('toggle', (event) => {
      const details = event.target;
      if (!(details instanceof HTMLDetailsElement) || details.dataset.printing) return;
      const owner = details.id ? details : details.closest('[id]');
      send('disclosure_toggled', { id: owner ? owner.id : 'page', state: details.open ? 'open' : 'closed' });
    }, true);

    // Which claim sources people open.
    document.addEventListener('toggle', (event) => {
      const pop = event.target;
      if (pop instanceof HTMLElement && pop.classList.contains('proof') && event.newState === 'open') send('proof_opened', { id: pop.id });
    }, true);

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

  // ---- Claim sources: a link inside the popover jumps in the page, so put the popover away ----
  document.addEventListener('click', (event) => {
    const link = event.target.closest ? event.target.closest('.proof a[href^="#"]') : null;
    const pop = link ? link.closest('[popover]') : null;
    if (pop && pop.hidePopover) pop.hidePopover();
  });

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
    const apply = (next) => {
      root.setAttribute('data-theme', next);
      try {
        localStorage.setItem('theme', next);
      } catch (error) {
        // Not saved; the choice lasts until the page is closed.
      }
      sync();
    };
    toggle.addEventListener('click', () => {
      const next = currentTheme() === 'dark' ? 'light' : 'dark';
      send('theme_changed', { theme: next });
      if (!document.startViewTransition || (reducedMotion && reducedMotion.matches)) {
        apply(next);
        return;
      }
      // The new theme spreads from the button as a circle (View Transitions API).
      const box = toggle.getBoundingClientRect();
      const x = box.left + box.width / 2;
      const y = box.top + box.height / 2;
      const radius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
      root.classList.add('theme-switching');
      const transition = document.startViewTransition(() => apply(next));
      transition.ready.then(() => {
        root.animate(
          { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`] },
          { duration: 560, easing: 'cubic-bezier(0.2, 0.7, 0.2, 1)', pseudoElement: '::view-transition-new(root)' },
        );
      }).catch(() => {});
      transition.finished.finally(() => root.classList.remove('theme-switching'));
    });
    if (prefersDark && prefersDark.addEventListener) prefersDark.addEventListener('change', sync);
    sync();
  }

  // ---- Pins: show where each capability was used ----
  const panel = document.getElementById('evidence-panel');
  const pins = Array.from(document.querySelectorAll('.pin__link'));
  if (panel && pins.length) {
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
  }

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

  // ---- Phones: a bar in thumb reach with "Write to me" and Share, between the hero button and the form ----
  const dock = document.querySelector('[data-dock]');
  const heroAction = document.querySelector('.hero__actions');
  const contact = document.getElementById('contact');
  if (dock && heroAction && contact && 'IntersectionObserver' in window) {
    let heroPassed = false;
    let atContact = false;
    const update = () => {
      const show = heroPassed && !atContact;
      dock.classList.toggle('is-shown', show);
      dock.inert = !show;
    };
    dock.hidden = false;
    update();
    new IntersectionObserver(([entry]) => {
      heroPassed = !entry.isIntersecting && entry.boundingClientRect.top < 0;
      update();
    }).observe(heroAction);
    new IntersectionObserver(([entry]) => {
      atContact = entry.isIntersecting;
      update();
    }, { rootMargin: '0px 0px -25% 0px' }).observe(contact);
  }

  // Share: the phone's own share sheet, or copy the link where there is none.
  const share = document.querySelector('[data-share]');
  if (share && (navigator.share || (navigator.clipboard && window.isSecureContext))) {
    const label = share.querySelector('[data-share-label]');
    const canonical = document.querySelector('link[rel="canonical"]');
    const url = canonical ? canonical.href : location.href;
    share.hidden = false;
    share.addEventListener('click', () => {
      if (navigator.share) {
        navigator.share({ title: document.title, url })
          .then(() => send('shared', { method: 'sheet' }))
          .catch(() => {}); // closed without sharing
        return;
      }
      navigator.clipboard.writeText(url).then(() => {
        const text = label.textContent;
        label.textContent = share.dataset.copied;
        share.setAttribute('aria-live', 'polite');
        setTimeout(() => { label.textContent = text; share.removeAttribute('aria-live'); }, 2000);
        send('shared', { method: 'copy' });
      }).catch(() => {});
    });
  }

  // ---- Project screens: a swipeable row on phones, with a dot per screen ----
  document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const shots = Array.from(gallery.children);
    const dots = document.createElement('div');
    dots.className = 'gallery__dots';
    dots.setAttribute('aria-hidden', 'true');
    shots.forEach(() => dots.append(document.createElement('span')));
    gallery.after(dots);

    const sync = () => {
      const scrollable = gallery.scrollWidth > gallery.clientWidth + 1;
      dots.hidden = !scrollable;
      if (scrollable) {
        // a scrollable region must be reachable and named for keyboard and screen reader users
        gallery.setAttribute('role', 'region');
        gallery.setAttribute('aria-label', gallery.dataset.gallery);
        gallery.tabIndex = 0;
      } else {
        gallery.removeAttribute('role');
        gallery.removeAttribute('aria-label');
        gallery.removeAttribute('tabindex');
      }
    };
    sync();
    window.addEventListener('resize', sync, { passive: true });

    if ('IntersectionObserver' in window) {
      const watcher = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          dots.children[shots.indexOf(entry.target)].classList.toggle('is-on', entry.isIntersecting);
        });
      }, { root: gallery, threshold: 0.6 });
      shots.forEach((shot) => watcher.observe(shot));
    }
  });

  // ---- Folded details: open the ones a link points into, and print everything ----
  const unfold = () => {
    const target = location.hash.length > 1 ? document.getElementById(decodeURIComponent(location.hash.slice(1))) : null;
    for (let node = target; node; node = node.parentElement) {
      if (node instanceof HTMLDetailsElement) node.open = true;
    }
  };
  unfold();
  window.addEventListener('hashchange', unfold);
  const folded = [];
  window.addEventListener('beforeprint', () => {
    document.querySelectorAll('details:not([open])').forEach((details) => {
      details.dataset.printing = 'true';
      details.open = true;
      folded.push(details);
    });
  });
  window.addEventListener('afterprint', () => {
    const restored = folded.splice(0);
    restored.forEach((details) => { details.open = false; });
    // the toggle events are queued; drop the marker only after they have run
    setTimeout(() => restored.forEach((details) => { delete details.dataset.printing; }), 0);
  });
})();
