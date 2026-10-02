<?php

/**
 * PowerDownload - Web-Installer, Schritt 1 (Systemprüfung)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\Html;
use PowerDownload\Installer\Requirements;

/**
 * @param list<array{id: string, label: string, ok: bool, kind: string, detail: string}> $checks
 * @param array<string, string> $errors
 */
return static function (string $csrf, array $errors, string $message, array $checks, bool $allOk): void {
    ?>
<section class="card shadow-sm mb-4">
  <header class="card-header bg-secondary-subtle">
    <h2 class="h5 mb-0">Voraussetzungen des Servers</h2>
  </header>
  <div class="card-body">
    <p id="pdl_install_intro">
      Willkommen! Dieser Assistent richtet PowerDownload in fünf Schritten ein. Er legt die Tabellen an,
      erstellt Ihr Administrator-Konto und speichert die Zugangsdaten in <code>pdl-inc/pdl_config.local.php</code>.
      Zuerst prüft er, ob Ihr Server alle Voraussetzungen erfüllt.
    </p>

    <?php echo Html::alert($message, 'danger', 'pdl_install_message'); ?>

    <ul class="list-group mb-3" id="pdl_install_checks">
<?php
    foreach ($checks as $check) {
        $badge = match (true) {
            $check['kind'] === Requirements::KIND_DEFERRED => '<span class="badge text-bg-info">folgt in Schritt 2</span>',
            $check['kind'] === Requirements::KIND_INFO => '<span class="badge text-bg-info">Info</span>',
            $check['ok'] => '<span class="badge text-bg-success">erfüllt</span>',
            $check['kind'] === Requirements::KIND_REQUIRED => '<span class="badge text-bg-danger">nicht erfüllt</span>',
            default => '<span class="badge text-bg-warning">Hinweis</span>',
        };
        $kindLabel = $check['kind'] === Requirements::KIND_REQUIRED || $check['kind'] === Requirements::KIND_DEFERRED ? 'Pflicht' : ($check['kind'] === Requirements::KIND_INFO ? 'Info' : 'Optional');
        ?>
      <li class="list-group-item d-flex justify-content-between align-items-start gap-3" id="pdl_install_check_<?php echo Html::escape($check['id']); ?>" data-ok="<?php echo $check['ok'] ? '1' : '0'; ?>" data-kind="<?php echo Html::escape($check['kind']); ?>">
        <div>
          <strong><?php echo Html::escape($check['label']); ?></strong>
          <span class="badge <?php echo $kindLabel === 'Pflicht' ? 'text-bg-secondary' : 'text-bg-light border'; ?> ms-1"><?php echo $kindLabel; ?></span>
          <div class="small text-body-secondary"><?php echo Html::escape($check['detail']); ?></div>
        </div>
        <?php echo $badge; ?>
      </li>
<?php
    }
    ?>
    </ul>
<?php if (!$allOk) { ?>
    <div class="alert alert-danger" role="alert" id="pdl_install_requirements_failed">
      Bitte beheben Sie die rot markierten Punkte. Danach
      <a class="alert-link" href="install.php?step=1" id="pdl_install_requirements_reload">prüfen Sie erneut</a>.
    </div>
<?php } ?>
  </div>
  <footer class="card-footer d-flex justify-content-end">
    <form method="post" action="install.php?step=1" id="pdl_install_form_requirements">
      <?php echo Html::csrfField($csrf); ?>
      <button type="submit" name="action" value="requirements" class="btn btn-primary" id="pdl_install_btn_requirements"<?php echo $allOk ? '' : ' disabled'; ?>>
        Weiter zur Datenbank
      </button>
    </form>
  </footer>
</section>
<?php
};
