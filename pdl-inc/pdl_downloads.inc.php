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
global $rendertime1;

// Initialize variables with defaults
$ordner_id = $ordner_id ?? 0;
$page = $page ?? 1;
$isindex = false;
$release = null;

// Outer table
echo '<table border="0" cellpadding="0" cellspacing="0" width="' .
     htmlspecialchars($template['all_width'] ?? '100%', ENT_QUOTES, 'UTF-8') . '"><tr><td>';

// Treeview or Alternative
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

echo '
<table border="0" width="100%">
  <tr>
    <td>';

$script_file_escaped = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');

if (($settings['enable_treeview'] ?? 'N') === "Y") {
    if ($isindex === true) {
        echo '<img src="pdl-gfx/folder_open.gif" border="0" alt=""> Index<br>';
    } else {
        echo '<img src="pdl-gfx/folder.gif" border="0" alt=""> <a href="' . $script_file_escaped . 'ordner_id=0">Index</a><br>';
    }
    treeview_ordner(0, "");
} else {
    $ordner_id = $sordner_id;
    if ($isindex === true) {
        echo "Index";
    } elseif ($isindex === false && $ordner_id === 0) {
        echo '<a href="' . $script_file_escaped . 'ordner_id=0">Index</a>';
    } else {
        treeview_pfeil($sordner_id);
    }

    if ($release !== null && empty($screen_id)) {
        echo " &raquo; " . htmlspecialchars($release['name'] ?? '', ENT_QUOTES, 'UTF-8');
    } elseif (!empty($screen_id) && $release !== null) {
        echo ' &raquo; <a href="' . $script_file_escaped . 'release_id=' . (int) $release['release_id'] . '">' .
             htmlspecialchars($release['name'] ?? '', ENT_QUOTES, 'UTF-8') . '</a> &raquo; Screenshot';
    }
}

echo '
    </td>
    <td align="right" valign="top">
      <a href="' . $script_file_escaped . '">Home</a>
      - <a href="' . $script_file_escaped . 'show_stats=1">Statistik</a>
      - <a href="' . $script_file_escaped . 'show_search=1">Suche</a> ';

if (empty($user_details)) {
    echo '- <a href="' . $script_file_escaped . 'usercenter=login">Login</a> ';
    echo '- <a href="' . $script_file_escaped . 'usercenter=register">Anmelden</a> ';
}

if (!empty($user_details)) {
    echo '- <a href="' . $script_file_escaped . 'usercenter=profil">Profil</a> ';
}

if (($user_rights['adminaccess'] ?? 'N') === "Y") {
    echo '- <a href="pdl-admin/">Admin Center</a> ';
}

if (!empty($user_details)) {
    echo '- <a href="' . $script_file_escaped . 'logout=1">Logout</a> ';
}

echo '
    </td>
  </tr>
</table>
<br>';

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
        echo '<center><b>Unbekanntes Modul.</b></center>';
    }
} elseif (!empty($release_id)) {
    include("pdl-inc/pdl_release.modul.php");
} elseif (!empty($show_search)) {
    include("pdl-inc/pdl_search.modul.php");
} elseif (!empty($show_stats)) {
    include("pdl-inc/pdl_stats.modul.php");
} elseif (!empty($wrong_referer)) {
    echo '<center><b>Es wurde illegal auf die Datei verlinkt.</b></center>';
} elseif (!empty($wrong_rights)) {
    echo '<center><b>Sie haben keine Berechtigung eine Datei zu downloaden.</b></center>';
} else {
    include("pdl-inc/pdl_ordner.modul.php");
}

// Admin Links
if (($settings['enable_extrernadmin'] ?? 'N') === "Y" && ($user_rights['adminaccess'] ?? 'N') === "Y") {
    if (!empty($release_id) && empty($screen_id)) {
        if (($user_rights['editfiles'] ?? 'N') === "Y" && ($user_rights['delfiles'] ?? 'N') === "Y") {
            echo '<div align="right"><select name="admin" onchange="window.location=(\'pdl-admin/\'+this.options[this.selectedIndex].value)">
            <option value="">Admin Optionen</option>';

            $release_id_escaped = (int) $release_id;
            if (($user_rights['editfiles'] ?? 'N') === "Y") {
                echo '<option value="editrelease.php?release_id=' . $release_id_escaped . '">Release Editieren</option>';
                echo '<option value="addfile.php?release_id=' . $release_id_escaped . '">Datei hinzufügen</option>';
                echo '<option value="addscreen.php?release_id=' . $release_id_escaped . '">Screenshot hochladen</option>';
            }

            if (($user_rights['delfiles'] ?? 'N') === "Y") {
                echo '<option value="delrelease.php?release_id=' . $release_id_escaped . '">Release Löschen</option>';
            }

            echo '</select></div>';
        }
    } else {
        if (empty($screen_id) && empty($usercenter) && empty($wrong_referer) &&
            empty($wrong_rights) && empty($show_search) && empty($show_stats)) {

            $ordner_id_escaped = (int) $ordner_id;
            echo '<div align="right"><select name="admin" onchange="window.location=(\'pdl-admin/\'+this.options[this.selectedIndex].value)">
            <option value="">Admin Optionen</option>
            <option value="addfile.php?ordner_id=' . $ordner_id_escaped . '">Datei Adden</option>';

            if (($user_rights['adddirs'] ?? 'N') === "Y") {
                echo '<option value="adddir.php?ordner_id=' . $ordner_id_escaped . '">Sub-Ordner Adden</option>';
            }

            if (($user_rights['editdirs'] ?? 'N') === "Y" && $ordner_id !== 0) {
                echo '<option value="editdir.php?ordner_id=' . $ordner_id_escaped . '">Ordner Editieren</option>';
            }

            if (($user_rights['deldirs'] ?? 'N') === "Y" && $ordner_id !== 0) {
                echo '<option value="deldir.php?ordner_id=' . $ordner_id_escaped . '">Ordner Löschen</option>';
            }

            echo '</select></div>';
        }
    }
}

// Copyright and Close outer table
echo '<br><center>';

if (($settings['debug'] ?? false) === true) {
    $rendertime2 = microtime(true);
    $rendertime = round($rendertime2 - $rendertime1, 3);
    echo 'Renderzeit: ' . $rendertime . 's; ' . $db_handler->querys . ' SQL Anfragen<br>';
}

if (($settings['showcopy'] ?? true) === true) {
    echo 'PowerDownload ' . htmlspecialchars($settings['pdlversion'] ?? '', ENT_QUOTES, 'UTF-8') .
         ' &copy; 2001-2002 by <a href="https://www.powerscripts.org" target="_blank" rel="noopener">PowerScripts</a>';
}

echo '</center>
</td></tr></table>';
