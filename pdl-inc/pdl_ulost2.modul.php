<?php
/**
 * PowerDownload - Password Lost Module (Step 2)
 * @license MIT
 */

$remind_code = $db_handler->sql_escape_string($remind_code ?? '');
$getuser = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE remind_code='" . $remind_code . "' AND remind_code != ''"));

if ($getuser) {
    $pw_new = generate_string(16);
    $pw_hash = password_hash($pw_new, PASSWORD_DEFAULT);
    $user_id_safe = $db_handler->sql_escape_int($getuser['user_id'] ?? 0);
    $pw_hash_safe = $db_handler->sql_escape_string($pw_hash);
    $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET passwort='" . $pw_hash_safe . "', remind_code='' WHERE user_id='" . $user_id_safe . "'");

    $message = str_replace("{user}", $getuser['nick'] ?? '', (string) ($template['mail_lost2'] ?? ''));
    $message = str_replace("{new_pw}", $pw_new, $message);

    $from_name = $settings['mail_fromname'] ?? 'PowerDownload';
    $from_addr = $settings['mail_fromaddr'] ?? 'noreply@example.com';
    @mail($getuser['email'] ?? '', "Accountdaten", $message, "FROM: " . $from_name . " <" . $from_addr . ">");
    echo "<center><b>Accountdaten versendet.</b></center>";
} else {
    echo "<center><b>Kein Benutzer mit diesem Bestaetigungscode gefunden.</b></center>";
}
