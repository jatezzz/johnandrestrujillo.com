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
      <h3 class="side-heading"><?= $e($k['elsewhere']) ?></h3>
      <ul class="plain-list">
        <li><a href="<?= $e($site['github_url']) ?>" rel="me">GitHub</a></li>
        <?php if ($site['linkedin_url'] !== ''): ?>
        <li><a href="<?= $e($site['linkedin_url']) ?>" rel="me">LinkedIn</a></li>
        <?php endif; ?>
        <?php if ($site['instagram_url'] !== ''): ?>
        <li><a href="<?= $e($site['instagram_url']) ?>" rel="me">Instagram</a></li>
        <?php endif; ?>
        <?php if ($site['tiktok_url'] !== ''): ?>
        <li><a href="<?= $e($site['tiktok_url']) ?>" rel="me">TikTok</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="contact__form">
      <?php if ($form['sent']): ?>
      <p class="notice notice--ok" role="status"><?= $e($k['sent']) ?></p>
      <?php elseif (!$site['form_enabled']): ?>
      <p class="notice"><?= $e($k['unavailable']) ?> <a href="<?= $e($site['github_url']) ?>">GitHub</a>.</p>
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
        <?php if ($site['captcha']): ?>
        <?php $challenge = $form['captcha']; ?>
        <div class="field field--captcha" data-captcha>
          <label for="contact-captcha"><?= $e($k['fields']['captcha']) ?></label>
          <p class="field__hint" id="captcha-hint"><?= $e($k['captcha']['hint']) ?></p>
          <div class="captcha">
            <img class="captcha__image" data-captcha-image width="160" height="50" alt="<?= $e($k['captcha']['alt']) ?>"<?= $challenge ? ' src="' . $e($challenge['image']) . '"' : ' hidden' ?>>
            <button class="captcha__new" type="button" data-captcha-new hidden><?= $e($k['captcha']['new']) ?></button>
            <a class="captcha__show" href="?challenge=1#contact" data-captcha-show data-loading="<?= $e($k['captcha']['loading']) ?>"<?= $challenge ? ' hidden' : '' ?>><?= $e($k['captcha']['show']) ?></a>
          </div>
          <input id="contact-captcha" name="captcha_answer" type="text" required maxlength="12" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" aria-describedby="captcha-hint<?= isset($fieldErrors['captcha']) ? ' error-captcha' : '' ?>"<?= isset($fieldErrors['captcha']) ? ' aria-invalid="true"' : '' ?>>
          <input type="hidden" name="captcha_token" value="<?= $e($challenge['token'] ?? '') ?>" data-captcha-token>
          <?php if (isset($fieldErrors['captcha'])): ?><p class="field__error" id="error-captcha"><?= $e($k['errors']['captcha']) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>
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
