<?php
include("header.inc.php");

$submit = $_GET['submit'] ?? '';
$name = $_POST['name'] ?? '';

if($user_rights['god'] == "Y")
 {
  if($submit == 1)
   {
    $name_escaped = $db_handler->sql_escape_string($name);
    $db_handler->sql_query("INSERT INTO " . $sql_table['templategroup'] . " VALUES ('','" . $name_escaped . "','')");
    echo "Template Gruppe eingetragen.";
   }
  else
   {
    echo "
<br>
<form action=\"addtgroup.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"45%\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border']) . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg']) . "\" align=\"center\" colspan=\"2\">
            <b>Template Gruppe hinzufügen</b>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['alt_1']) . "\">
            <b>Name</b><br>
            Name der Template Gruppe
          </td>
          <td bgcolor=\"" . htmlspecialchars($template['alt_1']) . "\">
            <input type=\"text\" name=\"name\" size=\"35\">
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\" align=\"center\" colspan=\"2\">
            <input type=\"submit\" value=\"Template Gruppe hinzufügen\">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
