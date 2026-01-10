<?
include("pdl-inc/pdl_header.inc.php");
include("pdl-admin/functions.inc.php");
$mysqlversion = $db_handler->sql_fetch_array($db_handler->sql_query("SHOW VARIABLES LIKE 'version'"));
$settings[mysqlversion] = intval(str_replace(".","",$mysqlversion[1]));

//checkt die GD Version. Hoffe das geht auch irgendwie schneller...
ob_start();
phpinfo(8);
$phpinfo=ob_get_contents();
ob_end_clean();
$phpinfo=strip_tags($phpinfo);
$phpinfo=stristr($phpinfo,"gd version");
$phpinfo=stristr($phpinfo,"version");
$end=strpos($phpinfo," ");
$phpinfo=substr($phpinfo,0,$end);
$phpinfo=substr($phpinfo,7);
$settings[gdversion] = intval($phpinfo);

$install = 1;

if(!$step) $step = 0;
?>
<html>
<head>
<title>PowerDownload <? echo $settings[pdlversion]; ?> - Update</title>
<link href="pdl-admin/style.css" rel="stylesheet" type="text/css">
<style>
body
 {
  margin-bottom: 0;
  margin-left: 0;
  margin-right: 0;
  margin-top: 0;
 }
</style>
</head>
<body bgcolor="#000000" text="#FFFFFF">
<center>
<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
  <tr>
    <td bgcolor="#9B0000">
      <table border="0" cellpadding="3" cellspacing="1" width="100%" height="100%">
        <tr>
          <td bgcolor="#700000" height="15">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" height="100%">
              <tr>
                <td height="15">
                  <small>PowerDownload <? echo $settings[pdlversion]; ?> - Update von 2.2.4</small>
                </td>
                <td align="right">
                  <small>PowerDownload &copy; 2002 by Arpad Borsos</small>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td height="100%" width="100%" bgcolor="#000000" align="center" valign="top">
<?
if($step == 0)
 {
  ?>
<br><br>
<form action="<? echo $PHP_SELF; ?>?step=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="500">
  <tr>
    <td bgcolor="#9B0000">
      <table border="0" cellpadding="3" cellspacing="1" width="100%" height="100%">
        <tr>
          <td bgcolor="#700000" align="center" colspan="3">
            <b>Installationsvorraussetzungen</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="#700000" align="center">
            <b>Eingenschaft</b>
          </td>
          <td bgcolor="#700000" align="center">
            <b>erforderlich</b>
          </td>
          <td bgcolor="#700000" align="center">
            <b>vorhanden</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            PHP Version
          </td>
          <td bgcolor="#3B0000">
            4.1.0
          </td>
          <td bgcolor="#3B0000">
            <font color="<? echo pdlif($settings[phpversion] >= 410,"green","red"); ?>"><? echo phpversion(); ?></font>
          </td>
        </tr>
        <tr>
          <td bgcolor="#2E0000">
            MySQL Version
          </td>
          <td bgcolor="#2E0000">
            3.23.20
          </td>
          <td bgcolor="#2E0000">
            <font color="<? echo pdlif($settings[mysqlversion] >= 32320,"green","red"); ?>"><? echo $mysqlversion[1]; ?></font>
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            GD Lib Version (Grafik)
          </td>
          <td bgcolor="#3B0000">
            2 - optional
          </td>
          <td bgcolor="#3B0000">
            <font color="<? if($settings[gdversion] == 2) echo "green"; elseif($settings[gdversion] == 1) echo "orange"; else echo "red"; ?>">
            <? if($settings[gdversion] == 2) echo "2.x"; elseif($settings[gdversion] == 1) echo "1.x"; else echo "nicht installiert"; ?>
            </font>
          </td>
        </tr>
        <tr>
          <td bgcolor="#2E0000">
            FTP Funktionen
          </td>
          <td bgcolor="#2E0000">
            aktiviert - optional
          </td>
          <td bgcolor="#2E0000">
            <?
            if(function_exists("ftp_connect")) $ftp = 1;
            else $ftp = 0;
            ?>
            <font color="<? echo pdlif($ftp == 1,"green","red"); ?>">
            <? echo pdlif($ftp == 1,"aktiviert","deaktiviert"); ?>
            </font>
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            upload_max_filesize (für Screenshot/Datei Upload)
          </td>
          <td bgcolor="#3B0000">
            > 0
          </td>
          <td bgcolor="#3B0000">
            <font color="<? echo pdlif(get_cfg_var("upload_max_filesize") > 0,"green","red"); ?>">
            <? echo get_cfg_var("upload_max_filesize"); ?>
            </font>
          </td>
        </tr>
        <tr>
          <td bgcolor="#2E0000">
            Schreibrechte in pdl-gfx/screens/
          </td>
          <td bgcolor="#2E0000">
            Ja
          </td>
          <td bgcolor="#2E0000">
            <font color="<? echo pdlif(is_writable("pdl-gfx/screens"),"green","red"); ?>">
            <? echo pdlif(is_writable("pdl-gfx/screens"),"Ja","Nein"); ?>
            </font>
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            Schreibrechte in pdl-gfx/smilies/
          </td>
          <td bgcolor="#3B0000">
            Ja
          </td>
          <td bgcolor="#3B0000">
            <font color="<? echo pdlif(is_writable("pdl-gfx/smilies"),"green","red"); ?>">
            <? echo pdlif(is_writable("pdl-gfx/smilies"),"Ja","Nein"); ?>
            </font>
          </td>
        </tr>
        <tr>
          <td bgcolor="#5F0000" align="center" colspan="3">
            <input type="submit" value="Update starten">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
  <?
 }
