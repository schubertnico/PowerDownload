<?php
/**
 * PowerDownload - User Profile Module
 * @license MIT
 */

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '');

if (!($user_details ?? null)) {
    echo "<center>Sie müssen eingeloggt sein um Ihr Profil zu bearbeiten.</center>";
    return;
}

$errors = [];

if ($submit == 1) {
    if (!csrf_verify($csrf_token ?? null)) {
        $errors[] = "Sicherheits-Token ungültig.";
    }

    $pw_old_raw = (string) ($pw_old ?? '');
    $pw_new_raw = (string) ($pw_new ?? '');
    $pw_new2_raw = (string) ($pw_new2 ?? '');
    $email_raw = (string) ($email ?? '');
    $homepage_raw = (string) ($homepage ?? '');
    $icq_raw = (int) ($icq ?? 0);
    $get_letter_raw = ($get_letter ?? '') == "Y" ? "Y" : "N";

    if ($email_raw === '' || !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Die E-Mail-Adresse ist ungültig.";
    }

    // Altes Passwort prüfen (bcrypt oder legacy MD5)
    $stored_password = (string) ($user_details['passwort'] ?? '');
    $password_valid = false;
    $info = password_get_info($stored_password);
    if (($info['algo'] ?? null) !== null && ($info['algo'] ?? 0) !== 0) {
        $password_valid = password_verify($pw_old_raw, $stored_password);
    } elseif (strlen($stored_password) === 32 && ctype_xdigit($stored_password)) {
        $password_valid = hash_equals($stored_password, md5($pw_old_raw));
    }

    if (!$password_valid) {
        $errors[] = "Altes Passwort ist falsch.";
    }

    if ($pw_new_raw !== '' && $pw_new_raw !== $pw_new2_raw) {
        $errors[] = "Neues Passwort stimmt nicht mit der Bestätigung überein.";
    }

    if ($pw_new_raw !== '' && strlen($pw_new_raw) < 8) {
        $errors[] = "Neues Passwort muss mindestens 8 Zeichen lang sein.";
    }

    if (!$errors) {
        $homepage_normalized = '';
        if ($homepage_raw !== '') {
            $candidate = $homepage_raw;
            if (!preg_match("!^https?://!i", $candidate)) {
                $candidate = "http://" . $candidate;
            }
            if (filter_var($candidate, FILTER_VALIDATE_URL) && preg_match("!^https?://!i", $candidate)) {
                $homepage_normalized = $candidate;
            }
        }

        $email_safe = $db_handler->sql_escape_string($email_raw);
        $homepage_safe = $db_handler->sql_escape_string($homepage_normalized);
        $user_id_safe = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);

        if ($pw_new_raw !== '') {
            $pw_hash = password_hash($pw_new_raw, PASSWORD_DEFAULT);
            $pw_hash_safe = $db_handler->sql_escape_string($pw_hash);
            $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET email='" . $email_safe . "', get_letter='" . $get_letter_raw . "', homepage='" . $homepage_safe . "', icq='" . (int) $icq_raw . "', passwort='" . $pw_hash_safe . "', session_token='' WHERE user_id='" . $user_id_safe . "'");
            echo '<center><b>Profil erfolgreich geändert. Da das Passwort geändert wurde, bitte erneut <a href="' . $script_file . 'usercenter=login">Einloggen</a>.</b></center>';
            return;
        }
        $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET email='" . $email_safe . "', get_letter='" . $get_letter_raw . "', homepage='" . $homepage_safe . "', icq='" . (int) $icq_raw . "' WHERE user_id='" . $user_id_safe . "'");
        echo "<center><b>Profil erfolgreich geändert.</b></center>";
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

$form = (string) ($template['uprofil_form'] ?? '');
$form = str_replace("{email}", htmlspecialchars($user_details['email'] ?? '', ENT_QUOTES, 'UTF-8'), $form);
$checked_val = ($user_details['get_letter'] ?? '') == "Y" ? " checked" : "";
$form = str_replace("{get_letter}", $checked_val, $form);
$form = str_replace("{homepage}", htmlspecialchars($user_details['homepage'] ?? '', ENT_QUOTES, 'UTF-8'), $form);
$icq_val = (int)($user_details['icq'] ?? 0);
$form = str_replace("{icq}", $icq_val > 0 ? (string)$icq_val : "", $form);

echo '<form action="' . $script_file . 'usercenter=profil&submit=1" method="post">' . csrf_input() . replace($form, []) . '</form>';
