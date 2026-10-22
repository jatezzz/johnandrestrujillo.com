<?php
/**
 * @var array<string, mixed> $c
 * @var callable $e
 */
$w = $c['work'];
$mobile = $w['mobile'];
?>
<section class="section wrap" id="work" aria-labelledby="work-title">
  <h2 class="section__title" id="work-title"><?= $e($w['title']) ?></h2>

  <?php foreach ($w['cases'] as $case): ?>
  <article class="case" id="case-<?= $e($case['id']) ?>" aria-labelledby="case-<?= $e($case['id']) ?>-title">
    <p class="case__kicker num"><?= $e($case['period']) ?></p>
    <h3 class="case__title" id="case-<?= $e($case['id']) ?>-title"><?= $e($case['title']) ?></h3>
    <div class="case__intro">
      <p class="case__summary"><?= $e($case['summary']) ?></p>
      <p><?= $e($case['role']) ?></p>
    </div>

    <?php if (!empty($case['video'])): $v = $case['video']; ?>
    <figure class="demo">
      <div class="demo__frame" data-youtube="<?= $e($v['youtube_id']) ?>" data-title="<?= $e($v['title']) ?>">
        <a class="demo__play" href="https://www.youtube.com/shorts/<?= $e($v['youtube_id']) ?>">
          <img src="<?= $e($asset($v['poster'])) ?>" width="432" height="960" loading="lazy" alt="<?= $e($v['poster_alt']) ?>">
          <span class="demo__button"><?= $e($v['play']) ?></span>
        </a>
      </div>
      <figcaption class="demo__caption">
        <span class="demo__title"><?= $e($v['title']) ?></span>
        <span><?= $e($v['text']) ?></span>
        <span class="demo__note"><?= $e($v['note']) ?></span>
      </figcaption>
    </figure>
    <?php endif; ?>

    <figure class="flow">
      <figcaption class="flow__caption"><?= $e($case['flow_title']) ?></figcaption>
      <ol class="flow__stages">
        <?php foreach ($case['flow'] as $stage): ?>
        <li class="flow__stage">
          <span class="flow__name"><?= $e($stage['name']) ?></span>
          <span class="flow__detail"><?= $e($stage['detail']) ?></span>
        </li>
        <?php endforeach; ?>
      </ol>
      <p class="flow__rail"><strong><?= $e($case['rail_label']) ?>:</strong> <?= $e($case['rail']) ?></p>
    </figure>

    <div class="case__columns">
      <table class="specs">
        <caption><?= $e($case['specs_caption']) ?></caption>
        <thead>
          <tr><?php foreach ($case['specs_head'] as $head): ?><th scope="col"><?= $e($head) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
          <?php foreach ($case['specs'] as [$name, $value, $conditions]): ?>
          <tr><th scope="row"><?= $e($name) ?></th><td class="num"><?= $e($value) ?></td><td><?= $e($conditions) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="decisions">
        <h4 class="decisions__title"><?= $e($case['decisions_title']) ?></h4>
        <dl>
          <?php foreach ($case['decisions'] as $decision): ?>
          <div class="decisions__item">
            <dt><?= $e($decision['title']) ?></dt>
            <dd><?= $e($decision['text']) ?></dd>
          </div>
          <?php endforeach; ?>
        </dl>
      </div>
    </div>

    <?php if (!empty($case['links'])): ?>
    <div class="case__sources">
      <h4 class="decisions__title"><?= $e($case['links_title']) ?></h4>
      <ul class="case__links">
        <?php foreach ($case['links'] as $link): ?>
        <li><a href="<?= $e($link['url']) ?>"><?= $e($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
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
