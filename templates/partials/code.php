<?php
/**
 * @var array<string, mixed> $c
 * @var array<string, mixed> $site
 * @var callable $e
 */
$k = $c['code'];
?>
<section class="section wrap" id="code" aria-labelledby="code-title">
  <div class="section__head">
    <h2 class="section__title" id="code-title"><?= $e($k['title']) ?></h2>
    <p class="section__intro"><?= $e($k['intro']) ?></p>
  </div>
  <ul class="repos">
    <?php foreach ($k['repos'] as $repo): ?>
    <li class="repo">
      <h3 class="repo__name"><a href="<?= $e($repo['url']) ?>"><?= $e($repo['name']) ?></a></h3>
      <p class="repo__meta num">
        <?= $e($repo['lang']) ?>
        <?php if ($repo['stars'] > 0): ?> · <?= $e($repo['stars'] === 1 ? $k['stars_one'] : str_replace('{n}', (string) $repo['stars'], $k['stars_many'])) ?><?php endif; ?>
      </p>
      <p class="repo__desc"><?= $e($repo['desc']) ?></p>
    </li>
    <?php endforeach; ?>
  </ul>
  <p><a class="arrow-link" href="<?= $e($site['github_url']) ?>"><?= $e($k['profile_label']) ?></a></p>
</section>
