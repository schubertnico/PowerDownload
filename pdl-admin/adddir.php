<?php
include("header.inc.php");

$ordner_id = isset($_POST['ordner_id']) ? (int)$_POST['ordner_id'] : 0;
$name = $_POST['name'] ?? '';
$text = $_POST['text'] ?? '';
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;

if($user_rights['adddirs'] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("INSERT INTO ".$sql_table['ordner']." (sordner_id, name, text) VALUES ('$ordner_id', '".$db_handler->sql_escape_string($name)."', '".$db_handler->sql_escape_string($text)."')");
    echo "<br>done...";
   }
  else
   { ?>
<br><br>
<form action="adddir.php?submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Ordner erstellen</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Name<br>
            <small>Wie der Ordner heist</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="name" size="35">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Subordner<br>
            <small>Wählen sie einen Subordner aus</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <select name="ordner_id">
            <option value="0">Index</option>
            <?php echo treeview_select(0,"-"); ?>
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
            <textarea name="text" cols="50" rows="5"></textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Ordner erstellen">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<?php   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
