<?php
/**
 * @var array<string, mixed> $c
 * @var callable $e
 * @var callable $asset
 */
$r = $c['recommendations'];
?>
<section class="section wrap" id="recommendations" aria-labelledby="recommendations-title">
  <div class="section__head">
    <h2 class="section__title" id="recommendations-title"><?= $e($r['title']) ?></h2>
    <p class="section__meta"><?= $e($r['subtitle']) ?></p>
  </div>
  <ul class="recs">
    <?php foreach ($r['items'] as $item): ?>
    <li class="rec">
      <figure class="rec__card">
        <a class="rec__avatar" href="<?= $e($item['url']) ?>" aria-label="LinkedIn: <?= $e($item['name']) ?>" rel="noopener nofollow" target="_blank">
          <span aria-hidden="true"><?= $e($item['initials']) ?></span>
          <?php if (!empty($item['photo'])): ?>
          <img src="<?= $e($asset($item['photo'])) ?>" width="56" height="56" alt="" loading="lazy" decoding="async">
          <?php endif; ?>
        </a>
        <blockquote class="rec__quote"><p><?= $e($item['quote']) ?></p></blockquote>
        <figcaption class="rec__by">
          <span class="rec__name"><?= $e($item['name']) ?></span>
          <span class="rec__role"><?= $e($item['role']) ?></span>
          <a class="rec__link" href="<?= $e($item['source_url'] ?? $item['url']) ?>" rel="noopener nofollow" target="_blank"><?= $e(($item['source_type'] ?? '') === 'comment' ? $r['comment_label'] : $r['via_label']) ?></a>
        </figcaption>
      </figure>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
