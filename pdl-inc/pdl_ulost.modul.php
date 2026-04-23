<?php
/**
 * PowerDownload - Password Lost Module (Step 1)
 * @license MIT
 */

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '');

if ($submit == 1) {
    if (!csrf_verify($csrf_token ?? null)) {
        echo '<center><b style="color:#c00">Sicherheits-Token ungültig.</b></center>';
        return;
    }

    $email_raw = (string) ($email ?? '');
    $email_safe = $db_handler->sql_escape_string($email_raw);
    $getuser = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE email='" . $email_safe . "'"));

    if ($getuser) {
        $remind_code_new = bin2hex(random_bytes(16));
        $remind_expires = time() + 3600;
        $user_id_safe = $db_handler->sql_escape_int($getuser['user_id'] ?? 0);
        $remind_code_safe = $db_handler->sql_escape_string($remind_code_new);
        $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET remind_code='" . $remind_code_safe . "', remind_expires='" . $remind_expires . "' WHERE user_id='" . $user_id_safe . "'");

        $message = str_replace("{user}", $getuser['nick'] ?? '', (string) ($template['mail_lost1'] ?? ''));
        $message = str_replace("{url}", ($settings['script_file'] ?? '') . "usercenter=lost2&remind_code=" . $remind_code_new, $message);

        $from_name = $settings['mail_fromname'] ?? 'PowerDownload';
        $from_addr = $settings['mail_fromaddr'] ?? 'noreply@example.com';
        if (!mail($getuser['email'] ?? '', "Passwort-Bestaetigungscode", $message, "FROM: " . $from_name . " <" . $from_addr . ">")) {
            error_log("PowerDownload: Mailversand an " . ($getuser['email'] ?? '') . " fehlgeschlagen (Lost1).");
        }
    }
    // Generische Antwort – keine User-Enumeration (BUG-006)
    echo "<center><b>Wenn ein Konto mit dieser E-Mail existiert, wurde eine E-Mail mit weiteren Schritten versendet.</b></center>";
    return;
}

echo '<form name="lost" method="post" action="' . $script_file . 'usercenter=lost&submit=1">' . csrf_input() . replace($template['ulost_form'] ?? '', []) . '</form>';
