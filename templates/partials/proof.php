<?php
/**
 * One claim with its source: a button opens a popover (Popover API: light dismiss, Esc and focus return come
 * from the browser) that CSS anchor positioning places next to the button and flips at the viewport edges.
 * No script is needed to open, place or close it. The evidence is the matching roles from Experience.
 *
 * @var array<string, mixed> $c
 * @var int $n
 * @var string $claim
 * @var array{roles: list<string>, case?: string} $evidence
 * @var callable $e
 */
$roles = [];
foreach ($c['experience']['items'] as $item) {
    $roles[$item['id']] = $item;
}
$id = 'proof-' . $n;
?>
<span id="<?= $e($id) ?>-claim"><?= $e($claim) ?></span>
<button type="button" class="proof__open" popovertarget="<?= $e($id) ?>" aria-describedby="<?= $e($id) ?>-claim"><?= $e($c['ui']['proof_open']) ?></button>
<div class="proof" id="<?= $e($id) ?>" popover aria-labelledby="<?= $e($id) ?>-title">
  <p class="proof__title" id="<?= $e($id) ?>-title"><?= $e($c['ui']['proof_title']) ?></p>
  <ul class="proof__list">
    <?php foreach ($evidence['roles'] as $roleId): ?>
    <?php $r = $roles[$roleId]; ?>
    <li><a href="#role-<?= $e($roleId) ?>"><span class="proof__period num"><?= $e($r['period']) ?></span> <?= $e($r['role']) ?>, <?= $e($r['context']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <p class="proof__more">
    <?php if (!empty($evidence['case'])): ?>
    <a href="<?= $e($c['work']['case_path'] . $evidence['case'] . '/') ?>"><?= $e($c['work']['case_link']) ?></a>
    <?php else: ?>
    <a href="#experience"><?= $e($c['ui']['proof_more']) ?></a>
    <?php endif; ?>
  </p>
</div>
