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

    if ($release !== null) {
        $sordner_id = (int) $release['ordner_id'];
    }
    $isindex = false;
}

if (!empty($wrong_referer) || !empty($wrong_rights) || !empty($usercenter) || !empty($show_search) || !empty($show_stats)) {
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
    } elseif ($isindex === false && $ordner_id === 0) {
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

if (!empty($screen_id)) {
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
    echo pdl_alert('danger', '<strong>Es wurde illegal auf die Datei verlinkt.</strong>');
} elseif (!empty($wrong_rights)) {
    echo pdl_alert('danger', '<strong>Sie haben keine Berechtigung eine Datei zu downloaden.</strong>');
} else {
    include("pdl-inc/pdl_ordner.modul.php");
}

// Admin Links
if (($settings['enable_extrernadmin'] ?? 'N') === "Y" && ($user_rights['adminaccess'] ?? 'N') === "Y") {
    if (!empty($release_id) && empty($screen_id) && !empty($release_exists)) {
        if (($user_rights['editfiles'] ?? 'N') === "Y" && ($user_rights['delfiles'] ?? 'N') === "Y") {
            echo '<div class="d-flex justify-content-end my-3">'
                . '<label class="form-label me-2 mb-0 align-self-center" for="pdlAdminOptionsRelease">Admin-Optionen</label>'
                . '<select id="pdlAdminOptionsRelease" class="form-select form-select-sm w-auto" name="admin"'
                . ' onchange="if(this.value){window.location=(\'pdl-admin/\'+this.value);}">'
                . '<option value="">Bitte wählen</option>';

            $release_id_escaped = (int) $release_id;
            if (($user_rights['editfiles'] ?? 'N') === "Y") {
                echo '<option value="editrelease.php?release_id=' . $release_id_escaped . '">Release editieren</option>';
                echo '<option value="addfile.php?release_id=' . $release_id_escaped . '">Datei hinzufügen</option>';
                echo '<option value="addscreen.php?release_id=' . $release_id_escaped . '">Screenshot hochladen</option>';
            }

            if (($user_rights['delfiles'] ?? 'N') === "Y") {
                echo '<option value="delrelease.php?release_id=' . $release_id_escaped . '">Release löschen</option>';
            }

            echo '</select></div>';
        }
    } else {
        if (empty($screen_id) && empty($usercenter) && empty($wrong_referer) &&
            empty($wrong_rights) && empty($show_search) && empty($show_stats)) {

            $ordner_id_escaped = $ordner_id;
            echo '<div class="d-flex justify-content-end my-3">'
                . '<label class="form-label me-2 mb-0 align-self-center" for="pdlAdminOptionsOrdner">Admin-Optionen</label>'
                . '<select id="pdlAdminOptionsOrdner" class="form-select form-select-sm w-auto" name="admin"'
                . ' onchange="if(this.value){window.location=(\'pdl-admin/\'+this.value);}">'
                . '<option value="">Bitte wählen</option>'
                . '<option value="addfile.php?ordner_id=' . $ordner_id_escaped . '">Datei hinzufügen</option>';

            if (($user_rights['adddirs'] ?? 'N') === "Y") {
                echo '<option value="adddir.php?ordner_id=' . $ordner_id_escaped . '">Sub-Ordner hinzufügen</option>';
            }

            if (($user_rights['editdirs'] ?? 'N') === "Y" && $ordner_id !== 0) {
                echo '<option value="editdir.php?ordner_id=' . $ordner_id_escaped . '">Ordner editieren</option>';
            }

            if (($user_rights['deldirs'] ?? 'N') === "Y" && $ordner_id !== 0) {
                echo '<option value="deldir.php?ordner_id=' . $ordner_id_escaped . '">Ordner löschen</option>';
            }

            echo '</select></div>';
        }
    }
}
