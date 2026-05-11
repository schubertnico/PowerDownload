<?php
$incdir = "../";
include($incdir."pdl-inc/pdl_header.inc.php");
#include("functions.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : (isset($_POST['submit']) ? (int)$_POST['submit'] : 0);

if($user_rights['backup'] == "Y")
 {
  if($submit == 1)
   {
    header("Content-Type: application/octetstream");
    header("Content-Disposition: filename=pdl_dump_".date("dmY").".sql");
    set_time_limit(300);
    header('Expires: 0');
    header("Pragma: no-cache");
    header("Cache-Control: no-cache, must-revalidate");
    $db_handler->sql_query("SET SQL_QUOTE_SHOW_CREATE = 0");
    echo "# PowerDownload ".$settings['pdlversion']." MySQL-Dump\n"
        ."# erstellt am ".date("d.m.Y")." um ".date("H:i")."\n";
    $tables_res = $db_handler->sql_query("SHOW TABLES FROM ".$db_handler->sql_escape_string($config_sql_database));
    while($tables_row = $db_handler->sql_fetch_array($tables_res))
     {
      echo "\n# -----------------------------------------------------\n"
          ."# Struktur für ".$tables_row[0].":\n\n"
          ."DROP TABLE IF EXISTS ".$tables_row[0].";\n";
      $create = $db_handler->sql_fetch_array($db_handler->sql_query("SHOW CREATE TABLE ".$tables_row[0]));
      echo $create[1].";\n\n";

      $rows_res = $db_handler->sql_query("SELECT * FROM ".$tables_row[0]);
      if($db_handler->sql_num_rows($rows_res) > 0)
       {
        echo "# -----------------------------------------------------\n"
            ."# Daten für ".$tables_row[0].":\n\n";
        while($rows_row = $db_handler->sql_fetch_array($rows_res))
         {
          echo "INSERT INTO ".$tables_row[0]." VALUES (";
          for($i = 0; $i < $db_handler->sql_num_fields($rows_res);$i++)
           {
            echo "'".$db_handler->sql_escape_string($rows_row[$i])."'";
            if($i != $db_handler->sql_num_fields($rows_res)-1) echo ", ";
           }
          echo ");\n";
         }
       }

     }
    echo "\n# -----------------------------------------------------";
   }
  else
   {
    include("header.inc.php");
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System'],
        ['title' => 'Datenbank-Backup'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Datenbank-Backup erstellen</h1>';
    echo makedialog(
        "Datenbank-Backup",
        '<p class="mb-2">Klicken Sie auf <strong>OK</strong> um ein Datenbank-Backup anzufertigen.</p>'
        . '<p class="mb-2">Das Backup kann auch unter dem Punkt <em>"Backup ausführen"</em> wieder eingespielt werden.</p>'
        . '<p class="mb-0"><strong>Wichtig:</strong> Das Backup beinhaltet nur den Datenbankinhalt, NICHT die Screenshots und Dateien. Diese müssen manuell gesichert werden.</p>',
        "OK",
        "backup.php"
    );
    include("footer.inc.php");
   }
 }
else
 {
  include("header.inc.php");
  echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
  include("footer.inc.php");
 }

?>
