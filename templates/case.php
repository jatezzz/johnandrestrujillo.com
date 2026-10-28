<?php
/**
 * One project in depth: its screens, the stack, what I did there and how it is built.
 * The home page keeps a card for it and links here.
 *
 * @var array<string, mixed> $c
 * @var array<string, mixed> $p the project
 * @var array<string, mixed>|null $role the matching role in Experience
 * @var \Portfolio\View $view
 * @var callable $e
 * @var callable $asset
 * @var string $home
 */
$w = $c['work'];
?>
<article class="case-study wrap" aria-labelledby="case-title">
  <p class="case-study__back"><a href="<?= $e($home . '#project-' . $p['id']) ?>"><?= $e($w['case_back']) ?></a></p>
  <header class="case-study__head">
    <p class="case__kicker num"><?= $e($p['kicker']) ?></p>
    <h1 class="case-study__title" id="case-title"><?= $e($p['title']) ?></h1>
    <p class="case-study__lede"><?= $e($p['text']) ?></p>
  </header>

  <?= $view->partial('media', ['c' => $c, 'p' => $p, 'asset' => $asset]) ?>

  <dl class="stack">
    <div class="stack__row"><dt><?= $e($w['front_label']) ?></dt><dd><?= $e($p['front']) ?></dd></div>
    <?php if (!empty($p['back'])): ?>
    <div class="stack__row"><dt><?= $e($w['back_label']) ?></dt><dd><?= $e($p['back']) ?></dd></div>
    <?php endif; ?>
  </dl>
  <?php if (!empty($p['note'])): ?>
  <p class="project__note"><?= $e($p['note']) ?></p>
  <?php endif; ?>

  <?php if ($role !== null): ?>
  <section class="case-study__section" aria-labelledby="case-did">
    <h2 class="section__title" id="case-did"><?= $e($w['case_did']) ?></h2>
    <p class="role__meta"><?= $e($role['role']) ?>, <?= $e($role['context']) ?> <span class="num"><?= $e($role['period']) ?></span></p>
    <ul class="highlights">
      <?php foreach ($role['highlights'] ?? [] as $item): ?>
      <li><?= $e($item['text']) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <?php if (!empty($p['details'])): ?>
  <section class="case-study__section" aria-labelledby="case-built">
    <h2 class="section__title" id="case-built"><?= $e($w['details_label']) ?></h2>
    <?= $view->partial('build', ['d' => $p['details']]) ?>
  </section>
  <?php endif; ?>

  <?php if (!empty($p['links'])): ?>
  <ul class="project__links" aria-label="<?= $e($w['links_label']) ?>">
    <?php foreach ($p['links'] as $link): ?>
    <li><a href="<?= $e($link['url']) ?>"><?= $e($link['label']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <p class="case-study__actions">
    <a class="button button--primary" href="<?= $e($home . '#contact') ?>" data-magnetic data-cta="write" data-loc="case_page"><?= $e($c['hero']['cta_contact']) ?></a>
    <a href="<?= $e($home . '#work') ?>"><?= $e($w['case_back']) ?></a>
  </p>
</article>
