<?php
/**
 * One Note (an article), English only. Modeled on case.php: same layout, header and footer.
 * The body is a list of typed blocks (h2, p, ul, code, demo); inline spans are escaped with
 * $e() and links carry only a URL and visible text, so the strict CSP stays as it is.
 *
 * @var array<string, mixed> $c
 * @var array<string, mixed> $note
 * @var \Portfolio\View $view
 * @var callable $e
 */
$n = $c['notes'];
/** Render a list of inline runs (text, code, em, strong, link), each value escaped. */
$runs = static function (array $parts) use ($e): string {
    $out = '';
    foreach ($parts as $run) {
        $text = $e($run['s']);
        switch ($run['t']) {
            case 'code':   $out .= '<code>' . $text . '</code>'; break;
            case 'em':     $out .= '<em>' . $text . '</em>'; break;
            case 'strong': $out .= '<strong>' . $text . '</strong>'; break;
            case 'link':
                $label = !empty($run['code']) ? '<code>' . $text . '</code>' : $text;
                $out .= '<a href="' . $e($run['url']) . '">' . $label . '</a>';
                break;
            default:       $out .= $text;
        }
    }

    return $out;
};
/**
 * Inline a trusted, hand-authored diagram from content/notes/diagrams (no user input).
 * The basename is validated and the SVG is echoed raw; it carries no links or scripts.
 */
$diagram = static function (string $src): string {
    if (preg_match('/^[a-z0-9-]+\.svg$/', $src) !== 1) {
        return '';
    }
    $file = __DIR__ . '/../content/notes/diagrams/' . $src;

    return is_file($file) ? (string) file_get_contents($file) : '';
};
?>
<article class="case-study note wrap" aria-labelledby="note-title">
  <p class="case-study__back"><a href="/notes/"><?= $e($n['to_index']) ?></a></p>
  <header class="case-study__head">
    <p class="note__meta num"><time datetime="<?= $e($note['date']) ?>"><?= $e($note['date_label']) ?></time> · <?= $e($note['reading_minutes']) ?> <?= $e($n['reading_suffix']) ?></p>
    <h1 class="case-study__title" id="note-title"><?= $e($note['title']) ?></h1>
    <p class="note__byline"><?= $e($note['author']) ?></p>
  </header>

  <div class="note__body">
    <?php foreach ($note['body'] as $block): ?>
    <?php if ($block['type'] === 'h2'): ?>
    <h2 class="note__h2"><?= $e($block['text']) ?></h2>
    <?php elseif ($block['type'] === 'p'): ?>
    <p><?= $runs($block['runs']) ?></p>
    <?php elseif ($block['type'] === 'demo'): ?>
    <p class="note__demo"><em><?= $runs($block['runs']) ?></em></p>
    <?php elseif ($block['type'] === 'ul'): ?>
    <ul class="note__list">
      <?php foreach ($block['items'] as $item): ?>
      <li><?= $runs($item) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php elseif ($block['type'] === 'code'): ?>
    <pre class="note__code"><code><?= $e($block['code']) ?></code></pre>
    <?php elseif ($block['type'] === 'tldr'): ?>
    <aside class="note__tldr" aria-label="Summary">
      <p class="note__tldr-label"><?= $e($block['label']) ?></p>
      <ul class="note__list">
        <?php foreach ($block['items'] as $item): ?>
        <li><?= $runs($item) ?></li>
        <?php endforeach; ?>
      </ul>
    </aside>
    <?php elseif ($block['type'] === 'figure'): ?>
    <figure class="note__figure"><?= $diagram($block['src']) ?><figcaption><?= $runs($block['caption']) ?></figcaption></figure>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <p class="case-study__actions">
    <a class="button button--primary" href="/#contact" data-magnetic data-cta="write" data-loc="note_page"><?= $e($c['hero']['cta_contact']) ?></a>
    <a href="/notes/"><?= $e($n['to_index']) ?></a>
    <a class="note__md-link" href="<?= $e('/notes/' . $note['slug'] . '.md') ?>"><?= $e($n['as_markdown']) ?></a>
  </p>
</article>
