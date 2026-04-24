<?php
include("header.inc.php");

// Extract GET/POST variables
$ordner_id = isset($_GET['ordner_id']) ? (int)$_GET['ordner_id'] : (isset($_POST['ordner_id']) ? (int)$_POST['ordner_id'] : 0);
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$name = isset($_POST['name']) ? $_POST['name'] : '';
$text = isset($_POST['text']) ? $_POST['text'] : '';
$sordner_id = isset($_POST['sordner_id']) ? (int)$_POST['sordner_id'] : 0;
$move_files = isset($_POST['move_files']) ? $_POST['move_files'] : '';
$delete_files = isset($_POST['delete_files']) ? $_POST['delete_files'] : '';
$release_to = isset($_POST['release_to']) ? (int)$_POST['release_to'] : 0;
$move_subdirs = isset($_POST['move_subdirs']) ? $_POST['move_subdirs'] : '';
$subdirs_to = isset($_POST['subdirs_to']) ? (int)$_POST['subdirs_to'] : 0;

if($user_rights['editdirs'] == "Y")
 {
  if($submit == 1)
   {
    $name_escaped = $db_handler->sql_escape_string($name);
    $text_escaped = $db_handler->sql_escape_string($text);
    $sordner_id_escaped = $db_handler->sql_escape_int($sordner_id);
    $ordner_id_escaped = $db_handler->sql_escape_int($ordner_id);
    $db_handler->sql_query("UPDATE `{$sql_table['ordner']}` SET name='$name_escaped', text='$text_escaped', sordner_id='$sordner_id_escaped' WHERE ordner_id='$ordner_id_escaped'");
    if($move_files == "Y")
     {
      $release_to_escaped = $db_handler->sql_escape_int($release_to);
      $files_res = $db_handler->sql_query("SELECT release_id FROM `{$sql_table['release']}` WHERE ordner_id='$ordner_id_escaped'");
      while($files_row = $db_handler->sql_fetch_array($files_res))
       { $db_handler->sql_query("UPDATE `{$sql_table['release']}` SET ordner_id='$release_to_escaped' WHERE release_id='" . $db_handler->sql_escape_int($files_row['release_id']) . "'"); }
     }
    if($delete_files == "Y")
     {
      $files_res = $db_handler->sql_query("SELECT release_id FROM `{$sql_table['release']}` WHERE ordner_id='$ordner_id_escaped'");
      while($files_row = $db_handler->sql_fetch_array($files_res))
       { delrelease($files_row['release_id']); }
     }
    if($move_subdirs == "Y")
     {
      $subdirs_to_escaped = $db_handler->sql_escape_int($subdirs_to);
      $ordner_res = $db_handler->sql_query("SELECT ordner_id FROM `{$sql_table['ordner']}` WHERE sordner_id='$ordner_id_escaped'");
      while($ordner_row = $db_handler->sql_fetch_array($ordner_res))
      { $db_handler->sql_query("UPDATE `{$sql_table['ordner']}` SET sordner_id='$subdirs_to_escaped' WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_row['ordner_id']) . "'"); }
     }
    echo "<br>done...";
   }
  else
   {
    $ordner_id_escaped = $db_handler->sql_escape_int($ordner_id);
    $ordner_res = $db_handler->sql_query("SELECT * FROM `{$sql_table['ordner']}` WHERE ordner_id='$ordner_id_escaped'");
    $subordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM `{$sql_table['ordner']}` WHERE sordner_id='$ordner_id_escaped'"));
    $release_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM `{$sql_table['release']}` WHERE ordner_id='$ordner_id_escaped'"));
    while($ordner_row = $db_handler->sql_fetch_array($ordner_res))
     {?>
<br><br>
<form action="editdir.php?submit=1" method="post">
<input type="hidden" name="ordner_id" value="<?php echo htmlspecialchars($ordner_id); ?>">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Ordner bearbeiten</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Name<br>
            <small>Wie der Ordner heist</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="name" size="35" value="<?php echo htmlspecialchars($ordner_row['name']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Subordner<br>
            <small>Wahlen sie einen Subordner aus</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <select name="sordner_id">
            <option value="0">Index</option>
            <?php
            $ordner_id = $ordner_row['sordner_id'];
            echo treeview_select(0,"-"); ?>
            </select>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Beschreibung<br>
            <small>Detailiertere Beschreibung was in dem Ordner zu finden ist.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <textarea name="text" cols="50" rows="5"><?php echo htmlspecialchars($ordner_row['text']); ?></textarea>
          </td>
        </tr>
        <?php
        if($subordner_check > 0 OR $release_check > 0)
         { $alt = alt_switch();
          $ordner_id = 0;
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Optionen<br>
            <small>Was soll mit den Files und Unterordnern geschehen?</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php if($release_check > 0)
             {
              if($user_rights['editfiles'] == "Y")
               {?>
            <input type="checkbox" name="move_files" value="Y">Release verschieben nach
            <select name="release_to">
            <option value="0">Index</option>
            <?php
            echo treeview_select(0,"-"); ?>
            </select><br>
            <?php }
            if($user_rights['delfiles'] == "Y")
             {?>
            <input type="checkbox" name="delete_files" value="Y">Release loschen<br>
            <?php }
            }
            if($subordner_check > 0)
             { ?>
            <input type="checkbox" name="move_subdirs" value="Y">Subordner verschieben nach
            <select name="subdirs_to">
            <option value="0">Index</option>
            <?php
            echo treeview_select(0,"-"); ?>
            </select><br>
            <?php } ?>
          </td>
        </tr>
        <?php } ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Ordner editieren">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<?php   }
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
