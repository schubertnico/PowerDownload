<?php
/**
 * PowerDownload - Profil bearbeiten und Konto löschen
 * @license MIT
 *
 * Ablauf bei POST-Anfragen: pdl_header.inc.php bindet dieses Modul vor
 * jeder Ausgabe ein ($pdl_prerender = true). So können nach dem Speichern
 * Cookies gesetzt und Weiterleitungen gesendet werden (Post/Redirect/Get):
 *   - Speichern erfolgreich → usercenter=profil&saved=1 (bzw. saved=2 nach
 *     Passwortwechsel; dieses Gerät bleibt angemeldet, andere werden abgemeldet)
 *   - Konto gelöscht        → account_deleted=1, sofort abgemeldet
 * Bei Fehlern puffert der Header die Ausgabe in $pdl_uprofil_output; das
 * reguläre Einbinden durch pdl_downloads.inc.php gibt sie dann aus.
 * Ohne Header (z. B. in Tests) arbeitet das Modul wie bisher in einem Schritt.
 */

/**
 * Werte aus dem einbindenden Skript. Je nach Aufrufer (Header-Vorabverarbeitung,
 * pdl_downloads.inc.php, Tests) sind sie gesetzt oder nicht. Psalm wertet das
 * Modul nur im Header-Kontext aus und hielte die Prüfungen sonst für überflüssig.
 *
 * @var bool|null $pdl_prerender nur vom Header gesetzt
 * @var string|null $pdl_uprofil_output gepufferte Ausgabe der Vorabverarbeitung
 * @var array<int|string, mixed>|null $user_details null = nicht angemeldet
 */
include_once __DIR__ . '/pdl_mail.inc.php';

$pdl_profil_prerender = ($pdl_prerender ?? false) && !headers_sent();

if (!$pdl_profil_prerender && isset($pdl_uprofil_output)) {
    echo $pdl_uprofil_output;
    $pdl_uprofil_output = null;
    return;
}

$script_file_raw = (string) ($settings['script_file'] ?? '');
$script_file = htmlspecialchars($script_file_raw, ENT_QUOTES, 'UTF-8');
$from_admin = (($_GET['from'] ?? '') === 'admin');
$from_admin_query = $from_admin ? '&from=admin' : '';

if (!($user_details ?? null)) {
    echo pdl_alert('info', 'Bitte <a href="' . $script_file . 'usercenter=login" class="alert-link">melden Sie sich an</a>, um Ihr Profil zu bearbeiten.');
    return;
}

$errors = [];
$delete_errors = [];
$notice = '';
$wants_password_change = false;
$current_user_id = (int) ($user_details['user_id'] ?? 0);
$values = [
    'email' => (string) ($user_details['email'] ?? ''),
    'homepage' => (string) ($user_details['homepage'] ?? ''),
    'get_letter' => (($user_details['get_letter'] ?? '') === 'Y') ? 'Y' : 'N',
];

$pdl_profil_redirect = static function (string $url) use ($pdl_profil_prerender): bool {
    if (!$pdl_profil_prerender || headers_sent()) {
        return false;
    }
    header('Location: ' . $url);
    return true;
};

$pdl_profil_cookie = static function (string $name, string $value, int $expires) use ($pdl_profil_prerender): void {
    if (!$pdl_profil_prerender || headers_sent()) {
        return;
    }
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => '/',
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
};

$saved_flag = (int) ($_GET['saved'] ?? 0);
if ($saved_flag === 1) {
    $notice = '<strong>Ihr Profil wurde gespeichert.</strong>';
} elseif ($saved_flag === 2) {
    $notice = '<strong>Ihr Profil wurde gespeichert.</strong> Ihr Passwort wurde geändert; auf anderen Geräten wurden Sie abgemeldet.';
}

