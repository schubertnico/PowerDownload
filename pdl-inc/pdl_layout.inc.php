<?php

/**
 * PowerDownload - Bootstrap 5 Layout Helper
 *
 * Stellt zentrale Layout-Helfer für den öffentlichen Bereich bereit.
 * Diese Funktionen rendern ein konsistentes Bootstrap-5-Gerüst mit
 * Doctype, Head, Navbar und passendem Footer.
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);

if (!function_exists('pdl_show_dashboard_widgets')) {
    /**
     * Liefert true, wenn die Startseiten-Widgets (Statistik, Top, Flop,
     * Neueste, Bestbewertet) auf der aktuellen Seite erscheinen dürfen.
     *
     * Maßgeblich ist $pdl_is_start_page aus pdl_header.inc.php. Sie gilt nur
     * für die echte Startseite (GET ohne Unterseiten-Parameter), also nicht
     * nach dem Absenden eines Formulars. Ohne Header (z. B. in Tests) werden
     * GET und POST direkt geprüft.
     */
    function pdl_show_dashboard_widgets(): bool
    {
        if (isset($GLOBALS['pdl_is_start_page']) && is_bool($GLOBALS['pdl_is_start_page'])) {
            return $GLOBALS['pdl_is_start_page'];
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            return false;
        }
        $subpage_keys = ['usercenter', 'show_stats', 'show_search', 'release_id', 'file_id', 'screen_id', 'load_file', 'wrong_referer', 'wrong_rights'];
        foreach ($subpage_keys as $key) {
            if (!empty($_GET[$key]) || !empty($_POST[$key])) {
                return false;
            }
        }
        $ordner = $_GET['ordner_id'] ?? $_POST['ordner_id'] ?? 0;
        return !(is_scalar($ordner) && (int) $ordner > 0);
    }
}

if (!function_exists('pdl_layout_is_admin_context')) {
    function pdl_layout_is_admin_context(array $userRights): bool
    {
        return (($_GET['from'] ?? '') === 'admin')
            && (($userRights['adminaccess'] ?? 'N') === 'Y');
    }
}

if (!function_exists('pdl_layout_resolve_title')) {
    /**
     * Ermittelt den Seitentitel für Benutzer-, Statistik- und Suchseiten und
     * überschreibt damit den übergebenen Titel. GET und POST zählen gleich,
     * damit auch nach dem Absenden eines Formulars der passende Titel steht.
     */
    function pdl_layout_resolve_title(string $title): string
    {
        $param = static function (string $key): string {
            /** @var mixed $value auch Zahlen, wenn GET/POST im Code gesetzt werden */
            $value = $_GET[$key] ?? $_POST[$key] ?? '';
            return is_scalar($value) ? (string) $value : '';
        };
        $usercenter = strtolower($param('usercenter'));
        $usercenter_titles = [
            'login' => 'Anmelden',
            'register' => 'Registrieren',
            'lost' => 'Passwort vergessen',
            'lost2' => 'Neues Passwort setzen',
            'profil' => 'Profil',
            'comments' => 'Kommentar schreiben',
        ];
        if ($usercenter !== '' && isset($usercenter_titles[$usercenter])) {
            return $usercenter_titles[$usercenter];
        }
        if (!in_array($param('show_stats'), ['', '0'], true)) {
            return 'Statistik';
        }
        if (!in_array($param('show_search'), ['', '0'], true)) {
            return 'Suche';
        }
        return $title;
    }
}

