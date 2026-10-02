<?php
$incdir = "../";
$inadmin = 1;

// A05: Eine fehlgeschlagene Anmeldung leitet pdl_header.inc.php auf die
// Login-Seite des öffentlichen Bereichs um ("…usercenter=login&login_error=N").
// Aus dem Adminbereich heraus zeigt dieser relative Pfad ins Leere (404).
// Darum hier die Weiterleitung auf die Anmeldeseite des Adminbereichs biegen.
header_register_callback(static function (): void {
    foreach (headers_list() as $pdl_header_line) {
        if (stripos($pdl_header_line, 'Location:') === 0
            && preg_match('/usercenter=login&login_error=(\d+)/', $pdl_header_line, $pdl_login_error) === 1) {
            header('Location: index.php?login_error=' . (int) $pdl_login_error[1], true, 302);
            return;
        }
    }
});

include_once($incdir."pdl-inc/pdl_header.inc.php");
include("functions.inc.php");

// Ensure $user_rights is an array to prevent undefined key warnings
/** @psalm-suppress TypeDoesNotContainType,TypeDoesNotContainNull */
if (!isset($user_rights) || !is_array($user_rights)) {
    $user_rights = [];
}

$template['bg'] = "#000000";
$template['table_border'] = "#9B0000";
$template['header_bg'] = "#700000";
$template['footer_bg'] = "#5F0000";
$template['alt_1'] = "#2E0000";
$template['alt_2'] = "#3B0000";

