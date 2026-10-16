<?php
/**
 * Skill groups, then one evidence list per pin. The evidence lists are the no-JS
 * target of each pin and the source site.js copies into the panel beside the chip.
 *
 * @var array<string, mixed> $c
 * @var array<string, list<array{title: string, period: string, lines: list<string>}>> $evidence
 * @var callable $e
 */
$s = $c['skills'];
?>
<section class="section wrap" id="skills" aria-labelledby="skills-title">
  <div class="section__head">
    <h2 class="section__title" id="skills-title"><?= $e($s['title']) ?></h2>
    <p class="section__intro"><?= $e($s['intro']) ?></p>
  </div>
  <div class="skill-groups">
    <?php foreach ($s['groups'] as $group): ?>
    <section class="skill-group">
      <h3 class="skill-group__name label"><?= $e($group['name']) ?></h3>
      <ul class="tags">
        <?php foreach ($group['items'] as $skill): ?>
        <li><?= $e($skill) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endforeach; ?>
  </div>

  <h3 class="evidence-index__title"><?= $e($s['evidence_title']) ?></h3>
  <div class="evidence-index">
    <?php foreach ($c['pins'] as $pin): ?>
    <details class="evidence" id="pin-<?= $e($pin['id']) ?>">
      <summary><span class="evidence__pin label"><?= $e($pin['label']) ?></span> <?= $e($pin['name']) ?> <span class="evidence__count num"><?= count($evidence[$pin['id']]) ?></span></summary>
      <div class="evidence__body">
        <ul class="evidence__list">
          <?php foreach ($evidence[$pin['id']] as $item): ?>
          <li class="evidence__item">
            <p class="evidence__where"><span class="evidence__title"><?= $e($item['title']) ?></span> <span class="evidence__period num"><?= $e($item['period']) ?></span></p>
            <ul class="evidence__lines">
              <?php foreach ($item['lines'] as $line): ?>
              <li><?= $e($line) ?></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </details>
    <?php endforeach; ?>
  </div>
</section>
