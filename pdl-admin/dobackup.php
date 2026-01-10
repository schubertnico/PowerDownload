<?
include("header.inc.php");
if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    if(is_uploaded_file($backup))
     {
      $query = implode("",file($backup));
      $querys = array();
      set_time_limit(300);
      split_query($querys,$query);
      for($i = 0; $i < count($querys); $i++)
       {
        $db_handler->sql_query($querys[$i]);
       }
      echo "<br>done...";
     }
    else
     { echo "<br>Bitte eine Datei auswählen."; }
   }
  else
   {
    ?>
<br><br>
<form action="dobackup.php?submit=1" method="post" enctype="multipart/form-data">
<table border="0" cellpadding="0" cellspacing="0" width="55%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Backup ausführen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="2" align="center">
            Führen sie ein Backup nur aus, wenn es absolut notwendig ist. Jenachdem wie
            alt das Backup ist gehen Daten verloren. Also vorsicht!
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Backup Datei</b><br>
            <small>Die .sql Datei, die beim Backup erstellt wurde.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="file" name="backup" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Los!">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
    <?
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
