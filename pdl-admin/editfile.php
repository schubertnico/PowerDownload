<?php
include("header.inc.php");

// Extract variables for PHP 8.4 compatibility
$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : (isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0);
$name = $_POST['name'] ?? '';
$downloads = isset($_POST['downloads']) ? (int)$_POST['downloads'] : 0;
$size = isset($_POST['size']) ? (int)$_POST['size'] : 0;
$url = $_POST['url'] ?? '';
$mirror = $_POST['mirror'] ?? '';
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;

if($user_rights['editfiles'] == "Y")
 {
  if($submit == 1)
   {
    $safe_file_id = sql_escape_int($file_id);
    $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT release_id FROM " . $sql_table['files'] . " WHERE file_id=" . $safe_file_id));
    $release_id = (int)$release['release_id'];

    $safe_name = $db_handler->sql_escape_string($name);
    $safe_downloads = sql_escape_int($downloads);
    $safe_size = sql_escape_int($size);
    $safe_url = $db_handler->sql_escape_string($url);
    $safe_mirror = $db_handler->sql_escape_string($mirror);

    $db_handler->sql_query("UPDATE " . $sql_table['files'] . " SET name='" . $safe_name . "', downloads=" . $safe_downloads . ", size=" . $safe_size . ", url='" . $safe_url . "', mirror='" . $safe_mirror . "' WHERE file_id=" . $safe_file_id);
    echo "<br>done...<br><a href=\"editrelease.php?release_id=" . htmlspecialchars((string)$release_id) . "\">Zurueck zum Release</a>";
   }
  else
   {
    $safe_file_id = sql_escape_int($file_id);
    $getfile = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE file_id=" . $safe_file_id));
    ?>
<br><br>
<form action="editfile.php?submit=1" method="post">
<input type="hidden" name="file_id" value="<?php echo htmlspecialchars((string)$file_id); ?>">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Datei bearbeiten</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Name<br>
            <small>Wird beim Download Link angezeigt</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="name" size="35" value="<?php echo htmlspecialchars($getfile['name']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Downloads<br>
            <small>Wie oft die Datei heruntergeladen wurde</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="downloads" size="35" value="<?php echo htmlspecialchars((string)$getfile['downloads']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Groesse<br>
            <small>Dateigroesse in Byte</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="size" size="35" value="<?php echo htmlspecialchars((string)$getfile['size']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            URL<br>
            <small>URL zur Datei</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="url" size="35" value="<?php echo htmlspecialchars($getfile['url']); ?>">
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
            $safe_release_id = sql_escape_int($getfile['release_id']);
            $safe_file_id = sql_escape_int($file_id);
            $mirror_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id=" . $safe_release_id . " AND mirror='0' AND file_id!=" . $safe_file_id);
            while($mirror_row = $db_handler->sql_fetch_array($mirror_res))
             {
              echo "<option value=\"" . htmlspecialchars((string)$mirror_row['file_id']) . "\"" . pdlif($mirror_row['file_id'] == $getfile['mirror'], " selected", "") . ">" . htmlspecialchars($mirror_row['name']) . "</option>";
             }
            ?>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Datei editieren">
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