if($step == 1)
 {
  set_time_limit(300);

  include("install_querys.inc");
  $querys = array();
  split_query($querys,$install_querys);
  for($i = 0; $i < count($querys); $i++)
   {
    $db_handler->sql_query($querys[$i]);
   }
  echo "<br>Tabellen und Standardkonfiguration erzeugt.";
  ?>
<br><br>
<form action="<? echo $PHP_SELF; ?>?step=2" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="400">
  <tr>
    <td bgcolor="#9B0000">
      <table border="0" cellpadding="3" cellspacing="1" width="100%" height="100%">
        <tr>
          <td bgcolor="#700000" align="center" colspan="2">
            <b>Alte Tabellennamen.</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="#5F0000" colspan="2">
            Hinweis: Geben sie hier die Tabellennamen von PDL 2.2.4 an. Sollten sie die nicht geändert
            haben lassen sie die Tabellennamen einfach so.<br>
            Mit dem nächsten Schritt werden alle Daten von PowerDownload umconvertiert.
            Dies kann u.U. sehr lange dauernd, jenachdem wieviel Downloads sie in der Datenbank
            haben und ob sie die Screenshots direkt umändern möchten. Die alte Datenbank bleibt auf jedenfall erhalten auf den Fall hin, das dieses Update
            schiefgeht.<br><br>
            Was gemacht wird jetzt:<br>
            - Alle User werden umgewandelt. Die einteilung in die Usergruppen wird automatisch
            vorgenommen. Überprüfen sie nach dem Update, ob die User auch alle die richtigen Rechte haben.<br>
            - Die Kommentare werden 1:1 umconvertiert.<br>
            - Die Ordner werden 1:1 umconvertiert.<br>
            - Die Release werden 1:1 umconvertiert.<br>
            - Name der Hauptdatei bzw. der Mirrors wird nach dem Dateinamen der verlinkten Datei vergeben. Bei Mirrors wird noch ein " - Mirror by ..." hinzugefügt.<br>
            - Alte Settings werden, falls möglich, übernommen.
            - Screenshots werden NICHT übernommen.
          </td>
        </tr>
        <tr>
          <td bgcolor="#2F0000">
            Admins
          </td>
          <td bgcolor="#2F0000">
            <input type="text" name="admins" size="30" value="pdl_admins">
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            Comments
          </td>
          <td bgcolor="#3B0000">
            <input type="text" name="comments" size="30" value="pdl_comments">
          </td>
        </tr>
        <tr>
          <td bgcolor="#2F0000">
            Dirs
          </td>
          <td bgcolor="#2F0000">
            <input type="text" name="dirs" size="30" value="pdl_dirs">
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            Files
          </td>
          <td bgcolor="#3B0000">
            <input type="text" name="files" size="30" value="pdl_files">
          </td>
        </tr>
        <tr>
          <td bgcolor="#2F0000">
            Mirrors
          </td>
          <td bgcolor="#2F0000">
            <input type="text" name="mirrors" size="30" value="pdl_mirrors">
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            Settings
          </td>
          <td bgcolor="#3B0000">
            <input type="text" name="settings" size="30" value="pdl_settings">
          </td>
        </tr>
        <tr>
          <td bgcolor="#5F0000" align="center" colspan="2">
            <input type="submit" value="Weiter zum nächsten Schritt.">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
  <?
 }
