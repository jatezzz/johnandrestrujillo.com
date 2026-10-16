<?php
/**
 * @var array<string, mixed> $c
 * @var \Portfolio\View $view
 * @var callable $e
 */
$x = $c['experience'];
$showAll = $c['ui']['show_all'];
?>
<section class="section wrap" id="experience" aria-labelledby="experience-title">
  <div class="section__head">
    <h2 class="section__title" id="experience-title"><?= $e($x['title']) ?></h2>
    <p class="section__meta num"><?= $e($x['range']) ?></p>
  </div>
  <ol class="roles">
    <?php foreach ($x['items'] as $item): ?>
    <li class="role" id="role-<?= $e($item['id']) ?>">
      <p class="role__period num"><?= $e($item['period']) ?></p>
      <div class="role__body">
        <h3 class="role__title"><?= $e($item['title']) ?></h3>
        <p class="role__meta"><?= $e($item['role']) ?>, <?= $e($item['context']) ?></p>
        <?php if (!empty($item['highlights'])): ?>
        <?= $view->partial('highlights', ['items' => $item['highlights'], 'lead' => (int) ($item['lead'] ?? 0), 'showAll' => $showAll]) ?>
        <?php endif; ?>
        <?php if (!empty($item['engagements'])): ?>
        <ol class="engagements">
          <?php foreach ($item['engagements'] as $engagement): ?>
          <li class="engagement">
            <h4 class="engagement__title"><?= $e($engagement['title']) ?> <span class="engagement__period num"><?= $e($engagement['period']) ?></span></h4>
            <?= $view->partial('highlights', ['items' => $engagement['highlights'], 'lead' => 0, 'showAll' => $showAll]) ?>
          </li>
          <?php endforeach; ?>
        </ol>
        <?php endif; ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
