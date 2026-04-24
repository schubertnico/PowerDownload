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
    echo "<center><b>Ungültiger oder abgelaufener Code.</b></center>";
    return;
}

$remind_code_safe = $db_handler->sql_escape_string($remind_code_raw);
$now = time();
$getuser = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT * FROM " . $sql_table['user'] . " WHERE remind_code='" . $remind_code_safe . "' AND remind_code != '' AND remind_expires > " . (int) $now
));

if (!$getuser) {
    echo "<center><b>Ungültiger oder abgelaufener Code.</b></center>";
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

        echo '<center><b>Passwort erfolgreich gesetzt. Bitte <a href="' . $script_file . 'usercenter=login">einloggen</a>.</b></center>';
        return;
    }
}

if ($errors) {
    echo '<div style="color:#c00;text-align:center;margin:10px"><ul style="display:inline-block;text-align:left">';
    foreach ($errors as $err) {
        echo "<li>" . htmlspecialchars($err) . "</li>";
    }
    echo '</ul></div>';
}

$remind_code_html = htmlspecialchars($remind_code_raw, ENT_QUOTES, 'UTF-8');
echo '<center><h3>Neues Passwort setzen</h3></center>';
echo '<form method="post" action="downloads.php">';
echo csrf_input();
echo '<input type="hidden" name="usercenter" value="lost2">';
echo '<input type="hidden" name="submit" value="1">';
echo '<input type="hidden" name="remind_code" value="' . $remind_code_html . '">';
echo '<table align="center"><tr><td><label for="pw_new">Neues Passwort:</label></td><td><input type="password" id="pw_new" name="pw_new" required minlength="8"></td></tr>';
echo '<tr><td><label for="pw_new2">Bestätigung:</label></td><td><input type="password" id="pw_new2" name="pw_new2" required minlength="8"></td></tr>';
echo '<tr><td colspan="2" align="center"><button type="submit">Passwort setzen</button></td></tr></table></form>';
