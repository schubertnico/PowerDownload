<?php
include("header.inc.php");

$chdir = isset($_GET['chdir']) ? $_GET['chdir'] : '';
$cdup = isset($_GET['cdup']) ? (int)$_GET['cdup'] : 0;
$release_id = isset($_GET['release_id']) ? (int)$_GET['release_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;

if($user_rights['adminaccess'] == "Y")
 {
  if($settings['ftp_on'] == "Y" && function_exists("ftp_connect"))
   {
    set_time_limit(300);
    $ftp_handler = ftp_connect($settings['ftp_server']);
    if(!ftp_login($ftp_handler,$settings['ftp_user'],$settings['ftp_passwort']))
     { echo "Login Fehlgeschlagen. Überprüfen sie die Login Daten."; }
    else
     {
      if($chdir) ftp_chdir($ftp_handler,$chdir);
      if($cdup) ftp_cdup($ftp_handler);
      $ftp_ordner = ftp_pwd($ftp_handler);
      if(substr($ftp_ordner,strlen($ftp_ordner)-1,strlen($ftp_ordner)) != "/") $ftp_ordner .= "/";
      $rawlist = ftp_rawlist($ftp_handler,$ftp_ordner);
      $ordner = array();
      $dateien = array();
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
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>">
            Unterwegs auf
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>">
            <?php echo htmlspecialchars($settings['ftp_server_url'].$ftp_ordner); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>">
            <a href="ftp_upload.php?upload_to=<?php echo urlencode($ftp_ordner); ?>&amp;release_id=<?php echo (int)$release_id; ?>">Datei Uploaden</a><br>
            <?php if($ftp_ordner != "/") echo "<a href=\"ftp_browser.php?cdup=1&amp;release_id=".(int)$release_id."\">zum Unterordner</a>"; ?>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="3" align="center">
            <b>Ordner</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?php
        if(count($ordner) == 0)
         {
          $alt = alt_switch();
          ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="3" align="center">
            Keine Ordner vorhanden
          </td>
        </tr>
        <?php }
        else
         {
          for($i = 0; $i < count($ordner); $i++)
           {
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="2">
            <a href="ftp_browser.php?chdir=<?php echo urlencode($ftp_ordner.$ordner[$i][9]."/"); ?>&amp;release_id=<?php echo (int)$release_id; ?>"><?php echo htmlspecialchars($ordner[$i][9]); ?></a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="ftp_upload.php?upload_to=<?php echo urlencode($ftp_ordner.$ordner[$i][9]."/"); ?>&amp;release_id=<?php echo (int)$release_id; ?>">Datei Uploaden</a>
          </td>
        </tr>
        <?php   }
         }
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="3" align="center">
            <b>Dateien</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Größe</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?php
        $total_size = 0;
        if(count($dateien) == 0)
         {
          $alt = alt_switch();
          ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="3" align="center">
            Keine Dateien vorhanden
          </td>
        </tr>
        <?php }
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
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($dateien[$i][9]); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars(size($dateien[$i][5])); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="addfile.php?url=<?php echo urlencode($settings['ftp_server_url'].$ftp_ordner.$dateien[$i][9]); ?>&amp;release_id=<?php echo (int)$release_id; ?>&amp;size=<?php echo (int)$dateien[$i][5]; ?>">Zum Release hinzufügen</a>
          </td>
        </tr>
        <?php   }
         }
        if(count($dateien) > 0)
         {
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>">
            <b>Total:</b> <?php echo (int)count($dateien); ?> Files
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>">
            <?php echo htmlspecialchars(size($total_size)); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>">
            &nbsp;
          </td>
        </tr>
        <?php
         }
        else
         {
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
        <?php } ?>
        <?php if(count($dateien) > 25)
         { ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="3" align="center">
            <?php echo seiten(count($dateien),25,"","ftp_browser.php?chdir=".urlencode($ftp_ordner)."&amp;release_id=".(int)$release_id."&amp;"); ?>
          </td>
        </tr>
        <?php } ?>
      </table>
    </td>
  </tr>
</table>
<br><br>
      <?php
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
