<?php

/**
 * PowerDownload - Web-Installer, Schritt 5 (Zusammenfassung und Abschluss)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\Html;
use PowerDownload\Installer\Wizard;
use PowerDownload\LocalConfig;

/**
 * @param array<string, string> $errors
 * @param string|null $lockTarget relativer Pfad der künftigen Sperrdatei, null = nicht beschreibbar
 */
return static function (string $csrf, array $errors, string $message, Wizard $wizard, bool $configWritable, ?string $lockTarget): void {
    $database = $wizard->database();
    $website = $wizard->website();
    $admin = $wizard->admin();

    if ($database === null || $website === null || $admin === null) {
        return;
    }
    ?>
<p id="pdl_install_finish_intro">Bitte prüfen Sie Ihre Angaben. „Jetzt installieren“ legt die Tabellen an, erstellt Ihr Administrator-Konto und sperrt den Installer.</p>

<?php echo Html::alert($message, 'danger', 'pdl_install_message'); ?>

<div class="row g-3 mb-4" id="pdl_install_summary">
  <div class="col-md-6">
    <section class="card shadow-sm h-100">
      <header class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Datenbank</h2>
        <a class="small" href="install.php?step=2" id="pdl_install_edit_database">Ändern</a>
      </header>
      <dl class="card-body row align-content-start mb-0 small">
        <dt class="col-5">Server</dt>
        <dd class="col-7 text-break" id="pdl_install_summary_db_host"><?php echo Html::escape($database['host'] . ':' . $database['port']); ?></dd>
        <dt class="col-5">Datenbank</dt>
        <dd class="col-7 text-break" id="pdl_install_summary_db_name"><?php echo Html::escape($database['database']); ?></dd>
        <dt class="col-5">Benutzer</dt>
        <dd class="col-7 text-break" id="pdl_install_summary_db_user"><?php echo Html::escape($database['user']); ?></dd>
        <dt class="col-5">Erkannt</dt>
        <dd class="col-7 text-break mb-0" id="pdl_install_summary_db_server"><?php echo Html::escape($wizard->serverLabel()); ?></dd>
      </dl>
    </section>
  </div>
  <div class="col-md-6">
    <section class="card shadow-sm h-100">
      <header class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Administrator</h2>
        <a class="small" href="install.php?step=4" id="pdl_install_edit_admin">Ändern</a>
      </header>
      <dl class="card-body row align-content-start mb-0 small">
        <dt class="col-5">Benutzername</dt>
        <dd class="col-7 text-break" id="pdl_install_summary_admin_nick"><?php echo Html::escape($admin['nick']); ?></dd>
        <dt class="col-5">E-Mail</dt>
        <dd class="col-7 text-break mb-0" id="pdl_install_summary_admin_email"><?php echo Html::escape($admin['email']); ?></dd>
      </dl>
    </section>
  </div>
  <div class="col-12">
    <section class="card shadow-sm">
      <header class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Download-Seite</h2>
        <a class="small" href="install.php?step=3" id="pdl_install_edit_website">Ändern</a>
      </header>
      <dl class="card-body row align-content-start mb-0 small">
        <dt class="col-sm-4">Name</dt>
        <dd class="col-sm-8 text-break" id="pdl_install_summary_site_name"><?php echo Html::escape($website['name']); ?></dd>
        <dt class="col-sm-4">Adresse</dt>
        <dd class="col-sm-8 text-break" id="pdl_install_summary_site_url"><?php echo Html::escape($website['url']); ?></dd>
        <dt class="col-sm-4">Absender</dt>
        <dd class="col-sm-8 text-break" id="pdl_install_summary_site_email"><?php echo Html::escape($website['email']); ?></dd>
        <dt class="col-sm-4">Kurzbeschreibung</dt>
        <dd class="col-sm-8 text-break mb-0" id="pdl_install_summary_site_description"><?php echo $website['description'] === '' ? '<span class="text-body-secondary">keine</span>' : Html::escape($website['description']); ?></dd>
      </dl>
    </section>
  </div>
</div>

<?php if ($configWritable) { ?>
<div class="alert alert-info" role="note" id="pdl_install_config_mode_auto">
  Die Zugangsdaten zur Datenbank werden in <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code> gespeichert.
</div>
<?php } else { ?>
<div class="alert alert-warning" role="note" id="pdl_install_config_mode_manual">
  Das Verzeichnis <code>pdl-inc/</code> ist nicht beschreibbar. Nach der Installation bietet der Installer
  <code><?php echo Html::escape(LocalConfig::FILENAME); ?></code> zum Herunterladen an. Sie laden die Datei dann selbst
  (z. B. per FTP) nach <code>pdl-inc/</code> hoch.
</div>
<?php } ?>

<?php if ($lockTarget === null) { ?>
<div class="alert alert-danger" role="alert" id="pdl_install_lock_mode_missing">
  Weder <code>pdl-inc/</code> noch <code>logs/</code> ist beschreibbar. Der Installer könnte sich nicht sperren.
  Bitte machen Sie <code>logs/</code> beschreibbar, bevor Sie installieren.
</div>
<?php } else { ?>
<p class="small text-body-secondary" id="pdl_install_lock_mode_info">
  Nach der Installation sperrt sich der Installer über die Datei <code><?php echo Html::escape($lockTarget); ?></code>.
</p>
<?php } ?>

<form method="post" action="install.php?step=5" id="pdl_install_form_finish" class="d-flex justify-content-between align-items-center mb-4">
  <?php echo Html::csrfField($csrf); ?>
  <a class="btn btn-outline-secondary" href="install.php?step=4" id="pdl_install_btn_back">Zurück</a>
  <button type="submit" name="action" value="finish" class="btn btn-success btn-lg" id="pdl_install_btn_install">Jetzt installieren</button>
</form>
<?php
};
