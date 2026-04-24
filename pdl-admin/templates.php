<?php
include("header.inc.php"); ?>
<script language="JavaScript">
function updatecolor(preview,newvalue)
 {
  preview.style.background = newvalue;
 }
</script>
<?php

$submit = isset($_GET['submit']) ? $_GET['submit'] : (isset($_POST['submit']) ? $_POST['submit'] : 0);

if($user_rights['templates'] == "Y")
 {
  if($submit == 1)
   {
    foreach($_POST as $variablenname => $wert)
     {
      $wert = (string) preg_replace('/&amp;/', '&', (string) preg_replace('/&quot;/',"\"", (string) preg_replace('/&lt;/', '<', (string) preg_replace('/&gt;/', '>', $wert))));
      $variablenname_escaped = $db_handler->sql_escape_string($variablenname);
      $wert_escaped = $db_handler->sql_escape_string($wert);
      $db_handler->sql_query("UPDATE `" . $sql_table['template'] . "` SET wert='" . $wert_escaped . "' WHERE variablenname='" . $variablenname_escaped . "'");
     }
    echo "Templates uebernommen.";
   }
  else
   {
    $tgroup_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['templategroup'] . "` ORDER BY reihenfolge ASC");
    while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res))
     {
      echo "<li><a href=\"#" . htmlspecialchars($tgroup_row['tgroup_id']) . "\">" . htmlspecialchars($tgroup_row['name']) . "</a></li>";
     }
    echo "
    <br>
    <form action=\"templates.php?submit=1\" method=\"post\">";

    $tgroup_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['templategroup'] . "` ORDER BY reihenfolge ASC");
    while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res))
     {
      echo "
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border']) . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg']) . "\" align=\"center\" colspan=\"2\">
            <a name=\"" . htmlspecialchars($tgroup_row['tgroup_id']) . "\"><b>" . htmlspecialchars($tgroup_row['name']) . "</b></a>
          </td>
        </tr>
      ";

      $templates_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['template'] . "` WHERE tgroup_id='" . $db_handler->sql_escape_string($tgroup_row['tgroup_id']) . "' ORDER BY reihenfolge ASC");
      while($templates_row = $db_handler->sql_fetch_array($templates_res))
       {
        $alt = alt_switch();
        echo "
        <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\" width=\"35%\">
            <b>" . htmlspecialchars($templates_row['name']) . "</b><br>
            <small>" . htmlspecialchars($templates_row['bez']) . "</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">";
        if($templates_row['eingabe'] == "textarea")
         {
          echo "<textarea cols=\"60\" rows=\"10\" name=\"" . htmlspecialchars($templates_row['variablenname']) . "\">" . htmlspecialchars($templates_row['wert']) . "</textarea>";
         }
        elseif($templates_row['eingabe'] == "input")
         {
          echo "<input type=\"text\" name=\"" . htmlspecialchars($templates_row['variablenname']) . "\" value=\"" . htmlspecialchars($templates_row['wert']) . "\" size=\"35\">";
         }
        elseif($templates_row['eingabe'] == "farbe")
         {
          echo"
                  <input type=\"text\" name=\"" . htmlspecialchars($templates_row['variablenname']) . "\" style=\"width: 58px\" value=\"" . htmlspecialchars($templates_row['wert']) . "\" onchange=\"updatecolor(prev_" . htmlspecialchars($templates_row['variablenname']) . ",this.value)\">
                  <input type=\"button\" disabled id=\"prev_" . htmlspecialchars($templates_row['variablenname']) . "\" style=\"background:" . htmlspecialchars($templates_row['wert']) . "; width: 55px\">
          ";
         }
        else
         {
          // WARNING: Original code used eval() here which is a security risk
          // eval("echo \"$templates_row[eingabe]\";");
          // Replaced with safe output - custom eingabe types should be handled explicitly
          echo htmlspecialchars($templates_row['eingabe']);
         }
        echo "
          </td>
        </tr>
        ";
       }

      echo "
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\" colspan=\"2\">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br>
      ";
     }
    echo "
    <input type=\"submit\" value=\"Templates aendern\">
    </form>";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
