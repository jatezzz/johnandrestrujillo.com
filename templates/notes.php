<?php
/**
 * The Notes index: the published notes in reverse-chronological order (English only).
 * Modeled on the Work section; each row links to its article at /notes/<slug>/.
 *
 * @var array<string, mixed> $c
 * @var \Portfolio\View $view
 * @var callable $e
 * @var string $home where the portfolio link points ('/')
 */
$n = $c['notes'];
?>
<section class="section wrap notes-index" aria-labelledby="notes-title">
  <div class="section__head">
    <h1 class="section__title" id="notes-title"><?= $e($n['title']) ?></h1>
    <p class="section__meta"><?= $e($n['intro']) ?></p>
  </div>

  <ul class="notes-list">
    <?php foreach ($n['items'] as $note): ?>
    <li class="notes-list__item">
      <article aria-labelledby="note-<?= $e($note['slug']) ?>">
        <p class="notes-list__meta num"><time datetime="<?= $e($note['date']) ?>"><?= $e($note['date_label']) ?></time> · <?= $e($note['reading_minutes']) ?> <?= $e($n['reading_suffix']) ?></p>
        <h2 class="notes-list__title" id="note-<?= $e($note['slug']) ?>"><a href="<?= $e('/notes/' . $note['slug'] . '/') ?>"><?= $e($note['title']) ?></a></h2>
        <p class="notes-list__desc"><?= $e($note['description']) ?></p>
      </article>
    </li>
    <?php endforeach; ?>
  </ul>

  <p class="notes-index__back"><a href="<?= $e($home) ?>"><?= $e($n['back']) ?></a></p>
</section>
