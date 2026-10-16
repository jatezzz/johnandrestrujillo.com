<?php
/**
 * @var array<string, mixed> $c
 * @var callable $e
 */
$w = $c['work'];
$case = $w['case'];
$mobile = $w['mobile'];
?>
<section class="section wrap" id="work" aria-labelledby="work-title">
  <h2 class="section__title" id="work-title"><?= $e($w['title']) ?></h2>

  <article class="case" aria-labelledby="case-title">
    <p class="case__kicker"><span class="num"><?= $e($case['period']) ?></span> · <?= $e($case['context']) ?></p>
    <h3 class="case__title" id="case-title"><?= $e($case['title']) ?></h3>
    <div class="case__intro">
      <p class="case__summary"><?= $e($case['summary']) ?></p>
      <p><?= $e($case['role']) ?></p>
    </div>

    <figure class="flow">
      <figcaption class="flow__caption label"><?= $e($case['flow_title']) ?></figcaption>
      <ol class="flow__stages">
        <?php foreach ($case['flow'] as $stage): ?>
        <li class="flow__stage">
          <span class="flow__name"><?= $e($stage['name']) ?></span>
          <span class="flow__detail"><?= $e($stage['detail']) ?></span>
        </li>
        <?php endforeach; ?>
      </ol>
      <p class="flow__rail"><span class="label"><?= $e($case['rail_label']) ?></span> <?= $e($case['rail']) ?></p>
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
        <h4 class="decisions__title label"><?= $e($case['decisions_title']) ?></h4>
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
  </article>

  <article class="case case--compact" aria-labelledby="mobile-title">
    <p class="case__kicker"><span class="num"><?= $e($mobile['period']) ?></span></p>
    <h3 class="case__title" id="mobile-title"><?= $e($mobile['title']) ?></h3>
    <p class="case__summary"><?= $e($mobile['text']) ?></p>
    <p><a class="arrow-link" href="#experience"><?= $e($mobile['link']) ?></a></p>
  </article>
</section>