if (!function_exists('pdl_layout_start')) {
    /**
     * Rendert den Kopf der öffentlichen Seite (Doctype, Head, Navbar).
     *
     * Titel im Browser-Tab: auf der Startseite "<Name der Seite> – Download Center",
     * sonst "<Seitentitel> – <Name der Seite>" (z. B. Release- oder Ordnername).
     * Name der Seite: Einstellung sitename, Rückfall "PowerDownload".
     *
     * @param string               $title       Sichtbarer Seitentitel.
     * @param array<string, mixed> $settings    Settings-Array aus pdl_header.
     * @param array<string, mixed> $userRights  User-Rechte-Array.
     * @param array<string, mixed>|null $userDetails User-Details oder null.
     */
    function pdl_layout_start(
        string $title,
        array $settings,
        array $userRights,
        ?array $userDetails
    ): void {
        $script_file = htmlspecialchars((string)($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8');
        $version = htmlspecialchars((string)($settings['pdlversion'] ?? ''), ENT_QUOTES, 'UTF-8');
        $original_title = $title;
        $title = pdl_layout_resolve_title($title);
        $is_homepage = ($title === $original_title) && pdl_show_dashboard_widgets();
        $site_name_raw = trim((string) ($settings['sitename'] ?? ''));
        $site_name_raw = $site_name_raw !== '' ? $site_name_raw : 'PowerDownload';
        $site_name = htmlspecialchars($site_name_raw, ENT_QUOTES, 'UTF-8');
        $document_title = $is_homepage ? $site_name_raw . ' – ' . $title : $title . ' – ' . $site_name_raw;
        $page_title = htmlspecialchars($document_title, ENT_QUOTES, 'UTF-8');
        $is_logged_in = !empty($userDetails);
        // Abmelde-Link mit CSRF-Token (pdl_logout_url() aus pdl_csrf.inc.php)
        $logout_href = (function_exists('pdl_logout_url') && session_status() === PHP_SESSION_ACTIVE)
            ? htmlspecialchars(pdl_logout_url((string) ($settings['script_file'] ?? '')), ENT_QUOTES, 'UTF-8')
            : $script_file . 'logout=1';
        $admin_access = (($userRights['adminaccess'] ?? 'N') === 'Y');
        $search_on = (($settings['enable_search'] ?? 'Y') === 'Y');
        $description = (string) ($settings['site_description'] ?? 'PowerDownload - Datei- und Release-Verwaltung mit Statistik, Suche und Benutzerbereich.');
        $description_escaped = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        $nick = $is_logged_in
            ? htmlspecialchars((string)($userDetails['nick'] ?? ''), ENT_QUOTES, 'UTF-8')
            : '';
        ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $description_escaped; ?>">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" href="pdl-gfx/favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo (defined('PDL_LAYOUT_PUBLIC_CSS_PATH') ? htmlspecialchars(PDL_LAYOUT_PUBLIC_CSS_PATH, ENT_QUOTES, 'UTF-8') : 'pdl-gfx/pdl-public.css'); ?>" rel="stylesheet">
</head>
<body class="pdl-public">
<?php if (pdl_layout_is_admin_context($userRights)) { ?>
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="pdl-admin/index.php"><?php echo $site_name; ?></a>
        <span class="navbar-text text-light">Mein Profil</span>
        <div class="ms-auto d-flex gap-2">
            <a class="btn btn-sm btn-outline-light" href="pdl-admin/index.php">&larr; Adminbereich</a>
            <a class="btn btn-sm btn-light" href="<?php echo $logout_href; ?>">Abmelden</a>
        </div>
    </div>
</nav>
<?php } else { ?>
<nav class="navbar navbar-expand-lg pdl-navbar mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?php echo $script_file; ?>"><?php echo $site_name; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pdlPublicNav" aria-controls="pdlPublicNav" aria-expanded="false" aria-label="Navigation umschalten">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="pdlPublicNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $script_file; ?>">Startseite</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $script_file; ?>show_stats=1">Statistik</a>
                </li>
                <?php if ($search_on) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $script_file; ?>show_search=1">Suche</a>
                </li>
                <?php } ?>
                <?php if ($admin_access) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="pdl-admin/">Adminbereich</a>
                </li>
                <?php } ?>
            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <?php if ($is_logged_in) { ?>
                <li class="nav-item">
                    <span class="navbar-text me-3">Hallo <strong><?php echo $nick; ?></strong></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $script_file; ?>usercenter=profil">Profil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $logout_href; ?>">Abmelden</a>
                </li>
                <?php } else { ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $script_file; ?>usercenter=login">Anmelden</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $script_file; ?>usercenter=register">Registrieren</a>
                </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>
<?php } ?>
<main class="container-fluid pdl-main pb-4">
        <?php if ($is_homepage) { ?>
        <h1 class="visually-hidden"><?php echo $site_name; ?></h1>
        <?php }
        if (!empty($_GET['account_deleted'])) {
            echo pdl_alert('success', '<strong>Ihr Konto wurde gelöscht.</strong> Vielen Dank, dass Sie ' . $site_name . ' genutzt haben.');
        }
    }
}

if (!function_exists('pdl_layout_end')) {
    /**
     * Rendert das Ende der öffentlichen Seite (Footer + Bootstrap JS).
     *
     * @param array<string, mixed> $settings   Settings-Array aus pdl_header.
     * @param float                $rendertime Rohzeit aus pdl_header.
     * @param int                  $querycount SQL-Query-Anzahl.
     */
    function pdl_layout_end(array $settings, float $rendertime, int $querycount): void
    {
        $version = htmlspecialchars((string)($settings['pdlversion'] ?? ''), ENT_QUOTES, 'UTF-8');
        $debug = !empty($settings['debug']);
        $showcopy = ($settings['showcopy'] ?? true) === true;
        ?>
</main>
<footer class="pdl-footer mt-auto py-3 text-center small">
    <div class="container-fluid">
        <?php if ($debug) {
            $rendertime2 = microtime(true);
            $rendered = round($rendertime2 - $rendertime, 3);
            ?>
        <div class="text-muted mb-2">Renderzeit: <?php echo htmlspecialchars((string) $rendered, ENT_QUOTES, 'UTF-8'); ?>s &middot; <?php echo $querycount; ?> SQL-Anfragen</div>
        <?php } ?>
        <?php if ($showcopy) { ?>
        <div>&copy; <a href="https://www.powerscripts.org" target="_blank" rel="noopener">https://www.powerscripts.org</a></div>
        <?php } ?>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
        <?php
    }
}

if (!function_exists('pdl_alert')) {
    /**
     * Rendert ein Bootstrap-Alert.
     *
     * @param string $type    Bootstrap-Variante (success|danger|warning|info|primary|secondary).
     * @param string $message Bereits ge-escaped oder vertrauenswürdiger HTML-Inhalt.
     */
    function pdl_alert(string $type, string $message): string
    {
        $allowed = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'];
        if (!in_array($type, $allowed, true)) {
            $type = 'info';
        }
        return '<div class="alert alert-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8')
            . '" role="alert">' . $message . '</div>';
    }
}

if (!function_exists('pdl_card_start')) {
    /**
     * Öffnet eine Bootstrap-Card mit Header.
     *
     * @param string $title       Titel der Card (wird ge-escaped).
     * @param string $extraClasses Zusätzliche Klassen für .card.
     */
    function pdl_card_start(string $title, string $extraClasses = ''): string
    {
        $classes = trim('card pdl-card mb-4 ' . $extraClasses);
        return '<section class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '">'
            . '<header class="card-header pdl-card-header"><h2 class="h6 mb-0">'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            . '</h2></header>'
            . '<div class="card-body">';
    }
}

if (!function_exists('pdl_card_end')) {
    /**
     * Schließt eine zuvor mit pdl_card_start geöffnete Card.
     */
    function pdl_card_end(): string
    {
        return '</div></section>';
    }
}
