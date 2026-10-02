<?php

/**
 * PowerDownload - Header Include
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);
ini_set('display_errors', '0');

// Backwards compatibility: file_id → release_id
if (isset($_GET['file_id'])) {
    $release_id = (int) $_GET['file_id'];
}

// Error Reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Zeitmessung
$rendertime1 = microtime(true);

// Include required Files
/** @psalm-suppress TypeDoesNotContainNull */
if (!isset($incdir)) {
    $incdir = "";
}
require($incdir . "pdl-inc/pdl_config.inc.php");
require_once($incdir . "pdl-inc/pdl_setup_required.inc.php");

// Noch keine Zugangsdaten (weder PDL_DB_* noch pdl_config.local.php):
// Hinweisseite mit Verweis auf den Web-Installer statt Verbindungsfehler.
if ($config_source === \PowerDownload\LocalConfig::SOURCE_DEFAULTS) {
    pdl_setup_required_page('defaults', $incdir);
}

require($incdir . "pdl-inc/pdl_db_class_" . strtolower($config_sql_type) . ".inc.php");
require($incdir . "pdl-inc/pdl_functions.inc.php");
require($incdir . "pdl-inc/pdl_csrf.inc.php");
require($incdir . "pdl-inc/pdl_admin_validation.inc.php");
require($incdir . "pdl-inc/pdl_admin_audit.inc.php");
require_once($incdir . "pdl-inc/pdl_locks.inc.php");
require_once($incdir . "pdl-inc/pdl_layout.inc.php");

// Initialize SQL Class
$db_handler = new pdl_db_class();

$db_handler->config_sql_server = $config_sql_server;
$db_handler->config_sql_port = $config_sql_port;
$db_handler->config_sql_database = $config_sql_database;
$db_handler->config_sql_user = $config_sql_user;
$db_handler->config_sql_password = $config_sql_password;
$db_handler->config_sql_persistent = $config_sql_persistent;

try {
    $db_handler->sql_connect();
} catch (\RuntimeException $e) {
    // Die Originalmeldung nennt Server und Benutzer: nur ins Fehlerprotokoll.
    error_log('PowerDownload: ' . $e->getMessage());
    pdl_setup_required_page('connection', $incdir);
}

$config_sql_password = "";
$db_handler->config_sql_password = "";

// Load Settings
$settings = [];
try {
    $settings_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['settings']);
    if ($settings_res === false) {
        throw new Exception("Settings table not found");
    }
    while ($settings_row = $db_handler->sql_fetch_array($settings_res)) {
        $settings[$settings_row['variablenname']] = $settings_row['wert'];
    }
} catch (mysqli_sql_exception|Exception $e) {
    // Datenbank erreichbar, aber ohne PowerDownload-Tabellen
    error_log('PowerDownload: ' . $e->getMessage());
    pdl_setup_required_page('tables', $incdir);
}

$script_file_raw = $settings['script_file'] ?? '';
if ($script_file_raw === '' || substr($script_file_raw, -1) === '?' || substr($script_file_raw, -1) === '&') {
    $settings['script_file'] = $script_file_raw;
} elseif (strpos($script_file_raw, '?') !== false) {
    $settings['script_file'] = $script_file_raw . "&";
} else {
    $settings['script_file'] = $script_file_raw . "?";
}

$settings['pdlversion'] = "v3.6.0";
$settings['debug'] = false;
$settings['showcopy'] = true;
$settings['phpversion'] = str_replace(".", "", phpversion());

if (empty($settings['ftp_server'])) {
    $settings['ftp_on'] = "N";
}

// Load Templates
$template = [];
$gettemplate_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['template']);
while ($gettemplate_row = $db_handler->sql_fetch_array($gettemplate_res)) {
    $template[$gettemplate_row['variablenname']] = $gettemplate_row['wert'];
}

// Load Users
$users = [];
$users_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['user']);
while ($users_row = $db_handler->sql_fetch_array($users_res)) {
    $user_id = $users_row['user_id'];
    $users[$user_id]['nick'] = $users_row['nick'];
    $users[$user_id]['email'] = ascii_encode($users_row['email']);
    $users[$user_id]['homepage'] = $users_row['homepage'];
}

