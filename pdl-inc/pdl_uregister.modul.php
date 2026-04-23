<?php
/**
 * PowerDownload - User Registration Module
 * @license MIT
 */

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '');
$errors = [];
$values = [
    'nick' => '',
    'email' => '',
    'homepage' => '',
    'icq' => '',
    'get_letter' => 'Y',
];

if ($user_details ?? null) {
    echo "<center><b>Sie sind bereits angemeldet und eingeloggt. Doppelanmeldungen werden nicht geduldet.</b></center>";
    return;
}

if ($submit == 1) {
    // CSRF
    if (!csrf_verify($csrf_token ?? null)) {
        $errors[] = "Sicherheits-Token ungültig. Bitte Formular erneut öffnen.";
    }

    $nick_raw = (string) ($_POST['nick'] ?? '');
    $email_raw = (string) ($email ?? '');
    $pw_new_raw = (string) ($pw_new ?? '');
    $pw_new2_raw = (string) ($pw_new2 ?? '');
    $homepage_raw = (string) ($homepage ?? '');
    $icq_raw = (int) ($icq ?? 0);
    $get_letter_raw = ($get_letter ?? '') == "Y" ? "Y" : "N";

    $values['nick'] = $nick_raw;
    $values['email'] = $email_raw;
    $values['homepage'] = $homepage_raw;
    $values['icq'] = $icq_raw > 0 ? (string) $icq_raw : '';
    $values['get_letter'] = $get_letter_raw;

    if ($nick_raw === '') {
        $errors[] = "Bitte geben Sie einen Nickname an.";
    }
    if ($email_raw === '') {
        $errors[] = "Bitte geben Sie eine E-Mail-Adresse an.";
    } elseif (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Die E-Mail-Adresse ist ungültig.";
    }
    if ($pw_new_raw === '' || $pw_new2_raw === '' || $pw_new_raw !== $pw_new2_raw) {
        $errors[] = "Passwort und Bestätigung stimmen nicht überein oder sind leer.";
    } elseif (strlen($pw_new_raw) < 8) {
        $errors[] = "Passwort muss mindestens 8 Zeichen lang sein.";
    }

    if (!$errors && $nick_raw !== '' && $email_raw !== '') {
        $nick_safe = $db_handler->sql_escape_string($nick_raw);
        $email_safe = $db_handler->sql_escape_string($email_raw);

        if ($db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE nick='" . $nick_safe . "'")) > 0) {
            $errors[] = "Es ist bereits ein Benutzer mit diesem Nickname registriert.";
        } elseif ($db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE email='" . $email_safe . "'")) > 0) {
            $errors[] = "Es ist bereits ein Benutzer mit dieser E-Mail-Adresse registriert.";
        }
    }

    if (!$errors) {
        // Homepage normalisieren & validieren
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

        $pw_hash = password_hash($pw_new_raw, PASSWORD_DEFAULT);
        $pw_hash_safe = $db_handler->sql_escape_string($pw_hash);
        $homepage_safe = $db_handler->sql_escape_string($homepage_normalized);

        $db_handler->sql_query("INSERT INTO " . $sql_table['user'] . " (nick,email,passwort,homepage,icq,get_letter,ugroup_id,lastactive) VALUES ('" . $nick_safe . "','" . $email_safe . "','" . $pw_hash_safe . "','" . $homepage_safe . "','" . (int) $icq_raw . "','" . $get_letter_raw . "','2','" . time() . "')");

        echo '<center><b>Anmeldung erfolgreich. Sie können sich nun <a href="' . $script_file . 'usercenter=login">Einloggen</a>. Eine Bestätigung wurde per E-Mail versendet.</b></center>';

        $message = str_replace("{nick}", $nick_raw, (string) ($template['mail_register'] ?? ''));
        $message = str_replace("{script_file}", $settings['script_file'] ?? '', $message);
        // Passwort wird NICHT mehr in der Mail versendet (BUG-016)
        $message = str_replace("{pw}", "(aus Sicherheitsgründen nicht mehr in der Mail enthalten)", $message);

        $from_name = $settings['mail_fromname'] ?? 'PowerDownload';
        $from_addr = $settings['mail_fromaddr'] ?? 'noreply@example.com';
        if (!mail($email_raw, "Anmeldung", $message, "FROM: " . $from_name . " <" . $from_addr . ">")) {
            error_log("PowerDownload: Mailversand an " . $email_raw . " fehlgeschlagen (Register).");
        }
        return;
    }
}

// Formular rendern (ggf. mit Fehler + vorbefüllten Werten)
if ($errors) {
    echo '<div style="color:#c00;text-align:center;margin:10px"><ul style="display:inline-block;text-align:left">';
    foreach ($errors as $err) {
        echo "<li>" . htmlspecialchars($err) . "</li>";
    }
    echo '</ul></div>';
}

$form = (string) ($template['uregister_form'] ?? '');
// Werte in Form einsetzen (Fallback: Template enthält ggf. keine Platzhalter – dann ohne Vorbelegung)
$form = str_replace('{nick}', htmlspecialchars($values['nick'], ENT_QUOTES, 'UTF-8'), $form);
$form = str_replace('{email}', htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8'), $form);
$form = str_replace('{homepage}', htmlspecialchars($values['homepage'], ENT_QUOTES, 'UTF-8'), $form);
$form = str_replace('{icq}', htmlspecialchars($values['icq'], ENT_QUOTES, 'UTF-8'), $form);
$form = str_replace('{get_letter}', $values['get_letter'] === 'Y' ? ' checked' : '', $form);

echo '<form action="' . $script_file . 'usercenter=register&submit=1" method="post">' . csrf_input() . replace($form, []) . '</form>';
