<?
include("header.inc.php"); ?>
<script language="JavaScript">
function updatecolor(preview,newvalue)
 {
  preview.style.background = newvalue;
 }
</script>
<?

if($user_rights[templates] == "Y")
 {
  if($submit == 1)
   {
    foreach($_POST as $variablenname => $wert)
     {
      $wert = ereg_replace('&amp;', '&', ereg_replace('&quot;',"\"", ereg_replace('&lt;', '<',ereg_replace('&gt;', '>', $wert))));
      $db_handler->sql_query("UPDATE $sql_table[template] SET wert='$wert' WHERE variablenname='$variablenname'");
     }
    echo "Templates übernommen.";
   }
  else
   {
    $tgroup_res = $db_handler->sql_query("SELECT * FROM $sql_table[templategroup] ORDER BY reihenfolge ASC");
    while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res))
     {
      echo "<li><a href=\"#$tgroup_row[tgroup_id]\">$tgroup_row[name]</a></li>";
     }
    echo "
    <br>
    <form action=\"templates.php?submit=1\" method=\"post\">";

    $tgroup_res = $db_handler->sql_query("SELECT * FROM $sql_table[templategroup] ORDER BY reihenfolge ASC");
    while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res))
     {
      echo "
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\" colspan=\"2\">
            <a name=\"$tgroup_row[tgroup_id]\"><b>$tgroup_row[name]</b></a>
          </td>
        </tr>
      ";

      $templates_res = $db_handler->sql_query("SELECT * FROM $sql_table[template] WHERE tgroup_id='$tgroup_row[tgroup_id]' ORDER BY reihenfolge ASC");
      while($templates_row = $db_handler->sql_fetch_array($templates_res))
       {
        $alt = alt_switch();
        echo "
        <tr>
          <td bgcolor=\"$alt\" width=\"35%\">
            <b>$templates_row[name]</b><br>
            <small>$templates_row[bez]</small>
          </td>
          <td bgcolor=\"$alt\">";
        if($templates_row[eingabe] == "textarea")
         {
          echo "<textarea cols=\"60\" rows=\"10\" name=\"$templates_row[variablenname]\">".htmlspecialchars($templates_row[wert])."</textarea>";
         }
        elseif($templates_row[eingabe] == "input")
         {
          echo "<input type=\"text\" name=\"$templates_row[variablenname]\" value=\"$templates_row[wert]\" size=\"35\">";
         }
        elseif($templates_row[eingabe] == "farbe")
         {
          echo"
                  <input type=\"text\" name=\"$templates_row[variablenname]\" style=\"width: 58px\" value=\"$templates_row[wert]\" onchange=\"updatecolor(prev_$templates_row[variablenname],this.value)\">
                  <input type=\"button\" disabled id=\"prev_$templates_row[variablenname]\" style=\"background:$templates_row[wert]; width: 55px\">
          ";
         }
        else
         {
          eval("echo \"$templates_row[eingabe]\";");
         }
        echo "
          </td>
        </tr>
        ";
       }

      echo "
        </tr>
        <tr>
          <td bgcolor=\"$template[footer_bg]\" colspan=\"2\">
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
    <input type=\"submit\" value=\"Templates ändern\">
    </form>";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
