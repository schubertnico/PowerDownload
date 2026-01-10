<?
include("pdl-inc/pdl_header.inc.php");
include("pdl-admin/functions.inc.php");
$mysqlversion = $db_handler->sql_fetch_array($db_handler->sql_query("SHOW VARIABLES LIKE 'version'"));
$settings[mysqlversion] = intval(str_replace(".","",$mysqlversion[1]));

//checkt die GD Version. Hoffe das geht auch irgendwie schneller...
check_gd();

$install = 1;

if(!$step) $step = 0;
?>
<html>
<head>
<title>PowerDownload <? echo $settings[pdlversion]; ?> - Install</title>
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
                  <small>PowerDownload <? echo $settings[pdlversion]; ?> - Install</small>
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
            <input type="submit" value="Installation starten">
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
<table border="0" cellpadding="0" cellspacing="0" width="350">
  <tr>
    <td bgcolor="#9B0000">
      <table border="0" cellpadding="3" cellspacing="1" width="100%" height="100%">
        <tr>
          <td bgcolor="#700000" align="center" colspan="2">
            <b>Godadmin erstellen</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            Nickname
          </td>
          <td bgcolor="#3B0000">
            <input type="text" name="nick" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="#2E0000">
            Passwort
          </td>
          <td bgcolor="#2E0000">
            <input type="password" name="pw_neu" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000">
            Bestätigung
          </td>
          <td bgcolor="#3B0000">
            <input type="password" name="pw_neu2" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="#5F0000" align="center" colspan="2">
            <input type="submit" value="Godadmin erstellen.">
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
  if(($pw_neu == $pw_neu2) && $pw_neu)
   {
    $db_handler->sql_query("INSERT INTO $sql_table[user] (nick,passwort,ugroup_id, lastactive) VALUES ('$nick','".md5($pw_neu)."','1','".time()."')");
    ?>
<br><br>
<b>Die Installation ist hiermit abgeschlossen.</b><br>
Sie können sich nun ins <a href="pdl-admin/">Admin Interface</a> einloggen.
Dort müssen sie zuerst die Einstellungen unter Settings ändern und die Templates anpassen.<br>
Und nicht vergessen diese Installationsdatei zu löschen!
    <?
   }
  else
   { echo "<br>Passwort stimmt nicht mit Bestätigung überein oder wurde garnicht ausgefüllt.<br><a href=\"javascript:history.back()\">Zurück</a>"; }
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
