<?php
/**
 * How a project is built: the flow, the numbers and the design decisions. Shown on the
 * project's case page; the home page links there instead of carrying it.
 *
 * @var array<string, mixed> $d the project's details
 * @var callable $e
 */
?>
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
    <h3 class="decisions__title"><?= $e($d['decisions_title']) ?></h3>
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
