<?php
/**
 * PowerDownload - User Profile Module
 * @license MIT
 */

$submit = (int)($submit ?? 0);

if ($submit == 1) {
    $pw_old = $pw_old ?? '';
    $pw_new = $pw_new ?? '';
    $pw_new2 = $pw_new2 ?? '';
    $email = $db_handler->sql_escape_string($email ?? '');
    $homepage = $db_handler->sql_escape_string($homepage ?? '');
    $icq = $db_handler->sql_escape_int($icq ?? 0);
    $get_letter = ($get_letter ?? '') == "Y" ? "Y" : "N";

    // Check old password - support both MD5 (legacy) and password_hash
    $stored_password = $user_details['passwort'] ?? '';
    $password_valid = false;
    if (password_get_info($stored_password)['algo'] !== null && password_get_info($stored_password)['algo'] !== 0) {
        $password_valid = password_verify($pw_old, $stored_password);
    } else {
        $password_valid = (md5($pw_old) === $stored_password);
    }

    if ($password_valid) {
        if ($pw_new) {
            if ($pw_new == $pw_new2) {
                $pw_hash = password_hash($pw_new, PASSWORD_DEFAULT);
                $pw_hash_safe = $db_handler->sql_escape_string($pw_hash);
                if ($homepage && !preg_match("!^https?://!", $homepage)) {
                    $homepage = "http://" . $homepage;
                }
                $user_id_safe = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);
                $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET email='" . $email . "', get_letter='" . $get_letter . "', homepage='" . $homepage . "', icq='" . $icq . "', passwort='" . $pw_hash_safe . "' WHERE user_id='" . $user_id_safe . "'");
                echo "<center><b>Profil erfolgreich geaendert. Da das Passwort geaendert wurde muessen sie sich neu <a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "usercenter=login\">Einloggen</a>.</b></center>";
            } else {
                echo "<center><b>Neues Passwort stimmt nicht mit der Bestaetigung ueberein.</b></center>";
            }
        } else {
            if ($homepage && !preg_match("!^https?://!", $homepage)) {
                $homepage = "http://" . $homepage;
            }
            $user_id_safe = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);
            $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET email='" . $email . "', get_letter='" . $get_letter . "', homepage='" . $homepage . "', icq='" . $icq . "' WHERE user_id='" . $user_id_safe . "'");
            echo "<center><b>Profil erfolgreich geaendert.</b></center>";
        }
    } else {
        echo "<center><b>Altes Passwort ist falsch.</b></center>";
    }
} else {
    if (!($user_details ?? null)) {
        echo "<center>Sie muessen eingeloggt sein um Ihr Profil zu bearbeiten.</center>";
    } else {
        $form = str_replace("{email}", htmlspecialchars($user_details['email'] ?? ''), (string) ($template['uprofil_form'] ?? ''));
        $get_letter = ($user_details['get_letter'] ?? '') == "Y" ? " checked" : "";
        $form = str_replace("{get_letter}", $get_letter, $form);
        $form = str_replace("{homepage}", htmlspecialchars($user_details['homepage'] ?? ''), $form);
        $icq = (int)($user_details['icq'] ?? 0);
        $form = str_replace("{icq}", $icq > 0 ? (string)$icq : "", $form);
        echo "<form action=\"" . htmlspecialchars($settings['script_file'] ?? '') . "usercenter=profil&submit=1\" method=\"post\">" . replace($form, []) . "</form>";
    }
}