// Load Replacements
$smilies = [];
$smilies_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='s' ORDER BY LENGTH(old) DESC");
while ($smilies_row = $db_handler->sql_fetch_array($smilies_res)) {
    $smilies[] = ["old" => $smilies_row['old'], "neu" => $smilies_row['neu']];
}

$glossary = [];
$glossary_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='g' ORDER BY LENGTH(old) DESC");
while ($glossary_row = $db_handler->sql_fetch_array($glossary_res)) {
    $glossary[] = ["old" => $glossary_row['old'], "neu" => $glossary_row['neu']];
}

$badwords = [];
$badwords_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='b' ORDER BY LENGTH(old) DESC");
while ($badwords_row = $db_handler->sql_fetch_array($badwords_res)) {
    $badwords[] = $badwords_row['old'];
}

// IP Lock säubern
$loesch = time() - 24 * 3600;
$db_handler->sql_query("DELETE FROM " . $sql_table['iplock'] . " WHERE art='vote' AND time<" . $loesch);
$loesch = time() - 60;
$db_handler->sql_query("DELETE FROM " . $sql_table['iplock'] . " WHERE art='comment' AND time<" . $loesch);

// Get variables from request (replaces register_globals)
$ordner_id = isset($_GET['ordner_id']) ? (int) $_GET['ordner_id'] : (isset($_POST['ordner_id']) ? (int) $_POST['ordner_id'] : 0);
$release_id = isset($_GET['release_id']) ? (int) $_GET['release_id'] : (isset($_POST['release_id']) ? (int) $_POST['release_id'] : ($release_id ?? 0));
$screen_id = isset($_GET['screen_id']) ? (int) $_GET['screen_id'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$usercenter = is_string($_GET['usercenter'] ?? null) ? $_GET['usercenter'] : (is_string($_POST['usercenter'] ?? null) ? $_POST['usercenter'] : '');
$show_search = isset($_GET['show_search']) ? (int) $_GET['show_search'] : 0;
$show_stats = isset($_GET['show_stats']) ? (int) $_GET['show_stats'] : 0;
$wrong_referer = isset($_GET['wrong_referer']) ? (int) $_GET['wrong_referer'] : 0;
$wrong_rights = isset($_GET['wrong_rights']) ? (int) $_GET['wrong_rights'] : 0;
$login = isset($_POST['login']) ? (int) $_POST['login'] : (isset($_GET['login']) ? (int) $_GET['login'] : 0);
$logout = isset($_GET['logout']) ? (int) $_GET['logout'] : 0;
$load_file = isset($_GET['load_file']) && is_scalar($_GET['load_file']) ? (int) $_GET['load_file'] : 0;
$nick = $_POST['nick'] ?? '';
$pw = $_POST['pw'] ?? '';
$submit = isset($_POST['submit']) ? 1 : (isset($_GET['submit']) ? (int) $_GET['submit'] : 0);
$change_list = isset($_GET['change_list']) ? (int) $_GET['change_list'] : 0;
$orderseq = $_GET['orderseq'] ?? $_POST['orderseq'] ?? '';
$orderby = $_GET['orderby'] ?? $_POST['orderby'] ?? '';
$perpage = $_GET['perpage'] ?? $_POST['perpage'] ?? '';

// User-Center-Felder (Register, Profil, Lost, Comments)
$email = $_POST['email'] ?? $_GET['email'] ?? '';
$pw_old = $_POST['pw_old'] ?? '';
$pw_new = $_POST['pw_new'] ?? '';
$pw_new2 = $_POST['pw_new2'] ?? '';
$homepage = $_POST['homepage'] ?? '';
$get_letter = $_POST['get_letter'] ?? '';
$titel = is_string($_POST['titel'] ?? null) ? $_POST['titel'] : '';
// Kommentartext (POST) bzw. Suchbegriff (POST beim Absenden, GET beim Blättern)
$text = pdl_request_string('text');
$remind_code = $_GET['remind_code'] ?? $_POST['remind_code'] ?? '';
$csrf_token = pdl_request_string('csrf_token');

// Suche: Bereich (Whitelist im Suchmodul)
$in = pdl_request_string('in', 'texttitel');

// Bewertung (nur per POST) und Rückmeldung nach der Weiterleitung
$vote = isset($_POST['vote']) && is_scalar($_POST['vote']) ? (int) $_POST['vote'] : 0;
$vote_id = isset($_POST['vote_id']) && is_scalar($_POST['vote_id']) ? (int) $_POST['vote_id'] : 0;
$vote_feedback = isset($_GET['voted']) ? (int) $_GET['voted'] : 0;

// Echte Startseite (GET ohne Unterseiten-Parameter)? Nur dort erscheinen die
// Startseiten-Widgets, siehe pdl_show_dashboard_widgets().
$pdl_is_start_page = ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    && $usercenter === '' && $release_id === 0 && $screen_id === 0 && $ordner_id <= 0
    && $show_search === 0 && $show_stats === 0 && $wrong_referer === 0 && $wrong_rights === 0
    && $load_file === 0;

/** @psalm-suppress RedundantCondition */
$inadmin = $inadmin ?? 0;

// andere Vars
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// Cookie-Sicherheits-Defaults (Secure nur bei HTTPS, sonst Browser löscht Cookie sofort)
$cookie_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$cookie_opts = [
    'expires' => time() + 8760 * 3600,
    'path' => '/',
    'httponly' => true,
    'secure' => $cookie_secure,
    'samesite' => 'Lax',
];
$cookie_clear = [
    'expires' => time() - 3600,
    'path' => '/',
    'httponly' => true,
    'secure' => $cookie_secure,
    'samesite' => 'Lax',
];

// Session für CSRF/Login-Status starten
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => $cookie_secure,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Sicherheitslücke schließen:
$user_details = null;
$ugroup_id = 0;
$user_rights = [];

// Check Cookie (Session-Token-basiert statt Password-Hash)
$login_id = is_string($_COOKIE['login_id'] ?? null) ? $_COOKIE['login_id'] : '';
$login_token = is_string($_COOKIE['login_token'] ?? null) ? $_COOKIE['login_token'] : '';

if ($login_id !== '' && $login_token !== '') {
    $login_id_escaped = $db_handler->sql_escape_int($login_id);
    $login_token_escaped = $db_handler->sql_escape_string($login_token);
    $check_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE user_id='" . $login_id_escaped . "' AND session_token='" . $login_token_escaped . "' AND session_token != ''");
    $check = $db_handler->sql_num_rows($check_res);
    if ($check == 1) {
        $user_details = $db_handler->sql_fetch_array($check_res);
    } else {
        setcookie("login_id", "", $cookie_clear);
        setcookie("login_token", "", $cookie_clear);
    }
}

// Rechte: Angemeldete bekommen die Rechte ihrer Benutzergruppe, nicht
// angemeldete Besucher die der Gastgruppe aus der Einstellung guest_group_id
// (Vorgabe 3 „Gast“). Fehlt die Gruppe oder die Einstellung (z. B. vor
// update.php), gelten sichere Vorgaben: nur Download. Gäste erhalten nie
// Admin-Rechte, auch wenn die Gastgruppe sie hätte.
$user_rights = [
    'download' => 'Y', 'vote' => 'N', 'addcomments' => 'N', 'comment' => 'N',
    'adminaccess' => 'N', 'addfiles' => 'N', 'editfiles' => 'N', 'delfiles' => 'N',
    'adddirs' => 'N', 'editdirs' => 'N', 'deldirs' => 'N', 'adduser' => 'N',
    'edituser' => 'N', 'deluser' => 'N', 'settings' => 'N', 'templates' => 'N',
    'replacements' => 'N', 'backup' => 'N',
];
if ($user_details) {
    $ugroup_id = (int) $user_details['ugroup_id'];
} else {
    $ugroup_id = (int) ($settings['guest_group_id'] ?? 0);
}

if ($ugroup_id > 0) {
    $ugroup_id_escaped = $db_handler->sql_escape_int($ugroup_id);
    $rights_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['usergroup'] . " WHERE ugroup_id='" . $ugroup_id_escaped . "'"));
    if (is_array($rights_row)) {
        if ($user_details) {
            $user_rights = $rights_row;
        } else {
            foreach (['download', 'vote', 'addcomments'] as $guest_right) {
                $user_rights[$guest_right] = (($rights_row[$guest_right] ?? 'N') === 'Y') ? 'Y' : 'N';
            }
            $user_rights['ugroup_id'] = $rights_row['ugroup_id'] ?? $ugroup_id;
            $user_rights['name'] = $rights_row['name'] ?? '';
        }
    }
}

