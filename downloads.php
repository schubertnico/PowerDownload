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
</head>
<body bgcolor="#000000" text="#FFFFFF">
<center>
<table border="0">
  <tr>
    <td><?php include("pdl-inc/pdl_stats.inc.php"); ?></td>
    <td><?php include("pdl-inc/pdl_top.inc.php"); ?></td>
    <td><?php include("pdl-inc/pdl_flop.inc.php"); ?></td>
    <td><?php include("pdl-inc/pdl_latest.inc.php"); ?></td>
    <td><?php include("pdl-inc/pdl_rated.inc.php"); ?></td>
  </tr>
</table>
<hr>
<?php include("pdl-inc/pdl_downloads.inc.php"); ?>
</center>
</body>
</html>
