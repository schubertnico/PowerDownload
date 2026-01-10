<?php
/**
 * PowerDownload - User Registration Module
 * @license MIT
 */

$submit = (int)($submit ?? 0);

if ($submit == 1) {
    $nick = $db_handler->sql_escape_string($nick ?? '');
    $email = $db_handler->sql_escape_string($email ?? '');
    $pw_new = $pw_new ?? '';
    $pw_new2 = $pw_new2 ?? '';
    $homepage = $db_handler->sql_escape_string($homepage ?? '');
    $icq = $db_handler->sql_escape_int($icq ?? 0);
    $get_letter = ($get_letter ?? '') == "Y" ? "Y" : "N";

    if (!$nick) {
        echo "<center><b>Bitte geben sie einen Nickname an.<br><a href=\"javascript:history.back()\">Zurueck</a></b></center>";
    } elseif (!$email) {
        echo "<center><b>Bitte geben sie eine Email Adresse an.<br><a href=\"javascript:history.back()\">Zurueck</a></b></center>";
    } elseif (($pw_new != $pw_new2) || (!$pw_new || !$pw_new2)) {
        echo "<center><b>Es ist kein Passwort eingegeben oder es stimmt nicht mit der Bestaetigung ueberein.<br><a href=\"javascript:history.back()\">Zurueck</a></b></center>";
    } elseif ($db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE nick='" . $nick . "'")) > 0) {
        echo "<center><b>Es ist bereits ein User mit diesem Nick registriert. Doppelanmeldungen werden nicht geduldet.<br><a href=\"javascript:history.back()\">Zurueck</a></b></center>";
    } elseif ($db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE email='" . $email . "'")) > 0) {
        echo "<center><b>Es ist bereits ein User mit dieser Email Adresse registriert. Doppelanmeldungen werden nicht geduldet.<br><a href=\"javascript:history.back()\">Zurueck</a></b></center>";
    } else {
        if ($homepage && !preg_match("!^https?://!", $homepage)) {
            $homepage = "http://" . $homepage;
        }
        $pw_hash = password_hash($pw_new, PASSWORD_DEFAULT);
        $pw_hash_safe = $db_handler->sql_escape_string($pw_hash);
        $db_handler->sql_query("INSERT INTO " . $sql_table['user'] . " (nick,email,passwort,homepage,icq,get_letter,ugroup_id,lastactive) VALUES ('" . $nick . "','" . $email . "','" . $pw_hash_safe . "','" . $homepage . "','" . $icq . "','" . $get_letter . "','2','" . time() . "')");

        $script_file = htmlspecialchars($settings['script_file'] ?? '');
        echo "<center><b>Anmeldung erfolgreich. Sie koennen sich nun mit den Daten <a href=\"" . $script_file . "usercenter=login\">Einloggen</a>. Sie erhalten auch eine Bestaetigung per Email.</b></center>";

        $message = str_replace("{nick}", $nick, $template['mail_register'] ?? '');
        $message = str_replace("{pw}", $pw_new, $message);
        $message = str_replace("{script_file}", $settings['script_file'] ?? '', $message);

        $from_name = $settings['mail_fromname'] ?? 'PowerDownload';
        $from_addr = $settings['mail_fromaddr'] ?? 'noreply@example.com';
        mail($email, "Anmeldung", $message, "FROM: " . $from_name . " <" . $from_addr . ">");
    }
} else {
    if ($user_details ?? null) {
        echo "<center><b>Sie sind bereits angemeldet und eingeloggt. Doppelanmeldungen werden nicht geduldet.</b></center>";
    } else {
        echo "<form action=\"" . htmlspecialchars($settings['script_file'] ?? '') . "usercenter=register&submit=1\" method=\"post\">" . replace($template['uregister_form'] ?? '', []) . "</form>";
    }
}
