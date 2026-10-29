<?php
/**
 * Under the hood: the site's own datasheet. The live readings are filled in by hood.js from the Performance
 * APIs and feature checks; without script the rows say so and the rest of the page stands on its own.
 *
 * @var array<string, mixed> $c
 * @var array<string, mixed> $site
 * @var array{html: int, css: int, js: int, js_files: int, fonts: int, font_files: int, total: int} $weights
 * @var string $csp
 * @var callable $e
 */
$h = $c['hood'];
$kb = static fn (int $bytes): string => number_format($bytes / 1024, 1) . ' KB';
$code = static function (string $file) use ($site, $e): string {
    if ($site['source_url'] === '') {
        return '<code>' . $e($file) . '</code>';
    }

    return '<a href="' . $e(rtrim($site['source_url'], '/') . '/blob/main/' . $file) . '"><code>' . $e($file) . '</code></a>';
};
?>
<article class="hood wrap" aria-labelledby="hood-title">
  <header class="hood__head">
    <h1 class="hood__title" id="hood-title"><?= $e($h['title']) ?></h1>
    <p class="hood__lede"><?= $e($h['lede']) ?></p>
  </header>

  <section class="hood__section" aria-labelledby="hood-live" data-hood
    data-waiting="<?= $e($h['waiting']) ?>" data-interact="<?= $e($h['interact']) ?>" data-cached="<?= $e($h['cached']) ?>"
    data-yes="<?= $e($h['yes']) ?>" data-no="<?= $e($h['no']) ?>"
    data-arrival="<?= $e((string) json_encode($h['arrival'], JSON_UNESCAPED_UNICODE)) ?>"
    data-prefs="<?= $e((string) json_encode($h['pref_values'], JSON_UNESCAPED_UNICODE)) ?>">
    <h2 class="section__title" id="hood-live"><?= $e($h['live_title']) ?></h2>
    <p class="hood__note"><?= $e($h['live_note']) ?></p>
    <noscript><p class="hood__note"><?= $e($h['noscript']) ?></p></noscript>
    <dl class="readings">
      <?php foreach ($h['live'] as $key => $label): ?>
      <div class="readings__row"><dt><?= $e($label) ?></dt><dd class="num" data-reading="<?= $e($key) ?>">–</dd></div>
      <?php endforeach; ?>
    </dl>

    <div class="hood__pair">
      <div>
        <h3 class="hood__sub"><?= $e($h['support_title']) ?></h3>
        <dl class="readings readings--compact">
          <?php foreach ($h['support'] as $key => $label): ?>
          <div class="readings__row"><dt><?= $e($label) ?></dt><dd data-support="<?= $e($key) ?>">–</dd></div>
          <?php endforeach; ?>
        </dl>
      </div>
      <div>
        <h3 class="hood__sub"><?= $e($h['prefs_title']) ?></h3>
        <dl class="readings readings--compact">
          <?php foreach ($h['prefs'] as $key => $label): ?>
          <div class="readings__row"><dt><?= $e($label) ?></dt><dd data-pref="<?= $e($key) ?>">–</dd></div>
          <?php endforeach; ?>
        </dl>
      </div>
    </div>
  </section>

  <section class="hood__section" aria-labelledby="hood-build">
    <h2 class="section__title" id="hood-build"><?= $e($h['build_title']) ?></h2>
    <p class="hood__lede hood__lede--small"><?= $e($h['build_lede']) ?></p>
    <h3 class="hood__sub"><?= $e($h['weights_title']) ?></h3>
    <table class="weights">
      <tbody>
        <tr><th scope="row"><?= $e($h['weights']['html']) ?></th><td class="num"><?= $e($kb($weights['html'])) ?></td></tr>
        <tr><th scope="row"><?= $e($h['weights']['css']) ?></th><td class="num"><?= $e($kb($weights['css'])) ?></td></tr>
        <tr><th scope="row"><?= $e(str_replace('{n}', (string) $weights['js_files'], $h['weights']['js'])) ?></th><td class="num"><?= $e($kb($weights['js'])) ?></td></tr>
        <tr><th scope="row"><?= $e(str_replace('{n}', (string) $weights['font_files'], $h['weights']['fonts'])) ?></th><td class="num"><?= $e($kb($weights['fonts'])) ?></td></tr>
      </tbody>
      <tfoot>
        <tr><th scope="row"><?= $e($h['weights']['total']) ?></th><td class="num"><?= $e($kb($weights['total'])) ?></td></tr>
      </tfoot>
    </table>
    <p class="hood__note"><?= $e($h['weights_note']) ?></p>

    <h3 class="hood__sub"><?= $e($h['csp_title']) ?></h3>
    <pre class="hood__csp"><code><?= $e(str_replace('; ', ";\n", $csp)) ?></code></pre>
    <p class="hood__note"><?= $e($h['csp_note']) ?></p>
  </section>

  <section class="hood__section" aria-labelledby="hood-tech">
    <h2 class="section__title" id="hood-tech"><?= $e($h['tech_title']) ?></h2>
    <ol class="techniques">
      <?php foreach ($h['techniques'] as $t): ?>
      <li class="technique">
        <h3 class="technique__title"><?= $e($t['title']) ?></h3>
        <p><?= $e($t['what']) ?></p>
        <p class="technique__hard"><strong><?= $e($h['hard_label']) ?>.</strong> <?= $e($t['hard']) ?></p>
        <p class="technique__code"><?= $e($h['code']) ?>: <?= implode(', ', array_map($code, $t['files'])) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </section>
</article>
