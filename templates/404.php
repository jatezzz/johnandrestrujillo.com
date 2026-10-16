<?php
/**
 * @var array<string, mixed> $c
 * @var string $lang
 * @var callable $e
 */
?>
<section class="section wrap not-found" aria-labelledby="not-found-title">
  <p class="eyebrow num">404</p>
  <h1 class="section__title" id="not-found-title"><?= $e($c['ui']['not_found_title']) ?></h1>
  <p><?= $e($c['ui']['not_found_body']) ?></p>
  <p><a class="button button--primary" href="<?= $e($lang === 'es' ? '/es/' : '/') ?>"><?= $e($c['ui']['not_found_home']) ?></a></p>
</section>
