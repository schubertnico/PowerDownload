<?
include("pdl-inc/pdl_header.inc.php");
include("pdl-admin/functions.inc.php");
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
                  <small>PowerDownload <? echo $settings[pdlversion]; ?> - Update von 3.0.1</small>
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
          <td bgcolor="#700000" align="center">
            <b>Installationshinweise</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="#3B0000" align="center">
            Hiermit wird PowerDownload 3.0.1 auf die Version 3.0.2 geupdatet.<br>
            Es werden dabei einige Sachen in der Datenbank gelöscht und hinzugefügt.
          </td>
        </tr>
        <tr>
          <td bgcolor="#5F0000" align="center">
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
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='bg'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='ordner_header'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='ordner_close'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='files_header'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='files_close'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='top_header'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='top_footer'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='latest_header'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='latest_footer'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='flop_header'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='flop_footer'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='rated_header'");
  $db_handler->sql_query("DELETE FROM $sql_table[template] WHERE variablenname='rated_footer'");

  $db_handler->sql_query("UPDATE $sql_table[template] SET variablenname='release_row' WHERE variablenname='files_row'");
  $db_handler->sql_query("UPDATE $sql_table[template] SET reihenfolge='2' WHERE variablenname='top_row'");
  $db_handler->sql_query("UPDATE $sql_table[template] SET reihenfolge='4' WHERE variablenname='flop_row'");
  $db_handler->sql_query("UPDATE $sql_table[template] SET reihenfolge='6' WHERE variablenname='latest_row'");
  $db_handler->sql_query("UPDATE $sql_table[template] SET reihenfolge='8' WHERE variablenname='rated_row'");

  $db_handler->sql_query("INSERT INTO $sql_table[template] VALUES ('', 'Box Ordner Übersicht', 'Die Box für die Ordner Übersicht.\\n{rows} wird durch die Zeilen ersetz.', 'ordner_box', '$template[ordner_header]\\n{rows}\\n$template[ordner_close]', 'textarea', '4', '1')");
  $db_handler->sql_query("INSERT INTO $sql_table[template] VALUES ('', 'Box Release Übersicht', 'Die Box für die Release Übersicht.\n{rows} wird durch die Zeilen ersetz.', 'release_box', '$template[files_header]\n{rows}\n$template[files_close]', 'textarea', 5, 1)");
  $db_handler->sql_query("INSERT INTO $sql_table[template] VALUES ('', 'Box Top Downloads', 'Die Box für die Top Downloads.\n{rows} wird durch die Zeilen ersetz.', 'top_box', '$template[top_header]\n{rows}\n$template[top_footer]', 'textarea', 8, 1)");
  $db_handler->sql_query("INSERT INTO $sql_table[template] VALUES ('', 'Box Flop Downloads', 'Die Box für die Flop Downloads.\n{rows} wird durch die Zeilen ersetz.', 'flop_box', '$template[flop_header]\n{rows}\n$template[flop_footer]', 'textarea', 8, 3)");
  $db_handler->sql_query("INSERT INTO $sql_table[template] VALUES ('', 'Box Latest Downloads', 'Die Box für die Neuesten Downloads.\n{rows} wird durch die Zeilen ersetz.', 'latest_box', '$template[latest_header]\n{rows}\n$template[latest_footer]', 'textarea', 8, 5)");
  $db_handler->sql_query("INSERT INTO $sql_table[template] VALUES ('', 'Box Rated Downloads', 'Die Box für die Best Bewertetsten Downloads.\n{rows} wird durch die Zeilen ersetz.', 'rated_box', '$template[rated_header]\n{rows}\n$template[rated_footer]', 'textarea', 8, 7)");

  $db_handler->sql_query("UPDATE $sql_table[templategroup] SET reihenfolge='6' WHERE tgroup_id='10'");
  $db_handler->sql_query("UPDATE $sql_table[templategroup] SET reihenfolge='7' WHERE tgroup_id='9'");
  $db_handler->sql_query("UPDATE $sql_table[templategroup] SET reihenfolge='8' WHERE tgroup_id='7'");
  $db_handler->sql_query("UPDATE $sql_table[templategroup] SET reihenfolge='9' WHERE tgroup_id='8'");
  $db_handler->sql_query("UPDATE $sql_table[templategroup] SET reihenfolge='10' WHERE tgroup_id='6'");

  echo "<br><br>Update erfolgreich durchgeführt. Löschen sie nun diese Update Datei.";
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
