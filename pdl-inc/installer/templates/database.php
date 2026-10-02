<?php

/**
 * PowerDownload - Web-Installer, Schritt 2 (Datenbank)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\FormValidator;
use PowerDownload\Installer\Html;
use PowerDownload\Installer\ServerVersion;

/**
 * @param array<string, string> $errors
 * @param array<string, string> $old
 * @param list<string> $tables bereits vorhandene pdl3_-Tabellen
 */
return static function (string $csrf, array $errors, string $message, array $old, array $tables): void {
    ?>
<form method="post" action="install.php?step=2" id="pdl_install_form_database" novalidate>
  <?php echo Html::csrfField($csrf); ?>
  <input type="hidden" name="action" value="database">

  <section class="card shadow-sm mb-4">
    <header class="card-header bg-secondary-subtle">
      <h2 class="h5 mb-0">Zugang zur Datenbank</h2>
    </header>
    <div class="card-body">
      <p>
        Legen Sie die Datenbank vorher im Kundenmenü Ihres Hosters an. Dort finden Sie auch Server,
        Benutzername und Passwort. Die Verbindung wird beim Absenden geprüft; benötigt wird
        <?php echo Html::escape(ServerVersion::requirement()); ?>.
      </p>

      <?php echo Html::alert($message, $tables === [] ? 'danger' : 'warning', 'pdl_install_message'); ?>

<?php if ($tables !== []) { ?>
      <div class="alert alert-light border small" id="pdl_install_existing_tables">
        <strong>Gefundene Tabellen:</strong> <?php echo Html::escape(implode(', ', $tables)); ?>
        <hr>
        Für ein <strong>Update</strong> einer bestehenden PowerDownload-Installation brauchen Sie den Installer nicht:
        Legen Sie <code>pdl-inc/pdl_config.local.php</code> nach der Vorlage <code>pdl-inc/pdl_config.local.example.php</code> an,
        melden Sie sich im Adminbereich an und rufen Sie <code>update.php</code> auf. Für eine <strong>Neuinstallation</strong>
        wählen Sie bitte eine leere Datenbank.
      </div>
<?php } ?>

      <div class="row">
        <div class="col-md-8">
          <?php echo Html::input('db_host', 'Datenbankserver', $old['db_host'] ?? '', $errors, [
              'required' => true,
              'maxlength' => FormValidator::HOST_MAX,
              'autocomplete' => 'off',
              'spellcheck' => 'false',
          ], 'Meist „localhost“. Manche Hoster nennen einen eigenen Servernamen, z. B. „sql.example.org“.'); ?>
        </div>
        <div class="col-md-4">
          <?php echo Html::input('db_port', 'Port', $old['db_port'] ?? '3306', $errors, [
              'type' => 'number',
              'required' => true,
              'min' => 1,
              'max' => 65535,
              'inputmode' => 'numeric',
          ], 'Standard: 3306'); ?>
        </div>
      </div>

      <?php echo Html::input('db_name', 'Name der Datenbank', $old['db_name'] ?? '', $errors, [
          'required' => true,
          'maxlength' => FormValidator::DB_NAME_MAX,
          'autocomplete' => 'off',
          'spellcheck' => 'false',
      ], 'Die Datenbank muss bereits existieren und sollte leer sein.'); ?>

      <?php echo Html::input('db_user', 'Benutzername', $old['db_user'] ?? '', $errors, [
          'required' => true,
          'maxlength' => FormValidator::DB_USER_MAX,
          'autocomplete' => 'off',
          'spellcheck' => 'false',
      ]); ?>

      <?php echo Html::input('db_password', 'Passwort', '', $errors, [
          'type' => 'password',
          'maxlength' => FormValidator::DB_PASSWORD_MAX,
          'autocomplete' => 'new-password',
      ], 'Wird genau so übernommen, wie Sie es eingeben. Nach einem Fehler wird es aus Sicherheitsgründen nicht wieder angezeigt.'); ?>
    </div>
    <footer class="card-footer d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="install.php?step=1" id="pdl_install_btn_back">Zurück</a>
      <button type="submit" class="btn btn-primary" id="pdl_install_btn_database">Verbindung prüfen und weiter</button>
    </footer>
  </section>
</form>
<?php
};
