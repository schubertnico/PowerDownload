<?
include("header.inc.php");
if($user_rights[editdirs] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("UPDATE $sql_table[ordner] SET name='$name', text='$text', sordner_id='$sordner_id' WHERE ordner_id='$ordner_id'");
    if($move_files == "Y")
     {
      $files_res = $db_handler->sql_query("SELECT release_id FROM $sql_table[release] WHERE ordner_id='$ordner_id'");
      while($files_row = $db_handler->sql_fetch_array($files_res))
       { $db_handler->sql_query("UPDATE $sql_table[release] SET ordner_id='$release_to' WHERE release_id='$files_row[release_id]'"); }
     }
    if($delete_files == "Y")
     {
      $files_res = $db_handler->sql_query("SELECT release_id FROM $sql_table[release] WHERE ordner_id='$ordner_id'");
      while($files_row = $db_handler->sql_fetch_array($files_res))
       { delrelease($files_row[release_id]); }
     }
    if($move_subdirs == "Y")
     {
      $ordner_res = $db_handler->sql_query("SELECT ordner_id FROM $sql_table[ordner] WHERE sordner_id='$ordner_id'");
      while($ordner_row = $db_handler->sql_fetch_array($ordner_res))
      { $db_handler->sql_query("UPDATE $sql_table[ordner] SET sordner_id='$subdirs_to' WHERE ordner_id='$ordner_row[ordner_id]'"); }
     }
    echo "<br>done...";
   }
  else
   {
    $ordner_res = $db_handler->sql_query("SELECT * FROM $sql_table[ordner] WHERE ordner_id='$ordner_id'");
    $subordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM $sql_table[ordner] WHERE sordner_id='$ordner_id'"));
    $release_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM $sql_table[release] WHERE ordner_id='$ordner_id'"));
    while($ordner_row = $db_handler->sql_fetch_array($ordner_res))
     {?>
<br><br>
<form action="editdir.php?submit=1" method="post">
<input type="hidden" name="ordner_id" value="<? echo $ordner_id; ?>">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Ordner bearbeiten</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Name<br>
            <small>Wie der Ordner heist</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="name" size="35" value="<? echo stripslashes($ordner_row[name]); ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Subordner<br>
            <small>Wählen sie einen Subordner aus</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <select name="sordner_id">
            <option value="0">Index</option>
            <?
            $ordner_id = $ordner_row[sordner_id];
            echo treeview_select(0,"-"); ?>
            </select>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Beschreibung<br>
            <small>Detailiertere Beschreibung was in dem Ordner zu finden ist.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <textarea name="text" cols="50" rows="5"><? echo stripslashes($ordner_row[text]); ?></textarea>
          </td>
        </tr>
        <?
        if($subordner_check > 0 OR $release_check > 0)
         { $alt = alt_switch();
          $ordner_id = 0;
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Optionen<br>
            <small>Was soll mit den Files und Unterordnern geschehen?</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? if($release_check > 0)
             {
              if($user_rights[editfiles] == "Y")
               {?>
            <input type="checkbox" name="move_files" value="Y">Release verschieben nach
            <select name="release_to">
            <option value="0">Index</option>
            <?
            echo treeview_select(0,"-"); ?>
            </select><br>
            <? }
            if($user_rights[delfiles] == "Y")
             {?>
            <input type="checkbox" name="delete_files" value="Y">Release löschen<br>
            <? }
            }
            if($subordner_check > 0)
             { ?>
            <input type="checkbox" name="move_subdirs" value="Y">Subordner verschieben nach
            <select name="subdirs_to">
            <option value="0">Index</option>
            <?
            echo treeview_select(0,"-"); ?>
            </select><br>
            <? } ?>
          </td>
        </tr>
        <? } ?>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Ordner editieren">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<?   }
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
