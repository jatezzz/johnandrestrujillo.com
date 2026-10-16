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
  <table class="repos">
    <thead>
      <tr><?php foreach ($k['head'] as $head): ?><th scope="col"><?= $e($head) ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
      <?php foreach ($k['repos'] as $repo): ?>
      <tr>
        <th scope="row" class="repos__name"><a href="<?= $e($repo['url']) ?>"><?= $e($repo['name']) ?></a></th>
        <td class="repos__lang">
          <?= $e($repo['lang']) ?><?php if ($repo['stars'] > 0): ?>, <?= $e($repo['stars'] === 1 ? $k['stars_one'] : str_replace('{n}', (string) $repo['stars'], $k['stars_many'])) ?><?php endif; ?>
        </td>
        <td><?= $e($repo['desc']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p><a href="<?= $e($site['github_url']) ?>"><?= $e($k['profile_label']) ?></a></p>
</section>
