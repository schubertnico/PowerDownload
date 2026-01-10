<?
include("header.inc.php");

$protected = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16);
if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    for($i = 0; $i < count($rights); $i++)
     {
      $db_handler->sql_query("UPDATE $sql_table[rights] SET name='".$rights[$i][name]."', bez='".$rights[$i][bez]."', reihenfolge='".$rights[$i][reihenfolge]."' WHERE right_id='".$rights[$i][right_id]."'");
      if($rights[$i][delete] == 1)
       {
        $dodelete = true;
        for($j = 0; $j < count($protected); $j++)
         {
          if($protected[$j] == $rights[$i][right_id])
           {
            $dodelete = false;
            break;
           }
         }
        if($dodelete == true)
         {
          $db_handler->sql_query("ALTER TABLE $sql_table[usergroup] DROP ".$rights[$i][variablenname]);
          $db_handler->sql_query("DELETE FROM $sql_table[rights] WHERE right_id='".$rights[$i][right_id]."'");
         }
       }
     }
    echo "Rechte geändert";
   }
  echo "
<br>
<form action=\"editdeluright.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"80%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\" colspan=\"2\">
            <b>Rechte ändern/löschen</b>
          </td>
        </tr>";
  $rights_count = -1;
  $rights_res = $db_handler->sql_query("SELECT * FROM $sql_table[rights] ORDER BY reihenfolge ASC");
  while($rights_row = $db_handler->sql_fetch_array($rights_res))
   {
    $rights_count++;
    echo "    <tr>
          <td bgcolor=\"$template[footer_bg]\">
            <input type=\"text\" name=\"rights[$rights_count][reihenfolge]\" value=\"$rights_row[reihenfolge]\" size=\"1\">
          </td>
          <td bgcolor=\"$template[footer_bg]\">
            <input type=\"hidden\" name=\"rights[$rights_count][right_id]\" value=\"$rights_row[right_id]\">
            <b>$rights_row[name]</b>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            <b>Name</b><br>
            Name des Rechtes
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"text\" name=\"rights[$rights_count][name]\" value=\"$rights_row[name]\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            <b>Beschreibung</b><br>
            kleine Beschreibung
          </td>
          <td bgcolor=\"$alt\">
            <textarea name=\"rights[$rights_count][bez]\" cols=\"60\" rows=\"3\">$rights_row[bez]</textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            <b>Variablenname</b>
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"hidden\" name=\"rights[$rights_count][variablenname]\" value=\"$rights_row[variablenname]\">
            $rights_row[variablenname] (kann nicht geändert werden)
          </td>
        </tr>";
    $dodelete = true;
    for($i = 0; $i < count($protected); $i++)
     {
      if($protected[$i] == $rights_row[right_id])
       {
        $dodelete = false;
        break;
       }
     }
    if($dodelete == true)
     {
      $alt = alt_switch();
      echo "    <tr>
          <td bgcolor=\"$alt\">
            <b>Löschen?</b><br>
            Soll das Recht gelöscht werden?
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"checkbox\" name=\"rights[$rights_count][delete]\" value=\"1\">
          </td>
        </tr>";
     }
   }
  echo "
      </table>
    </td>
  </tr>
</table>
<br><br>
<input type=\"submit\" value=\"Userrecht ändern\">
</form>
";
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
