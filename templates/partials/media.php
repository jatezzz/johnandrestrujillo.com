<?php
/**
 * A project's screens: images, short recordings and the click-to-load demo. On phones a
 * project with several screens becomes a swipeable row (site.js adds the dots).
 *
 * @var array<string, mixed> $c
 * @var array<string, mixed> $p
 * @var callable $e
 * @var callable $asset
 * @var bool $eager on a case study the lead screen is the first thing seen (and the page-transition target)
 */
$eager = !empty($eager);
?>
<div class="project__media<?= count($p['media']) > 1 ? ' project__media--gallery' : '' ?>"<?php if (count($p['media']) > 1): ?> data-gallery="<?= $e(str_replace('{title}', $p['title'], $c['ui']['gallery'])) ?>"<?php endif; ?>>
  <?php foreach ($p['media'] as $i => $m): ?>
  <figure class="shot shot--<?= $e($m['frame']) ?><?= $i === 0 ? ' shot--lead' : '' ?><?= $m['height'] > $m['width'] ? ' shot--portrait' : '' ?>">
    <?php if ($m['type'] === 'youtube'): ?>
    <div class="shot__frame demo__frame" data-youtube="<?= $e($m['youtube_id']) ?>" data-title="<?= $e($m['caption']) ?>">
      <a class="demo__play" href="https://www.youtube.com/shorts/<?= $e($m['youtube_id']) ?>">
        <img src="<?= $e($asset($m['poster'])) ?>" width="<?= (int) $m['width'] ?>" height="<?= (int) $m['height'] ?>" loading="lazy" alt="<?= $e($m['alt']) ?>">
        <span class="demo__button"><?= $e($m['play']) ?></span>
      </a>
    </div>
    <figcaption><?= $e($m['caption']) ?> <span class="shot__note"><?= $e($m['note']) ?></span></figcaption>
    <?php elseif ($m['type'] === 'video'): ?>
    <div class="shot__frame">
      <video src="<?= $e($asset($m['src'])) ?>" poster="<?= $e($asset($m['poster'])) ?>" width="<?= (int) $m['width'] ?>" height="<?= (int) $m['height'] ?>" muted loop playsinline controls preload="none" aria-label="<?= $e($m['alt']) ?>" data-autoplay></video>
    </div>
    <figcaption><?= $e($m['caption']) ?></figcaption>
    <?php else: ?>
    <div class="shot__frame">
      <img src="<?= $e($asset($m['src'])) ?>" width="<?= (int) $m['width'] ?>" height="<?= (int) $m['height'] ?>"<?= $eager && $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?> alt="<?= $e($m['alt']) ?>">
    </div>
    <figcaption><?= $e($m['caption']) ?></figcaption>
    <?php endif; ?>
  </figure>
  <?php endforeach; ?>
</div>
