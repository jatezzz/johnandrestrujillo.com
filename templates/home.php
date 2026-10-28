<?php
/**
 * @var array<string, mixed> $c
 * @var \Portfolio\View $view
 * @var callable $e
 * @var callable $m
 * @var array<string, mixed> $site
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
      <a class="button button--primary" href="#contact" data-magnetic data-cta="write" data-loc="hero"><?= $e($hero['cta_contact']) ?></a>
      <?php if ($site['linkedin_url'] !== ''): ?>
      <a class="button" href="<?= $e($site['linkedin_url']) ?>" rel="me" data-magnetic data-cta="linkedin" data-loc="hero">LinkedIn</a>
      <?php endif; ?>
      <a class="button" href="<?= $e($site['github_url']) ?>" rel="me" data-magnetic data-cta="github" data-loc="hero">GitHub</a>
      <a class="button" href="<?= $e($asset('cv/john-andres-trujillo-cv.pdf')) ?>" type="application/pdf" data-magnetic data-cta="cv" data-loc="hero"><?= $e($hero['cta_cv']) ?></a>
    </p>
    <table class="spec-sheet">
      <caption><?= $e($hero['facts']['label']) ?></caption>
      <tbody>
        <?php foreach ($hero['facts']['rows'] as [$name, $value]): ?>
        <tr><th scope="row"><?= $e($name) ?></th><td><?= $e($value) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= $view->partial('pinout', $vars) ?>
</section>
<?= $view->partial('work', $vars) ?>
<?= $view->partial('experience', $vars) ?>
<?= $view->partial('skills', $vars) ?>
<?= $view->partial('code', $vars) ?>
<?= $view->partial('education', $vars) ?>
<?= $view->partial('contact', $vars) ?>
<div class="dock" data-dock hidden>
  <a class="button button--primary dock__cta" href="#contact" data-cta="write" data-loc="bottom_bar"><?= $e($hero['cta_contact']) ?></a>
  <button class="button dock__share" type="button" data-share data-copied="<?= $e($c['ui']['share_copied']) ?>" hidden>
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3v12M7.5 7.5 12 3l4.5 4.5M5 12v7.5h14V12"/></svg>
    <span data-share-label><?= $e($c['ui']['share']) ?></span>
  </button>
</div>