// Anmeldung im Adminbereich (pdl-admin/index.php) oder im öffentlichen Bereich?
$pdl_auth_admin_entry = (basename($_SERVER['PHP_SELF'] ?? '') === "index.php");

// Login
if ($login == 1 && is_string($nick) && is_string($pw) && $nick !== '' && $pw !== '') {
    // Rücksprungziel: Release, von dem aus sich der Besucher anmeldet
    // (back_release aus dem Anmeldeformular, siehe pdl_ulogin.modul.php).
    // Der Anhang steht hinter login_error=N, damit pdl-admin/header.inc.php
    // die Weiterleitung weiterhin erkennt.
    $login_back = $pdl_auth_admin_entry ? 0 : pdl_back_release($_POST['back_release'] ?? null);
    $login_back_query = pdl_back_release_query($login_back);

    // CSRF: gültiges Token aus dem Formular. Ohne Token nur, wenn nichts auf
    // eine fremde Seite hindeutet (Sec-Fetch-Site, Origin, Referer).
    if (!csrf_verify(is_string($csrf_token) ? $csrf_token : null) && pdl_request_is_cross_site()) {
        header("Location: " . ($pdl_auth_admin_entry ? "index.php" : $settings['script_file'] . "usercenter=login&login_error=3" . $login_back_query));
        exit;
    }

    // Sperre: höchstens 5 Fehlversuche je IP-Adresse in 15 Minuten, gleich
    // für welches Konto (pdl_locks.inc.php).
    $login_ip = substr($ip, 0, 32);
    if (pdl_login_failures($db_handler, $sql_table, $login_ip) >= 5) {
        header("Location: " . $settings['script_file'] . "usercenter=login&login_error=2" . $login_back_query);
        exit;
    }

    $nick_escaped = $db_handler->sql_escape_string($nick);
    $check_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE nick='" . $nick_escaped . "'");
    $login_temp = $db_handler->sql_num_rows($check_res) == 1 ? $db_handler->sql_fetch_array($check_res) : null;

    $password_ok = false;
    if ($login_temp) {
        $stored = (string) ($login_temp['passwort'] ?? '');
        $info = password_get_info($stored);
        if (($info['algo'] ?? null) !== null && ($info['algo'] ?? 0) !== 0) {
            // Moderner Hash
            $password_ok = password_verify($pw, $stored);
        } elseif (strlen($stored) === 32 && ctype_xdigit($stored)) {
            // Legacy MD5 - nach erfolgreichem Vergleich auf bcrypt migrieren
            if (hash_equals($stored, md5($pw))) {
                $password_ok = true;
                $new_hash = password_hash($pw, PASSWORD_DEFAULT);
                $new_hash_safe = $db_handler->sql_escape_string($new_hash);
                $user_id_safe = $db_handler->sql_escape_int($login_temp['user_id'] ?? 0);
                $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET passwort='" . $new_hash_safe . "' WHERE user_id='" . $user_id_safe . "'");
            }
        }
    }

    if ($password_ok && $login_temp) {
        // Nur die Fehlversuche dieses Kontos von dieser Adresse löschen.
        // Fehlversuche gegen andere Konten zählen weiter; sonst ließe sich
        // die Sperre mit einer Anmeldung am eigenen Konto zurücksetzen.
        pdl_login_failures_clear($db_handler, $sql_table, $login_ip, (int) ($login_temp['user_id'] ?? 0));

        // Sitzungs-Token: ein vorhandenes weiterverwenden, damit eine Anmeldung
        // auf einem zweiten Gerät das erste nicht abmeldet. Abmelden, Passwort
        // vergessen und Passwortwechsel setzen das Token neu (alle Geräte ab).
        $session_token = (string) ($login_temp['session_token'] ?? '');
        if (preg_match('/^[0-9a-f]{64}$/', $session_token) !== 1) {
            $session_token = bin2hex(random_bytes(32));
        }
        $session_token_safe = $db_handler->sql_escape_string($session_token);
        $user_id_safe = $db_handler->sql_escape_int($login_temp['user_id'] ?? 0);
        $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET session_token='" . $session_token_safe . "', lastactive='" . time() . "' WHERE user_id='" . $user_id_safe . "'");

        session_regenerate_id(true);
        setcookie("login_id", (string) ($login_temp['user_id'] ?? ''), $cookie_opts);
        setcookie("login_token", $session_token, $cookie_opts);

        // Zurück zum Release, sofern es (noch) öffentlich ist
        if ($login_back > 0 && $db_handler->sql_fetch_array($db_handler->sql_query(
            "SELECT release_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($login_back) . "' AND released='Y'"
        )) === null) {
            $login_back = 0;
        }

        if ($pdl_auth_admin_entry) {
            header("Location: index.php");
        } elseif ($login_back > 0) {
            // Rückmeldung „Sie sind jetzt angemeldet.“ auf der Release-Seite
            header("Location: " . $settings['script_file'] . "release_id=" . $login_back . "&login_ok=1");
        } else {
            // Rückmeldung „Sie sind jetzt angemeldet.“ im Anmelde-Modul
            header("Location: " . $settings['script_file'] . "usercenter=login&login_ok=1");
        }
        exit;
    } else {
        // Fehlversuch mit dem versuchten Konto speichern (0 = Benutzername unbekannt)
        pdl_login_failure_add($db_handler, $sql_table, $login_ip, (int) ($login_temp['user_id'] ?? 0));
        header("Location: " . $settings['script_file'] . "usercenter=login&login_error=1" . $login_back_query);
        exit;
    }
}

