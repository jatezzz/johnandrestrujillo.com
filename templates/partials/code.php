<?php
/**
 * @var array<string, mixed> $c
 * @var array<string, mixed> $site
 * @var callable $e
 */
$k = $c['code'];
// Mobile repos first; the rest (university and utility projects) fold under "Show all n".
$lead = 7;
$groups = array_filter([array_slice($k['repos'], 0, $lead), array_slice($k['repos'], $lead)]);
?>
<section class="section wrap" id="code" aria-labelledby="code-title">
  <div class="section__head">
    <h2 class="section__title" id="code-title"><?= $e($k['title']) ?></h2>
    <p class="section__intro"><?= $e($k['intro']) ?></p>
  </div>
  <?php foreach ($groups as $i => $repos): ?>
  <?php if ($i === 1): ?>
  <details class="more">
    <summary><?= $e(str_replace('{n}', (string) count($k['repos']), $c['ui']['show_all'])) ?></summary>
  <?php endif; ?>
  <table class="repos">
    <thead>
      <tr><?php foreach ($k['head'] as $head): ?><th scope="col"><?= $e($head) ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
      <?php foreach ($repos as $repo): ?>
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
  <?php if ($i === 1): ?>
  </details>
  <?php endif; ?>
  <?php endforeach; ?>
  <p><a href="<?= $e($site['github_url']) ?>"><?= $e($k['profile_label']) ?></a></p>
</section>
