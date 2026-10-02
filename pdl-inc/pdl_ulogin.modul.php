<?php
/**
 * PowerDownload - Anmelden und Abmelden
 * @license MIT
 *
 * Die eigentliche Anmeldung und Abmeldung verarbeitet pdl_header.inc.php
 * (vor jeder Ausgabe, wegen der Cookies). Dieses Modul zeigt das Formular
 * und die Rückmeldungen:
 *   login_ok=1        „Sie sind jetzt angemeldet.“
 *   logout_ok=1       „Sie wurden abgemeldet.“
 *   logout_confirm=1  Rückfrage, wenn ein Abmelde-Link von fremder Seite kam
 *   login_error=1..3  falsche Zugangsdaten, zu viele Versuche, Sicherheitsprüfung
 *   back_release=N    Rücksprungziel: nach der Anmeldung zurück zu Release N
 *                     (Hinweise auf der Release-Seite und bei fehlendem
 *                     Download-Recht, siehe pdl_header.inc.php)
 */

require_once __DIR__ . '/pdl_locks.inc.php';

$script_file = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');
$login_back = pdl_back_release($_POST['back_release'] ?? ($_GET['back_release'] ?? null));

$pdl_logout_form = static function (string $script_file, string $label, string $class): string {
    return '<form id="pdlLogoutForm" name="logout" method="post" action="' . $script_file . 'usercenter=login" class="d-inline">'
        . csrf_input()
        . '<input type="hidden" name="logout" value="1">'
        . '<button type="submit" id="pdlLogoutSubmit" class="' . $class . '">' . $label . '</button>'
        . '</form>';
};

if ($user_details ?? null) {
    $nick_html = htmlspecialchars((string) ($user_details['nick'] ?? ''), ENT_QUOTES, 'UTF-8');

    if (!empty($_GET['logout_confirm'])) {
        echo '<section class="card pdl-card mx-auto" style="max-width: 540px;">';
        echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Abmelden</h2></header>';
        echo '<div class="card-body">';
        echo '<p>Möchten Sie sich als <strong>' . $nick_html . '</strong> abmelden?</p>';
        echo '<div class="d-flex flex-wrap gap-2">';
        echo $pdl_logout_form($script_file, 'Abmelden', 'btn btn-primary');
        echo '<a class="btn btn-outline-light" href="' . $script_file . '">Angemeldet bleiben</a>';
        echo '</div></div></section>';
        return;
    }

    if (!empty($_GET['login_ok'])) {
        echo pdl_alert('success', '<strong>Sie sind jetzt angemeldet.</strong> Willkommen, ' . $nick_html . '!');
    } else {
        echo pdl_alert('info', 'Sie sind bereits als <strong>' . $nick_html . '</strong> angemeldet.');
    }
    echo '<div class="d-flex flex-wrap gap-2 justify-content-center mt-3">';
    echo '<a class="btn btn-primary" id="pdlLoginHome" href="' . $script_file . '">Zur Startseite</a>';
    echo '<a class="btn btn-outline-light" id="pdlLoginProfil" href="' . $script_file . 'usercenter=profil">Zum Profil</a>';
    echo $pdl_logout_form($script_file, 'Abmelden', 'btn btn-outline-light');
    echo '</div>';
    return;
}

$login_error = isset($_GET['login_error']) ? (int) $_GET['login_error'] : 0;
if (!empty($_GET['logout_ok'])) {
    echo pdl_alert('success', '<strong>Sie wurden abgemeldet.</strong>');
}
if (!empty($_GET['login_ok'])) {
    // Weiterleitung nach erfolgreicher Anmeldung, aber kein gültiges Cookie
    echo pdl_alert('warning', '<strong>Die Anmeldung hat nicht geklappt.</strong> Bitte erlauben Sie Cookies für diese Seite und melden Sie sich erneut an.');
}
if ($login_error === 1) {
    echo pdl_alert('danger', 'Benutzername oder Passwort ist nicht korrekt. Bitte versuchen Sie es erneut.');
} elseif ($login_error === 2) {
    echo pdl_alert('warning', 'Zu viele Fehlversuche. Bitte versuchen Sie es in 15 Minuten erneut.');
} elseif ($login_error === 3) {
    echo pdl_alert('danger', 'Die Anmeldung wurde aus Sicherheitsgründen abgelehnt. Bitte laden Sie diese Seite neu und melden Sie sich erneut an.');
}

// Leeres Formular sauber abfangen statt zur Startseite zu fallen.
$is_post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$posted_login = $is_post && isset($_POST['login']) && (int) $_POST['login'] === 1;
$nick_posted = is_string($_POST['nick'] ?? null) ? $_POST['nick'] : '';
$pw_posted = is_string($_POST['pw'] ?? null) ? $_POST['pw'] : '';
if ($posted_login && ($nick_posted === '' || $pw_posted === '')) {
    echo pdl_alert('warning', 'Bitte geben Sie Benutzername und Passwort ein.');
}

$nick_attr = htmlspecialchars($nick_posted, ENT_QUOTES, 'UTF-8');

echo '<section class="card pdl-card mx-auto" style="max-width: 540px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Anmelden</h2></header>';
echo '<div class="card-body">';
echo '<form id="pdlLoginForm" name="login" method="post" action="' . $script_file . 'usercenter=login" novalidate>';
echo csrf_input();
echo '<input type="hidden" name="login" value="1">';
echo '<input type="hidden" name="usercenter" value="login">';
if ($login_back > 0) {
    echo '<input type="hidden" name="back_release" value="' . $login_back . '">';
}

echo '<div class="mb-3">';
echo '<label for="pdlLoginNick" class="form-label">Benutzername</label>';
echo '<input type="text" id="pdlLoginNick" name="nick" class="form-control" required autocomplete="username" value="' . $nick_attr . '">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlLoginPw" class="form-label">Passwort</label>';
echo '<input type="password" id="pdlLoginPw" name="pw" class="form-control" required autocomplete="current-password">';
echo '</div>';

echo '<button type="submit" id="pdlLoginSubmit" class="btn btn-primary">Anmelden</button>';
echo '</form>';
echo '<p class="mt-3 mb-0 small"><a id="pdlLoginLost" href="' . $script_file . 'usercenter=lost">Passwort vergessen?</a> &middot; <a id="pdlLoginRegister" href="' . $script_file . 'usercenter=register">Neu registrieren</a></p>';
echo '</div></section>';
