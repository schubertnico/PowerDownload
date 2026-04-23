<?php
/**
 * PowerDownload - User Login Module
 * @license MIT
 */

$script_file = htmlspecialchars($settings['script_file'] ?? '');

$login_error = isset($_GET['login_error']) ? (int) $_GET['login_error'] : 0;
if ($login_error === 1) {
    echo '<center><b style="color:#c00">Benutzername oder Passwort ist nicht korrekt.</b></center>';
} elseif ($login_error === 2) {
    echo '<center><b style="color:#c00">Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.</b></center>';
}

echo "
<form name=\"login\" method=\"post\" action=\"" . $script_file . "login=1\">
" . csrf_input() . "
" . replace($template['ulogin_form'] ?? '', []) . "
</form>";
