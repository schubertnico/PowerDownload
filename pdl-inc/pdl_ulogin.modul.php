<?php
/**
 * PowerDownload - User Login Module
 * @license MIT
 */

$script_file = htmlspecialchars($settings['script_file'] ?? '');

$login_error = isset($_GET['login_error']) ? (int) $_GET['login_error'] : 0;
if ($login_error === 1) {
    echo pdl_alert('danger', 'Benutzername oder Passwort ist nicht korrekt.');
} elseif ($login_error === 2) {
    echo pdl_alert('warning', 'Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.');
}

// Bug 2.1: Leeres Login-Formular sauber abfangen statt zur Startseite zu fallen.
$is_post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$posted_login = $is_post && isset($_POST['login']) && (int) $_POST['login'] === 1;
$nick_posted = (string) ($_POST['nick'] ?? '');
$pw_posted = (string) ($_POST['pw'] ?? '');
if ($posted_login && ($nick_posted === '' || $pw_posted === '')) {
    echo pdl_alert('warning', 'Bitte Benutzername und Passwort eingeben.');
}

$nick_attr = htmlspecialchars($nick_posted, ENT_QUOTES, 'UTF-8');

echo '<section class="card pdl-card mx-auto" style="max-width: 540px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Login</h2></header>';
echo '<div class="card-body">';
echo '<form name="login" method="post" action="downloads.php">';
echo csrf_input();
echo '<input type="hidden" name="login" value="1">';
echo '<input type="hidden" name="usercenter" value="login">';

echo '<div class="mb-3">';
echo '<label for="pdlLoginNick" class="form-label">Nickname</label>';
echo '<input type="text" id="pdlLoginNick" name="nick" class="form-control" required autocomplete="username" value="' . $nick_attr . '">';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="pdlLoginPw" class="form-label">Passwort</label>';
echo '<input type="password" id="pdlLoginPw" name="pw" class="form-control" required autocomplete="current-password">';
echo '</div>';

echo '<button type="submit" class="btn btn-primary">Login</button>';
echo '</form>';
echo '<p class="mt-3 mb-0 small"><a href="' . $script_file . 'usercenter=lost">Zugangsdaten vergessen?</a> &middot; <a href="' . $script_file . 'usercenter=register">Neu registrieren</a></p>';
echo '</div></section>';
