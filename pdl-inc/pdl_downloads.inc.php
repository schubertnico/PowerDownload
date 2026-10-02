<?php

/**
 * PowerDownload - Downloads Display
 *
 * Handles the main download page display and module routing.
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);

// Global variables from pdl_header.inc.php
/** @psalm-suppress InvalidGlobal */
global $rendertime1;

require_once __DIR__ . '/pdl_locks.inc.php';

// Initialize variables with defaults
/** @psalm-suppress TypeDoesNotContainNull */
$ordner_id = $ordner_id ?? 0;
/** @psalm-suppress TypeDoesNotContainNull */
$page = $page ?? 1;
$isindex = false;
$release = null;

// Treeview or Alternative
/** @psalm-suppress RedundantCondition */
if ($ordner_id === 0) {
    $isindex = true;
}

$sordner_id = $ordner_id;

if (!empty($release_id) || !empty($screen_id)) {
    if (!empty($screen_id)) {
        $escaped_screen_id = $db_handler->sql_escape_int($screen_id);
        $screen = $db_handler->sql_fetch_array(
            $db_handler->sql_query(
                "SELECT release_id FROM " . $sql_table['screens'] . " WHERE screen_id='" . $escaped_screen_id . "'"
            )
        );
        $release_id = $screen !== null ? (int) $screen['release_id'] : 0;
    }

    $escaped_release_id = $db_handler->sql_escape_int($release_id);
    $release = $db_handler->sql_fetch_array(
        $db_handler->sql_query(
            "SELECT * FROM " . $sql_table['release'] . " WHERE release_id='" . $escaped_release_id . "'"
        )
    );

    if ($release !== null && ($release['released'] ?? 'Y') !== 'Y') {
        // Versteckte Releases erscheinen auch nicht im Navigationspfad.
        $release = null;
    }
    if ($release !== null) {
        $sordner_id = (int) $release['ordner_id'];
    }
    $isindex = false;
}

if (!empty($wrong_referer) || !empty($wrong_rights) || !empty($usercenter) || !empty($show_search) || !empty($show_stats) || $pdl_download_missing) {
    $isindex = false;
}

$script_file_escaped = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');

// Breadcrumb / Treeview-Bereich
echo '<nav aria-label="Navigationspfad" class="pdl-treeview mb-3 small text-muted">';

