<?php
/**
 * PowerDownload - Registrierung
 * @license MIT
 *
 * Neue Benutzer kommen in Gruppe 1 („Mitglied“). Schutz vor Bots:
 * verstecktes Feld (still abgewiesen), Mindestzeit und optionale
 * Rechenaufgabe (mit Hinweis), höchstens 5 Registrierungen je IP und Stunde.
 */

include_once __DIR__ . '/pdl_captcha.inc.php';
include_once __DIR__ . '/pdl_mail.inc.php';

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');
$errors = [];
$values = [
    'nick' => '',
    'email' => '',
    'homepage' => '',
    'get_letter' => 'N',
];
$pdl_reg_limit = 5;

if ($user_details ?? null) {
    echo pdl_alert('warning', '<strong>Sie sind bereits angemeldet.</strong> Für ein weiteres Benutzerkonto melden Sie sich bitte zuerst ab.');
    return;
}

$pdl_reg_success_html = static function (string $script_file, bool $mail_sent, bool $logged_in): string {
    $msg = '<strong>Registrierung erfolgreich.</strong> Ihr Benutzerkonto ist eingerichtet.';
    $msg .= $mail_sent
        ? ' Eine Bestätigung wurde per E-Mail verschickt.'
        : ' Die Bestätigungs-E-Mail konnte leider nicht verschickt werden. Sie können sich trotzdem sofort anmelden.';
    $msg .= '<div class="mt-3 d-flex flex-wrap gap-2">';
    if ($logged_in) {
        $msg .= '<a class="btn btn-primary btn-lg" id="pdlRegProfil" href="' . $script_file . 'usercenter=profil">Zum Profil</a>';
    } else {
        $msg .= '<a class="btn btn-primary btn-lg" id="pdlRegLogin" href="' . $script_file . 'usercenter=login">Jetzt anmelden</a>';
    }
    $msg .= '</div>';
    return $msg;
};

if ($submit == 1) {
    $ip_raw = $_SERVER['REMOTE_ADDR'] ?? '';

    $nick_post = $_POST['nick'] ?? ($nick ?? '');
    $values['nick'] = is_string($nick_post) ? $nick_post : '';
    $values['email'] = trim(is_string($email ?? null) ? $email : '');
    $values['homepage'] = trim(is_string($homepage ?? null) ? $homepage : '');
    $values['get_letter'] = (($get_letter ?? '') === 'Y') ? 'Y' : 'N';
    $pw_new_raw = is_string($pw_new ?? null) ? $pw_new : '';
    $pw_new2_raw = is_string($pw_new2 ?? null) ? $pw_new2 : '';

    $spam = pdl_spam_check();
    if ($spam === 'honeypot') {
        // Einziger stiller Fall: Bots sollen keinen Hinweis auf die Falle bekommen.
        error_log('PDL register: Honeypot ausgefüllt, IP=' . $ip_raw);
        echo pdl_alert('success', $pdl_reg_success_html($script_file, true, false));
        return;
    }

    $rate_count = pdl_rate_limit_count($db_handler, $sql_table, 'register', $ip_raw);
    if ($rate_count >= $pdl_reg_limit) {
        error_log('PDL register: Limit erreicht, IP=' . $ip_raw);
        echo pdl_alert('warning', '<strong>Zu viele Registrierungen.</strong> Von Ihrer Internetadresse wurden in der letzten Stunde bereits mehrere Benutzerkonten angelegt. Bitte versuchen Sie es in einer Stunde erneut.');
        return;
    }

    if ($spam === 'too_fast') {
        $errors[] = pdl_spam_too_fast_error();
    } elseif (!pdl_captcha_verify($settings)) {
        $errors[] = pdl_captcha_error();
    } elseif (!csrf_verify(is_string($csrf_token ?? null) ? $csrf_token : null)) {
        $errors[] = "Das Formular ist abgelaufen. Bitte senden Sie es erneut ab.";
    }

    if (!$errors) {
        if (trim($values['nick']) === '') {
            $errors[] = "Bitte geben Sie einen Benutzernamen ein.";
        } elseif (strlen($values['nick']) > 64) {
            $errors[] = "Der Benutzername darf höchstens 64 Zeichen lang sein.";
        }
        if ($values['email'] === '') {
            $errors[] = "Bitte geben Sie Ihre E-Mail-Adresse ein.";
        } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 128) {
            $errors[] = "Die E-Mail-Adresse ist ungültig. Bitte prüfen Sie die Schreibweise.";
        }
        $errors = array_merge($errors, pdl_validate_password($pw_new_raw, $pw_new2_raw));
    }

    $homepage_normalized = '';
    if (!$errors && $values['homepage'] !== '') {
        $candidate = $values['homepage'];
        if (!preg_match("!^https?://!i", $candidate)) {
            $candidate = "http://" . $candidate;
        }
        if (filter_var($candidate, FILTER_VALIDATE_URL) && preg_match("!^https?://!i", $candidate) && strlen($candidate) <= 128) {
            $homepage_normalized = $candidate;
        } else {
            $errors[] = "Die Homepage-Adresse ist ungültig. Bitte geben Sie sie vollständig ein, z. B. https://example.com.";
        }
    }

    if (!$errors) {
        $nick_safe = $db_handler->sql_escape_string($values['nick']);
        $email_safe = $db_handler->sql_escape_string($values['email']);

        if ($db_handler->sql_num_rows($db_handler->sql_query("SELECT user_id FROM " . $sql_table['user'] . " WHERE nick='" . $nick_safe . "'")) > 0) {
            $errors[] = "Dieser Benutzername ist bereits vergeben. Bitte wählen Sie einen anderen.";
        } elseif ($db_handler->sql_num_rows($db_handler->sql_query("SELECT user_id FROM " . $sql_table['user'] . " WHERE email='" . $email_safe . "'")) > 0) {
            $errors[] = "Mit dieser E-Mail-Adresse ist bereits ein Benutzerkonto registriert. Falls es Ihres ist, nutzen Sie „Passwort vergessen“.";
        }
    }

    if (!$errors) {
        $pw_hash_safe = $db_handler->sql_escape_string(password_hash($pw_new_raw, PASSWORD_DEFAULT));
        $homepage_safe = $db_handler->sql_escape_string($homepage_normalized);

        // Selbst registrierte Benutzer kommen in Gruppe 1 („Mitglied“), nie in die Admin-Gruppe 2.
        $insert_ok = $db_handler->sql_query("INSERT INTO " . $sql_table['user'] . " (nick,email,passwort,homepage,get_letter,ugroup_id,lastactive) VALUES ('" . $nick_safe . "','" . $email_safe . "','" . $pw_hash_safe . "','" . $homepage_safe . "','" . $values['get_letter'] . "','1','" . time() . "')");

        if ($insert_ok === false) {
            $errors[] = "Das Benutzerkonto konnte nicht angelegt werden. Bitte versuchen Sie es später erneut.";
        } else {
            // Das Limit zählt nur tatsächlich angelegte Benutzerkonten.
            pdl_rate_limit_record($db_handler, $sql_table, 'register', $ip_raw);

            $mail_sent = pdl_send_template_mail('mail_register', $values['email'], $settings, $template ?? [], [
                'nick' => $values['nick'],
                'email' => $values['email'],
            ]);

            echo pdl_alert('success', $pdl_reg_success_html($script_file, $mail_sent, !empty($user_details)));
            return;
        }
    }
}

