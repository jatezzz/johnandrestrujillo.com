// Page-to-page morph between a work card and its case study (cross-document View Transitions).
// Loaded in <head> without defer: pagereveal fires before the first frame, so its listener must exist by then.
// The old page names the card's lead screen and title as it leaves (pageswap); the new page names the matching
// pair as it appears (pagereveal); the browser morphs one into the other. Names are set from script because the
// CSP forbids inline styles, and only for the one project involved, since a name must be unique on a page.
// Browsers without cross-document transitions never fire these events with a transition: plain navigation.
(() => {
  'use strict';

  const CASE = /^\/(?:work|es\/trabajo)\/([a-z0-9-]+)\/$/;
  const HOME = /^\/(?:es\/)?$/;
  const caseId = (url) => {
    const match = CASE.exec(new URL(url, location.href).pathname);
    return match ? match[1] : null;
  };
  const isHome = (url) => HOME.test(new URL(url, location.href).pathname);

  // Arriving at a #fragment from another page (the case study's "Back" link): jump straight to it. The page's
  // smooth scrolling is for in-page links; here it would slide down from the top while the morph plays.
  // The browser can scroll to the fragment again after load, so smooth scrolling comes back only when the
  // visitor scrolls or presses a key.
  if (location.hash) {
    const root = document.documentElement;
    root.style.scrollBehavior = 'auto';
    const restore = () => { root.style.scrollBehavior = ''; };
    ['wheel', 'touchstart', 'keydown', 'pointerdown'].forEach((type) => window.addEventListener(type, restore, { once: true, passive: true }));
  }

  const parts = (id) => {
    const scope = id ? document.getElementById('project-' + id) : document.querySelector('.case-study');
    if (!scope) return [];
    return [
      [scope.querySelector('.shot--lead .shot__frame'), 'case-media'],
      [id ? document.getElementById('project-' + id + '-title') : document.getElementById('case-title'), 'case-title'],
    ].filter(([el]) => el);
  };
  const name = (pairs, transition) => {
    pairs.forEach(([el, value]) => { el.style.viewTransitionName = value; });
    transition.finished.finally(() => pairs.forEach(([el]) => { el.style.viewTransitionName = ''; }));
  };

  // Leaving: home -> case names the card; case -> home names the case header.
  window.addEventListener('pageswap', (event) => {
    if (!event.viewTransition || !event.activation || !event.activation.entry) return;
    const to = event.activation.entry.url;
    const here = location.href;
    if (isHome(here) && caseId(to)) name(parts(caseId(to)), event.viewTransition);
    else if (caseId(here) && isHome(to)) name(parts(null), event.viewTransition);
  });

  // Arriving: the mirror image, keyed on where the visitor came from.
  window.addEventListener('pagereveal', (event) => {
    if (!event.viewTransition || !window.navigation || !navigation.activation || !navigation.activation.from) return;
    const from = navigation.activation.from.url;
    const here = location.href;
    if (caseId(here) && isHome(from)) name(parts(null), event.viewTransition);
    else if (isHome(here) && caseId(from)) {
      // land on the card before the new state is captured, so the screen flies back to where it lives
      const target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
      if (target) target.scrollIntoView({ block: 'start', behavior: 'instant' });
      name(parts(caseId(from)), event.viewTransition);
    }
  });
})();
