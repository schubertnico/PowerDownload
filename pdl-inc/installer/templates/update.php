<?php

/**
 * PowerDownload - update.php: Vorschau, Ausführung und Protokoll
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\Html;
use PowerDownload\Installer\ServerVersion;
use PowerDownload\Installer\Updater;
use PowerDownload\LocalConfig;

/**
 * @param array{actions: list<array{kind: string, table: string, label: string, queries: list<array{sql: string, params: list<string|null>}>}>, kept: list<array{table: string, key: string, column: string}>, notes: list<string>} $plan
 * @param list<array{kind: string, label: string, ok: bool, message: string}>|null $log Protokoll des letzten Laufs
 */
return static function (
    string $csrf,
    string $message,
    string $version,
    ?ServerVersion $server,
    string $configSource,
    array $plan,
    ?array $log,
    bool $installerPresent,
): void {
    $sourceText = match ($configSource) {
        LocalConfig::SOURCE_FILE => LocalConfig::RELATIVE_PATH . ' (bleibt bei künftigen Updates erhalten)',
        LocalConfig::SOURCE_ENVIRONMENT => 'Umgebungsvariablen PDL_DB_*',
        default => 'keine',
    };
    $groups = [];

    foreach ($plan['actions'] as $action) {
        $groups[$action['kind']][] = $action['label'];
    }

    $keptTemplates = [];
    $keptLabels = [];

    foreach ($plan['kept'] as $entry) {
        if ($entry['table'] === 'pdl3_template' && $entry['column'] === 'wert') {
            $keptTemplates[] = $entry['key'];
        } else {
            $keptLabels[] = $entry['table'] . ': ' . $entry['key'] . ' (' . Updater::columnLabel($entry['column']) . ')';
        }
    }
    ?>
<p id="pdl_update_intro">
  Diese Seite bringt die Datenbank einer bestehenden PowerDownload-Installation auf den Stand der hochgeladenen Dateien.
  Sie ergänzt nur, was fehlt, und lässt sich gefahrlos wiederholen. Gelöscht wird nichts. Legen Sie vorher trotzdem eine Datensicherung an.
</p>

<?php echo Html::alert($message, 'danger', 'pdl_update_message'); ?>

<?php if ($log !== null) { ?>
<section class="card shadow-sm mb-4" id="pdl_update_log">
  <header class="card-header bg-secondary-subtle"><h2 class="h6 mb-0">Protokoll des Updates</h2></header>
  <ul class="list-group list-group-flush">
<?php if ($log === []) { ?>
    <li class="list-group-item">Es war nichts zu tun.</li>
<?php } ?>
<?php foreach ($log as $entry) { ?>
    <li class="list-group-item d-flex justify-content-between gap-3" data-ok="<?php echo $entry['ok'] ? '1' : '0'; ?>">
      <div><?php echo Html::escape($entry['label']); ?><?php if (!$entry['ok']) { ?><div class="small text-danger"><?php echo Html::escape($entry['message']); ?></div><?php } ?></div>
<?php if ($entry['kind'] === 'note') { ?>
      <span class="badge text-bg-info align-self-start">Hinweis</span>
<?php } else { ?>
      <span class="badge <?php echo $entry['ok'] ? 'text-bg-success' : 'text-bg-danger'; ?> align-self-start"><?php echo $entry['ok'] ? 'erledigt' : 'fehlgeschlagen'; ?></span>
<?php } ?>
    </li>
<?php } ?>
  </ul>
</section>
<?php } ?>

<section class="card shadow-sm mb-4" id="pdl_update_status">
  <header class="card-header bg-secondary-subtle"><h2 class="h6 mb-0">Installation</h2></header>
  <dl class="card-body row mb-0 small">
    <dt class="col-sm-4">Programmversion (Dateien)</dt>
    <dd class="col-sm-8" id="pdl_update_version"><?php echo Html::escape($version); ?></dd>
    <dt class="col-sm-4">Datenbankserver</dt>
    <dd class="col-sm-8" id="pdl_update_dbserver">
<?php if ($server === null) { ?>
      unbekannt
<?php } else { ?>
      <?php echo Html::escape($server->label()); ?>
      <span class="badge <?php echo $server->isSupported() ? 'text-bg-success' : 'text-bg-danger'; ?>"><?php echo $server->isSupported() ? 'geeignet' : 'zu alt, benötigt wird ' . Html::escape(ServerVersion::requirement()); ?></span>
<?php } ?>
    </dd>
    <dt class="col-sm-4">Zugangsdaten aus</dt>
    <dd class="col-sm-8 mb-0" id="pdl_update_config_source"><?php echo Html::escape($sourceText); ?></dd>
  </dl>
</section>

<section class="card shadow-sm mb-4" id="pdl_update_plan">
  <header class="card-header bg-secondary-subtle"><h2 class="h6 mb-0">Abgleich mit dem Schema</h2></header>
  <div class="card-body">
<?php if ($plan['actions'] === []) { ?>
    <p class="text-success fw-semibold mb-0" id="pdl_update_nothing">Die Datenbank ist auf dem aktuellen Stand. Es ist nichts zu tun.</p>
<?php } else { ?>
    <p class="fw-semibold">Folgendes wird ergänzt bzw. aktualisiert:</p>
    <div id="pdl_update_preview">
<?php foreach (Updater::KIND_LABELS as $kind => $heading) { ?>
<?php if (isset($groups[$kind])) { ?>
      <h3 class="h6 mt-3"><?php echo Html::escape($heading); ?> <span class="badge text-bg-secondary"><?php echo count($groups[$kind]); ?></span></h3>
      <ul class="small mb-2" id="pdl_update_preview_<?php echo Html::escape($kind); ?>">
<?php foreach ($groups[$kind] as $label) { ?>
        <li><?php echo Html::escape($label); ?></li>
<?php } ?>
      </ul>
<?php } ?>
<?php } ?>
    </div>
<?php } ?>

<?php foreach ($plan['notes'] as $index => $note) { ?>
    <div class="alert alert-warning small mt-3 mb-0" id="pdl_update_note_<?php echo (int) $index; ?>"><?php echo Html::escape($note); ?></div>
<?php } ?>
<?php if ($keptTemplates !== []) { ?>
    <div class="alert alert-info small mt-3 mb-0" id="pdl_update_kept_templates">
      <strong>Diese Vorlagen haben Sie angepasst. Sie bleiben unverändert:</strong>
      <?php echo Html::escape(implode(', ', $keptTemplates)); ?>.
      Die neue Fassung finden Sie in <code>pdl-inc/pdl3_schema.sql</code>; übernehmen Sie Änderungen bei Bedarf von Hand im Adminbereich.
    </div>
<?php } ?>
<?php if ($keptLabels !== []) { ?>
    <p class="small text-body-secondary mt-3 mb-0" id="pdl_update_kept_labels">
      Eigene Beschriftungen bleiben ebenfalls erhalten: <?php echo Html::escape(implode('; ', $keptLabels)); ?>.
    </p>
<?php } ?>
  </div>
  <footer class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="pdl-admin/" id="pdl_update_link_admin">Zum Adminbereich</a>
<?php if ($plan['actions'] !== []) { ?>
    <form method="post" action="update.php" id="pdl_update_form">
      <?php echo Html::csrfField($csrf); ?>
      <button type="submit" class="btn btn-primary" id="pdl_update_btn_run">Jetzt aktualisieren</button>
    </form>
<?php } ?>
  </footer>
</section>

<?php if ($installerPresent) { ?>
<div class="alert alert-warning small" role="alert" id="pdl_update_installer_present">
  <code>install.php</code> liegt noch auf dem Server. Für ein Update wird die Datei nicht gebraucht, bitte löschen Sie sie.
</div>
<?php } ?>
<?php
};
