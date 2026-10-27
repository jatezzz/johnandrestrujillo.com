<?php
/**
 * @var array<string, mixed> $c
 * @var \Portfolio\View $view
 * @var callable $e
 * @var callable $m
 */
$vars = get_defined_vars();
$hero = $c['hero'];
?>
<section class="hero wrap" aria-labelledby="hero-title">
  <div class="hero__text">
    <figure class="hero__person">
      <img src="<?= $e($asset('img/john.webp')) ?>" width="120" height="150" alt="<?= $e($c['person']['name']) ?>">
      <figcaption>
        <span class="hero__name"><?= $e($c['person']['name']) ?></span>
        <span class="hero__role"><?= $e($c['person']['role']) ?>, <?= $e($c['person']['location']) ?></span>
      </figcaption>
    </figure>
    <h1 class="hero__title" id="hero-title">
      <?php foreach ($hero['title_lines'] as $line): ?>
      <span class="hero__line"><?= $m($line) ?></span>
      <?php endforeach; ?>
    </h1>
    <p class="hero__lede"><?= $e($hero['lede']) ?></p>
    <ul class="hero__proof" aria-label="<?= $e($hero['proof']['label']) ?>">
      <?php foreach ($hero['proof']['items'] as $item): ?>
      <li><?= $e($item) ?></li>
      <?php endforeach; ?>
    </ul>
    <p class="hero__actions">
      <a class="button button--primary" href="#contact"><?= $e($hero['cta_contact']) ?></a>
    </p>
  </div>
  <?= $view->partial('pinout', $vars) ?>
</section>
<?= $view->partial('work', $vars) ?>
<?= $view->partial('experience', $vars) ?>
<?= $view->partial('skills', $vars) ?>
<?= $view->partial('code', $vars) ?>
<?= $view->partial('education', $vars) ?>
<?= $view->partial('contact', $vars) ?>