if (($settings['enable_treeview'] ?? 'N') === "Y") {
    /** @psalm-suppress RedundantCondition */
    if ($isindex === true) {
        echo '<img src="pdl-gfx/folder_open.gif" alt="" class="me-1"> Index<br>';
    } else {
        echo '<img src="pdl-gfx/folder.gif" alt="" class="me-1"> <a href="' . $script_file_escaped . 'ordner_id=0">Index</a><br>';
    }
    treeview_ordner(0, "");
} else {
    $ordner_id = $sordner_id;
    /** @psalm-suppress RedundantCondition */
    if ($isindex === true) {
        echo '<span aria-current="page">Index</span>';
    } elseif (($isindex === false && $ordner_id === 0) || (isset($pdl_current_ordner) && $pdl_current_ordner === false)) {
        echo '<a href="' . $script_file_escaped . 'ordner_id=0">Index</a>';
    } else {
        treeview_pfeil($sordner_id);
    }

    if ($release !== null && empty($screen_id)) {
        echo ' &raquo; <span aria-current="page">' . htmlspecialchars($release['name'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>';
    } elseif (!empty($screen_id) && $release !== null) {
        echo ' &raquo; <a href="' . $script_file_escaped . 'release_id=' . (int) $release['release_id'] . '">' .
             htmlspecialchars($release['name'] ?? '', ENT_QUOTES, 'UTF-8') . '</a> &raquo; <span aria-current="page">Screenshot</span>';
    }
}

echo '</nav>';

// Module Include with Path Traversal Protection (Whitelist)
$allowed_modules = [
    'login' => 'pdl-inc/pdl_ulogin.modul.php',
    'register' => 'pdl-inc/pdl_uregister.modul.php',
    'profil' => 'pdl-inc/pdl_uprofil.modul.php',
    'lost' => 'pdl-inc/pdl_ulost.modul.php',
    'lost2' => 'pdl-inc/pdl_ulost2.modul.php',
    'comments' => 'pdl-inc/pdl_ucomments.modul.php',
];

if ($pdl_download_missing) {
    $back_link = !empty($pdl_download_release_id)
        ? '<a class="btn btn-outline-light" href="' . $script_file_escaped . 'release_id=' . $pdl_download_release_id . '">Zurück zum Release</a>'
        : '<a class="btn btn-outline-light" href="' . $script_file_escaped . '">Zur Startseite</a>';
    echo '<section class="card pdl-card mb-4" id="pdlDownloadMissing" role="alert">'
        . '<div class="card-body">'
        . '<h2 class="h5">Datei nicht gefunden</h2>'
        . '<p class="mb-3">Diese Datei ist nicht (mehr) verfügbar. Möglicherweise wurde sie entfernt oder das Release ist nicht öffentlich.</p>'
        . '<div class="d-flex flex-wrap gap-2">' . $back_link . '</div>'
        . '</div></section>';
} elseif (!empty($screen_id)) {
    include("pdl-inc/pdl_showscreen.modul.php");
} elseif (!empty($usercenter)) {
    // Path traversal protection using whitelist
    $usercenter_lower = strtolower($usercenter);
    if (isset($allowed_modules[$usercenter_lower])) {
        include($allowed_modules[$usercenter_lower]);
    } else {
        echo pdl_alert('warning', 'Unbekanntes Modul.');
    }
} elseif (!empty($release_id)) {
    include("pdl-inc/pdl_release.modul.php");
} elseif (!empty($show_search)) {
    include("pdl-inc/pdl_search.modul.php");
} elseif (!empty($show_stats)) {
    include("pdl-inc/pdl_stats.modul.php");
} elseif (!empty($wrong_referer)) {
    echo pdl_alert('danger', '<strong>Direktlinks auf Dateien sind nicht erlaubt.</strong> Bitte laden Sie die Datei über die Release-Seite herunter.');
} elseif (!empty($wrong_rights)) {
    // Rücksprungziel: das Release der Datei (vom Header mitgegeben). Nach der
    // Anmeldung geht es dorthin zurück, siehe pdl_header.inc.php.
    $rights_back = pdl_back_release($_GET['back_release'] ?? null);
    $rights_hint = !empty($user_details)
        ? 'Ihre Benutzergruppe darf keine Dateien herunterladen.'
        : 'Bitte <a class="alert-link" id="pdlRightsLogin" href="' . $script_file_escaped . 'usercenter=login'
            . htmlspecialchars(pdl_back_release_query($rights_back), ENT_QUOTES, 'UTF-8') . '">melden Sie sich an</a>, um Dateien herunterzuladen.';
    echo '<div id="pdlWrongRights">' . pdl_alert('danger', '<strong>Sie dürfen diese Datei nicht herunterladen.</strong> ' . $rights_hint) . '</div>';
    if ($rights_back > 0) {
        echo '<p><a class="btn btn-outline-light" id="pdlRightsBack" href="' . $script_file_escaped . 'release_id=' . $rights_back . '">Zurück zum Release</a></p>';
    }
} else {
    include("pdl-inc/pdl_ordner.modul.php");
}

// Admin Links: Auswahlliste mit den Einträgen, für die der Besucher das
// jeweilige Recht hat (wie in den Seiten des Adminbereichs). Ohne Eintrag
// erscheint keine Liste.
if (($settings['enable_extrernadmin'] ?? 'N') === "Y" && ($user_rights['adminaccess'] ?? 'N') === "Y") {
    $has_right = static fn (string $right): bool => ($user_rights[$right] ?? 'N') === "Y";
    $admin_select = static function (string $id, array $options): string {
        if ($options === []) {
            return '';
        }
        $html = '<div class="d-flex justify-content-end my-3">'
            . '<label class="form-label me-2 mb-0 align-self-center" for="' . $id . '">Admin-Optionen</label>'
            . '<select id="' . $id . '" class="form-select form-select-sm w-auto" name="admin"'
            . ' onchange="if(this.value){window.location=(\'pdl-admin/\'+this.value);}">'
            . '<option value="">Bitte wählen</option>';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">' . $label . '</option>';
        }
        return $html . '</select></div>';
    };

    if (!empty($release_id) && empty($screen_id) && !empty($release_exists)) {
        $release_id_escaped = (int) $release_id;
        $release_options = [];
        if ($has_right('editfiles')) {
            $release_options['editrelease.php?release_id=' . $release_id_escaped] = 'Release bearbeiten';
        }
        if ($has_right('addfiles') || $has_right('editfiles')) {
            $release_options['addfile.php?release_id=' . $release_id_escaped] = 'Datei hinzufügen';
            $release_options['addscreen.php?release_id=' . $release_id_escaped] = 'Screenshot hochladen';
        }
        if ($has_right('delfiles')) {
            $release_options['delrelease.php?release_id=' . $release_id_escaped] = 'Release löschen';
        }
        echo $admin_select('pdlAdminOptionsRelease', $release_options);
    } else {
        if (empty($screen_id) && empty($usercenter) && empty($wrong_referer) &&
            empty($wrong_rights) && empty($show_search) && empty($show_stats) &&
            !$pdl_download_missing && !(isset($pdl_current_ordner) && $pdl_current_ordner === false)) {

            $ordner_id_escaped = (int) $ordner_id;
            $ordner_options = [];
            // Dateien gehören immer zu einem Release: im Ordner zuerst ein Release anlegen
            if ($has_right('addfiles')) {
                $ordner_options['addrelease.php?ordner_id=' . $ordner_id_escaped] = 'Release hinzufügen';
            }
            if ($has_right('adddirs')) {
                $ordner_options['adddir.php?ordner_id=' . $ordner_id_escaped] = 'Unterordner hinzufügen';
            }
            if ($has_right('editdirs') && $ordner_id_escaped !== 0) {
                $ordner_options['editdir.php?ordner_id=' . $ordner_id_escaped] = 'Ordner bearbeiten';
            }
            if ($has_right('deldirs') && $ordner_id_escaped !== 0) {
                $ordner_options['deldir.php?ordner_id=' . $ordner_id_escaped] = 'Ordner löschen';
            }
            echo $admin_select('pdlAdminOptionsOrdner', $ordner_options);
        }
    }
}
