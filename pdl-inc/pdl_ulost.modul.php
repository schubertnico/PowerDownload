<?php
/**
 * PowerDownload - Passwort vergessen (Schritt 1: Link anfordern)
 * @license MIT
 *
 * Verschickt einen Link zum Festlegen eines neuen Passworts. Die Meldung
 * ist immer dieselbe, egal ob es ein Konto mit der Adresse gibt (Schutz
 * vor dem Ausforschen von Adressen). Höchstens 3 Anforderungen je IP und
 * Stunde; das versteckte Bot-Feld wird still abgewiesen.
 */

include_once __DIR__ . '/pdl_captcha.inc.php';
include_once __DIR__ . '/pdl_mail.inc.php';

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');
$errors = [];
$email_value = '';
$pdl_lost_limit = 3;

$pdl_lost_success = static function (string $script_file): void {
    echo pdl_alert('info', '<strong>Bitte prüfen Sie Ihr Postfach.</strong> Wenn zu dieser E-Mail-Adresse ein Benutzerkonto existiert, haben wir einen Link zum Festlegen eines neuen Passworts verschickt. Sehen Sie bitte auch im Spam-Ordner nach. Der Link ist ' . pdl_password_reset_ttl_text() . ' gültig.');
    echo '<p class="text-center mt-3"><a class="btn btn-outline-light" id="pdlLostBackToLogin" href="' . $script_file . 'usercenter=login">Zurück zur Anmeldung</a></p>';
};

if ($submit == 1) {
    $ip_raw = $_SERVER['REMOTE_ADDR'] ?? '';
    $email_value = trim(is_string($email ?? null) ? $email : '');

    $spam = pdl_spam_check();
    if ($spam === 'honeypot') {
        // Einziger stiller Fall: Bots sollen keinen Hinweis auf die Falle bekommen.
        error_log('PDL lostpw: Honeypot ausgefüllt, IP=' . $ip_raw);
        $pdl_lost_success($script_file);
        return;
    }

    $rate_count = pdl_rate_limit_count($db_handler, $sql_table, 'lostpw', $ip_raw);
    if ($rate_count >= $pdl_lost_limit) {
        error_log('PDL lostpw: Limit erreicht, IP=' . $ip_raw);
        echo pdl_alert('warning', '<strong>Zu viele Anforderungen.</strong> Von Ihrer Internetadresse wurden in der letzten Stunde bereits mehrere neue Passwörter angefordert. Bitte versuchen Sie es in einer Stunde erneut.');
        echo '<p class="text-center mt-3"><a class="btn btn-outline-light" href="' . $script_file . 'usercenter=login">Zurück zur Anmeldung</a></p>';
        return;
    }

    if ($spam === 'too_fast') {
        $errors[] = pdl_spam_too_fast_error();
    } elseif (!pdl_captcha_verify($settings)) {
        $errors[] = pdl_captcha_error();
    } elseif (!csrf_verify(is_string($csrf_token ?? null) ? $csrf_token : null)) {
        $errors[] = 'Das Formular ist abgelaufen. Bitte senden Sie es erneut ab.';
    } elseif ($email_value === '') {
        $errors[] = 'Bitte geben Sie die E-Mail-Adresse Ihres Benutzerkontos ein.';
    } elseif (!filter_var($email_value, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Die E-Mail-Adresse ist ungültig. Bitte prüfen Sie die Schreibweise.';
    }

    if (!$errors) {
        // Das Limit zählt jede angenommene Anforderung, unabhängig davon, ob es das Konto gibt.
        pdl_rate_limit_record($db_handler, $sql_table, 'lostpw', $ip_raw);

        $email_safe = $db_handler->sql_escape_string($email_value);
        $getuser = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE email='" . $email_safe . "'"));

        if ($getuser) {
            $remind_code_new = bin2hex(random_bytes(16));
            $remind_expires = time() + pdl_password_reset_ttl();
            $user_id_safe = $db_handler->sql_escape_int($getuser['user_id'] ?? 0);
            $remind_code_safe = $db_handler->sql_escape_string($remind_code_new);
            $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET remind_code='" . $remind_code_safe . "', remind_expires='" . $remind_expires . "' WHERE user_id='" . $user_id_safe . "'");

            pdl_send_template_mail('mail_lost1', (string) ($getuser['email'] ?? ''), $settings, $template ?? [], [
                'nick' => (string) ($getuser['nick'] ?? ''),
                'email' => (string) ($getuser['email'] ?? ''),
                'link' => pdl_script_url($settings, 'usercenter=lost2&remind_code=' . $remind_code_new),
            ]);
        }
        $pdl_lost_success($script_file);
        return;
    }
}

if ($errors) {
    $err_html = '<strong>Bitte prüfen Sie Ihre Eingabe:</strong><ul class="mb-0 mt-2">';
    foreach ($errors as $err) {
        $err_html .= '<li>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $err_html .= '</ul>';
    echo pdl_alert('danger', $err_html);
}

$email_attr = htmlspecialchars($email_value, ENT_QUOTES, 'UTF-8');

echo '<section class="card pdl-card mx-auto" style="max-width: 540px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Passwort vergessen</h2></header>';
echo '<div class="card-body">';
echo '<form id="pdlLostForm" name="lost" method="post" action="' . $script_file . 'usercenter=lost" novalidate>';
echo csrf_input();
echo '<input type="hidden" name="usercenter" value="lost">';
echo '<input type="hidden" name="submit" value="1">';
echo pdl_spam_fields();

echo '<div class="mb-3">';
echo '<label for="pdlLostEmail" class="form-label">E-Mail-Adresse</label>';
echo '<input type="email" id="pdlLostEmail" name="email" class="form-control" required autocomplete="email" value="' . $email_attr . '" aria-describedby="pdlLostEmailHelp">';
echo '<div id="pdlLostEmailHelp" class="form-text">Geben Sie die E-Mail-Adresse ein, mit der Sie sich registriert haben. Wir senden Ihnen einen Link, über den Sie ein neues Passwort festlegen.</div>';
echo '</div>';

echo pdl_captcha_render($settings);

echo '<button type="submit" id="pdlLostSubmit" class="btn btn-primary">Link anfordern</button>';
echo '</form>';
echo '<p class="mt-3 mb-0 small"><a href="' . $script_file . 'usercenter=login">Zurück zur Anmeldung</a></p>';
echo '</div></section>';
