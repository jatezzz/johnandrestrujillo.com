<?php
/**
 * @var array<string, mixed> $c
 * @var string $lang
 * @var array<string, mixed> $site
 * @var array{values: array<string, string>, errors: array<string, string>, sent: bool} $form
 * @var callable $e
 */
$k = $c['contact'];
$errors = $form['errors'];
$values = $form['values'] + ['name' => '', 'email' => '', 'org' => '', 'message' => ''];
$fieldErrors = array_diff_key($errors, ['form' => true]);

/** Attributes that tie a field to its error message, when it has one. */
$invalid = static function (string $field) use ($fieldErrors): string {
    return isset($fieldErrors[$field]) ? ' aria-invalid="true" aria-describedby="error-' . $field . '"' : '';
};
?>
<section class="section wrap" id="contact" aria-labelledby="contact-title">
  <div class="contact">
    <div class="contact__intro">
      <h2 class="section__title" id="contact-title"><?= $e($k['title']) ?></h2>
      <p><?= $e($k['intro']) ?></p>
      <p class="muted"><?= $e($k['privacy']) ?></p>
      <h3 class="label"><?= $e($k['elsewhere']) ?></h3>
      <ul class="plain-list">
        <li><a href="<?= $e($site['github_url']) ?>">GitHub</a></li>
        <?php if ($site['linkedin_url'] !== ''): ?>
        <li><a href="<?= $e($site['linkedin_url']) ?>">LinkedIn</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="contact__form">
      <?php if ($form['sent']): ?>
      <p class="notice notice--ok" role="status"><?= $e($k['sent']) ?></p>
      <?php elseif (!$site['form_enabled']): ?>
      <p class="notice"><?= $e($k['unavailable']) ?> <a href="<?= $e($site['github_url']) ?>">GitHub</a></p>
      <?php else: ?>
      <?php if ($errors !== []): ?>
      <div class="notice notice--error" role="alert">
        <p><?= $e(isset($errors['form']) ? $k['errors'][$errors['form']] : $k['errors']['summary']) ?></p>
      </div>
      <?php endif; ?>
      <form class="form" method="post" action="/contact" novalidate>
        <input type="hidden" name="lang" value="<?= $e($lang) ?>">
        <input type="hidden" name="token" value="<?= $e($site['form_token']) ?>">
        <div class="field">
          <label for="contact-name"><?= $e($k['fields']['name']) ?></label>
          <input id="contact-name" name="name" type="text" autocomplete="name" required maxlength="120" value="<?= $e($values['name']) ?>"<?= $invalid('name') ?>>
          <?php if (isset($fieldErrors['name'])): ?><p class="field__error" id="error-name"><?= $e($k['errors']['name']) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="contact-email"><?= $e($k['fields']['email']) ?></label>
          <input id="contact-email" name="email" type="email" autocomplete="email" required maxlength="254" value="<?= $e($values['email']) ?>"<?= $invalid('email') ?>>
          <?php if (isset($fieldErrors['email'])): ?><p class="field__error" id="error-email"><?= $e($k['errors']['email']) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="contact-org"><?= $e($k['fields']['org']) ?> <span class="muted">(<?= $e($k['fields']['optional']) ?>)</span></label>
          <input id="contact-org" name="org" type="text" autocomplete="organization" maxlength="160" value="<?= $e($values['org']) ?>">
        </div>
        <div class="field">
          <label for="contact-message"><?= $e($k['fields']['message']) ?></label>
          <textarea id="contact-message" name="message" rows="6" required minlength="20" maxlength="5000"<?= $invalid('message') ?>><?= $e($values['message']) ?></textarea>
          <?php if (isset($fieldErrors['message'])): ?><p class="field__error" id="error-message"><?= $e($k['errors']['message']) ?></p><?php endif; ?>
        </div>
        <div class="field field--trap" aria-hidden="true">
          <label for="contact-website"><?= $e($k['fields']['website']) ?></label>
          <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <button class="button button--primary" type="submit"><?= $e($k['submit']) ?></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>
