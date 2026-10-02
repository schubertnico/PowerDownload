<?php

/**
 * PowerDownload - Main Entry Point
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

include("pdl-inc/pdl_header.inc.php");

// Seitentitel (Browser-Tab) und HTTP-Status vor der ersten Ausgabe festlegen:
// Release- bzw. Ordnername, bei unbekannten IDs "… nicht gefunden" mit 404.
$pdl_page_title = 'Download Center';
$pdl_current_ordner = null;
if ($pdl_download_missing) {
    $pdl_page_title = 'Datei nicht gefunden';
} elseif ($usercenter === '' && ($screen_id > 0 || $release_id > 0)) {
    if ($screen_id > 0) {
        $pdl_title_row = $db_handler->sql_fetch_array($db_handler->sql_query(
            "SELECT r.name, r.released FROM " . $sql_table['screens'] . " AS s"
            . " JOIN " . $sql_table['release'] . " AS r ON r.release_id = s.release_id"
            . " WHERE s.screen_id='" . $db_handler->sql_escape_int($screen_id) . "'"
        ));
    } else {
        $pdl_title_row = $db_handler->sql_fetch_array($db_handler->sql_query(
            "SELECT name, released FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'"
        ));
    }
    if ($pdl_title_row !== null && ($pdl_title_row['released'] ?? 'N') === 'Y') {
        $pdl_page_title = ($screen_id > 0 ? 'Screenshot – ' : '') . stripslashes((string) $pdl_title_row['name']);
    } else {
        http_response_code(404);
        $pdl_page_title = $screen_id > 0 ? 'Screenshot nicht gefunden' : 'Release nicht gefunden';
    }
} elseif ($usercenter === '' && $show_search === 0 && $show_stats === 0 && $ordner_id > 0) {
    $pdl_current_ordner = $db_handler->sql_fetch_array($db_handler->sql_query(
        "SELECT ordner_id, name FROM " . $sql_table['ordner'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "'"
    ));
    if ($pdl_current_ordner !== null) {
        $pdl_page_title = stripslashes((string) $pdl_current_ordner['name']);
    } else {
        http_response_code(404);
        $pdl_page_title = 'Ordner nicht gefunden';
        $pdl_current_ordner = false;
    }
}

pdl_layout_start($pdl_page_title, $settings, $user_rights, $user_details);

if (pdl_show_dashboard_widgets()) {
    ?>
<div class="pdl-info-grid" id="pdlStartWidgets">
    <div><?php include("pdl-inc/pdl_stats.inc.php"); ?></div>
    <div><?php include("pdl-inc/pdl_top.inc.php"); ?></div>
    <div><?php include("pdl-inc/pdl_flop.inc.php"); ?></div>
    <div><?php include("pdl-inc/pdl_latest.inc.php"); ?></div>
    <div><?php include("pdl-inc/pdl_rated.inc.php"); ?></div>
</div>
<hr class="border-secondary mb-4">
    <?php
}
include("pdl-inc/pdl_downloads.inc.php");
pdl_layout_end($settings, (float)$rendertime1, (int)$db_handler->querys);