// Logout: per POST (Formular mit Token) oder per Link (logout=1, mit Token
// siehe pdl_logout_url()). Kommt ein Link ohne gültiges Token nachweislich
// von einer fremden Seite, wird nicht abgemeldet, sondern nachgefragt.
$pdl_logout_post = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') && is_scalar($_POST['logout'] ?? null) && (int) $_POST['logout'] === 1;
if ($logout == 1 || $pdl_logout_post) {
    if (!csrf_verify(is_string($csrf_token) ? $csrf_token : null) && pdl_request_is_cross_site()) {
        header("Location: " . ($pdl_auth_admin_entry ? "index.php" : $settings['script_file'] . "usercenter=login&logout_confirm=1"));
        exit;
    }
    // Session-Token in DB löschen, damit Cookie auf anderen Geräten ungültig wird
    if ($user_details) {
        $user_id_safe = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);
        $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET session_token='' WHERE user_id='" . $user_id_safe . "'");
    }
    setcookie("login_id", "", $cookie_clear);
    setcookie("login_token", "", $cookie_clear);
    // Alten (legacy) Cookie ebenfalls löschen, falls noch gesetzt
    setcookie("login_pw", "", $cookie_clear);
    $_SESSION = [];
    session_destroy();
    if ($pdl_auth_admin_entry) {
        header("Location: index.php");
    } else {
        // Rückmeldung „Sie wurden abgemeldet.“ im Anmelde-Modul
        header("Location: " . $settings['script_file'] . "usercenter=login&logout_ok=1");
    }
    exit;
}

