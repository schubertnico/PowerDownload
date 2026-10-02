<?php
/**
 * PowerDownload - Sicherung einspielen
 *
 * Spielt eine mit „Sicherung erstellen“ erzeugte SQL-Datei ein und ersetzt
 * damit alle Daten von PowerDownload. Nur per POST mit CSRF-Token und
 * ausdrücklicher Bestätigung; fehlgeschlagene Anweisungen werden gezählt und
 * angezeigt.
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'backup')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('backup'));
    include("footer.inc.php");
    return;
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'System'],
    ['title' => 'Sicherung einspielen'],
]);
echo '<h1 class="h3 pdl-page-title">Sicherung einspielen</h1>';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $tmp = isset($_FILES['backup']['tmp_name']) && is_string($_FILES['backup']['tmp_name']) ? $_FILES['backup']['tmp_name'] : '';
    $upload_error = isset($_FILES['backup']['error']) && is_int($_FILES['backup']['error']) ? $_FILES['backup']['error'] : UPLOAD_ERR_NO_FILE;
    $error = '';
    if (!pdl_sys_csrf_ok()) {
        $error = pdl_sys_csrf_error_text();
    } elseif (pdl_sys_post('confirm') !== '1') {
        $error = 'Bitte bestätigen Sie, dass alle Daten durch den Stand der Sicherung ersetzt werden sollen.';
    } elseif ($upload_error === UPLOAD_ERR_INI_SIZE || $upload_error === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Die Datei ist größer als der Server beim Hochladen erlaubt (' . htmlspecialchars((string) ini_get('upload_max_filesize')) . '). Spielen Sie sie z. B. mit phpMyAdmin ein.';
    } elseif ($tmp === '' || !is_uploaded_file($tmp)) {
        $error = 'Bitte wählen Sie die Sicherungsdatei (.sql) aus.';
    } else {
        $sql = (string) file_get_contents($tmp);
        if (str_starts_with($sql, "\xEF\xBB\xBF")) {
            $sql = substr($sql, 3);
        }
        if (!pdl_sys_backup_is_powerdownload($sql)) {
            $error = 'Diese Datei ist keine Sicherung von PowerDownload (die erste Zeile muss mit „-- PowerDownload“ bzw. „# PowerDownload“ beginnen). Es wurde nichts eingespielt.';
        } else {
            set_time_limit(300);
            $result = pdl_sys_backup_import($db_handler, $sql);
            pdl_audit_log($db_handler, $sql_table, $user_details, 'restore', 'database', $result['ok']);
            if ($result['failed'] === 0) {
                echo pdl_admin_alert('success', '<strong>Die Sicherung wurde eingespielt.</strong> ' . pdl_sys_num($result['ok'])
                    . ' Anweisungen wurden ausgeführt. Bitte melden Sie sich neu an, falls PowerDownload Sie abmeldet.');
            } else {
                echo pdl_admin_alert('danger', '<strong>Die Sicherung wurde nur teilweise eingespielt.</strong> '
                    . pdl_sys_num($result['ok']) . ' von ' . pdl_sys_num($result['total']) . ' Anweisungen waren erfolgreich, '
                    . pdl_sys_num($result['failed']) . ' sind fehlgeschlagen. Erste Fehler:<ul class="mb-0">'
                    . implode('', array_map(static fn (string $e): string => '<li><code>' . htmlspecialchars($e) . '</code></li>', $result['errors']))
                    . '</ul>Prüfen Sie die Datei und spielen Sie bei Bedarf eine andere Sicherung ein.');
            }
            echo '<a class="btn btn-outline-light" href="index.php">Zur Übersicht</a>';
            include("footer.inc.php");
            return;
        }
    }
    echo pdl_admin_alert('danger', '<strong>Es wurde nichts eingespielt.</strong> ' . $error);
}
?>
<div class="alert alert-danger" role="alert" id="pdlRestoreWarning">
    <strong>Achtung:</strong> Beim Einspielen werden <strong>alle Daten von PowerDownload</strong> (Releases, Ordner, Kommentare, Benutzer, Einstellungen und Vorlagen) durch den Stand der Sicherung ersetzt.
    Was seit der Sicherung hinzugekommen ist, geht verloren. <a class="alert-link" href="backup.php">Erstellen Sie vorher eine aktuelle Sicherung.</a>
</div>
<form action="dobackup.php" method="post" enctype="multipart/form-data" id="pdlRestoreForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Sicherungsdatei auswählen</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlBackupFile" class="form-label">Sicherungsdatei (.sql)</label>
                <input type="file" id="pdlBackupFile" name="backup" class="form-control" accept=".sql" required style="max-width: 36rem" aria-describedby="pdlBackupFileHelp">
                <div class="form-text" id="pdlBackupFileHelp">Eine Datei, die Sie unter „Sicherung erstellen“ heruntergeladen haben. Größte erlaubte Dateigröße: <?php echo htmlspecialchars((string) ini_get('upload_max_filesize')); ?>. Dateien unter <code>pdl-files/</code> stellen Sie getrennt per FTP wieder her.</div>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="pdlBackupConfirm" name="confirm" value="1" required>
                <label class="form-check-label" for="pdlBackupConfirm">Ich weiß, dass alle Daten von PowerDownload durch den Stand der Sicherung ersetzt werden.</label>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-danger" id="pdlBackupImport">Sicherung einspielen</button>
    </div>
</form>
<?php
include("footer.inc.php");
