<?
include("header.inc.php");

$protected = array(2,3,4,5);
if($user_rights[edituser] == "Y" && $user_rights[deluser] == "Y")
 {
  if($submit == 1)
   {
    if($eugroup_id == 1) echo "Usergruppe Godadmin darf nicht geändert werden.";
    else
     {
      for($i = 0;$i < count($rights);$i++)
       {
        $sets .= ", ".$rights[$i][variablenname]."='".$rights[$i][wert]."'";
       }
      $db_handler->sql_query("UPDATE $sql_table[usergroup] SET name='$name'$sets WHERE ugroup_id='$eugroup_id'");
      echo "Usergruppe geändert.";
      if($delete == 1)
       {
        $dodelete = true;
        for($i = 0; $i < count($protected); $i++)
         {
          if($protected[$i] == $eugroup_id)
           {
            $dodelete = false;
            break;
           }
         }
        if($dodelete == true)
         {
          $db_handler->sql_query("DELETE FROM $sql_table[usergroup] WHERE ugroup_id='$eugroup_id'");
         }
       }
     }
   }
  elseif($eugroup_id)
   {
    if($eugroup_id == 1) echo "Usergruppe Godadmin darf nicht geändert werden.";
    else
     {
      $ugroup_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[usergroup] WHERE ugroup_id='$eugroup_id' AND ugroup_id!=1"));
      echo "
<br>
<form action=\"editdelugroup.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\" colspan=\"2\">
            <b>Usergruppe ändern</b>
          </td>
        </tr>";
      $alt = alt_switch();
      echo "    <tr>
          <td bgcolor=\"$alt\">
            <b>Name</b><br>
            Name der Usergruppe
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"text\" name=\"name\" value=\"$ugroup_row[name]\" size=\"35\">
            <input type=\"hidden\" name=\"eugroup_id\" value=\"$eugroup_id\">
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$template[footer_bg]\" align=\"center\" colspan=\"2\">
            <b>Rechte</b>
          </td>
        </tr>";
      $rights_count = -1;
      $rights_res = $db_handler->sql_query("SELECT * FROM $sql_table[rights] ORDER BY reihenfolge ASC");
      while($rights_row = $db_handler->sql_fetch_array($rights_res))
       {
        $rights_count++;
        $alt = alt_switch();
        echo "    <tr>
          <td bgcolor=\"$alt\">
            <b>$rights_row[name]</b><br>
            $rights_row[bez]
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"hidden\" name=\"rights[$rights_count][variablenname]\" value=\"$rights_row[variablenname]\">
            <input type=\"radio\" name=\"rights[$rights_count][wert]\" value=\"N\"";
        if($ugroup_row[$rights_row[variablenname]] == "N") echo " checked";
        echo ">Nein,
            <input type=\"radio\" name=\"rights[$rights_count][wert]\" value=\"Y\"";
        if($ugroup_row[$rights_row[variablenname]] == "Y") echo " checked";
        echo ">Ja
          </td>
        </tr>";
       }
      $dodelete = true;
      for($i = 0; $i < count($protected); $i++)
       {
        if($protected[$i] == $ugroup_row[ugroup_id])
         {
          $dodelete = false;
          break;
         }
       }
      if($dodelete == true)
       {
        $alt = alt_switch();
        echo "  <tr>
          <td bgcolor=\"$alt\">
            <b>Löschen</b><br>
            Soll die User Gruppe gelöscht werden?
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"checkbox\" name=\"delete\" value=\"1\">
          </td>
        </tr>";
       }
      echo "
        <tr>
          <td bgcolor=\"$template[footer_bg]\" align=\"center\" colspan=\"2\">
            <input type=\"submit\" value=\"Usergruppe ändern\">
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
   {
    echo "
<br>
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"20%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\">
            <b>Usergruppe auswählen</b>
          </td>
        </tr>";
    $ugroups_res = $db_handler->sql_query("SELECT * FROM $sql_table[usergroup] WHERE ugroup_id!=1 AND name!=''");
    while($ugroups_row = $db_handler->sql_fetch_array($ugroups_res))
     {
      $alt = alt_switch();
      echo "  <tr>
          <td bgcolor=\"$alt\" align=\"center\">
            <a href=\"editdelugroup.php?eugroup_id=$ugroups_row[ugroup_id]\">$ugroups_row[name]</a>
          </td>
        </tr>";
     }
    echo "
        <tr>
          <td bgcolor=\"$template[footer_bg]\" align=\"center\">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
