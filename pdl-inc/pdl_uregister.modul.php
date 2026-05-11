<?php
/**
 * PowerDownload - User Registration Module
 * @license MIT
 */

include_once __DIR__ . '/pdl_captcha.inc.php';

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '');
$errors = [];
$values = [
    'nick' => '',
    'email' => '',
    'homepage' => '',
    'get_letter' => 'Y',
];

if ($user_details ?? null) {
    echo pdl_alert('warning', '<strong>Sie sind bereits angemeldet und eingeloggt.</strong> Doppelanmeldungen werden nicht geduldet.');
    return;
}

$pdl_reg_success_html = static function (string $script_file): string {
    $login_url = $script_file . 'usercenter=login';
    $profil_url = $script_file . 'usercenter=profil';
    $msg = '<strong>Anmeldung erfolgreich.</strong> Sie können sich nun einloggen. Eine Bestätigung wurde per E-Mail versendet.';
    $msg .= '<div class="mt-3 d-flex flex-wrap gap-2">';
    $msg .= '<a class="btn btn-primary btn-lg" href="' . $login_url . '">Jetzt einloggen</a>';
    $msg .= '<a class="btn btn-outline-secondary btn-lg" href="' . $profil_url . '">Zum Profil</a>';
    $msg .= '</div>';
    return $msg;
};

