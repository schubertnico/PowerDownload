<?
include("header.inc.php");
if($user_rights[editfiles] == "Y")
 {
  if($submit == 1)
   {
    $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT release_id FROM $sql_table[files] WHERE file_id='$file_id'"));
    $release_id = $release[release_id];
    $db_handler->sql_query("UPDATE $sql_table[files] SET name='$name', downloads='$downloads', size='$size', url='$url', mirror='$mirror' WHERE file_id='$file_id'");
    echo "<br>done...<br><a href=\"editrelease.php?release_id=$release_id\">Zurück zum Release</a>";
   }
  else
   {
    $getfile = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[files] WHERE file_id='$file_id'"));
    ?>
<br><br>
<form action="editfile.php?submit=1" method="post">
<input type="hidden" name="file_id" value="<? echo $file_id; ?>">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Datei bearbeiten</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Name<br>
            <small>Wird beim Download Link angezeigt</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="name" size="35" value="<? echo $getfile[name]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Downloads<br>
            <small>Wie oft die Datei heruntergeladen wurde</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="downloads" size="35" value="<? echo $getfile[downloads]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Größe<br>
            <small>Dateigröße in Byte</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="size" size="35" value="<? echo $getfile[size]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            URL<br>
            <small>URL zur Datei</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="url" size="35" value="<? echo $getfile[url]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Fungiert als Mirror von<br>
            <small>Geben sie hier die Datei an, dessen Mirror diese Datei darstellen soll.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <select name="mirror">
            <option value="0">Kein Mirror</option>
            <?
            $mirror_res = $db_handler->sql_query("SELECT * FROM $sql_table[files] WHERE release_id='$getfile[release_id]' AND mirror='0' AND file_id!='$file_id'");
            while($mirror_row = $db_handler->sql_fetch_array($mirror_res))
             {
              echo "<option value=\"$mirror_row[file_id]\"".pdlif($mirror_row[file_id] == $getfile[mirror], " selected","").">$mirror_row[name]</option>";
             }
            ?>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Datei editieren">
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
