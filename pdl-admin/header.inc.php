<?php
$incdir = "../";
$inadmin = 1;
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

$pdl_admin_version = htmlspecialchars((string)($settings['pdlversion'] ?? ''), ENT_QUOTES, 'UTF-8');
$pdl_admin_user = $user_details ? htmlspecialchars((string)($user_details['nick'] ?? ''), ENT_QUOTES, 'UTF-8') : '';
$pdl_admin_script_file = htmlspecialchars((string)($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerDownload <?php echo $pdl_admin_version; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
</head>
<body class="pdl-admin">
<a class="visually-hidden-focusable" href="#pdlAdminMain">Zum Hauptinhalt springen</a>
<nav class="navbar navbar-expand-lg pdl-admin-navbar sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">PowerDownload <small class="opacity-75"><?php echo $pdl_admin_version; ?></small> - Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#pdlAdminSidebar" aria-controls="pdlAdminSidebar" aria-label="Admin-Navigation umschalten">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="d-none d-lg-flex align-items-center ms-auto">
            <?php if ($user_details) { ?>
            <span class="navbar-text me-3">Hallo <strong><?php echo $pdl_admin_user; ?></strong></span>
            <a class="btn btn-sm btn-outline-light me-2" href="../<?php echo $pdl_admin_script_file; ?>usercenter=profil">Profil</a>
            <a class="btn btn-sm btn-outline-light me-2" href="../<?php echo $pdl_admin_script_file; ?>">Zur Webseite</a>
            <a class="btn btn-sm btn-light" href="index.php?logout=1">Logout</a>
            <?php } else { ?>
            <span class="navbar-text">Bitte einloggen.</span>
            <?php } ?>
        </div>
    </div>
</nav>

<div class="offcanvas-lg offcanvas-start pdl-admin-sidebar" tabindex="-1" id="pdlAdminSidebar" aria-labelledby="pdlAdminSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="pdlAdminSidebarLabel">Admin-Menü</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#pdlAdminSidebar" aria-label="Schließen"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php if ($user_details) { ?>
        <div class="d-lg-none px-3 py-2 border-bottom border-secondary">
            Hallo <strong><?php echo $pdl_admin_user; ?></strong><br>
            <a href="index.php?logout=1" class="link-light">Logout</a> &middot;
            <a href="../<?php echo $pdl_admin_script_file; ?>usercenter=profil" class="link-light">Profil</a>
        </div>
        <?php } ?>
        <nav class="pdl-admin-nav" aria-label="Admin-Bereiche">
            <?php
            $master_if = false;
            menu_topic(($user_rights['adminaccess'] ?? '') == "Y","Nützliches");
            menu_link(($user_rights['adminaccess'] ?? '') == "Y","Ersetzungen anzeigen","showreplacements.php");
            menu_link(($user_rights['templates'] ?? '') == "Y","Template-Variablen anzeigen","showtempvars.php");
            menu_close();
            menu_topic(($user_rights['adminaccess'] ?? '') == "Y","Releases");
            menu_link(($settings['ftp_on'] ?? '') == "Y" && function_exists("ftp_connect"),"FTP Browser/Upload","ftp_browser.php");
            menu_link(($user_rights['adminaccess'] ?? '') == "Y","hinzufügen","addrelease.php");
            menu_link(($user_rights['editfiles'] ?? '') == "Y" || ($user_rights['delfiles'] ?? '') == "Y","ändern/löschen","or_list.php");
            menu_close();
            menu_topic(($user_rights['adddirs'] ?? '') == "Y" || ($user_rights['editdirs'] ?? '') == "Y" || ($user_rights['deldirs'] ?? '') == "Y", "Ordner");
            menu_link(($user_rights['adddirs'] ?? '') == "Y","hinzufügen","adddir.php");
            menu_link(($user_rights['editdirs'] ?? '') == "Y" || ($user_rights['deldirs'] ?? '') == "Y","ändern/löschen","or_list.php");
            menu_close();
            menu_topic(($user_rights['edituser'] ?? '') == "Y" || ($user_rights['deluser'] ?? '') == "Y","User");
            menu_link(($user_rights['edituser'] ?? '') == "Y" || ($user_rights['deluser'] ?? '') == "Y","Userliste / Suche","users.php");
            menu_link(($user_rights['edituser'] ?? '') == "Y" && ($user_rights['deluser'] ?? '') == "Y","Usergruppe hinzufügen","addugroup.php");
            menu_link(($user_rights['edituser'] ?? '') == "Y" && ($user_rights['deluser'] ?? '') == "Y","Usergruppe ändern/löschen","editdelugroup.php");
            menu_close();
            menu_topic(($user_rights['writeletter'] ?? '') == "Y","Newsletter");
            menu_link(($user_rights['writeletter'] ?? '') == "Y","Newsletter generieren/senden","makeletter.php");
            menu_close();
            menu_topic(($user_rights['templates'] ?? '') == "Y" || ($user_rights['replacements'] ?? '') == "Y","Vorlagen und Ersetzungen");
            menu_link(($user_rights['replacements'] ?? '') == "Y","Ersetzungen anzeigen","showreplacements.php");
            menu_link(($user_rights['replacements'] ?? '') == "Y","Ersetzung hinzufügen","addreplacement.php");
            menu_link(($user_rights['replacements'] ?? '') == "Y","Ersetzung löschen","delreplacement.php");
            menu_link(($user_rights['templates'] ?? '') == "Y","Vorlagen bearbeiten","templates.php");
            menu_close();
            menu_topic(($user_rights['god'] ?? '') == "Y","System");
            menu_link(($user_rights['god'] ?? '') == "Y","Settings","settings.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Datenbank-Backup","backup.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Backup ausführen","dobackup.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Datenbank optimieren","optimize.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Download-Datenbank zurücksetzen","reset.php");
            menu_close();
            menu_topic(($user_rights['god'] ?? '') == "Y","System-Erweiterungen");
            menu_link(($user_rights['god'] ?? '') == "Y","Setting hinzufügen","addsettings.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Setting-Gruppe hinzufügen","addsgroup.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Settings/Gruppen ändern/löschen","editdelsettingssgroup.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Template hinzufügen","addtemplate.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Template-Gruppe hinzufügen","addtgroup.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Template/Gruppen ändern/löschen","editdeltemplatestgroup.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Userrechte hinzufügen","adduright.php");
            menu_link(($user_rights['god'] ?? '') == "Y","Userrechte ändern/löschen","editdeluright.php");
            menu_close();
            ?>
        </nav>
    </div>
</div>

<main id="pdlAdminMain" class="pdl-admin-main">
    <div class="container-fluid py-3">
