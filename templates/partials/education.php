<?php
/**
 * @var array<string, mixed> $c
 * @var callable $e
 */
$ed = $c['education'];
?>
<section class="section wrap" id="education" aria-labelledby="education-title">
  <h2 class="section__title" id="education-title"><?= $e($ed['title']) ?></h2>
  <div class="education">
    <ul class="degrees">
      <?php foreach ($ed['degrees'] as $degree): ?>
      <li class="degree">
        <p class="degree__period num"><?= $e($degree['period']) ?></p>
        <h3 class="degree__title"><?= $e($degree['title']) ?></h3>
        <p class="degree__school"><?= $e($degree['school']) ?></p>
      </li>
      <?php endforeach; ?>
    </ul>
    <div class="education__side">
      <h3 class="side-heading"><?= $e($ed['certs_title']) ?></h3>
      <ul class="plain-list">
        <?php foreach ($ed['certs'] as $cert): ?>
        <li><?= $e($cert['name']) ?> <span class="muted">(<?= $e($cert['issuer']) ?>)</span></li>
        <?php endforeach; ?>
      </ul>
      <h3 class="side-heading"><?= $e($ed['languages_title']) ?></h3>
      <ul class="plain-list">
        <?php foreach ($ed['languages'] as $language): ?>
        <li><?= $e($language['name']) ?>: <span class="muted"><?= $e($language['level']) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