// Profil speichern und Konto löschen vor jeder Ausgabe verarbeiten, damit
// Cookies gesetzt und Weiterleitungen gesendet werden können (die Navigation
// zeigt danach sofort den richtigen Anmeldestatus). Bei Fehlern wird die
// Ausgabe gepuffert und von pdl_uprofil.modul.php später ausgegeben.
$pdl_uprofil_output = null;
if ($user_details && $inadmin != 1 && strtolower($usercenter) === 'profil' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $pdl_prerender = true;
    ob_start();
    include __DIR__ . '/pdl_uprofil.modul.php';
    $pdl_uprofil_output = (string) ob_get_clean();
    $pdl_prerender = false;
}

// Kommentar speichern ebenfalls vor jeder Ausgabe (Post/Redirect/Get): Nach
// dem Speichern leitet pdl_ucomments.modul.php auf das Release um, Neuladen
// schickt den Kommentar also nicht noch einmal. Bei Fehlern wird die Ausgabe
// gepuffert und beim regulären Einbinden des Moduls ausgegeben.
$pdl_ucomments_output = null;
if ($inadmin != 1 && strtolower($usercenter) === 'comments' && $submit == 1 && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $pdl_prerender = true;
    ob_start();
    include __DIR__ . '/pdl_ucomments.modul.php';
    $pdl_ucomments_output = (string) ob_get_clean();
    $pdl_prerender = false;
}

