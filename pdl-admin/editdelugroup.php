<?php
include("header.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$eugroup_id = isset($_POST['eugroup_id']) ? (int)$_POST['eugroup_id'] : (isset($_GET['eugroup_id']) ? (int)$_GET['eugroup_id'] : 0);
$name = isset($_POST['name']) ? $_POST['name'] : '';
$rights = isset($_POST['rights']) ? $_POST['rights'] : array();
$delete = isset($_POST['delete']) ? (int)$_POST['delete'] : 0;

$protected = array(2,3,4,5);
if($user_rights['edituser'] == "Y" && $user_rights['deluser'] == "Y")
 {
  if($submit == 1)
   {
    if($eugroup_id == 1) echo "Usergruppe Godadmin darf nicht geändert werden.";
    else
     {
      $sets = "";
      for($i = 0;$i < count($rights);$i++)
       {
        $sets .= ", ".$db_handler->sql_escape_string($rights[$i]['variablenname'])."='".$db_handler->sql_escape_string($rights[$i]['wert'])."'";
       }
      $db_handler->sql_query("UPDATE ".$sql_table['usergroup']." SET name='".$db_handler->sql_escape_string($name)."'".$sets." WHERE ugroup_id='".$db_handler->sql_escape_int($eugroup_id)."'");
      echo "Usergruppe geändert.";
      if($delete == 1)
       {
        $dodelete = true;
        foreach($protected as $prot_id)
         {
          if($prot_id == $eugroup_id)
           {
            $dodelete = false;
            break;
           }
         }
        if($dodelete == true)
         {
          $db_handler->sql_query("DELETE FROM ".$sql_table['usergroup']." WHERE ugroup_id='".$db_handler->sql_escape_int($eugroup_id)."'");
         }
       }
     }
   }
  elseif($eugroup_id)
   {
    if($eugroup_id == 1) echo "Usergruppe Godadmin darf nicht geändert werden.";
    else
     {
      $ugroup_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM ".$sql_table['usergroup']." WHERE ugroup_id='".$db_handler->sql_escape_int($eugroup_id)."' AND ugroup_id!=1"));
      echo "
<br>
<form action=\"editdelugroup.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"".htmlspecialchars($template['table_border'])."\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['header_bg'])."\" align=\"center\" colspan=\"2\">
            <b>Usergruppe ändern</b>
          </td>
        </tr>";
      $alt = alt_switch();
      echo "    <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <b>Name</b><br>
            Name der Usergruppe
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <input type=\"text\" name=\"name\" value=\"".htmlspecialchars($ugroup_row['name'])."\" size=\"35\">
            <input type=\"hidden\" name=\"eugroup_id\" value=\"".htmlspecialchars((string) $eugroup_id)."\">
          </td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\" align=\"center\" colspan=\"2\">
            <b>Rechte</b>
          </td>
        </tr>";
      $rights_count = -1;
      $rights_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['rights']." ORDER BY reihenfolge ASC");
      while($rights_row = $db_handler->sql_fetch_array($rights_res))
       {
        $rights_count++;
        $alt = alt_switch();
        echo "    <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <b>".htmlspecialchars($rights_row['name'])."</b><br>
            ".htmlspecialchars($rights_row['bez'])."
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <input type=\"hidden\" name=\"rights[".$rights_count."][variablenname]\" value=\"".htmlspecialchars($rights_row['variablenname'])."\">
            <input type=\"radio\" name=\"rights[".$rights_count."][wert]\" value=\"N\"";
        if($ugroup_row[$rights_row['variablenname']] == "N") echo " checked";
        echo ">Nein,
            <input type=\"radio\" name=\"rights[".$rights_count."][wert]\" value=\"Y\"";
        if($ugroup_row[$rights_row['variablenname']] == "Y") echo " checked";
        echo ">Ja
          </td>
        </tr>";
       }
      $dodelete = true;
      foreach($protected as $prot_id)
       {
        if($prot_id == $ugroup_row['ugroup_id'])
         {
          $dodelete = false;
          break;
         }
       }
      if($dodelete == true)
       {
        $alt = alt_switch();
        echo "  <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <b>Löschen</b><br>
            Soll die User Gruppe gelöscht werden?
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <input type=\"checkbox\" name=\"delete\" value=\"1\">
          </td>
        </tr>";
       }
      echo "
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\" align=\"center\" colspan=\"2\">
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
    <td bgcolor=\"".htmlspecialchars($template['table_border'])."\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['header_bg'])."\" align=\"center\">
            <b>Usergruppe auswählen</b>
          </td>
        </tr>";
    $ugroups_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['usergroup']." WHERE ugroup_id!=1 AND name!=''");
    while($ugroups_row = $db_handler->sql_fetch_array($ugroups_res))
     {
      $alt = alt_switch();
      echo "  <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\" align=\"center\">
            <a href=\"editdelugroup.php?eugroup_id=".htmlspecialchars($ugroups_row['ugroup_id'])."\">".htmlspecialchars($ugroups_row['name'])."</a>
          </td>
        </tr>";
     }
    echo "
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\" align=\"center\">
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
