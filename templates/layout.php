<?php
/**
 * @var array<string, mixed> $c
 * @var string $lang
 * @var array<string, string> $urls
 * @var string $canonical
 * @var array<string, mixed> $site
 * @var callable $asset
 * @var callable $e
 * @var string $json_ld
 * @var string $og_image
 * @var bool $touch_icon
 * @var string $body
 */
$other = $c['ui']['other_lang'];
?><!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($c['meta']['title']) ?></title>
<meta name="description" content="<?= $e($c['meta']['description']) ?>">
<link rel="canonical" href="<?= $e($canonical) ?>">
<link rel="alternate" hreflang="en" href="<?= $e($urls['en']) ?>">
<link rel="alternate" hreflang="es" href="<?= $e($urls['es']) ?>">
<link rel="alternate" hreflang="x-default" href="<?= $e($urls['en']) ?>">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#F5F7F6" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0A2018" media="(prefers-color-scheme: dark)">
<meta property="og:type" content="profile">
<meta property="og:title" content="<?= $e($c['meta']['title']) ?>">
<meta property="og:description" content="<?= $e($c['meta']['description']) ?>">
<meta property="og:url" content="<?= $e($canonical) ?>">
<meta property="og:locale" content="<?= $e($c['locale']) ?>">
<?php if ($og_image !== ''): ?>
<meta property="og:image" content="<?= $e($og_image) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= $e($c['meta']['og_alt']) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<link rel="icon" href="<?= $e($asset('img/favicon.svg')) ?>" type="image/svg+xml">
<?php if ($touch_icon): ?>
<link rel="icon" href="<?= $e($asset('img/favicon-32.png')) ?>" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="<?= $e($asset('img/apple-touch-icon.png')) ?>">
<?php endif; ?>
<link rel="preload" href="/assets/fonts/atkinson-next-v7-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= $e($asset('css/site.css')) ?>">
<script src="<?= $e($asset('js/theme.js')) ?>"></script>
<script src="<?= $e($asset('js/site.js')) ?>" defer></script>
<script src="<?= $e($asset('js/motion.js')) ?>" defer></script>
<script type="application/ld+json"><?= $json_ld ?></script>
</head>
<body data-status="<?= (int) $status ?>"<?php if ($site['metrics']): ?> data-metrics="on"<?php endif; ?>>
<a class="skip" href="#main"><?= $e($c['ui']['skip']) ?></a>
<?php if ($site['staging']): ?>
<p class="stage-banner"><?= $e($c['ui']['staging']) ?><?php if ($site['revision'] !== ''): ?>: <span class="num"><?= $e($site['revision']) ?></span><?php endif; ?></p>
<?php endif; ?>
<header class="masthead">
  <div class="masthead__inner">
    <a class="brand" href="<?= $e($lang === 'es' ? '/es/' : '/') ?>">
      <svg class="brand__chip" viewBox="0 0 24 32" aria-hidden="true" focusable="false"><rect x="5" y="2" width="14" height="28" rx="2"/><path class="brand__notch" d="M9.5 2a2.5 2.5 0 0 0 5 0"/><g class="brand__pins"><rect x="1" y="6" width="4" height="2"/><rect x="1" y="12" width="4" height="2"/><rect x="1" y="18" width="4" height="2"/><rect x="1" y="24" width="4" height="2"/><rect x="19" y="6" width="4" height="2"/><rect x="19" y="12" width="4" height="2"/><rect x="19" y="18" width="4" height="2"/><rect x="19" y="24" width="4" height="2"/></g></svg>
      <span class="brand__name"><?= $e($c['person']['name']) ?></span>
    </a>
    <nav class="masthead__nav" aria-label="<?= $e($c['ui']['nav_label']) ?>">
      <ul>
        <?php foreach ($c['ui']['nav'] as $item): ?>
        <li><a href="#<?= $e($item['id']) ?>"><?= $e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="masthead__tools">
      <a class="lang-switch" data-magnetic href="<?= $e($other['url']) ?>" hreflang="<?= $e($other['code']) ?>" lang="<?= $e($other['code']) ?>"><?= $e($other['label']) ?></a>
      <button class="theme-toggle" type="button" data-theme-toggle data-magnetic aria-pressed="false" hidden>
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.5"/><path d="M12 3.5a8.5 8.5 0 0 1 0 17z"/></svg>
        <span class="sr-only"><?= $e($c['ui']['theme']) ?></span>
      </button>
    </div>
  </div>
</header>
<main id="main" tabindex="-1">
<?= $body ?>
</main>
<footer class="colophon">
  <div class="colophon__inner">
    <p>© <span class="num"><?= $e(substr($c['updated'], 0, 4)) ?></span> <?= $e($c['person']['name']) ?></p>
    <p><?= $e($c['footer']['colophon']) ?></p>
    <p><?= $e($c['footer']['updated']) ?> <span class="num"><?= $e($c['updated_label']) ?></span>.</p>
    <?php if ($site['source_url'] !== ''): ?>
    <p><a href="<?= $e($site['source_url']) ?>"><?= $e($c['footer']['source']) ?></a></p>
    <?php endif; ?>
  </div>
</footer>
</body>
</html>
