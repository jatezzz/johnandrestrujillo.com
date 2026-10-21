# johnandrestrujillo.com

Source of my personal site: who I am as an engineer, what I've built, and how to reach me.
English at `/`, Spanish at `/es/`.

The page is laid out like a component datasheet. The chip at the top has one pin per
capability; selecting a pin lists the projects and years where I used it, so every claim
on the page points at its evidence.

## How it's made

I write this site with Claude Code, an AI coding assistant, under my direction. Every change runs
the tests and the publication guard below, and nothing goes live until I approve it. Commits record
me as the author; this note is the disclosure.

## Stack

- **PHP 8.2, no framework, one vendored library** (`vendor/gregwar-captcha`, MIT, for the
  contact-form image check). The host is shared PHP hosting: no Node, no Composer at runtime,
  a 2 MB default memory limit (the app raises it at bootstrap).
- **Content as data.** All copy lives in `content/en.json` and `content/es.json`, which share
  one shape; the templates only arrange it. Tests fail if the two languages drift apart.
- **Fast and strict by default.** Server-rendered HTML, one stylesheet, two small scripts,
  self-hosted fonts, no cookies, no third-party requests. A strict Content Security Policy
  (`script-src 'self'`, `connect-src 'self'`, no inline scripts or styles).
- **Observable without tracking.** The browser sends a few anonymous events (page views, Core
  Web Vitals, JavaScript errors, which pin or link was used) to `/e` on this site; PHP checks
  them against an allow-list and relays them to PostHog after the response is sent, together
  with server-side events (contact-form outcomes, server errors). No cookies or storage, no
  person profiles, no IP address or GeoIP, bots dropped, and a visitor id that changes daily.
  See `src/Metrics.php`.
- **Accessible.** Skip link, landmarks, visible focus, keyboard-navigable pins (arrow keys),
  reduced-motion support, AA contrast in both themes. Typeface: Atkinson Hyperlegible
  (Braille Institute), designed for low-vision readers.

```
public/      web root: front controller, .htaccess, assets
src/         App (routing, headers), Content, View, Contact, Captcha, Metrics, RateLimit, Config
vendor/      gregwar/captcha v1.3.0 (subset)
templates/   layout, pages and partials
content/     site copy, one JSON file per language
tests/       zero-dependency test runner
tools/       publication guard, staging script
docker/      staging container that mirrors the host's PHP limits
deploy/      host-only .htaccess lines
```

## Run it

```sh
php -S localhost:8000 -t public public/index.php   # http://localhost:8000
php tests/run.php                                   # content, routes, headers, HTML, contact form, captcha, metrics
```

## Contact form

No cookies and no third-party captcha service. Spam is handled with a hidden honeypot field, a
signed render timestamp (too fast or too old is refused), a self-hosted image check
(gregwar/captcha; the token is HMAC-signed, carries only a keyed hash of the answer and works
once) and an hourly limit per connection, stored
outside the web root. Copy `config/config.example.php` to `config/local.php` to configure the
inbox and the form secret; without them the form says it is offline instead of pretending
to send.

## Publication guard

`tools/guard.php` runs in CI and before every deploy. It fails when:

- any tracked file, commit message, branch name or rendered page contains an entry from a
  private term list (passed as a CI secret, never committed, never printed);
- a commit was authored from an address other than the allowed one, or carries a co-author trailer;
- a page links to an external host outside a short allow-list;
- with `--fetch`, a linked page contains an entry from the list (one hop).

## Staging and deploy

- `tools/stage.sh` syncs the tree to a staging machine and runs `docker/compose.staging.yml`:
  `php:8.2-apache` with the host's limits (2 MB memory limit, 10 MB POST), mail written to a
  log, `noindex` on.
- Production is an rsync of `public/` to the host's web root (with `deploy/htaccess.head`
  prepended to `.htaccess`) and of the rest to a `site/` folder beside it. The front
  controller finds the app in either layout.
- `tools/deploy.sh` does that publish from the last commit; it is a dry run unless `DRY_RUN=0`.
  `deploy/Jenkinsfile` runs the lint, the tests and the guard first, and publishes only from
  `main` when started by hand with `DRY_RUN` off.
- Files a publish replaces or removes are kept in `deploy-backups/<stamp>/` on the host. To undo
  a publish, copy that folder's `site/` and `public_html/` back over the live ones.

## License

Code: MIT (see `LICENSE`). Text, the chip design and other content: © John Andrés Trujillo,
all rights reserved. Fonts: SIL Open Font License 1.1 (see `public/assets/fonts/`).
