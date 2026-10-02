<?php

/**
 * PowerDownload - Web-Installer, Schritt 4 (Administrator)
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
 */
return static function (string $csrf, array $errors, string $message, array $old): void {
    ?>
<form method="post" action="install.php?step=4" id="pdl_install_form_admin" novalidate>
  <?php echo Html::csrfField($csrf); ?>
  <input type="hidden" name="action" value="admin">

  <section class="card shadow-sm mb-4">
    <header class="card-header bg-secondary-subtle">
      <h2 class="h5 mb-0">Ihr Administrator-Konto</h2>
    </header>
    <div class="card-body">
      <p>
        Mit diesem Konto verwalten Sie PowerDownload. Sie melden sich später im Adminbereich
        (<code>pdl-admin/</code>) mit <strong>Benutzername und Passwort</strong> an.
      </p>

      <?php echo Html::alert($message, 'danger', 'pdl_install_message'); ?>

      <?php echo Html::input('admin_nick', 'Benutzername', $old['admin_nick'] ?? '', $errors, [
          'required' => true,
          'minlength' => FormValidator::NICK_MIN,
          'maxlength' => FormValidator::NICK_MAX,
          'autocomplete' => 'username',
          'spellcheck' => 'false',
      ], '3 bis 30 Zeichen: Buchstaben (auch Umlaute), Ziffern sowie . _ -'); ?>

      <?php echo Html::input('admin_email', 'E-Mail-Adresse', $old['admin_email'] ?? '', $errors, [
          'type' => 'email',
          'required' => true,
          'maxlength' => FormValidator::EMAIL_MAX,
          'autocomplete' => 'email',
      ], 'Ihre eigene Adresse, z. B. für „Passwort vergessen“. Sie wird nicht öffentlich angezeigt.'); ?>

      <div class="row">
        <div class="col-md-6">
          <?php echo Html::input('admin_password', 'Passwort', '', $errors, [
              'type' => 'password',
              'required' => true,
              'minlength' => FormValidator::PASSWORD_MIN,
              'maxlength' => FormValidator::PASSWORD_MAX_BYTES,
              'autocomplete' => 'new-password',
          ], 'Mindestens ' . FormValidator::PASSWORD_MIN . ' Zeichen mit mindestens einem Buchstaben und einer Ziffer. Gespeichert wird nur ein Hash.'); ?>
        </div>
        <div class="col-md-6">
          <?php echo Html::input('admin_password_confirm', 'Passwort wiederholen', '', $errors, [
              'type' => 'password',
              'required' => true,
              'minlength' => FormValidator::PASSWORD_MIN,
              'maxlength' => FormValidator::PASSWORD_MAX_BYTES,
              'autocomplete' => 'new-password',
          ]); ?>
        </div>
      </div>
    </div>
    <footer class="card-footer d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="install.php?step=3" id="pdl_install_btn_back">Zurück</a>
      <button type="submit" class="btn btn-primary" id="pdl_install_btn_admin">Weiter zur Zusammenfassung</button>
    </footer>
  </section>
</form>
<?php
};