if($step == 2)
 {
  set_time_limit(300);

  // user convertieren
  $oldusers_res = $db_handler->sql_query("SELECT * FROM $admins");
  while($oldusers_row = $db_handler->sql_fetch_array($oldusers_res))
   {
    if($oldusers_row[access_admin] == "Y")
     {
      if($oldusers_row[extra_recht] == "Y") $ugroup_id = 1;
      elseif($oldusers_row[adddirs] == "Y" && $oldusers_row[editdirs] == "Y") $ugroup_id = 5;
      else $ugroup_id = 4;
     }
    else
     { $ugroup_id = 2; }

    $db_handler->sql_query("INSERT INTO $sql_table[user] VALUES ('$oldusers_row[id]','$oldusers_row[nick]','$oldusers_row[mail]','".md5(base64_decode($oldusers_row[pw]))."','$oldusers_row[url]','$oldusers_row[icq]','Y','','$ugroup_id','','".time()."')");
   }

  // ordner,files,mirrors und screens convertieren
  $ordner_res = $db_handler->sql_query("SELECT * FROM $dirs");
  while($ordner_row = $db_handler->sql_fetch_array($ordner_res))
   {
    $db_handler->sql_query("INSERT INTO $sql_table[ordner] VALUES ('$ordner_row[id]','$ordner_row[ordner_id]','".addslashes($ordner_row[name])."','".addslashes($ordner_row[text])."')");
   }

  // release convertieren
  $release_res = $db_handler->sql_query("SELECT * FROM $files");
  while($release_row = $db_handler->sql_fetch_array($release_res))
   {
    $db_handler->sql_query("INSERT INTO $sql_table[release] VALUES ('$release_row[id]','".addslashes($release_row[name])."','".addslashes($release_row[text])."','$release_row[timestamp]','$release_row[views]','$release_row[ordner_id]','$release_row[uploader]','$release_row[author]','$release_row[author_nick]','$release_row[author_mail]','$release_row[author_url]','$release_row[author_icq]','$release_row[released]','$release_row[votes]','$release_row[vote]')");
    if($release_row[loads] > $release_row[views]) $release_row[loads] = $release_row[views];
    $dateiname = basename($release_row[url]);
    $size = $release_row[size]*1024;
    $db_handler->sql_query("INSERT INTO $sql_table[files] VALUES ('','$release_row[id]','$release_row[loads]','$release_row[url]','$size','$dateiname','')");
    $file_id = $db_handler->sql_insert_id();
    $mirror_res = $db_handler->sql_query("SELECT * FROM $mirrors WHERE file_id='$release_row[id]'");
    while($mirror_row = $db_handler->sql_fetch_array($mirror_res))
     {
      $db_handler->sql_query("INSERT INTO $sql_table[files] VALUES ('','$release_row[id]','0','$mirror_row[url]','','$dateiname - Mirror by $mirror_row[name]','$file_id')");
     }
   }

  // settings convertieren
  $oldsettings_res = $db_handler->sql_query("SELECT * FROM $_POST[settings]");
  while($oldsettings_row = $db_handler->sql_fetch_array($oldsettings_res))
   {
    $oldsettings[$oldsettings_row[name]] = $oldsettings_row[value];
   }

  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[script_file]' WHERE variablenname='script_file'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[time_config]' WHERE variablenname='date_format'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[ftp_server]' WHERE variablenname='ftp_server'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[ftp_user]' WHERE variablenname='ftp_user'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[ftp_passwort]' WHERE variablenname='ftp_passwort'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[ftp_server_url]' WHERE variablenname='ftp_server_url'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[top_count]' WHERE variablenname='top_count'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[show_comments]' WHERE variablenname='enable_comments'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[show_search]' WHERE variablenname='enable_search'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[show_treeview]' WHERE variablenname='enable_treeview'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[show_extern_admin]' WHERE variablenname='enable_extrernadmin'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[allowed_referer]' WHERE variablenname='allowed_referer'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[referer_check]' WHERE variablenname='referer_check'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[trennstring]' WHERE variablenname='trenn_string'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[chars_count]' WHERE variablenname='trenn_zeichen'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[config_seite]' WHERE variablenname='perpage'");

  $installed = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT timestamp FROM $files ORDER BY timestamp ASC LIMIT 0,1"));

  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$installed[timestamp]' WHERE variablenname='installed'");

  // kommentare convertieren
  $comments_res = $db_handler->sql_query("SELECT * FROM $comments");
  while($comments_row = $db_handler->sql_fetch_array($comments_res))
   {
    $db_handler->sql_query("INSERT INTO $sql_table[comments] VALUES ('','$comments_row[user]','$comments_row[file_id]','".addslashes($comments_row[titel])."','".addslashes($comments_row[text])."','$comments_row[timestamp]')");
   }

  echo "<br><br>Update erfolgreich durchgeführt. Überprüfen Sie alle Admins und erstellen sie ggf. neue Usergruppen. Überprüfen sie alle Release mit mehreren Files. Außerdem müssen sie die alten Screens neu hochladen. Löschen sie nun diese Update Datei.<br><a href=\"pdl-admin/\">weiter zum Admin Center</a>";

 }

$rendertime2=microtime();
$rendertimetemp=explode(" ",$rendertime2);
$rendertime2=$rendertimetemp[0]+$rendertimetemp[1];
$rendertime=$rendertime2-$rendertime1;
$rendertime=round($rendertime,3);
?>
          </td>
        </tr>
        <tr>
          <td bgcolor="#5F0000" align="center">
            Renderzeit: <? echo $rendertime; ?>s; <? echo $db_handler->querys; ?> SQL Anfragen
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</center>
</body>
</html>
