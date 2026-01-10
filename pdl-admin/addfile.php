<?php
include("header.inc.php");

// Extract POST/GET variables with proper defaults
$name = $_POST['name'] ?? '';
$size = isset($_POST['size']) ? (int)$_POST['size'] : 0;
$url = $_POST['url'] ?? '';
$mirror = $_POST['mirror'] ?? '';
$release_id = isset($_POST['release_id']) ? (int)$_POST['release_id'] : (isset($_GET['release_id']) ? (int)$_GET['release_id'] : 0);
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;

if($user_rights['adminaccess'] == "Y")
 {
  if($submit == 1)
   {
    $escaped_name = $db_handler->sql_escape_string($name);
    $escaped_url = $db_handler->sql_escape_string($url);
    $escaped_mirror = $db_handler->sql_escape_string($mirror);
    $escaped_release_id = sql_escape_int($release_id);
    $escaped_size = sql_escape_int($size);

    $db_handler->sql_query("INSERT INTO " . $sql_table['files'] . " (release_id,url,size,name,mirror) VALUES ('" . $escaped_release_id . "', '" . $escaped_url . "', '" . $escaped_size . "', '" . $escaped_name . "', '" . $escaped_mirror . "')");
    echo "<br>done...<br><a href=\"editrelease.php?release_id=" . htmlspecialchars($release_id) . "\">Zurueck zum Release</a>";
   }
  else
   {
  ?>
<br><br>
<form action="addfile.php?submit=1" method="post">
<input type="hidden" name="release_id" value="<?php echo htmlspecialchars($release_id); ?>">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Datei hinzufuegen</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Name<br>
            <small>Wird beim Download Link angezeigt</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="name" size="35">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Groesse<br>
            <small>Dateigroesse in Byte</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="size" size="35" value="<?php echo htmlspecialchars($size); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            URL<br>
            <small>URL zur Datei</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="url" size="35" value="<?php echo htmlspecialchars(urldecode($url)); ?>"><br />
            <?php
            if($settings['ftp_on'] == "Y" && function_exists("ftp_connect"))
             { echo "<a href=\"ftp_browser.php?release_id=" . htmlspecialchars($release_id) . "\">FTP Browser/Upload</a>"; }
            ?>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Fungiert als Mirror von<br>
            <small>Geben sie hier die Datei an, dessen Mirror diese Datei darstellen soll.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <select name="mirror">
            <option value="0">Kein Mirror</option>
            <?php
            $escaped_release_id = sql_escape_int($release_id);
            $mirror_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id='" . $escaped_release_id . "' AND mirror='0'");
            while($mirror_row = $db_handler->sql_fetch_array($mirror_res))
             {
              echo "<option value=\"" . htmlspecialchars($mirror_row['file_id']) . "\">" . htmlspecialchars($mirror_row['name']) . "</option>";
             }
            ?>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Datei hinzufuegen">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
  <?php
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
