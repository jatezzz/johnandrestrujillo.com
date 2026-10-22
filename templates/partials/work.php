<?php
/**
 * @var array<string, mixed> $c
 * @var callable $e
 * @var callable $asset
 */
$w = $c['work'];
$mobile = $w['mobile'];
?>
<section class="section wrap" id="work" aria-labelledby="work-title">
  <div class="section__head">
    <h2 class="section__title" id="work-title"><?= $e($w['title']) ?></h2>
    <p class="section__meta"><?= $e($w['intro']) ?></p>
  </div>

  <?php foreach ($w['projects'] as $p): ?>
  <article class="project" id="project-<?= $e($p['id']) ?>" aria-labelledby="project-<?= $e($p['id']) ?>-title">
    <div class="project__media">
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
          <img src="<?= $e($asset($m['src'])) ?>" width="<?= (int) $m['width'] ?>" height="<?= (int) $m['height'] ?>" loading="lazy" alt="<?= $e($m['alt']) ?>">
        </div>
        <figcaption><?= $e($m['caption']) ?></figcaption>
        <?php endif; ?>
      </figure>
      <?php endforeach; ?>
    </div>

    <div class="project__body">
      <p class="case__kicker num"><?= $e($p['kicker']) ?></p>
      <h3 class="project__title" id="project-<?= $e($p['id']) ?>-title"><?= $e($p['title']) ?></h3>
      <p class="project__text"><?= $e($p['text']) ?></p>
      <dl class="stack">
        <div class="stack__row"><dt><?= $e($w['front_label']) ?></dt><dd><?= $e($p['front']) ?></dd></div>
        <?php if (!empty($p['back'])): ?>
        <div class="stack__row"><dt><?= $e($w['back_label']) ?></dt><dd><?= $e($p['back']) ?></dd></div>
        <?php endif; ?>
      </dl>
      <?php if (!empty($p['note'])): ?>
      <p class="project__note"><?= $e($p['note']) ?></p>
      <?php endif; ?>
      <?php if (!empty($p['links'])): ?>
      <ul class="project__links" aria-label="<?= $e($w['links_label']) ?>">
        <?php foreach ($p['links'] as $link): ?>
        <li><a href="<?= $e($link['url']) ?>"><?= $e($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <?php $d = $p['details']; if (!empty($d)): ?>
    <details class="more project__details"<?= isset($d['specs']) ? '' : ' open' ?>>
      <summary><?= $e($w['details_label']) ?></summary>
      <figure class="flow">
        <figcaption class="flow__caption"><?= $e($d['flow_title']) ?></figcaption>
        <ol class="flow__stages">
          <?php foreach ($d['flow'] as $stage): ?>
          <li class="flow__stage">
            <span class="flow__name"><?= $e($stage['name']) ?></span>
            <span class="flow__detail"><?= $e($stage['detail']) ?></span>
          </li>
          <?php endforeach; ?>
        </ol>
        <?php if (isset($d['rail'])): ?>
        <p class="flow__rail"><strong><?= $e($d['rail_label']) ?>:</strong> <?= $e($d['rail']) ?></p>
        <?php endif; ?>
      </figure>

      <?php if (isset($d['specs'])): ?>
      <div class="case__columns">
        <table class="specs">
          <caption><?= $e($d['specs_caption']) ?></caption>
          <thead>
            <tr><?php foreach ($d['specs_head'] as $head): ?><th scope="col"><?= $e($head) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($d['specs'] as [$name, $value, $conditions]): ?>
            <tr><th scope="row"><?= $e($name) ?></th><td class="num"><?= $e($value) ?></td><td><?= $e($conditions) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="decisions">
          <h4 class="decisions__title"><?= $e($d['decisions_title']) ?></h4>
          <dl>
            <?php foreach ($d['decisions'] as $decision): ?>
            <div class="decisions__item">
              <dt><?= $e($decision['title']) ?></dt>
              <dd><?= $e($decision['text']) ?></dd>
            </div>
            <?php endforeach; ?>
          </dl>
        </div>
      </div>
      <?php endif; ?>
    </details>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>

  <article class="case case--compact" aria-labelledby="mobile-title">
    <p class="case__kicker num"><?= $e($mobile['period']) ?></p>
    <h3 class="case__title" id="mobile-title"><?= $e($mobile['title']) ?></h3>
    <p class="case__summary"><?= $e($mobile['text']) ?></p>
    <p><a href="#experience"><?= $e($mobile['link']) ?></a></p>
  </article>
</section>
