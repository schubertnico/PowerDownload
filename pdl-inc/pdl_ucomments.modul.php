<?php
/**
 * PowerDownload - User Comments Module
 *
 * Speichert einen Kommentar (Formular auf der Release-Seite oder unter
 * usercenter=comments) und zeigt bei Bedarf das Formular erneut an.
 *
 * Ablauf beim Absenden: pdl_header.inc.php bindet dieses Modul vor jeder
 * Ausgabe ein ($pdl_prerender = true). Nach dem Speichern geht es per
 * Weiterleitung zurück zum Release (release_id=N&commented=1#pdlComment<ID>,
 * Post/Redirect/Get); die Release-Seite zeigt dort #pdlCommentSaved. Bei
 * Fehlern puffert der Header die Ausgabe in $pdl_ucomments_output, das
 * reguläre Einbinden durch pdl_downloads.inc.php gibt sie dann aus.
 * Ohne Header (z. B. in Tests) arbeitet das Modul in einem Schritt.
 *
 * @license MIT
 */

/**
 * Werte aus dem einbindenden Skript (Header-Vorabverarbeitung,
 * pdl_downloads.inc.php oder Tests); je nach Aufrufer gesetzt oder nicht.
 *
 * @var bool|null $pdl_prerender nur vom Header gesetzt
 * @var string|null $pdl_ucomments_output gepufferte Ausgabe der Vorabverarbeitung
 * @var mixed $submit
 * @var mixed $titel
 * @var mixed $ip
 */
require_once __DIR__ . '/pdl_locks.inc.php';

$pdl_comment_prerender = ($pdl_prerender ?? false) && !headers_sent();

if (!$pdl_comment_prerender && isset($pdl_ucomments_output)) {
    echo $pdl_ucomments_output;
    $pdl_ucomments_output = null;
    return;
}

$script_file_raw = (string) ($settings['script_file'] ?? '');
$script_file = htmlspecialchars($script_file_raw, ENT_QUOTES, 'UTF-8');
$release_id = (int)($release_id ?? 0);
$back_query = htmlspecialchars(pdl_back_release_query($release_id), ENT_QUOTES, 'UTF-8');

if (($settings['enable_comments'] ?? 'N') != "Y") {
    echo pdl_alert('warning', 'Kommentare sind auf dieser Seite ausgeschaltet.');
    return;
}

if (!($user_details ?? null)) {
    echo pdl_alert('info', 'Bitte <a href="' . $script_file . 'usercenter=login' . $back_query . '" class="alert-link">melden Sie sich an</a>'
        . ' oder <a href="' . $script_file . 'usercenter=register" class="alert-link">registrieren Sie sich</a>, um einen Kommentar zu schreiben.');
    return;
}

if (($user_rights['addcomments'] ?? 'N') != "Y") {
    echo pdl_alert('warning', 'Ihre Benutzergruppe darf keine Kommentare schreiben.');
    return;
}

$release_id_safe = $db_handler->sql_escape_int($release_id);
$comment_release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT release_id, name FROM " . $sql_table['release'] . " WHERE release_id='" . $release_id_safe . "' AND released='Y'"
));
if ($comment_release === null) {
    echo pdl_alert('warning', 'Dieses Release gibt es nicht oder es ist nicht öffentlich.');
    return;
}

$submit = (int)($submit ?? 0);
$errors = [];
$titel_raw = trim((string) ($titel ?? ''));
$text_raw = trim((string) ($text ?? ''));

if ($submit == 1) {
    if (!csrf_verify($csrf_token ?? null)) {
        $errors[] = "Die Sitzung ist abgelaufen. Bitte senden Sie das Formular erneut ab.";
    }
    if ($titel_raw === '' || $text_raw === '') {
        $errors[] = "Bitte geben Sie Titel und Text ein.";
    }
    if (mb_strlen($titel_raw, 'UTF-8') > 128) {
        $errors[] = "Der Titel darf höchstens 128 Zeichen lang sein.";
    }
    if (mb_strlen($text_raw, 'UTF-8') > 5000) {
        $errors[] = "Der Kommentar darf höchstens 5.000 Zeichen lang sein.";
    }

    $comment_ip = (string) ($ip ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
    $comment_user_id = (int) ($user_details['user_id'] ?? 0);
    if (!$errors) {
        // Schutz vor Doppelposts: eine Minute Pause je Konto (nicht je IP,
        // damit Mitglieder im selben WLAN nicht aufeinander warten müssen).
        // Abgelaufene Einträge räumt der Header auf.
        if (pdl_lock_exists($db_handler, $sql_table, 'comment', 0, $comment_user_id, $comment_ip, time() - 60)) {
            $errors[] = "Bitte warten Sie eine Minute, bevor Sie den nächsten Kommentar schreiben.";
        }
    }

    if (!$errors) {
        $user_id_safe = $db_handler->sql_escape_int($comment_user_id);
        $titel_safe = $db_handler->sql_escape_string($titel_raw);
        $text_safe = $db_handler->sql_escape_string($text_raw);
        $saved = $db_handler->sql_query("INSERT INTO " . $sql_table['comments'] . " (user_id,release_id,titel,text,time) VALUES ('" . $user_id_safe . "','" . $release_id_safe . "','" . $titel_safe . "','" . $text_safe . "','" . time() . "')");
        if ($saved !== false) {
            $comment_id = (int) $db_handler->sql_insert_id();
            pdl_lock_add($db_handler, $sql_table, 'comment', $release_id, $comment_user_id, $comment_ip);

            // Zurück zum Release, direkt zum neuen Kommentar. Die Meldung
            // #pdlCommentSaved zeigt pdl_release.modul.php (Sitzung + commented=1).
            $_SESSION['pdl_comment_flash'] = ['release_id' => $release_id, 'comment_id' => $comment_id];
            $comment_target = $script_file_raw . 'release_id=' . $release_id . '&commented=1'
                . ($comment_id > 0 ? '#pdlComment' . $comment_id : '#pdlComments');
            if ($pdl_comment_prerender) {
                header('Location: ' . $comment_target);
                exit;
            }
            echo '<div id="pdlCommentSaved">' . pdl_alert('success', '<strong>Ihr Kommentar wurde veröffentlicht.</strong> '
                . '<a href="' . htmlspecialchars($comment_target, ENT_QUOTES, 'UTF-8') . '" class="alert-link">Zum Kommentar</a>') . '</div>';
            return;
        }
        $errors[] = "Der Kommentar konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.";
    }
}

if ($errors) {
    $err_html = '<strong>Bitte prüfen Sie Ihre Eingaben:</strong><ul class="mb-0 mt-2">';
    foreach ($errors as $err) {
        $err_html .= '<li>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $err_html .= '</ul>';
    echo pdl_alert('danger', $err_html);
}

echo '<section class="card pdl-card mx-auto" style="max-width: 720px;" id="pdlCommentPage">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Kommentar zu „' . htmlspecialchars(stripslashes((string) ($comment_release['name'] ?? '')), ENT_QUOTES, 'UTF-8') . '“</h2></header>';
echo '<div class="card-body">';
echo pdl_comment_form($release_id, (string) ($user_details['nick'] ?? ''), $titel_raw, $text_raw);
echo '<p class="small mt-3 mb-0"><a href="' . $script_file . 'release_id=' . $release_id . '">Zurück zum Release</a></p>';
echo '</div></section>';
