<?php
/**
 * PowerDownload - Password Lost Module (Step 2)
 * @license MIT
 *
 * User setzt das neue Passwort selbst über ein Formular.
 * remind_code ist 60 Minuten gültig.
 */

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '');
$remind_code_raw = (string) ($remind_code ?? '');

if ($remind_code_raw === '') {
    echo pdl_alert('danger', '<strong>Ungültiger oder abgelaufener Code.</strong>');
    return;
}

$remind_code_safe = $db_handler->sql_escape_string($remind_code_raw);
$now = time();
$getuser = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT * FROM " . $sql_table['user'] . " WHERE remind_code='" . $remind_code_safe . "' AND remind_code != '' AND remind_expires > " . (int) $now
));

if (!$getuser) {
    echo pdl_alert('danger', '<strong>Ungültiger oder abgelaufener Code.</strong>');
    return;
}

$errors = [];
if ($submit == 1) {
    if (!csrf_verify($csrf_token ?? null)) {
        $errors[] = "Sicherheits-Token ungültig.";
    }
    $pw_new_raw = (string) ($pw_new ?? '');
    $pw_new2_raw = (string) ($pw_new2 ?? '');
    if ($pw_new_raw === '' || $pw_new_raw !== $pw_new2_raw) {
        $errors[] = "Passwort und Bestätigung stimmen nicht überein.";
    } elseif (strlen($pw_new_raw) < 8) {
        $errors[] = "Passwort muss mindestens 8 Zeichen lang sein.";
    }

    if (!$errors) {
        $pw_hash = password_hash($pw_new_raw, PASSWORD_DEFAULT);
        $pw_hash_safe = $db_handler->sql_escape_string($pw_hash);
        $user_id_safe = $db_handler->sql_escape_int($getuser['user_id'] ?? 0);
        $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET passwort='" . $pw_hash_safe . "', remind_code='', remind_expires=0, session_token='' WHERE user_id='" . $user_id_safe . "'");

        $message = str_replace("{user}", $getuser['nick'] ?? '', (string) ($template['mail_lost2'] ?? ''));
        $message = str_replace("{new_pw}", "(Sie haben Ihr Passwort selbst gesetzt.)", $message);

        $from_name = $settings['mail_fromname'] ?? 'PowerDownload';
        $from_addr = $settings['mail_fromaddr'] ?? 'noreply@example.com';
        if (!mail($getuser['email'] ?? '', "Passwort geändert", $message, "FROM: " . $from_name . " <" . $from_addr . ">")) {
            error_log("PowerDownload: Mailversand an " . ($getuser['email'] ?? '') . " fehlgeschlagen (Lost2).");
        }

        echo pdl_alert('success', '<strong>Passwort erfolgreich gesetzt.</strong> Bitte <a href="' . $script_file . 'usercenter=login" class="alert-link">einloggen</a>.');
        return;
    }
}

if ($errors) {
    $err_html = '<strong>Bitte prüfen Sie die folgenden Eingaben:</strong><ul class="mb-0 mt-2">';
    foreach ($errors as $err) {
        $err_html .= '<li>' . htmlspecialchars($err) . '</li>';
    }
    $err_html .= '</ul>';
    echo pdl_alert('danger', $err_html);
}

$remind_code_html = htmlspecialchars($remind_code_raw, ENT_QUOTES, 'UTF-8');
?>
<section class="card pdl-card mx-auto" style="max-width: 540px;">
    <header class="card-header pdl-card-header"><h2 class="h5 mb-0">Neues Passwort setzen</h2></header>
    <div class="card-body">
        <form method="post" action="downloads.php" novalidate>
            <?php echo csrf_input(); ?>
            <input type="hidden" name="usercenter" value="lost2">
            <input type="hidden" name="submit" value="1">
            <input type="hidden" name="remind_code" value="<?php echo $remind_code_html; ?>">
            <div class="mb-3">
                <label for="pdlPwNew" class="form-label">Neues Passwort</label>
                <input type="password" id="pdlPwNew" name="pw_new" class="form-control" required minlength="8" aria-describedby="pdlPwNewHelp">
                <div id="pdlPwNewHelp" class="form-text">Mindestens 8 Zeichen.</div>
            </div>
            <div class="mb-3">
                <label for="pdlPwNew2" class="form-label">Passwort-Bestätigung</label>
                <input type="password" id="pdlPwNew2" name="pw_new2" class="form-control" required minlength="8">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Passwort setzen</button>
            </div>
        </form>
    </div>
</section>
