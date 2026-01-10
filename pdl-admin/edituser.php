<?
include("header.inc.php");
if($user_rights[edituser] == "Y")
 {
  if($submit == 1)
   {
    $checkgod = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[user] WHERE user_id='$user_id'"));
    if($checkgod[ugroup_id] == 1)
     { echo "<br>User ist ein Godadmin und darf nicht editiert werden."; }
    else
     {
      if(!preg_match("!http:\/\/!",$homepage)) $homepage = "http://$homepage";
      if($get_letter != "Y") $get_letter = "N";
      $db_handler->sql_query("UPDATE $sql_table[user] SET nick='$nick', email='$email', homepage='$homepage', icq='$icq', get_letter='$get_letter', ugroup_id='$nugroup_id' WHERE user_id='$user_id'");
      echo "<br>done...";
     }
   }
  elseif($user_id)
   {
    $getuser = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[user] WHERE user_id='$user_id'"));
    if($getuser[ugroup_id] == 1)
     { echo "<br>User ist ein Godadmin und kann nicht editiert werden."; }
    else
     {
     ?>
<br><br>
<form action="edituser.php?submit=1" method="post">
<input type="hidden" name="user_id" value="<? echo $user_id; ?>">
<table border="0" cellpadding="0" cellspacing="0" width="75%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>User Editieren</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Nickname</b><br>
            <small>Hier können sie den Nickname des Users ändern.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="nick" size="30" value="<? echo $getuser[nick]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Email Adresse</b><br>
            <small>Hier können sie die Email Adresse des Users einsehen bzw. ändern.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="email" size="30" value="<? echo $getuser[email]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Homepage</b><br>
            <small>Hier können sie die Homepage des Users einsehen bzw. ändern.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="homepage" size="30" value="<? echo $getuser[homepage]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>ICQ</b><br>
            <small>Hier können sie die ICQ Nummer des Users einsehen bzw. ändern.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="icq" size="30" value="<? if($getuser[icq] > 0) echo $getuser[icq]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Download Letter erhalten</b><br>
            <small>Wenn sie unbedingt möchten, das der User einen Download Letter erhält können sie das hier extra eingeben.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="checkbox" name="get_letter" value="Y"<? if($getuser[get_letter] == "Y") echo " checked"; ?>> Ja, User soll den Letter erhalten.
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Usergruppe</b><br>
            <small>Hier können sie den User einer bestimmten Usergruppe zuteilen.
            <b>Achtung:</b> Passen sie gut auf wen sie zum Godadmin ernennen. Denn dieser User kann dann weder gelöscht noch geändert werden danach.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <select name="nugroup_id">
            <?
            $ugroups_res = $db_handler->sql_query("SELECT * FROM $sql_table[usergroup] WHERE ugroup_id!='3'");
            while($ugroups_row = $db_handler->sql_fetch_array($ugroups_res))
             {
              echo "<option value=\"$ugroups_row[ugroup_id]\"".pdlif($ugroups_row[ugroup_id] == $getuser[ugroup_id]," selected","").">$ugroups_row[name]</option>";
             }
            ?>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="User editieren">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
     <?
     }
   }
  else
   {
    ?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="35%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Wählen sie den User aus, den sie editieren wollen.</b>
          </td>
        </tr>
        <?
        if(!$page) $page = 1;
        $temp1=$page * 25 - 25;
        $limit=$temp1.",25";
        $user_res = $db_handler->sql_query("SELECT $sql_table[user].nick, $sql_table[user].user_id, $sql_table[usergroup].name AS ugroup_name FROM $sql_table[user],$sql_table[usergroup] WHERE $sql_table[usergroup].ugroup_id=$sql_table[user].ugroup_id AND $sql_table[usergroup].ugroup_id!='1' AND $sql_table[user].user_id!='$user_details[user_id]' ORDER BY $sql_table[user].nick ASC LIMIT $limit");
        while($user_row = $db_handler->sql_fetch_array($user_res))
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <a href="edituser.php?user_id=<? echo $user_row[user_id]; ?>"><? echo $user_row[nick]; ?></a> - <? echo $user_row[ugroup_name]; ?>
          </td>
        </tr>
        <? } ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" align="center">
            <? echo seiten($db_handler->sql_num_rows($db_handler->sql_query("SELECT $sql_table[user].nick, $sql_table[user].user_id, $sql_table[usergroup].name AS ugroup_name FROM $sql_table[user],$sql_table[usergroup] WHERE $sql_table[usergroup].ugroup_id=$sql_table[user].ugroup_id AND $sql_table[usergroup].ugroup_id!='1' AND $sql_table[user].user_id!='$user_details[user_id]'")),25,"","edituser.php?"); ?>
            <? if($db_handler->sql_num_rows($user_res) == 0) echo "Es sind keine editierbaren User vorhanden."; ?>
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
    <?
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
