<?php
/**
 * PowerDownload - Password Lost Module (Step 1)
 * @license MIT
 */

include_once __DIR__ . '/pdl_captcha.inc.php';

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '');

$pdl_lost_success = static function (string $script_file): void {
    echo pdl_alert('info', 'Wenn ein Konto mit dieser E-Mail existiert, wurde eine E-Mail mit weiteren Schritten versendet. Bitte prüfe deinen Posteingang inkl. Spam-Ordner. Der Link ist 24 Stunden gültig.');
    echo '<p class="text-center mt-3"><a class="btn btn-outline-primary" href="' . $script_file . 'usercenter=login">Zurück zum Login</a></p>';
};

if ($submit == 1) {
    $ip_raw = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ip_safe = $db_handler->sql_escape_string($ip_raw);
    $rate_window = time() - 3600;
    $rate_res = $db_handler->sql_query("SELECT COUNT(*) AS c FROM " . $sql_table['iplock'] . " WHERE ip='" . $ip_safe . "' AND art='lostpw' AND time>" . $rate_window);
    $rate_row = $db_handler->sql_fetch_array($rate_res);
    $rate_count = (int) ($rate_row['c'] ?? 0);
    $db_handler->sql_query("INSERT INTO " . $sql_table['iplock'] . " (ip,time,file_id,user_id,art) VALUES ('" . $ip_safe . "','" . time() . "',0,0,'lostpw')");

    if (mt_rand(1, 100) === 1) {
        $db_handler->sql_query("DELETE FROM " . $sql_table['iplock'] . " WHERE time < '" . (time() - 7 * 24 * 3600) . "' AND art IN ('register','lostpw')");
    }

    if ($rate_count >= 3) {
        error_log('PDL lostpw rate-limit exceeded from IP=' . $ip_raw);
        $pdl_lost_success($script_file);
        return;
    }

    $hp_raw = (string) ($_POST['pdl_website'] ?? '');
    $ts_raw = (int) ($_POST['pdl_ts'] ?? 0);
    $is_bot = ($hp_raw !== '') || ($ts_raw > 0 && (time() - $ts_raw) < 3);

    if ($is_bot) {
        error_log('PDL spam register attempt from IP=' . ($_SERVER['REMOTE_ADDR'] ?? '?') . ' nick=' . substr((string)($_POST['nick'] ?? ''), 0, 50));
        $pdl_lost_success($script_file);
        return;
    }

    if (!pdl_captcha_verify($settings)) {
        error_log('PDL captcha fail lostpw from IP=' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        $pdl_lost_success($script_file);
        return;
    }

    if (!csrf_verify($csrf_token ?? null)) {
        echo pdl_alert('danger', 'Sicherheits-Token ungültig.');
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
        if (!mail($getuser['email'] ?? '', "Passwort-Bestätigungscode", $message, "FROM: " . $from_name . " <" . $from_addr . ">")) {
            error_log("PowerDownload: Mailversand an " . ($getuser['email'] ?? '') . " fehlgeschlagen (Lost1).");
        }
    }
    $pdl_lost_success($script_file);
    return;
}

echo '<section class="card pdl-card mx-auto" style="max-width: 540px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Passwort vergessen</h2></header>';
echo '<div class="card-body">';
echo '<form name="lost" method="post" action="downloads.php">';
echo csrf_input();
echo '<input type="hidden" name="usercenter" value="lost">';
echo '<input type="hidden" name="submit" value="1">';
echo '<input type="hidden" name="pdl_ts" value="' . time() . '">';

echo '<div class="visually-hidden" aria-hidden="true">';
echo '<label for="pdl_website">Website (bitte freilassen)</label>';
echo '<input type="text" id="pdl_website" name="pdl_website" tabindex="-1" autocomplete="off" value="">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlLostEmail" class="form-label">E-Mail</label>';
echo '<input type="email" id="pdlLostEmail" name="email" class="form-control" required autocomplete="email">';
echo '<div class="form-text">Gib die E-Mail-Adresse ein, mit der du dich registriert hast. Wir senden dir einen Link zum Zurücksetzen des Passworts.</div>';
echo '</div>';

echo pdl_captcha_render($settings);

echo '<button type="submit" class="btn btn-primary">Absenden</button>';
echo '</form>';
echo '</div></section>';
