<?php
/**
 * @var array<string, mixed> $c
 * @var \Portfolio\View $view
 * @var callable $e
 * @var callable $asset
 */
$w = $c['work'];
$mobile = $w['mobile'];
?>
<section class="section wrap" id="work" aria-labelledby="work-title">
  <div class="section__head">
    <h2 class="section__title" id="work-title"><?= $e($w['title']) ?></h2>
    <p class="section__meta"><?= $e($w['intro']) ?></p>
  </div>

  <article class="case case--compact" aria-labelledby="mobile-title">
    <p class="case__kicker num"><?= $e($mobile['period']) ?></p>
    <h3 class="case__title" id="mobile-title"><?= $e($mobile['title']) ?></h3>
    <p class="case__summary"><?= $e($mobile['text']) ?></p>
    <p><a href="#experience"><?= $e($mobile['link']) ?></a></p>
  </article>

  <?php foreach ($w['projects'] as $p): ?>
  <article class="project" id="project-<?= $e($p['id']) ?>" aria-labelledby="project-<?= $e($p['id']) ?>-title">
    <?= $view->partial('media', ['c' => $c, 'p' => $p, 'asset' => $asset]) ?>

    <div class="project__body">
      <p class="case__kicker num"><?= $e($p['kicker']) ?></p>
      <h3 class="project__title" id="project-<?= $e($p['id']) ?>-title"><?= $e($p['title']) ?></h3>
      <p class="project__text"><?= $e($p['text']) ?></p>
      <dl class="stack">
        <div class="stack__row"><dt><?= $e($w['front_label']) ?></dt><dd><?= $e($p['front']) ?></dd></div>
        <?php if (!empty($p['back'])): ?>
        <div class="stack__row"><dt><?= $e($w['back_label']) ?></dt><dd><?= $e($p['back']) ?></dd></div>
        <?php endif; ?>
      </dl>
      <?php if (!empty($p['note'])): ?>
      <p class="project__note"><?= $e($p['note']) ?></p>
      <?php endif; ?>
      <p class="project__case"><a class="button" href="<?= $e($w['case_path'] . $p['id'] . '/') ?>" data-cta="case_study" data-loc="work_card"><?= $e($w['case_link']) ?></a></p>
      <?php if (!empty($p['links'])): ?>
      <ul class="project__links" aria-label="<?= $e($w['links_label']) ?>">
        <?php foreach ($p['links'] as $link): ?>
        <li><a href="<?= $e($link['url']) ?>"><?= $e($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </article>
  <?php endforeach; ?>
</section>
