<?php

/**
 * PowerDownload - Web-Installer, Schritt 3 (Website)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\FormValidator;
use PowerDownload\Installer\Html;

/**
 * @param array<string, string> $errors
 * @param array<string, string> $old
 * @param array{type: string, message: string}|null $notice Ergebnis des Verbindungstests
 */
return static function (string $csrf, array $errors, string $message, array $old, ?array $notice): void {
    ?>
<?php echo $notice !== null ? Html::alert($notice['message'], $notice['type'], 'pdl_install_notice') : ''; ?>

<form method="post" action="install.php?step=3" id="pdl_install_form_website" novalidate>
  <?php echo Html::csrfField($csrf); ?>
  <input type="hidden" name="action" value="website">

  <section class="card shadow-sm mb-4">
    <header class="card-header bg-secondary-subtle">
      <h2 class="h5 mb-0">Ihre Download-Seite</h2>
    </header>
    <div class="card-body">
      <?php echo Html::alert($message, 'danger', 'pdl_install_message'); ?>

      <?php echo Html::input('site_name', 'Name der Download-Seite', $old['site_name'] ?? '', $errors, [
          'required' => true,
          'maxlength' => FormValidator::SITE_NAME_MAX,
          'autocomplete' => 'off',
      ], 'Erscheint in den E-Mails an Ihre Besucher und als deren Absender, z. B. „Downloads Fotoclub Lichtblick“.'); ?>

      <?php echo Html::input('site_url', 'Adresse der Download-Seite', $old['site_url'] ?? '', $errors, [
          'type' => 'url',
          'required' => true,
          'maxlength' => FormValidator::URL_MAX,
          'spellcheck' => 'false',
      ], 'Vollständige Adresse mit http:// oder https://, vorbelegt mit der Adresse, unter der Sie den Installer gerade aufrufen. Links in E-Mails bauen darauf auf.'); ?>

      <?php echo Html::input('site_email', 'Absenderadresse für E-Mails', $old['site_email'] ?? '', $errors, [
          'type' => 'email',
          'required' => true,
          'maxlength' => FormValidator::EMAIL_MAX,
          'autocomplete' => 'email',
      ], 'Nehmen Sie ein Postfach Ihrer eigenen Domain, sonst lehnen viele Empfänger die Mails ab.'); ?>

      <?php echo Html::input('site_description', 'Kurzbeschreibung für Suchmaschinen', $old['site_description'] ?? '', $errors, [
          'maxlength' => FormValidator::DESCRIPTION_MAX,
      ], 'Optional. Erscheint als Beschreibung in Suchergebnissen.'); ?>

      <p class="small text-body-secondary mb-0" id="pdl_install_website_later_hint">
        Alle Angaben können Sie später im Adminbereich ändern.
      </p>
    </div>
    <footer class="card-footer d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="install.php?step=2" id="pdl_install_btn_back">Zurück</a>
      <button type="submit" class="btn btn-primary" id="pdl_install_btn_website">Weiter zum Administrator</button>
    </footer>
  </section>
</form>
<?php
};