// Konto löschen (DSGVO Art. 17)
if (!empty($_POST['delete_account'])) {
    $delete_pw_raw = is_string($_POST['delete_pw'] ?? null) ? $_POST['delete_pw'] : '';
    $delete_confirm = (int) ($_POST['delete_confirm'] ?? 0);
    $delete_token = $_POST['csrf_token'] ?? null;

    if (!csrf_verify(is_string($delete_token) ? $delete_token : null)) {
        $delete_errors[] = 'Das Formular ist abgelaufen. Bitte geben Sie Ihr Passwort erneut ein.';
    } elseif ($current_user_id === 1) {
        $delete_errors[] = 'Der Hauptadministrator kann sich nicht selbst löschen.';
    } elseif ($delete_pw_raw === '') {
        $delete_errors[] = 'Bitte geben Sie zur Bestätigung Ihr aktuelles Passwort ein.';
    } elseif ($delete_confirm !== 1) {
        $delete_errors[] = 'Bitte bestätigen Sie mit dem Häkchen, dass die Löschung endgültig ist.';
    } elseif (!pdl_password_verify_stored($delete_pw_raw, (string) ($user_details['passwort'] ?? ''))) {
        $delete_errors[] = 'Das eingegebene Passwort ist nicht korrekt. Bitte versuchen Sie es erneut.';
    } else {
        if (function_exists('pdl_audit_log')) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'self_delete', 'user', $current_user_id);
        }
        // Nur die ID protokollieren: Name und Adresse sollen mit dem Konto verschwinden.
        error_log('pdl self_delete user_id=' . $current_user_id);

        $delete_ok = $db_handler->sql_query("DELETE FROM " . $sql_table['user'] . " WHERE user_id='" . $db_handler->sql_escape_int($current_user_id) . "'");
        if ($delete_ok === false) {
            $delete_errors[] = 'Das Konto konnte nicht gelöscht werden. Bitte versuchen Sie es später erneut.';
        } else {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            $pdl_profil_cookie('login_id', '', time() - 3600);
            $pdl_profil_cookie('login_token', '', time() - 3600);

            $redirect_url = ($script_file_raw !== '' ? $script_file_raw : 'downloads.php?') . 'account_deleted=1';
            if ($pdl_profil_redirect($redirect_url)) {
                exit;
            }
            // Ausgabe läuft bereits (ohne Header): Meldung und Weiterleitung per Seite
            $redirect_attr = htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8');
            echo pdl_alert('success', '<strong>Ihr Konto wurde gelöscht.</strong> Sie werden gleich zur Startseite weitergeleitet. <a href="' . $redirect_attr . '" class="alert-link">Jetzt weiter</a>.');
            echo '<meta http-equiv="refresh" content="3;url=' . $redirect_attr . '">';
            return;
        }
    }
} elseif ($submit == 1) {
    // Profil speichern
    $pw_old_raw = is_string($pw_old) ? $pw_old : '';
    $pw_new_raw = is_string($pw_new) ? $pw_new : '';
    $pw_new2_raw = is_string($pw_new2) ? $pw_new2 : '';
    $values['email'] = trim(is_string($email) ? $email : '');
    $values['homepage'] = trim(is_string($homepage) ? $homepage : '');
    $values['get_letter'] = ($get_letter === 'Y') ? 'Y' : 'N';
    $wants_password_change = ($pw_new_raw !== '' || $pw_new2_raw !== '');

    if (!csrf_verify(is_string($csrf_token ?? null) ? $csrf_token : null)) {
        $errors[] = "Das Formular ist abgelaufen. Bitte geben Sie Ihr aktuelles Passwort erneut ein und speichern Sie noch einmal.";
    }

    if ($values['email'] === '') {
        $errors[] = "Bitte geben Sie Ihre E-Mail-Adresse ein.";
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 128) {
        $errors[] = "Die E-Mail-Adresse ist ungültig. Bitte prüfen Sie die Schreibweise.";
    } elseif (strcasecmp($values['email'], (string) ($user_details['email'] ?? '')) !== 0) {
        $email_check = $db_handler->sql_query("SELECT user_id FROM " . $sql_table['user'] . " WHERE email='" . $db_handler->sql_escape_string($values['email']) . "' AND user_id!='" . $db_handler->sql_escape_int($current_user_id) . "'");
        if ($db_handler->sql_num_rows($email_check) > 0) {
            $errors[] = "Diese E-Mail-Adresse gehört bereits zu einem anderen Benutzerkonto.";
        }
    }

    $homepage_normalized = '';
    if ($values['homepage'] !== '') {
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

    if ($pw_old_raw === '') {
        $errors[] = "Bitte geben Sie zur Bestätigung Ihr aktuelles Passwort ein.";
    } elseif (!pdl_password_verify_stored($pw_old_raw, (string) ($user_details['passwort'] ?? ''))) {
        $errors[] = "Das aktuelle Passwort ist falsch. Bitte versuchen Sie es erneut.";
    }

    if ($wants_password_change) {
        foreach (pdl_validate_password($pw_new_raw, $pw_new2_raw) as $pw_error) {
            $errors[] = $pw_error;
        }
    }

    if (!$errors) {
        $email_safe = $db_handler->sql_escape_string($values['email']);
        $homepage_safe = $db_handler->sql_escape_string($homepage_normalized);
        $user_id_safe = $db_handler->sql_escape_int($current_user_id);
        $set_sql = "email='" . $email_safe . "', get_letter='" . $values['get_letter'] . "', homepage='" . $homepage_safe . "'";
        $new_token = '';

        if ($wants_password_change) {
            $set_sql .= ", passwort='" . $db_handler->sql_escape_string(password_hash($pw_new_raw, PASSWORD_DEFAULT)) . "'";
            // Andere Geräte abmelden. Mit Header bekommt dieses Gerät ein neues
            // Sitzungs-Token und bleibt angemeldet, sonst müssen sich alle neu anmelden.
            $new_token = $pdl_profil_prerender ? bin2hex(random_bytes(32)) : '';
            $set_sql .= ", session_token='" . $db_handler->sql_escape_string($new_token) . "'";
        }

        $update_ok = $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET " . $set_sql . " WHERE user_id='" . $user_id_safe . "'");

        if ($update_ok === false) {
            $errors[] = "Ihr Profil konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.";
        } else {
            if ($wants_password_change) {
                if ($new_token !== '') {
                    $pdl_profil_cookie('login_token', $new_token, time() + 8760 * 3600);
                    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                        session_regenerate_id(true);
                    }
                }
                pdl_send_template_mail('mail_lost2', $values['email'], $settings, $template, [
                    'nick' => (string) ($user_details['nick'] ?? ''),
                    'email' => $values['email'],
                ]);
            }

            if ($pdl_profil_redirect($script_file_raw . 'usercenter=profil&saved=' . ($wants_password_change ? '2' : '1') . $from_admin_query)) {
                exit;
            }

            // Ohne Weiterleitung (Ausgabe läuft bereits): Meldung über dem Formular
            if ($wants_password_change) {
                echo pdl_alert('success', '<strong>Ihr Profil wurde gespeichert.</strong> Ihr Passwort wurde geändert. Bitte <a href="' . $script_file . 'usercenter=login" class="alert-link">melden Sie sich mit dem neuen Passwort an</a>.');
                return;
            }
            $notice = '<strong>Ihr Profil wurde gespeichert.</strong>';
            $user_details['email'] = $values['email'];
            $user_details['homepage'] = $homepage_normalized;
            $user_details['get_letter'] = $values['get_letter'];
            $values['homepage'] = $homepage_normalized;
            $wants_password_change = false;
        }
    }
}

