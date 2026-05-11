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

pdl_layout_start('Download Center', $settings, $user_rights, $user_details);
?>
<div class="pdl-info-grid">
    <div><?php include("pdl-inc/pdl_stats.inc.php"); ?></div>
    <div><?php include("pdl-inc/pdl_top.inc.php"); ?></div>
    <div><?php include("pdl-inc/pdl_flop.inc.php"); ?></div>
    <div><?php include("pdl-inc/pdl_latest.inc.php"); ?></div>
    <div><?php include("pdl-inc/pdl_rated.inc.php"); ?></div>
</div>
<hr class="border-secondary mb-4">
<?php include("pdl-inc/pdl_downloads.inc.php"); ?>
<?php pdl_layout_end($settings, (float)$rendertime1, (int)$db_handler->querys); ?>
