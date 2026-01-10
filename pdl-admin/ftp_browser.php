<?
include("header.inc.php");
if($user_rights[adminaccess] == "Y")
 {
  if($settings[ftp_on] == "Y" && function_exists("ftp_connect"))
   {
    set_time_limit(300);
    $ftp_handler = ftp_connect($settings[ftp_server]);
    if(!ftp_login($ftp_handler,$settings[ftp_user],$settings[ftp_passwort]))
     { echo "Login Fehlgeschlagen. Überprüfen sie die Login Daten."; }
    else
     {
      if($chdir) ftp_chdir($ftp_handler,$chdir);
      if($cdup) ftp_cdup($ftp_handler);
      $ftp_ordner = ftp_pwd($ftp_handler);
      if(substr($ftp_ordner,strlen($ftp_ordner)-1,strlen($ftp_ordner)) != "/") $ftp_ordner .= "/";
      $rawlist = ftp_rawlist($ftp_handler,$ftp_ordner);
      for($i = 0; $i < count($rawlist); $i++)
       {
        preg_match("!([-drwx]+)\s+([0-9]+)\s+([a-zA-Z0-9]+)\s+([a-zA-Z0-9]+)\s+([0-9]+)\s+([a-zA-Z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+)!", $rawlist[$i], $daten);
        if(substr($daten[1],0,1) == "d" && ($daten[9] != "." && $daten[9] != "..")) $ordner[] = $daten;
        elseif($daten[9] != "." && $daten[9] != "..") $dateien[] = $daten;
       }
      function sortnachname($a,$b)
       {
        return strnatcasecmp($a[9], $b[9]);
       }
      if(count($dateien) > 1) usort($dateien, "sortnachname");
      if(count($ordner) > 1) usort($ordner, "sortnachname");
      ?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>">
            Unterwegs auf
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>">
            <? echo $settings[ftp_server_url].$ftp_ordner; ?>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>">
            <a href="ftp_upload.php?upload_to=<? echo $ftp_ordner; ?>&release_id=<? echo $release_id; ?>">Datei Uploaden</a><br>
            <? if($ftp_ordner != "/") echo "<a href=\"ftp_browser.php?cdup=1&release_id=$release_id\">zum Unterordner</a>"; ?>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="3" align="center">
            <b>Ordner</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?
        if(count($ordner) == 0)
         {
          $alt = alt_switch();
          ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="3" align="center">
            Keine Ordner vorhanden
          </td>
        </tr>
        <? }
        else
         {
          for($i = 0; $i < count($ordner); $i++)
           {
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="2">
            <a href="ftp_browser.php?chdir=<? echo $ftp_ordner.$ordner[$i][9]."/"; ?>&release_id=<? echo $release_id; ?>"><? echo $ordner[$i][9]; ?></a>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="ftp_upload.php?upload_to=<? echo $ftp_ordner.$ordner[$i][9]."/"; ?>&release_id=<? echo $release_id; ?>">Datei Uploaden</a>
          </td>
        </tr>
        <?   }
         }
        ?>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="3" align="center">
            <b>Dateien</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Größe</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?
        if(count($dateien) == 0)
         {
          $alt = alt_switch();
          ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="3" align="center">
            Keine Dateien vorhanden
          </td>
        </tr>
        <? }
        else
         {
          if(!$page) $page = 1;
          $start = $page * 25 - 25;
          if($start + 25 > count($dateien)) $ende = count($dateien);
          else $ende = $start + 25;

          for($i = $start; $i < $ende; $i++)
           {
            $total_size += $dateien[$i][5];
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <? echo $dateien[$i][9]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? echo size($dateien[$i][5]); ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="addfile.php?url=<? echo urlencode($settings[ftp_server_url].$ftp_ordner.$dateien[$i][9]); ?>&release_id=<? echo $release_id; ?>&size=<? echo $dateien[$i][5]; ?>">Zum Release hinzufügen</a>
          </td>
        </tr>
        <?   }
         }
        if(count($dateien) > 0)
         {
        ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>">
            <b>Total:</b> <? echo count($dateien); ?> Files
          </td>
          <td bgcolor="<? echo $template[footer_bg]; ?>">
            <? echo size($total_size); ?>
          </td>
          <td bgcolor="<? echo $template[footer_bg]; ?>">
            &nbsp;
          </td>
        </tr>
        <?
         }
        else
         {
        ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
        <? } ?>
        <? if(count($dateien) > 25)
         { ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="3" align="center">
            <? echo seiten(count($dateien),25,"","ftp_browser.php?chdir=$ftp_ordner&release_id=$release_id&"); ?>
          </td>
        </tr>
        <? } ?>
      </table>
    </td>
  </tr>
</table>
<br><br>
      <?
     }
    ftp_quit($ftp_handler);
   }
  else
   {
    echo "Der Server unterstützt keine FTP Funktionen oder ein Admin hat den FTP Browser ausgeschaltet.";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
