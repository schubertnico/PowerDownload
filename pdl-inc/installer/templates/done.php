<?php

/**
 * PowerDownload - Web-Installer, Installation abgeschlossen
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\Html;
use PowerDownload\LocalConfig;

/**
 * @param array{config_written: bool, config_source: string, lock_file: string, admin_nick: string} $done
 * @param list<string> $deleteFiles install.php und gefundene Altdateien
 */
return static function (array $done, bool $configPresent, string $csrf, array $deleteFiles): void {
    ?>
<div class="alert alert-success" role="status" id="pdl_install_success">
  <strong>PowerDownload ist installiert.</strong><br>
  Ihr Administrator-Konto <strong id="pdl_install_done_admin_nick"><?php echo Html::escape($done['admin_nick']); ?></strong> ist angelegt.
  Melden Sie sich im <a class="alert-link" href="pdl-admin/" id="pdl_install_link_admin_inline">Adminbereich</a> mit diesem Benutzernamen und Ihrem Passwort an.
</div>

<?php if ($done['config_written']) { ?>
<section class="card shadow-sm mb-4" id="pdl_install_config_written">
  <header class="card-header bg-secondary-subtle">
    <h2 class="h6 mb-0">Konfiguration gespeichert</h2>
  </header>
  <div class="card-body small">
    <p>
      Die Zugangsdaten stehen in <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code>. Die Datei ist per
      <code>.htaccess</code> vor direktem Abruf geschützt und hat, sofern der Server das zulässt, die Rechte <code>640</code> erhalten.
    </p>
    <p class="mb-0">Legen Sie eine Sicherungskopie der Datei an. Bei einem Update bleibt sie erhalten.</p>
  </div>
</section>
<?php } elseif ($configPresent) { ?>
<div class="alert alert-success" role="status" id="pdl_install_config_present">
  <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code> wurde gefunden, PowerDownload ist einsatzbereit.
</div>
<?php } else { ?>
<section class="card shadow-sm border-warning mb-4" id="pdl_install_config_manual">
  <header class="card-header bg-warning-subtle">
    <h2 class="h6 mb-0">Noch ein Schritt: <code><?php echo Html::escape(LocalConfig::FILENAME); ?></code> hochladen</h2>
  </header>
  <div class="card-body">
    <p>
      Der Installer durfte die Datei nicht selbst schreiben. Laden Sie sie herunter und legen Sie sie per FTP in das
      Verzeichnis <code>pdl-inc/</code> (dort, wo auch <code>pdl_config.inc.php</code> liegt). Bis dahin kann PowerDownload die
      Datenbank nicht erreichen. Die Datei enthält Ihr Datenbank-Passwort, bitte geben Sie sie nicht weiter.
    </p>
    <form method="post" action="install.php" class="mb-3" id="pdl_install_form_download_config">
      <?php echo Html::csrfField($csrf); ?>
      <button type="submit" name="action" value="download_config" class="btn btn-primary" id="pdl_install_btn_download_config">
        <?php echo Html::escape(LocalConfig::FILENAME); ?> herunterladen
      </button>
    </form>
    <label for="pdl_install_config_source" class="form-label fw-semibold">Oder Inhalt kopieren:</label>
    <textarea id="pdl_install_config_source" class="form-control font-monospace small" rows="18" readonly spellcheck="false"><?php echo Html::escape($done['config_source']); ?></textarea>
    <p class="small text-body-secondary mt-2 mb-0">
      Sobald die Datei auf dem Server liegt, <a href="install.php" id="pdl_install_config_recheck">laden Sie diese Seite neu</a>.
    </p>
  </div>
</section>
<?php } ?>

<?php if ($done['lock_file'] !== '') { ?>
<p class="small text-body-secondary" id="pdl_install_lock_written">
  Der Installer ist gesperrt (Sperrdatei <code><?php echo Html::escape($done['lock_file']); ?></code>). Jeder weitere Aufruf von
  <code>install.php</code> endet mit „Installer gesperrt“.
</p>
<?php } else { ?>
<div class="alert alert-danger" role="alert" id="pdl_install_lock_not_written">
  <strong>Die Sperrdatei konnte nicht angelegt werden.</strong>
  Der Installer ist erst gesperrt, wenn <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code> auf dem Server liegt.
  Löschen Sie <code>install.php</code> deshalb sofort.
</div>
<?php } ?>

<div class="alert alert-warning" role="alert" id="pdl_install_delete_files">
  <strong>Bitte löschen Sie jetzt diese Dateien vom Server:</strong>
  <ul class="mb-2 mt-1">
<?php foreach ($deleteFiles as $file) { ?>
    <li><code><?php echo Html::escape($file); ?></code></li>
<?php } ?>
  </ul>
  <span class="small">Der Installer ist gesperrt, gehört aber nicht auf eine laufende Website. Das Verzeichnis
  <code>pdl-inc/installer/</code> bleibt: <code>update.php</code> braucht es, und von außen ist es nicht abrufbar.</span>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
  <a class="btn btn-primary" href="pdl-admin/" id="pdl_install_link_admin">Zum Adminbereich</a>
  <a class="btn btn-outline-secondary" href="downloads.php" id="pdl_install_link_frontend">Zur Download-Seite</a>
</div>
<?php
};
