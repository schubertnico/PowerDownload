<?php
/**
 * PowerDownload - Passwort vergessen (Schritt 2: neues Passwort festlegen)
 * @license MIT
 *
 * Der Benutzer legt das neue Passwort über den Link aus der Mail selbst
 * fest. Gültigkeit: pdl_password_reset_ttl() (pdl_mail.inc.php). Nach dem
 * Speichern werden alle Geräte abgemeldet und eine Bestätigung verschickt.
 */

include_once __DIR__ . '/pdl_mail.inc.php';

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');
$remind_code_raw = is_string($remind_code ?? null) ? trim($remind_code) : '';

$pdl_lost2_invalid = static function (string $script_file): void {
    echo pdl_alert('danger', '<strong>Der Link ist ungültig oder abgelaufen.</strong> Bitte fordern Sie über „Passwort vergessen“ einen neuen Link an.');
    echo '<p class="text-center mt-3"><a class="btn btn-outline-light" id="pdlLost2Again" href="' . $script_file . 'usercenter=lost">Neuen Link anfordern</a></p>';
};

// Codes bestehen aus 32 Hex-Zeichen (bin2hex(random_bytes(16))).
if (preg_match('/^[0-9a-f]{32}$/i', $remind_code_raw) !== 1) {
    $pdl_lost2_invalid($script_file);
    return;
}

$remind_code_safe = $db_handler->sql_escape_string($remind_code_raw);
$now = time();
$getuser = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT * FROM " . $sql_table['user'] . " WHERE remind_code='" . $remind_code_safe . "' AND remind_code != '' AND remind_expires > " . $now
));

if (!$getuser) {
    $pdl_lost2_invalid($script_file);
    return;
}

$errors = [];
if ($submit == 1) {
    $pw_new_raw = is_string($pw_new ?? null) ? $pw_new : '';
    $pw_new2_raw = is_string($pw_new2 ?? null) ? $pw_new2 : '';

    if (!csrf_verify(is_string($csrf_token ?? null) ? $csrf_token : null)) {
        $errors[] = "Das Formular ist abgelaufen. Bitte geben Sie das neue Passwort erneut ein.";
    } else {
        $errors = pdl_validate_password($pw_new_raw, $pw_new2_raw);
    }

    if (!$errors) {
        $pw_hash_safe = $db_handler->sql_escape_string(password_hash($pw_new_raw, PASSWORD_DEFAULT));
        $user_id_safe = $db_handler->sql_escape_int($getuser['user_id'] ?? 0);
        // Code verbrauchen und alle Geräte abmelden (session_token leeren).
        $update_ok = $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET passwort='" . $pw_hash_safe . "', remind_code='', remind_expires=0, session_token='' WHERE user_id='" . $user_id_safe . "'");

        if ($update_ok === false) {
            $errors[] = "Das neue Passwort konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.";
        } else {
            pdl_send_template_mail('mail_lost2', (string) ($getuser['email'] ?? ''), $settings, $template ?? [], [
                'nick' => (string) ($getuser['nick'] ?? ''),
                'email' => (string) ($getuser['email'] ?? ''),
            ]);

            echo pdl_alert('success', '<strong>Ihr neues Passwort wurde gespeichert.</strong> Sie können sich jetzt damit anmelden.');
            echo '<p class="text-center mt-3"><a class="btn btn-primary" id="pdlLost2Login" href="' . $script_file . 'usercenter=login">Jetzt anmelden</a></p>';
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

$remind_code_html = htmlspecialchars($remind_code_raw, ENT_QUOTES, 'UTF-8');
$nick_html = htmlspecialchars((string) ($getuser['nick'] ?? ''), ENT_QUOTES, 'UTF-8');
$pw_hint = htmlspecialchars(pdl_password_hint(), ENT_QUOTES, 'UTF-8');
?>
<section class="card pdl-card mx-auto" style="max-width: 540px;">
    <header class="card-header pdl-card-header"><h2 class="h5 mb-0">Neues Passwort festlegen</h2></header>
    <div class="card-body">
        <p class="mb-3">Benutzername: <strong><?php echo $nick_html; ?></strong></p>
        <form id="pdlLost2Form" name="lost2" method="post" action="<?php echo $script_file; ?>usercenter=lost2" novalidate>
            <?php echo csrf_input(); ?>
            <input type="hidden" name="usercenter" value="lost2">
            <input type="hidden" name="submit" value="1">
            <input type="hidden" name="remind_code" value="<?php echo $remind_code_html; ?>">
            <div class="mb-3">
                <label for="pdlPwNew" class="form-label">Neues Passwort</label>
                <input type="password" id="pdlPwNew" name="pw_new" class="form-control" required minlength="8" autocomplete="new-password" aria-describedby="pdlPwNewHelp">
                <div id="pdlPwNewHelp" class="form-text"><?php echo $pw_hint; ?></div>
            </div>
            <div class="mb-3">
                <label for="pdlPwNew2" class="form-label">Neues Passwort (Wiederholung)</label>
                <input type="password" id="pdlPwNew2" name="pw_new2" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <div class="d-grid">
                <button type="submit" id="pdlLost2Submit" class="btn btn-primary">Passwort speichern</button>
            </div>
        </form>
    </div>
</section>