// Download: Hotlink-Schutz, Recht "download", Existenz der Datei, Zählerschutz
// und Auslieferung. Alle Dateilinks der Release-Seite laufen über load_file.
$pdl_download_missing = false;
$pdl_download_release_id = 0;
if ($load_file > 0) {
    $file_id = $load_file;
    $dl_allowed = true;
    if (($settings['referer_check'] ?? 'N') == "Y") {
        $dl_allowed = false;
        $http_referer = $_SERVER['HTTP_REFERER'] ?? '';
        $referer_host = strtolower((string) parse_url($http_referer, PHP_URL_HOST));
        $own_host = strtolower((string) preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
        if ($referer_host !== '' && $referer_host === $own_host) {
            // Klick auf der eigenen Seite ist immer erlaubt
            $dl_allowed = true;
        } else {
            $all_referer = explode(" ", $settings['allowed_referer'] ?? '');
            foreach ($all_referer as $allowed_referer) {
                if ($allowed_referer !== '' && preg_match("/" . preg_quote($allowed_referer, '/') . "/siU", $http_referer)) {
                    $dl_allowed = true;
                    break;
                }
            }
        }
    }

    if (!$dl_allowed) {
        header("Location: " . $settings['script_file'] . "wrong_referer=1");
        exit;
    }

    $file_id_escaped = $db_handler->sql_escape_int($file_id);
    $dl_row = $db_handler->sql_fetch_array($db_handler->sql_query(
        "SELECT f.file_id, f.url, f.release_id, r.released FROM " . $sql_table['files'] . " AS f"
        . " LEFT JOIN " . $sql_table['release'] . " AS r ON r.release_id = f.release_id"
        . " WHERE f.file_id='" . $file_id_escaped . "'"
    ));
    $dl_public = $dl_row !== null && ($dl_row['released'] ?? 'N') === 'Y';

    if (($user_rights['download'] ?? 'Y') == "N") {
        // Hinweisseite mit dem Release als Rücksprungziel (Anmelde-Link und
        // „Zurück zum Release“); versteckte Releases werden nicht genannt.
        $dl_back = $dl_public ? (int) ($dl_row['release_id'] ?? 0) : 0;
        header("Location: " . $settings['script_file'] . "wrong_rights=1" . pdl_back_release_query($dl_back));
        exit;
    }

    $dl_target = null;
    if ($dl_public) {
        $pdl_download_release_id = (int) ($dl_row['release_id'] ?? 0);
        $dl_target = pdl_download_target((string) ($dl_row['url'] ?? ''), dirname(__DIR__));
    }

    if ($dl_target === null) {
        // Unbekannte ID, verstecktes Release oder Datei fehlt: Hinweisseite statt leerer Weiterleitung.
        http_response_code(404);
        $pdl_download_missing = true;
    } else {
        // Zählerschutz: je Datei höchstens einmal pro Stunde, für Angemeldete
        // je Konto, für Gäste je IP-Adresse (pdl_locks.inc.php).
        $dl_since = time() - 3600;
        $dl_user_id = (int) ($user_details['user_id'] ?? 0);
        $db_handler->sql_query("DELETE FROM " . $sql_table['iplock'] . " WHERE art='download' AND time<" . $dl_since);
        if (!pdl_lock_exists($db_handler, $sql_table, 'download', $file_id, $dl_user_id, $ip)) {
            pdl_lock_add($db_handler, $sql_table, 'download', $file_id, $dl_user_id, $ip);
            $db_handler->sql_query("UPDATE " . $sql_table['files'] . " SET downloads=downloads+1 WHERE file_id='" . $file_id_escaped . "'");
        }

        if ($dl_target['type'] === 'local') {
            pdl_send_file($dl_target['path']);
        }
        header("Location: " . $dl_target['url']);
        exit;
    }
}

// Bewertung speichern (Post/Redirect/Get). Die Rückmeldung steht nach der
// Weiterleitung über der Bewertung auf der Release-Seite (Sitzung).
// Eine Bewertung je Release in 24 Stunden: für Angemeldete je Konto, für
// Gäste je IP-Adresse (pdl_locks.inc.php).
if ($vote === 1 && $release_id > 0 && $inadmin != 1) {
    $vote_status = 'ok';
    $vote_release_safe = $db_handler->sql_escape_int($release_id);
    $vote_user_id = (int) ($user_details['user_id'] ?? 0);
    if (!csrf_verify($csrf_token)) {
        $vote_status = 'csrf';
    } elseif (($user_rights['vote'] ?? 'N') !== 'Y') {
        $vote_status = 'rights';
    } elseif ($vote_id < 1 || $vote_id > 10) {
        $vote_status = 'invalid';
    } elseif ($db_handler->sql_fetch_array($db_handler->sql_query("SELECT release_id FROM " . $sql_table['release'] . " WHERE release_id='" . $vote_release_safe . "' AND released='Y'")) === null) {
        $vote_status = 'invalid';
    } elseif (pdl_lock_exists($db_handler, $sql_table, 'vote', $release_id, $vote_user_id, $ip)) {
        $vote_status = 'locked';
    } else {
        $vote_saved = pdl_lock_add($db_handler, $sql_table, 'vote', $release_id, $vote_user_id, $ip)
            && $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET votes=votes+1, voted=voted+" . $vote_id . " WHERE release_id='" . $vote_release_safe . "'") !== false;
        $vote_status = $vote_saved ? 'ok' : 'error';
    }
    $_SESSION['pdl_vote_flash'] = ['release_id' => $release_id, 'status' => $vote_status, 'vote' => $vote_id];
    header("Location: " . $settings['script_file'] . "release_id=" . $release_id . "&voted=1#pdlVote");
    exit;
}

// Individuelle Sortierung je Besucher (Cookie). Nur bekannte Werte werden
// übernommen; die Abfragen selbst prüfen zusätzlich gegen ihre Whitelist.
$pdl_list_orderby = ['name', 'text', 'time', 'date', 'views', 'votes', 'voted', 'voted/votes'];
$pdl_list_clean = static function (mixed $seq, mixed $by, mixed $per) use ($pdl_list_orderby): array {
    $seq = is_string($seq) && strtoupper($seq) === 'DESC' ? 'DESC' : 'ASC';
    $by = is_string($by) && in_array($by, $pdl_list_orderby, true) ? $by : 'name';
    $per = is_scalar($per) ? (int) $per : 0;
    $per = ($per >= 5 && $per <= 200) ? (string) $per : '';
    return [$seq, $by, $per];
};

if ($change_list == 1) {
    [$list_seq, $list_by, $list_per] = $pdl_list_clean($orderseq, $orderby, $perpage);
    setcookie("pdl_list", $list_seq . "###" . $list_by . "###" . $list_per, [
        'expires' => time() + 8760 * 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    header("Location: " . $settings['script_file']);
    exit;
}

$pdl_list = is_string($_COOKIE['pdl_list'] ?? null) ? $_COOKIE['pdl_list'] : '';
if ($pdl_list !== '' && $inadmin != 1) {
    $list_ops = explode("###", $pdl_list);
    [$list_seq, $list_by, $list_per] = $pdl_list_clean($list_ops[0] ?? '', $list_ops[1] ?? '', $list_ops[2] ?? '');
    $settings['orderseq'] = $list_seq;
    $settings['orderby'] = $list_by;
    if ($list_per !== '') {
        $settings['perpage'] = $list_per;
    }
}
