<?php
/**
 * The 1200x630 social preview card. Served only outside production (see App::card)
 * and captured to public/assets/img/og-<lang>.png with a headless browser.
 *
 * @var array<string, mixed> $c
 * @var string $lang
 * @var array{left: list<array<string, string>>, right: list<array<string, string>>} $sides
 * @var callable $asset
 * @var callable $e
 * @var callable $m
 */
?><!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
<meta charset="utf-8">
<title><?= $e($c['meta']['title']) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= $e($asset('css/site.css')) ?>">
<link rel="stylesheet" href="<?= $e($asset('css/og.css')) ?>">
</head>
<body class="og">
  <div class="og__text">
    <p class="og__name"><?= $view->partial('mark', []) ?><?= $e($c['person']['name']) ?></p>
    <p class="og__lines">
      <?php foreach ($c['hero']['title_lines'] as $line): ?>
      <span class="hero__line"><?= $m($line) ?></span>
      <?php endforeach; ?>
    </p>
    <p class="og__domain num">johnandrestrujillo.com</p>
  </div>
  <div class="pinout__body og__chip">
    <?php foreach (['left', 'right'] as $side): ?>
    <ul class="pins pins--<?= $side ?>">
      <?php foreach ($sides[$side] as $pin): ?>
      <li class="pin"><span class="pin__link"><span class="pin__label"><?= $e($pin['label']) ?></span><span class="pin__lead"></span></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($side === 'left'): ?>
    <div class="package">
      <span class="package__notch"></span>
      <span class="package__dot"></span>
      <span class="package__marks">
        <span class="package__mark package__mark--name"><?= $e($c['pinout']['marks'][0]) ?></span>
        <span class="package__mark"><?= $e($c['pinout']['marks'][1]) ?></span>
        <span class="package__mark package__mark--code"><?= $e($c['pinout']['marks'][2]) ?></span>
      </span>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>
</body>
</html>
