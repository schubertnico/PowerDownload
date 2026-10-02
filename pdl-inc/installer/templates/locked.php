<?php

/**
 * PowerDownload - Web-Installer gesperrt (HTTP 403)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\Html;
use PowerDownload\Installer\InstallState;
use PowerDownload\LocalConfig;

return static function (string $reason, string $lockFile): void {
    $text = match ($reason) {
        InstallState::REASON_LOCK_FILE => 'Die Installation wurde bereits abgeschlossen (Sperrdatei ' . $lockFile . ' vorhanden).',
        InstallState::REASON_LOCAL_CONFIG => 'Es gibt bereits ' . LocalConfig::RELATIVE_PATH . '. Damit eine bestehende Installation nicht überschrieben wird, startet der Installer nicht.',
        InstallState::REASON_DATABASE => 'In der konfigurierten Datenbank ist PowerDownload bereits eingerichtet.',
        InstallState::REASON_EXISTING_FILES => 'In ' . implode(' bzw. ', array_map(static fn (string $uploadDir): string => $uploadDir . '/', InstallState::UPLOAD_DIRS))
            . ' liegen bereits hochgeladene Dateien. Hier scheint also schon eine Installation von PowerDownload zu bestehen. '
            . 'Für ein Update nutzen Sie bitte update.php; die Anleitung steht in der README unter „Update von 3.5.0 auf 3.6.0“.',
        default => 'Per Umgebungsvariablen ist eine Datenbank eingerichtet, die gerade nicht erreichbar ist. Aus Sicherheitsgründen startet der Installer in diesem Zustand nicht.',
    };
    ?>
<div class="alert alert-warning" role="alert" id="pdl_install_locked" data-reason="<?php echo Html::escape($reason); ?>">
  <strong>Der Installer ist gesperrt.</strong><br>
  <?php echo Html::escape($text); ?>
</div>

<section class="card shadow-sm mb-4">
  <div class="card-body">
    <p id="pdl_install_locked_delete_hint">
      <strong>Bitte löschen Sie die Datei <code>install.php</code> vom Server.</strong>
      Für den laufenden Betrieb wird sie nicht gebraucht.
    </p>
    <p class="small text-body-secondary mb-0">
      Bestehende Tabellen verändert der Installer nie. Für ein Update ist er nicht nötig: Melden Sie sich im
      Adminbereich an und rufen Sie <code>update.php</code> auf. Wirklich neu installieren? Dann legen Sie eine
      Datensicherung an, entfernen <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code> und die Sperrdatei
      (<?php echo implode(' bzw. ', array_map(static fn (string $file): string => '<code>' . Html::escape($file) . '</code>', InstallState::LOCK_FILES)); ?>)
      und verwenden eine leere Datenbank.<?php if ($reason === InstallState::REASON_EXISTING_FILES) { ?>
      Leeren Sie außerdem <?php echo implode(' und ', array_map(static fn (string $uploadDir): string => '<code>' . Html::escape($uploadDir) . '/</code>', InstallState::UPLOAD_DIRS)); ?>
      (bis auf <code>.htaccess</code> und <code>index.html</code>).<?php } ?>
    </p>
  </div>
  <footer class="card-footer d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="downloads.php" id="pdl_install_link_frontend">Zur Download-Seite</a>
    <a class="btn btn-outline-secondary" href="pdl-admin/" id="pdl_install_link_admin">Zum Adminbereich</a>
  </footer>
</section>
<?php
};