if ($submit == 1) {
    $ip_raw = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ip_safe = $db_handler->sql_escape_string($ip_raw);
    $rate_window = time() - 3600;
    $rate_res = $db_handler->sql_query("SELECT COUNT(*) AS c FROM " . $sql_table['iplock'] . " WHERE ip='" . $ip_safe . "' AND art='register' AND time>" . $rate_window);
    $rate_row = $db_handler->sql_fetch_array($rate_res);
    $rate_count = (int) ($rate_row['c'] ?? 0);
    $db_handler->sql_query("INSERT INTO " . $sql_table['iplock'] . " (ip,time,file_id,user_id,art) VALUES ('" . $ip_safe . "','" . time() . "',0,0,'register')");

    if (mt_rand(1, 100) === 1) {
        $db_handler->sql_query("DELETE FROM " . $sql_table['iplock'] . " WHERE time < '" . (time() - 7 * 24 * 3600) . "' AND art IN ('register','lostpw')");
    }

    if ($rate_count >= 5) {
        error_log('PDL register rate-limit exceeded from IP=' . $ip_raw);
        echo pdl_alert('success', $pdl_reg_success_html($script_file));
        return;
    }

    $hp_raw = (string) ($_POST['pdl_website'] ?? '');
    $ts_raw = (int) ($_POST['pdl_ts'] ?? 0);
    $is_bot = ($hp_raw !== '') || ($ts_raw > 0 && (time() - $ts_raw) < 3);

    if ($is_bot) {
        error_log('PDL spam register attempt from IP=' . ($_SERVER['REMOTE_ADDR'] ?? '?') . ' nick=' . substr((string)($_POST['nick'] ?? ''), 0, 50));
        echo pdl_alert('success', $pdl_reg_success_html($script_file));
        return;
    }

    if (!pdl_captcha_verify($settings)) {
        error_log('PDL captcha fail register from IP=' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        echo pdl_alert('success', $pdl_reg_success_html($script_file));
        return;
    }

    if (!csrf_verify($csrf_token ?? null)) {
        $errors[] = "Sicherheits-Token ungültig. Bitte Formular erneut öffnen.";
    }

    $nick_raw = (string) ($_POST['nick'] ?? '');
    $email_raw = (string) ($email ?? '');
    $pw_new_raw = (string) ($pw_new ?? '');
    $pw_new2_raw = (string) ($pw_new2 ?? '');
    $homepage_raw = (string) ($homepage ?? '');
    $get_letter_raw = ($get_letter ?? '') == "Y" ? "Y" : "N";

    $values['nick'] = $nick_raw;
    $values['email'] = $email_raw;
    $values['homepage'] = $homepage_raw;
    $values['get_letter'] = $get_letter_raw;

    if ($nick_raw === '') {
        $errors[] = "Bitte geben Sie einen Nickname an.";
    }
    if ($email_raw === '') {
        $errors[] = "Bitte geben Sie eine E-Mail-Adresse an.";
    } elseif (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Die E-Mail-Adresse ist ungültig.";
    }
    if ($pw_new_raw === '' || $pw_new2_raw === '') {
        $errors[] = "Bitte geben Sie ein Passwort und die Bestätigung ein.";
    } elseif ($pw_new_raw !== $pw_new2_raw) {
        $errors[] = "Passwort und Bestätigung stimmen nicht überein.";
    } elseif (strlen($pw_new_raw) < 8) {
        $errors[] = "Passwort muss mindestens 8 Zeichen lang sein.";
    } elseif (!preg_match('/[A-Za-z]/', $pw_new_raw) || !preg_match('/\d/', $pw_new_raw)) {
        $errors[] = "Passwort muss mindestens einen Buchstaben und eine Ziffer enthalten.";
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

        // Bug 1.1: Self-Service-Registrierungen erhalten Gast-Rechte (ugroup_id=1), nicht Admin (=2).
        $db_handler->sql_query("INSERT INTO " . $sql_table['user'] . " (nick,email,passwort,homepage,get_letter,ugroup_id,lastactive) VALUES ('" . $nick_safe . "','" . $email_safe . "','" . $pw_hash_safe . "','" . $homepage_safe . "','" . $get_letter_raw . "','1','" . time() . "')");

        echo pdl_alert('success', $pdl_reg_success_html($script_file));

        $message = str_replace("{nick}", $nick_raw, (string) ($template['mail_register'] ?? ''));
        $message = str_replace("{script_file}", $settings['script_file'] ?? '', $message);
        $message = str_replace("{pw}", "(aus Sicherheitsgründen nicht mehr in der Mail enthalten)", $message);

        $from_name = $settings['mail_fromname'] ?? 'PowerDownload';
        $from_addr = $settings['mail_fromaddr'] ?? 'noreply@example.com';
        if (!mail($email_raw, "Anmeldung", $message, "FROM: " . $from_name . " <" . $from_addr . ">")) {
            error_log("PowerDownload: Mailversand an " . $email_raw . " fehlgeschlagen (Register).");
        }
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

$nick_attr = htmlspecialchars($values['nick'], ENT_QUOTES, 'UTF-8');
$email_attr = htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8');
$homepage_attr = htmlspecialchars($values['homepage'], ENT_QUOTES, 'UTF-8');
$get_letter_checked = $values['get_letter'] === 'Y' ? ' checked' : '';

echo '<section class="card pdl-card mx-auto" style="max-width: 720px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Registrierung</h2></header>';
echo '<div class="card-body">';
echo '<form action="downloads.php" method="post">';
echo csrf_input();
echo '<input type="hidden" name="usercenter" value="register">';
echo '<input type="hidden" name="submit" value="1">';
echo '<input type="hidden" name="pdl_ts" value="' . time() . '">';

echo '<div class="visually-hidden" aria-hidden="true">';
echo '<label for="pdl_website">Website (bitte freilassen)</label>';
echo '<input type="text" id="pdl_website" name="pdl_website" tabindex="-1" autocomplete="off" value="">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegNick" class="form-label">Nickname</label>';
echo '<input type="text" id="pdlRegNick" name="nick" class="form-control" required autocomplete="username" maxlength="64" value="' . $nick_attr . '">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegEmail" class="form-label">E-Mail</label>';
echo '<input type="email" id="pdlRegEmail" name="email" class="form-control" required autocomplete="email" value="' . $email_attr . '">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegPw" class="form-label">Passwort</label>';
echo '<input type="password" id="pdlRegPw" name="pw_new" class="form-control" required autocomplete="new-password" minlength="8" aria-describedby="pdlRegPwHelp">';
echo '<div id="pdlRegPwHelp" class="form-text">Mindestens 8 Zeichen, mit Buchstaben und Ziffern.</div>';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegPw2" class="form-label">Passwort (Wiederholung)</label>';
echo '<input type="password" id="pdlRegPw2" name="pw_new2" class="form-control" required autocomplete="new-password" minlength="8">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegHomepage" class="form-label">Homepage</label>';
echo '<input type="url" id="pdlRegHomepage" name="homepage" class="form-control" autocomplete="url" placeholder="https://example.com" value="' . $homepage_attr . '">';
echo '</div>';

echo '<div class="form-check mb-3">';
echo '<input type="checkbox" id="pdlRegGetLetter" name="get_letter" value="Y" class="form-check-input"' . $get_letter_checked . ' aria-describedby="pdlRegGetLetterHelp">';
echo '<label for="pdlRegGetLetter" class="form-check-label">Ja, ich möchte den Newsletter abonnieren</label>';
echo '<div id="pdlRegGetLetterHelp" class="form-text">Wir versenden den Newsletter ca. einmal pro Monat. Du kannst dich jederzeit über dein Profil abmelden.</div>';
echo '</div>';

echo pdl_captcha_render($settings);

echo '<button type="submit" class="btn btn-primary">Registrieren</button>';
echo '</form>';
echo '</div></section>';
