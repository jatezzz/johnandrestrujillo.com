<?php
/**
 * The signature: a DIP package marked with the name, one pin per capability.
 * Without JavaScript each pin links to its evidence list in the Skills section;
 * site.js turns the pins into a selector that shows that list beside the chip.
 *
 * @var array<string, mixed> $c
 * @var array{left: list<array<string, string>>, right: list<array<string, string>>} $sides
 * @var array<string, list<array<string, mixed>>> $evidence
 * @var callable $e
 */
$p = $c['pinout'];
?>
<div class="hero__chip">
  <figure class="pinout">
    <figcaption class="pinout__caption"><span class="label"><?= $e($p['title']) ?></span> <?= $e($p['hint']) ?></figcaption>
    <div class="pinout__body">
      <?php foreach (['left', 'right'] as $side): ?>
      <ul class="pins pins--<?= $side ?>">
        <?php foreach ($sides[$side] as $row => $pin): ?>
        <li class="pin">
          <a class="pin__link" href="#pin-<?= $e($pin['id']) ?>" data-pin="<?= $e($pin['id']) ?>" data-side="<?= $side ?>" data-row="<?= $row ?>" data-name="<?= $e($pin['name']) ?>" data-count="<?= count($evidence[$pin['id']]) ?>" aria-label="<?= $e($pin['name']) ?>">
            <span class="pin__label"><?= $e($pin['label']) ?></span>
            <span class="pin__lead" aria-hidden="true"></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($side === 'left'): ?>
      <div class="package" aria-hidden="true">
        <span class="package__notch"></span>
        <span class="package__dot"></span>
        <span class="package__marks">
          <span class="package__mark package__mark--name"><?= $e($p['marks'][0]) ?></span>
          <span class="package__mark"><?= $e($p['marks'][1]) ?></span>
          <span class="package__mark package__mark--code"><?= $e($p['marks'][2]) ?></span>
        </span>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </figure>
  <div class="evidence-panel" id="evidence-panel" hidden
       data-status-one="<?= $e($p['status_one']) ?>" data-status-many="<?= $e($p['status_many']) ?>">
    <p class="evidence-panel__status" data-status aria-live="polite"></p>
    <div class="evidence-panel__content" data-content>
      <p class="evidence-panel__empty"><?= $e($p['empty']) ?></p>
    </div>
  </div>
</div>
