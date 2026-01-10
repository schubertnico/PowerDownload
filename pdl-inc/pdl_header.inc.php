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

// Backwards compatibility: file_id → release_id
if (isset($_GET['file_id'])) {
    $release_id = (int) $_GET['file_id'];
}

// Error Reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Zeitmessung
$rendertime1 = microtime(true);

// Include required Files
if (!isset($incdir)) {
    $incdir = "";
}
require($incdir . "pdl-inc/pdl_config.inc.php");
require($incdir . "pdl-inc/pdl_db_class_" . strtolower($config_sql_type) . ".inc.php");
require($incdir . "pdl-inc/pdl_functions.inc.php");

// Initialize SQL Class
$db_handler = new pdl_db_class();

$db_handler->config_sql_server = $config_sql_server;
$db_handler->config_sql_database = $config_sql_database;
$db_handler->config_sql_user = $config_sql_user;
$db_handler->config_sql_password = $config_sql_password;
$db_handler->config_sql_persistent = $config_sql_persistent;

$db_handler->sql_connect();

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
    // Database not initialized - show friendly error
    http_response_code(503);
    echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>PowerDownload - Setup erforderlich</title>
    <style>
        body { background: #1a1a1a; color: #fff; font-family: Arial, sans-serif; padding: 50px; text-align: center; }
        .box { background: #2a2a2a; border: 2px solid #9B0000; padding: 30px; max-width: 500px; margin: 0 auto; border-radius: 8px; }
        h1 { color: #ff6b6b; }
        a { color: #4dabf7; }
        code { background: #333; padding: 2px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Setup erforderlich</h1>
        <p>Die Datenbank-Tabellen wurden noch nicht erstellt.</p>
        <p>Bitte führe zuerst das Setup aus:</p>
        <p><a href="setup.php"><strong>setup.php aufrufen</strong></a></p>
        <hr style="border-color:#444;margin:20px 0;">
        <p><small>Fehler: ' . htmlspecialchars($e->getMessage()) . '</small></p>
    </div>
</body>
</html>';
    exit;
}

if (preg_match("/\?/", $settings['script_file'] ?? '')) {
    $settings['script_file'] = ($settings['script_file'] ?? '') . "&";
} else {
    $settings['script_file'] = ($settings['script_file'] ?? '') . "?";
}

$settings['pdlversion'] = "v3.0.3";
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
    $users[$user_id]['icq'] = $users_row['icq'];
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
$usercenter = $_GET['usercenter'] ?? $_POST['usercenter'] ?? '';
$show_search = isset($_GET['show_search']) ? (int) $_GET['show_search'] : 0;
$show_stats = isset($_GET['show_stats']) ? (int) $_GET['show_stats'] : 0;
$wrong_referer = isset($_GET['wrong_referer']) ? (int) $_GET['wrong_referer'] : 0;
$wrong_rights = isset($_GET['wrong_rights']) ? (int) $_GET['wrong_rights'] : 0;
$login = isset($_POST['login']) ? (int) $_POST['login'] : (isset($_GET['login']) ? (int) $_GET['login'] : 0);
$logout = isset($_GET['logout']) ? (int) $_GET['logout'] : 0;
$load_file = isset($_GET['load_file']) ? (int) $_GET['load_file'] : 0;
$nick = $_POST['nick'] ?? '';
$pw = $_POST['pw'] ?? '';
$submit = isset($_POST['submit']) ? 1 : (isset($_GET['submit']) ? (int) $_GET['submit'] : 0);
$change_list = isset($_GET['change_list']) ? (int) $_GET['change_list'] : 0;
$orderseq = $_GET['orderseq'] ?? $_POST['orderseq'] ?? '';
$orderby = $_GET['orderby'] ?? $_POST['orderby'] ?? '';
$perpage = $_GET['perpage'] ?? $_POST['perpage'] ?? '';
$inadmin = isset($inadmin) ? (int) $inadmin : 0;

// andere Vars
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// Sicherheitslücke schließen:
$user_details = null;
$ugroup_id = 0;
$user_rights = [];

// Check Cookie
$login_id = $_COOKIE['login_id'] ?? '';
$login_pw = $_COOKIE['login_pw'] ?? '';

if ($login_id !== '' && $login_pw !== '') {
    $login_id_escaped = $db_handler->sql_escape_string($login_id);
    $login_pw_escaped = $db_handler->sql_escape_string($login_pw);
    $check_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE user_id='" . $login_id_escaped . "' AND passwort='" . $login_pw_escaped . "'");
    $check = $db_handler->sql_num_rows($check_res);
    if ($check == 1) {
        $user_details = $db_handler->sql_fetch_array($check_res);
    } else {
        setcookie("login_id", "", [
            'expires' => time() + 8760 * 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        setcookie("login_pw", "", [
            'expires' => time() + 8760 * 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}

if ($user_details) {
    $ugroup_id = (int) $user_details['ugroup_id'];
}

$ugroup_id_escaped = $db_handler->sql_escape_int($ugroup_id);
$rights_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['usergroup'] . " WHERE ugroup_id='" . $ugroup_id_escaped . "'");
$user_rights = $db_handler->sql_fetch_array($rights_res) ?? [];

// Login
if ($login == 1 && $nick !== '' && $pw !== '') {
    $pw_hash = md5($pw);
    $nick_escaped = $db_handler->sql_escape_string($nick);
    $check_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['user'] . " WHERE nick='" . $nick_escaped . "' AND passwort='" . $pw_hash . "'");
    if ($db_handler->sql_num_rows($check_res) == 1) {
        $login_temp = $db_handler->sql_fetch_array($check_res);
        setcookie("login_id", (string) $login_temp['user_id'], [
            'expires' => time() + 8760 * 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        setcookie("login_pw", $login_temp['passwort'], [
            'expires' => time() + 8760 * 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        if (basename($_SERVER['PHP_SELF'] ?? '') == "index.php") {
            header("Location: index.php");
        } else {
            header("Location: " . $settings['script_file']);
        }
        exit;
    } else {
        $login_error = true;
    }
}

// Logout
if ($logout == 1) {
    setcookie("login_id", "", [
        'expires' => time() + 8760 * 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    setcookie("login_pw", "", [
        'expires' => time() + 8760 * 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    if (basename($_SERVER['PHP_SELF'] ?? '') == "index.php") {
        header("Location: index.php");
    } else {
        header("Location: " . $settings['script_file']);
    }
    exit;
}

// Download
if ($load_file) {
    $file_id = $load_file;
    $dl_allowed = false;
    if (($settings['referer_check'] ?? 'N') == "Y") {
        $all_referer = explode(" ", $settings['allowed_referer'] ?? '');
        $http_referer = $_SERVER['HTTP_REFERER'] ?? '';
        for ($i = 0; $i < count($all_referer); $i++) {
            if ($all_referer[$i] !== '' && preg_match("/" . preg_quote($all_referer[$i], '/') . "/siU", $http_referer)) {
                $dl_allowed = true;
                break;
            }
        }
    } else {
        $dl_allowed = true;
    }

    if ($dl_allowed == true) {
        if (($user_rights['download'] ?? 'Y') == "N") {
            header("Location: " . $settings['script_file'] . "wrong_rights=1");
            exit;
        } else {
            $file_id_escaped = $db_handler->sql_escape_int($file_id);
            $dl_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE file_id='" . $file_id_escaped . "'"));
            $db_handler->sql_query("UPDATE " . $sql_table['files'] . " SET downloads=downloads+1 WHERE file_id='" . $file_id_escaped . "'");
            header("Location: " . ($dl_row['url'] ?? ''));
            exit;
        }
    } else {
        header("Location: " . $settings['script_file'] . "wrong_referer=1");
        exit;
    }
}

// individuelles Listen
if ($change_list == 1) {
    $pdl_list_value = $orderseq . "###" . $orderby . "###" . $perpage;
    setcookie("pdl_list", $pdl_list_value, [
        'expires' => time() + 8760 * 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? $settings['script_file']));
    exit;
}

$pdl_list = $_COOKIE['pdl_list'] ?? '';
if ($pdl_list !== '' && $inadmin != 1) {
    $list_ops = explode("###", $pdl_list);
    $settings['orderseq'] = $list_ops[0] ?? 'ASC';
    $settings['orderby'] = $list_ops[1] ?? 'name';
    $settings['perpage'] = $list_ops[2] ?? '10';
}

$settings_orderby = htmlspecialchars($settings['orderby'] ?? 'name', ENT_QUOTES, 'UTF-8');
$settings_orderseq = htmlspecialchars($settings['orderseq'] ?? 'ASC', ENT_QUOTES, 'UTF-8');
$settings_perpage = htmlspecialchars($settings['perpage'] ?? '10', ENT_QUOTES, 'UTF-8');

$list = 'Release sortieren nach
<select name="orderby">
<option value="name">Name</option>
<option value="text"' . (($settings_orderby == "text") ? " selected" : "") . '>Beschreibung</option>
<option value="time"' . (($settings_orderby == "time") ? " selected" : "") . '>Uploaddatum</option>
<option value="views"' . (($settings_orderby == "views") ? " selected" : "") . '>Views</option>
<option value="votes"' . (($settings_orderby == "votes") ? " selected" : "") . '>Bewertungen</option>
<option value="voted/votes"' . (($settings_orderby == "voted/votes") ? " selected" : "") . '>Wertung</option>
</select>
in <select name="orderseq">
<option value="ASC">aufsteigender</option>
<option value="DESC"' . (($settings_orderseq == "DESC") ? " selected" : "") . '
>absteigender</option></select> Reihenfolge mit
<input type="text" size="2" name="perpage" value="' . $settings_perpage . '">
Releasen auf einer Seite <input type="submit" value="GO">';