if ($errors) {
    $err_html = '<strong>Bitte prüfen Sie Ihre Eingaben:</strong><ul class="mb-0 mt-2">';
    foreach ($errors as $err) {
        $err_html .= '<li>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $err_html .= '</ul>';
    echo pdl_alert('danger', $err_html);
}

$nick_attr = htmlspecialchars($values['nick'], ENT_QUOTES, 'UTF-8');
$email_attr = htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8');
$homepage_attr = htmlspecialchars($values['homepage'], ENT_QUOTES, 'UTF-8');
$get_letter_checked = $values['get_letter'] === 'Y' ? ' checked' : '';
$pw_hint = htmlspecialchars(pdl_password_hint(), ENT_QUOTES, 'UTF-8');

echo '<section class="card pdl-card mx-auto" style="max-width: 720px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Registrierung</h2></header>';
echo '<div class="card-body">';
echo '<form id="pdlRegForm" name="register" action="' . $script_file . 'usercenter=register" method="post" novalidate>';
echo csrf_input();
echo '<input type="hidden" name="usercenter" value="register">';
echo '<input type="hidden" name="submit" value="1">';
echo pdl_spam_fields();

echo '<div class="mb-3">';
echo '<label for="pdlRegNick" class="form-label">Benutzername</label>';
echo '<input type="text" id="pdlRegNick" name="nick" class="form-control" required autocomplete="username" maxlength="64" value="' . $nick_attr . '">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegEmail" class="form-label">E-Mail-Adresse</label>';
echo '<input type="email" id="pdlRegEmail" name="email" class="form-control" required autocomplete="email" maxlength="128" value="' . $email_attr . '">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegPw" class="form-label">Passwort</label>';
echo '<input type="password" id="pdlRegPw" name="pw_new" class="form-control" required autocomplete="new-password" minlength="8" aria-describedby="pdlRegPwHelp">';
echo '<div id="pdlRegPwHelp" class="form-text">' . $pw_hint . '</div>';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegPw2" class="form-label">Passwort (Wiederholung)</label>';
echo '<input type="password" id="pdlRegPw2" name="pw_new2" class="form-control" required autocomplete="new-password" minlength="8">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlRegHomepage" class="form-label">Homepage <span class="text-muted small">(optional)</span></label>';
echo '<input type="url" id="pdlRegHomepage" name="homepage" class="form-control" autocomplete="url" maxlength="128" placeholder="https://example.com" value="' . $homepage_attr . '">';
echo '</div>';

echo '<div class="form-check mb-3">';
echo '<input type="checkbox" id="pdlRegGetLetter" name="get_letter" value="Y" class="form-check-input"' . $get_letter_checked . ' aria-describedby="pdlRegGetLetterHelp">';
echo '<label for="pdlRegGetLetter" class="form-check-label">Ja, ich möchte den Newsletter abonnieren</label>';
echo '<div id="pdlRegGetLetterHelp" class="form-text">Wir versenden den Newsletter etwa einmal im Monat. Sie können ihn jederzeit in Ihrem Profil abbestellen.</div>';
echo '</div>';

echo pdl_captcha_render($settings);

echo '<button type="submit" id="pdlRegSubmit" class="btn btn-primary">Registrieren</button>';
echo '</form>';
echo '</div></section>';
