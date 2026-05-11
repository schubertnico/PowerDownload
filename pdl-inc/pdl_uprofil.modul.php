<?php
/**
 * PowerDownload - User Profile Module
 * @license MIT
 */

$submit = (int)($submit ?? 0);
$script_file = htmlspecialchars($settings['script_file'] ?? '');
$from_admin = (($_GET['from'] ?? '') === 'admin');

if (!($user_details ?? null)) {
    echo pdl_alert('info', 'Sie müssen eingeloggt sein um Ihr Profil zu bearbeiten.');
    return;
}

$errors = [];
$wants_password_change = false;

// Bug 3.5: Self-Service-Kontolöschung (DSGVO Art. 17).
$delete_request = !empty($_POST['delete_account']) || (($_GET['delete_account'] ?? '') === '1' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
if ($delete_request) {
    $delete_pw_raw = (string) ($_POST['delete_pw'] ?? '');
    $delete_confirm = (int) ($_POST['delete_confirm'] ?? 0);
    $current_user_id = (int) ($user_details['user_id'] ?? 0);

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        echo pdl_alert('danger', 'Sicherheits-Token ungültig.');
    } elseif ($delete_confirm !== 1) {
        echo pdl_alert('danger', 'Bitte bestätige, dass die Löschung endgültig ist.');
    } elseif ($current_user_id === 1) {
        echo pdl_alert('danger', 'Der Hauptadministrator kann sich nicht selbst löschen.');
    } else {
        $stored_password_del = (string) ($user_details['passwort'] ?? '');
        $pw_ok_del = false;
        $info_del = password_get_info($stored_password_del);
        if (($info_del['algo'] ?? null) !== null && ($info_del['algo'] ?? 0) !== 0) {
            $pw_ok_del = password_verify($delete_pw_raw, $stored_password_del);
        } elseif (strlen($stored_password_del) === 32 && ctype_xdigit($stored_password_del)) {
            $pw_ok_del = hash_equals($stored_password_del, md5($delete_pw_raw));
        }

        if (!$pw_ok_del) {
            echo pdl_alert('danger', 'Das eingegebene Passwort ist nicht korrekt.');
        } else {
            $user_id_safe_del = $db_handler->sql_escape_int($current_user_id);

            if (function_exists('pdl_audit_log')) {
                pdl_audit_log($db_handler, $sql_table, $user_details, 'self_delete', 'user', $current_user_id);
            }
            error_log('pdl self_delete user_id=' . $current_user_id
                . ' Nick=' . ($user_details['nick'] ?? '')
                . ' Email=' . ($user_details['email'] ?? '')
                . ' IP=' . ($_SERVER['REMOTE_ADDR'] ?? ''));

            $db_handler->sql_query("DELETE FROM " . $sql_table['user'] . " WHERE user_id='" . $user_id_safe_del . "'");

            // Session-Cookies entwerten (Headers ggf. bereits gesendet -> nur DB-Seite,
            // Cookie verliert durch fehlenden DB-Eintrag seine Gültigkeit beim nächsten Request).
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_destroy();
            }
            $cookie_clear_local = [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'samesite' => 'Lax',
            ];
            @setcookie('login_id', '', $cookie_clear_local);
            @setcookie('login_token', '', $cookie_clear_local);

            $redirect_url = ($settings['script_file'] ?? 'downloads.php?') . 'account_deleted=1';
            $redirect_attr = htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8');
            echo pdl_alert('success', '<strong>Dein Konto wurde gelöscht.</strong> Du wirst gleich zur Startseite weitergeleitet. <a href="' . $redirect_attr . '" class="alert-link">Jetzt fortfahren</a>.');
            echo '<meta http-equiv="refresh" content="3;url=' . $redirect_attr . '">';
            return;
        }
    }
}

