<?php
/**
 * A list of highlights; past $lead items the rest folds into "Show all n".
 *
 * @var list<array{text: string, tags: list<string>}> $items
 * @var int $lead
 * @var string $showAll
 * @var callable $e
 */
$shown = $lead > 0 && count($items) > $lead + 1 ? array_slice($items, 0, $lead) : $items;
$folded = array_slice($items, count($shown));
?>
<ul class="highlights">
  <?php foreach ($shown as $item): ?>
  <li><?= $e($item['text']) ?></li>
  <?php endforeach; ?>
</ul>
<?php if ($folded !== []): ?>
<details class="more">
  <summary><?= $e(str_replace('{n}', (string) count($items), $showAll)) ?></summary>
  <ul class="highlights">
    <?php foreach ($folded as $item): ?>
    <li><?= $e($item['text']) ?></li>
    <?php endforeach; ?>
  </ul>
</details>
<?php endif; ?>
