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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerDownload</title>
    <link href="pdl-admin/style.css" rel="stylesheet" type="text/css">
    <style>
        body { background:#000; color:#fff; font-family:Arial,Helvetica,sans-serif; margin:0; padding:20px; }
        a { color:#5bb0ff; }
        .pdl-wrap { max-width:1200px; margin:0 auto; text-align:center; }
        .pdl-infoboxes { display:flex; flex-wrap:wrap; gap:12px; justify-content:center; margin-bottom:20px; }
        .pdl-infoboxes > * { flex:1 1 180px; min-width:160px; }
        hr { border:none; border-top:1px solid #333; margin:20px 0; }
    </style>
</head>
<body>
<div class="pdl-wrap">
    <div class="pdl-infoboxes">
        <div><?php include("pdl-inc/pdl_stats.inc.php"); ?></div>
        <div><?php include("pdl-inc/pdl_top.inc.php"); ?></div>
        <div><?php include("pdl-inc/pdl_flop.inc.php"); ?></div>
        <div><?php include("pdl-inc/pdl_latest.inc.php"); ?></div>
        <div><?php include("pdl-inc/pdl_rated.inc.php"); ?></div>
    </div>
    <hr>
    <?php include("pdl-inc/pdl_downloads.inc.php"); ?>
</div>
</body>
</html>