if ($submit == 1) {
    if (!csrf_verify($csrf_token ?? null)) {
        $errors[] = "Sicherheits-Token ungültig.";
    }

    $pw_old_raw = (string) ($pw_old ?? '');
    $pw_new_raw = (string) ($pw_new ?? '');
    $pw_new2_raw = (string) ($pw_new2 ?? '');
    $email_raw = (string) ($email ?? '');
    $homepage_raw = (string) ($homepage ?? '');
    $get_letter_raw = ($get_letter ?? '') == "Y" ? "Y" : "N";

    $wants_password_change = ($pw_new_raw !== '' || $pw_new2_raw !== '');

    if ($email_raw === '' || !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Die E-Mail-Adresse ist ungültig.";
    }

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

    if ($wants_password_change) {
        if ($pw_new_raw === '' || $pw_new2_raw === '') {
            $errors[] = "Bitte neues Passwort und Bestätigung ausfüllen.";
        } elseif ($pw_new_raw !== $pw_new2_raw) {
            $errors[] = "Neues Passwort stimmt nicht mit der Bestätigung überein.";
        } elseif (strlen($pw_new_raw) < 8) {
            $errors[] = "Neues Passwort muss mindestens 8 Zeichen lang sein.";
        } elseif (!preg_match('/[A-Za-z]/', $pw_new_raw) || !preg_match('/\d/', $pw_new_raw)) {
            $errors[] = "Neues Passwort muss mindestens einen Buchstaben und eine Ziffer enthalten.";
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

        $email_safe = $db_handler->sql_escape_string($email_raw);
        $homepage_safe = $db_handler->sql_escape_string($homepage_normalized);
        $user_id_safe = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);

        if ($wants_password_change) {
            $pw_hash = password_hash($pw_new_raw, PASSWORD_DEFAULT);
            $pw_hash_safe = $db_handler->sql_escape_string($pw_hash);
            $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET email='" . $email_safe . "', get_letter='" . $get_letter_raw . "', homepage='" . $homepage_safe . "', passwort='" . $pw_hash_safe . "', session_token='' WHERE user_id='" . $user_id_safe . "'");
            echo pdl_alert('success', '<strong>Profil erfolgreich geändert.</strong> Da das Passwort geändert wurde, bitte erneut <a href="' . $script_file . 'usercenter=login" class="alert-link">einloggen</a>.');
            return;
        }
        $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET email='" . $email_safe . "', get_letter='" . $get_letter_raw . "', homepage='" . $homepage_safe . "' WHERE user_id='" . $user_id_safe . "'");
        echo pdl_alert('success', '<strong>Profil erfolgreich geändert.</strong>');
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

$email_attr = htmlspecialchars($user_details['email'] ?? '', ENT_QUOTES, 'UTF-8');
$homepage_attr = htmlspecialchars($user_details['homepage'] ?? '', ENT_QUOTES, 'UTF-8');
$get_letter_checked = (($user_details['get_letter'] ?? '') == "Y") ? ' checked' : '';
$pw_section_expanded = $wants_password_change ? ' show' : '';
$pw_section_button_collapsed = $wants_password_change ? '' : ' collapsed';
$pw_section_aria = $wants_password_change ? 'true' : 'false';

if ($from_admin) {
    echo '<nav aria-label="Breadcrumb" class="mb-3 mx-auto" style="max-width: 720px;">';
    echo '<ol class="breadcrumb mb-0">';
    echo '<li class="breadcrumb-item"><a href="pdl-admin/index.php">Admin-Center</a></li>';
    echo '<li class="breadcrumb-item active" aria-current="page">Mein Profil</li>';
    echo '</ol>';
    echo '</nav>';
}

echo '<section class="card pdl-card mx-auto" style="max-width: 720px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Profil bearbeiten</h2></header>';
echo '<div class="card-body">';
echo '<form action="downloads.php" method="post">';
echo csrf_input();
echo '<input type="hidden" name="usercenter" value="profil">';
echo '<input type="hidden" name="submit" value="1">';

echo '<div class="mb-3">';
echo '<label for="pdlProfilEmail" class="form-label">E-Mail</label>';
echo '<input type="email" id="pdlProfilEmail" name="email" class="form-control" required autocomplete="email" value="' . $email_attr . '">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlProfilHomepage" class="form-label">Homepage</label>';
echo '<input type="url" id="pdlProfilHomepage" name="homepage" class="form-control" autocomplete="url" placeholder="https://example.com" value="' . $homepage_attr . '">';
echo '</div>';

echo '<div class="form-check mb-3">';
echo '<input type="checkbox" id="pdlProfilGetLetter" name="get_letter" value="Y" class="form-check-input"' . $get_letter_checked . ' aria-describedby="pdlProfilGetLetterHelp">';
echo '<label for="pdlProfilGetLetter" class="form-check-label">Newsletter abonnieren</label>';
echo '<div id="pdlProfilGetLetterHelp" class="form-text">Etwa einmal pro Monat. Häkchen entfernen, um den Newsletter abzubestellen.</div>';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlProfilPwOld" class="form-label">Aktuelles Passwort</label>';
echo '<input type="password" id="pdlProfilPwOld" name="pw_old" class="form-control" required autocomplete="current-password" aria-describedby="pdlProfilPwOldHelp">';
echo '<div id="pdlProfilPwOldHelp" class="form-text">Zur Bestätigung deiner Identität – auch wenn du nur E-Mail oder Homepage änderst.</div>';
echo '</div>';

// Bug 3.3 / 3.4: Passwortwechsel in eigenem Akkordeon-Block mit Helper-Text.
echo '<div class="accordion mb-3" id="pdlProfilPwAccordion">';
echo '<div class="accordion-item">';
echo '<h3 class="accordion-header" id="pdlProfilPwHead">';
echo '<button class="accordion-button' . $pw_section_button_collapsed . '" type="button" data-bs-toggle="collapse" data-bs-target="#pdlProfilPwCollapse" aria-expanded="' . $pw_section_aria . '" aria-controls="pdlProfilPwCollapse">Passwort ändern (optional)</button>';
echo '</h3>';
echo '<div id="pdlProfilPwCollapse" class="accordion-collapse collapse' . $pw_section_expanded . '" aria-labelledby="pdlProfilPwHead">';
echo '<div class="accordion-body">';
echo '<p class="form-text mt-0">Passwortfelder leer lassen, wenn das Passwort nicht geändert werden soll.</p>';

echo '<div class="mb-3">';
echo '<label for="pdlProfilPwNew" class="form-label">Neues Passwort</label>';
echo '<input type="password" id="pdlProfilPwNew" name="pw_new" class="form-control" autocomplete="new-password" minlength="8" aria-describedby="pdlProfilPwNewHelp">';
echo '<div id="pdlProfilPwNewHelp" class="form-text">Mindestens 8 Zeichen, mit Buchstaben und Ziffern.</div>';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlProfilPwNew2" class="form-label">Neues Passwort (Wiederholung)</label>';
echo '<input type="password" id="pdlProfilPwNew2" name="pw_new2" class="form-control" autocomplete="new-password" minlength="8">';
echo '</div>';

echo '</div></div></div></div>';

echo '<button type="submit" class="btn btn-primary">Speichern</button>';
echo '</form>';
echo '</div></section>';

$csrf_token_attr = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$is_main_admin = ((int) ($user_details['user_id'] ?? 0) === 1);

echo '<section class="card pdl-card mx-auto mt-4 mb-4 border-danger" style="max-width: 720px;">';
echo '<header class="card-header text-bg-danger"><h2 class="h5 mb-0">Konto löschen</h2></header>';
echo '<div class="card-body">';
echo '<p>Wenn du dein Konto löschst, werden dein Profil und deine persönlichen Daten endgültig entfernt. Bestehende Kommentare und Releases bleiben anonymisiert erhalten.</p>';
if ($is_main_admin) {
    echo pdl_alert('warning', 'Der Hauptadministrator kann sich nicht selbst löschen.');
} else {
    echo '<form method="post" action="downloads.php?usercenter=profil&amp;delete_account=1" novalidate>';
    echo '<input type="hidden" name="csrf_token" value="' . $csrf_token_attr . '">';
    echo '<input type="hidden" name="usercenter" value="profil">';
    echo '<input type="hidden" name="delete_account" value="1">';
    echo '<div class="mb-3">';
    echo '<label for="pdlProfileDelPw" class="form-label">Bitte gib zur Bestätigung dein aktuelles Passwort ein</label>';
    echo '<input type="password" id="pdlProfileDelPw" name="delete_pw" class="form-control" autocomplete="current-password" required>';
    echo '</div>';
    echo '<div class="form-check mb-3">';
    echo '<input class="form-check-input" type="checkbox" id="pdlProfileDelConfirm" name="delete_confirm" value="1" required>';
    echo '<label class="form-check-label" for="pdlProfileDelConfirm">Ich habe verstanden, dass diese Aktion nicht rückgängig gemacht werden kann.</label>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-danger">Konto endgültig löschen</button>';
    echo '</form>';
}
echo '</div></section>';
