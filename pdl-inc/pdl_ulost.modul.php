<?php
/**
 * PowerDownload - Password Lost Module (Step 1)
 * @license MIT
 */

$submit = (int)($submit ?? 0);

if ($submit == 1) {
    $email = $db_handler->sql_escape_string($email ?? '');
    $getuser = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE email='" . $email . "'"));

    if ($getuser) {
        $remind_code = bin2hex(random_bytes(16));
        $user_id_safe = $db_handler->sql_escape_int($getuser['user_id'] ?? 0);
        $remind_code_safe = $db_handler->sql_escape_string($remind_code);
        $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET remind_code='" . $remind_code_safe . "' WHERE user_id='" . $user_id_safe . "'");

        $message = str_replace("{user}", $getuser['nick'] ?? '', $template['mail_lost1'] ?? '');
        $message = str_replace("{url}", ($settings['script_file'] ?? '') . "usercenter=lost2&remind_code=" . $remind_code, $message);

        $from_name = $settings['mail_fromname'] ?? 'PowerDownload';
        $from_addr = $settings['mail_fromaddr'] ?? 'noreply@example.com';
        mail($getuser['email'] ?? '', "Password Bestaetigungscode", $message, "FROM: " . $from_name . " <" . $from_addr . ">");
        echo "<center><b>Bestaetigungsmail versendet.</b></center>";
    } else {
        echo "<center><b>Kein Benutzer mit dieser E-Mail Adresse gefunden.</b></center>";
    }
} else {
    $script_file = htmlspecialchars($settings['script_file'] ?? '');
    echo "
    <form name=\"lost\" method=\"post\" action=\"" . $script_file . "usercenter=lost&submit=1\">
    " . replace($template['ulost_form'] ?? '', []) . "
    </form>";
}
