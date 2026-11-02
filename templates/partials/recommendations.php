<?php
/**
 * @var array<string, mixed> $c
 * @var callable $e
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
        <span class="rec__avatar" aria-hidden="true"><?= $e($item['initials']) ?></span>
        <blockquote class="rec__quote"><p><?= $e($item['quote']) ?></p></blockquote>
        <figcaption class="rec__by">
          <span class="rec__name"><?= $e($item['name']) ?></span>
          <span class="rec__role"><?= $e($item['role']) ?></span>
          <a class="rec__link" href="<?= $e($item['url']) ?>" rel="noopener nofollow" target="_blank"><?= $e($r['via_label']) ?></a>
        </figcaption>
      </figure>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