$pdl_admin_version = htmlspecialchars($settings['pdlversion'] ?? '', ENT_QUOTES, 'UTF-8');
$pdl_admin_user = $user_details ? htmlspecialchars((string)($user_details['nick'] ?? ''), ENT_QUOTES, 'UTF-8') : '';
$pdl_admin_script_file = htmlspecialchars((string)($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8');
// Abmelden mit CSRF-Token (sonst fragt pdl_header.inc.php ggf. nach).
$pdl_admin_logout_href = htmlspecialchars(function_exists('pdl_logout_url') ? pdl_logout_url('index.php?') : 'index.php?logout=1', ENT_QUOTES, 'UTF-8');

// Menü: Abschnitt => Liste aus [Titel, Ziel, sichtbar?]. Rechte wie in den Seiten
// (siehe pdl_admin_has_right() in functions.inc.php).
$pdl_admin_menu = [
    'Releases' => [
        ['Ordner und Releases', 'or_list.php', pdl_admin_has_right('addfiles', 'editfiles', 'delfiles', 'adddirs', 'editdirs', 'deldirs')],
        ['Release hinzufügen', 'addrelease.php', pdl_admin_has_right('addfiles')],
        ['FTP-Browser', 'ftp_browser.php', ($settings['ftp_on'] ?? '') == "Y" && function_exists("ftp_connect") && pdl_admin_has_right('addfiles', 'editfiles')],
    ],
    'Ordner' => [
        ['Ordner hinzufügen', 'adddir.php', pdl_admin_has_right('adddirs')],
    ],
    'Benutzer' => [
        ['Benutzerliste', 'users.php', pdl_admin_has_right('edituser', 'deluser')],
        ['Benutzergruppe hinzufügen', 'addugroup.php', pdl_admin_has_right('edituser') && pdl_admin_has_right('deluser')],
        ['Benutzergruppen bearbeiten', 'editdelugroup.php', pdl_admin_has_right('edituser') && pdl_admin_has_right('deluser')],
    ],
    'Newsletter' => [
        ['Newsletter schreiben', 'makeletter.php', pdl_admin_has_right('edituser')],
    ],
    'Vorlagen und Ersetzungen' => [
        ['Vorlagen bearbeiten', 'templates.php', pdl_admin_has_right('templates')],
        ['Ersetzungen anzeigen', 'showreplacements.php', pdl_admin_has_right('replacements')],
        ['Ersetzung hinzufügen', 'addreplacement.php', pdl_admin_has_right('replacements')],
        ['Ersetzung löschen', 'delreplacement.php', pdl_admin_has_right('replacements')],
    ],
    'System' => [
        ['Einstellungen', 'settings.php', pdl_admin_has_right('settings')],
        ['Sicherung erstellen', 'backup.php', pdl_admin_has_right('backup')],
        ['Sicherung einspielen', 'dobackup.php', pdl_admin_has_right('backup')],
        ['Datenbank optimieren', 'optimize.php', pdl_admin_has_right('backup')],
        ['Zähler und Kommentare zurücksetzen', 'reset.php', pdl_admin_has_right('backup')],
    ],
    'Erweitert' => [
        ['Einstellung hinzufügen', 'addsettings.php', pdl_admin_has_right('settings')],
        ['Einstellungsgruppe hinzufügen', 'addsgroup.php', pdl_admin_has_right('settings')],
        ['Einstellungen und Gruppen bearbeiten', 'editdelsettingssgroup.php', pdl_admin_has_right('settings')],
        ['Vorlage hinzufügen', 'addtemplate.php', pdl_admin_has_right('templates')],
        ['Vorlagengruppe hinzufügen', 'addtgroup.php', pdl_admin_has_right('templates')],
        ['Vorlagen und Gruppen bearbeiten', 'editdeltemplatestgroup.php', pdl_admin_has_right('templates')],
        ['Benutzerrecht hinzufügen', 'adduright.php', pdl_admin_has_right('settings')],
        ['Benutzerrechte bearbeiten', 'editdeluright.php', pdl_admin_has_right('settings')],
    ],
    'Nützliches' => [
        ['Vorlagen-Platzhalter anzeigen', 'showtempvars.php', pdl_admin_has_right('templates')],
    ],
];

// Unterseiten ohne eigenen Menüpunkt markieren ihren Hauptpunkt als aktiv.
$pdl_admin_self = basename($_SERVER['PHP_SELF'] ?? '');
$pdl_admin_menu_parent = [
    'editrelease.php' => 'or_list.php', 'delrelease.php' => 'or_list.php',
    'addfile.php' => 'or_list.php', 'editfile.php' => 'or_list.php', 'delfile.php' => 'or_list.php',
    'addscreen.php' => 'or_list.php', 'delscreen.php' => 'or_list.php',
    'editdir.php' => 'or_list.php', 'deldir.php' => 'or_list.php',
    'editcomment.php' => 'or_list.php', 'delcomment.php' => 'or_list.php',
    'ftp_upload.php' => 'ftp_browser.php',
    'edituser.php' => 'users.php', 'deluser.php' => 'users.php',
];
$pdl_admin_active = $pdl_admin_menu_parent[$pdl_admin_self] ?? $pdl_admin_self;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerDownload <?php echo $pdl_admin_version; ?> – Adminbereich</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
</head>
<body class="pdl-admin">
<a class="visually-hidden-focusable" href="#pdlAdminMain">Zum Hauptinhalt springen</a>
<nav class="navbar navbar-expand-lg pdl-admin-navbar sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">PowerDownload <small class="opacity-75"><?php echo $pdl_admin_version; ?></small> – Adminbereich</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#pdlAdminSidebar" aria-controls="pdlAdminSidebar" aria-label="Menü des Adminbereichs umschalten">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="d-none d-lg-flex align-items-center ms-auto">
            <?php if ($user_details) { ?>
            <span class="navbar-text me-3">Hallo <strong><?php echo $pdl_admin_user; ?></strong></span>
            <a class="btn btn-sm btn-outline-light me-2" href="../<?php echo $pdl_admin_script_file; ?>usercenter=profil&amp;from=admin">Profil</a>
            <a class="btn btn-sm btn-outline-light me-2" href="../<?php echo $pdl_admin_script_file; ?>">Zur Webseite</a>
            <a class="btn btn-sm btn-light" id="pdlAdminLogout" href="<?php echo $pdl_admin_logout_href; ?>">Abmelden</a>
            <?php } else { ?>
            <span class="navbar-text">Bitte melden Sie sich an.</span>
            <?php } ?>
        </div>
    </div>
</nav>

<div class="offcanvas-lg offcanvas-start pdl-admin-sidebar" tabindex="-1" id="pdlAdminSidebar" aria-labelledby="pdlAdminSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="pdlAdminSidebarLabel">Menü</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#pdlAdminSidebar" aria-label="Schließen"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php if ($user_details) { ?>
        <div class="d-lg-none px-3 py-2 border-bottom border-secondary">
            Hallo <strong><?php echo $pdl_admin_user; ?></strong><br>
            <a href="<?php echo $pdl_admin_logout_href; ?>" class="link-light">Abmelden</a> &middot;
            <a href="../<?php echo $pdl_admin_script_file; ?>usercenter=profil&amp;from=admin" class="link-light">Profil</a>
        </div>
        <?php } ?>
        <nav class="pdl-admin-nav" aria-label="Bereiche des Adminbereichs">
            <?php
            if (pdl_admin_has_right()) {
                $pdl_dash_active = $pdl_admin_active === 'index.php';
                echo '<a class="pdl-menu-link pdl-menu-home' . ($pdl_dash_active ? ' active' : '') . '" href="index.php"'
                    . ($pdl_dash_active ? ' aria-current="page"' : '') . '>Übersicht</a>';
            }
            foreach ($pdl_admin_menu as $pdl_menu_title => $pdl_menu_links) {
                $pdl_menu_visible = array_filter($pdl_menu_links, static fn (array $link): bool => (bool) $link[2]);
                if ($pdl_menu_visible === []) {
                    continue;
                }
                echo '<h2 class="pdl-menu-topic h6">' . htmlspecialchars($pdl_menu_title) . '</h2>';
                foreach ($pdl_menu_visible as $pdl_menu_link) {
                    $pdl_is_active = $pdl_menu_link[1] === $pdl_admin_active;
                    echo '<a class="pdl-menu-link' . ($pdl_is_active ? ' active' : '') . '" href="' . htmlspecialchars($pdl_menu_link[1]) . '"'
                        . ($pdl_is_active ? ' aria-current="page"' : '') . '>'
                        . htmlspecialchars($pdl_menu_link[0]) . '</a>';
                }
            }
            ?>
        </nav>
    </div>
</div>

<main id="pdlAdminMain" class="pdl-admin-main">
    <div class="container-fluid py-3">
