<?
include("header.inc.php");
if($user_rights[adminaccess] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("INSERT INTO $sql_table[files] (release_id,url,size,name,mirror) VALUES ('$release_id', '$url', '$size', '$name', '$mirror')");
    echo "<br>done...<br><a href=\"editrelease.php?release_id=$release_id\">Zurück zum Release</a>";
   }
  else
   {
  ?>
<br><br>
<form action="addfile.php?submit=1" method="post">
<input type="hidden" name="release_id" value="<? echo $release_id; ?>">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Datei hinzufügen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Name<br>
            <small>Wird beim Download Link angezeigt</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="name" size="35">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Größe<br>
            <small>Dateigröße in Byte</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="size" size="35" value="<? echo $size; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            URL<br>
            <small>URL zur Datei</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="url" size="35" value="<? echo urldecode($url); ?>"><br />
            <?
            if($settings[ftp_on] == "Y" && function_exists("ftp_connect"))
             { echo "<a href=\"ftp_browser.php?release_id=$release_id\">FTP Browser/Upload</a>"; }
            ?>
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
            $mirror_res = $db_handler->sql_query("SELECT * FROM $sql_table[files] WHERE release_id='$release_id' AND mirror='0'");
            while($mirror_row = $db_handler->sql_fetch_array($mirror_res))
             {
              echo "<option value=\"$mirror_row[file_id]\">$mirror_row[name]</option>";
             }
            ?>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Datei hinzufügen">
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
