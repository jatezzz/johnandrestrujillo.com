<?php
/**
 * How John works with AI agents: autonomy set by risk, the planner/executor/critic design from Code on the Go,
 * and how this site itself is built. Sits between the hero and the work.
 *
 * @var array<string, mixed> $c
 * @var callable $e
 */
$a = $c['approach'];
?>
<section class="section wrap approach" id="approach" aria-labelledby="approach-title">
  <div class="section__head">
    <h2 class="section__title" id="approach-title"><?= $e($a['title']) ?></h2>
    <p class="section__meta"><?= $e($a['intro']) ?></p>
  </div>
  <ol class="approach__list">
    <?php foreach ($a['points'] as $i => $p): ?>
    <li class="approach__item">
      <span class="approach__n num" aria-hidden="true"><?= $e(sprintf('%02d', $i + 1)) ?></span>
      <h3 class="approach__title"><?= $e($p['title']) ?></h3>
      <p><?= $e($p['text']) ?></p>
    </li>
    <?php endforeach; ?>
  </ol>
  <p class="approach__more"><a href="<?= $e($c['work']['case_path'] . 'code-on-the-go/') ?>"><?= $e($a['link']) ?></a></p>
</section>