if ($notice !== '') {
    echo pdl_alert('success', $notice);
}

if ($errors) {
    $err_html = '<strong>Bitte prüfen Sie Ihre Eingaben:</strong><ul class="mb-0 mt-2">';
    foreach ($errors as $err) {
        $err_html .= '<li>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $err_html .= '</ul>';
    echo pdl_alert('danger', $err_html);
}

$email_attr = htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8');
$homepage_attr = htmlspecialchars($values['homepage'], ENT_QUOTES, 'UTF-8');
$get_letter_checked = ($values['get_letter'] === 'Y') ? ' checked' : '';
$pw_section_expanded = $wants_password_change ? ' show' : '';
$pw_section_button_collapsed = $wants_password_change ? '' : ' collapsed';
$pw_section_aria = $wants_password_change ? 'true' : 'false';
$pw_hint = htmlspecialchars(pdl_password_hint(), ENT_QUOTES, 'UTF-8');
$form_action = $script_file . 'usercenter=profil' . htmlspecialchars($from_admin_query, ENT_QUOTES, 'UTF-8');

if ($from_admin) {
    echo '<nav aria-label="Breadcrumb" class="mb-3 mx-auto" style="max-width: 720px;">';
    echo '<ol class="breadcrumb mb-0">';
    echo '<li class="breadcrumb-item"><a href="pdl-admin/index.php">Adminbereich</a></li>';
    echo '<li class="breadcrumb-item active" aria-current="page">Mein Profil</li>';
    echo '</ol>';
    echo '</nav>';
}

echo '<section class="card pdl-card mx-auto" style="max-width: 720px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Profil bearbeiten</h2></header>';
echo '<div class="card-body">';
echo '<p class="mb-3">Benutzername: <strong>' . htmlspecialchars((string) ($user_details['nick'] ?? ''), ENT_QUOTES, 'UTF-8') . '</strong></p>';
echo '<form id="pdlProfilForm" name="profil" action="' . $form_action . '" method="post" novalidate>';
echo csrf_input();
echo '<input type="hidden" name="usercenter" value="profil">';
echo '<input type="hidden" name="submit" value="1">';

echo '<div class="mb-3">';
echo '<label for="pdlProfilEmail" class="form-label">E-Mail-Adresse</label>';
echo '<input type="email" id="pdlProfilEmail" name="email" class="form-control" required autocomplete="email" maxlength="128" value="' . $email_attr . '">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlProfilHomepage" class="form-label">Homepage <span class="text-muted small">(optional)</span></label>';
echo '<input type="url" id="pdlProfilHomepage" name="homepage" class="form-control" autocomplete="url" maxlength="128" placeholder="https://example.com" value="' . $homepage_attr . '">';
echo '</div>';

echo '<div class="form-check mb-3">';
echo '<input type="checkbox" id="pdlProfilGetLetter" name="get_letter" value="Y" class="form-check-input"' . $get_letter_checked . ' aria-describedby="pdlProfilGetLetterHelp">';
echo '<label for="pdlProfilGetLetter" class="form-check-label">Newsletter abonnieren</label>';
echo '<div id="pdlProfilGetLetterHelp" class="form-text">Etwa einmal im Monat. Entfernen Sie das Häkchen, um den Newsletter abzubestellen.</div>';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlProfilPwOld" class="form-label">Aktuelles Passwort</label>';
echo '<input type="password" id="pdlProfilPwOld" name="pw_old" class="form-control" required autocomplete="current-password" aria-describedby="pdlProfilPwOldHelp">';
echo '<div id="pdlProfilPwOldHelp" class="form-text">Zur Bestätigung Ihrer Identität, auch wenn Sie nur E-Mail-Adresse oder Homepage ändern.</div>';
echo '</div>';

// Passwortwechsel in eigenem Aufklappbereich mit Hilfetext.
echo '<div class="accordion mb-3" id="pdlProfilPwAccordion">';
echo '<div class="accordion-item">';
echo '<h3 class="accordion-header" id="pdlProfilPwHead">';
echo '<button class="accordion-button' . $pw_section_button_collapsed . '" type="button" data-bs-toggle="collapse" data-bs-target="#pdlProfilPwCollapse" aria-expanded="' . $pw_section_aria . '" aria-controls="pdlProfilPwCollapse">Passwort ändern (optional)</button>';
echo '</h3>';
echo '<div id="pdlProfilPwCollapse" class="accordion-collapse collapse' . $pw_section_expanded . '" aria-labelledby="pdlProfilPwHead">';
echo '<div class="accordion-body">';
echo '<p class="form-text mt-0">Lassen Sie diese Felder leer, wenn Sie Ihr Passwort nicht ändern möchten.</p>';

echo '<div class="mb-3">';
echo '<label for="pdlProfilPwNew" class="form-label">Neues Passwort</label>';
echo '<input type="password" id="pdlProfilPwNew" name="pw_new" class="form-control" autocomplete="new-password" minlength="8" aria-describedby="pdlProfilPwNewHelp">';
echo '<div id="pdlProfilPwNewHelp" class="form-text">' . $pw_hint . '</div>';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlProfilPwNew2" class="form-label">Neues Passwort (Wiederholung)</label>';
echo '<input type="password" id="pdlProfilPwNew2" name="pw_new2" class="form-control" autocomplete="new-password" minlength="8">';
echo '</div>';

echo '</div></div></div></div>';

echo '<button type="submit" id="pdlProfilSubmit" class="btn btn-primary">Speichern</button>';
echo '</form>';
echo '</div></section>';

$is_main_admin = ($current_user_id === 1);

echo '<section id="pdlProfileDelete" class="card pdl-card mx-auto mt-4 mb-4 border-danger" style="max-width: 720px;">';
echo '<header class="card-header text-bg-danger"><h2 class="h5 mb-0">Konto löschen</h2></header>';
echo '<div class="card-body">';
echo '<p>Wenn Sie Ihr Konto löschen, werden Ihr Profil und Ihre persönlichen Daten endgültig entfernt. Bestehende Kommentare und Releases bleiben ohne Ihren Namen erhalten.</p>';
if ($is_main_admin) {
    echo pdl_alert('warning', 'Der Hauptadministrator kann sich nicht selbst löschen.');
} else {
    if ($delete_errors) {
        $del_html = '';
        foreach ($delete_errors as $err) {
            $del_html .= ($del_html === '' ? '' : '<br>') . htmlspecialchars($err, ENT_QUOTES, 'UTF-8');
        }
        echo pdl_alert('danger', $del_html);
    }
    echo '<form id="pdlProfileDelForm" name="delete_account" method="post" action="' . $form_action . '#pdlProfileDelete" novalidate>';
    echo csrf_input();
    echo '<input type="hidden" name="usercenter" value="profil">';
    echo '<input type="hidden" name="delete_account" value="1">';
    echo '<div class="mb-3">';
    echo '<label for="pdlProfileDelPw" class="form-label">Bitte geben Sie zur Bestätigung Ihr aktuelles Passwort ein</label>';
    echo '<input type="password" id="pdlProfileDelPw" name="delete_pw" class="form-control" autocomplete="current-password" required>';
    echo '</div>';
    echo '<div class="form-check mb-3">';
    echo '<input class="form-check-input" type="checkbox" id="pdlProfileDelConfirm" name="delete_confirm" value="1" required>';
    echo '<label class="form-check-label" for="pdlProfileDelConfirm">Ich habe verstanden, dass diese Aktion nicht rückgängig gemacht werden kann.</label>';
    echo '</div>';
    echo '<button type="submit" id="pdlProfileDelSubmit" class="btn btn-danger">Konto endgültig löschen</button>';
    echo '</form>';
}
echo '</div></section>';
