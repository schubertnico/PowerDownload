<?
include("header.inc.php");
if($user_rights[deluser] == "Y")
 {
  if($submit == 1)
   {
    $checkgod = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[user] WHERE user_id='$user_id'"));
    if($checkgod[ugroup_id] == 1)
     { echo "<br>User ist ein Godadmin und darf nicht gelöscht werden."; }
    else
     {
      $db_handler->sql_query("DELETE FROM $sql_table[user] WHERE user_id='$user_id'");
      echo "<br>done...";
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
            <b>Wählen sie den User aus, den sie löschen wollen.</b>
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
            <a href="deluser.php?submit=1&user_id=<? echo $user_row[user_id]; ?>"><? echo $user_row[nick]; ?></a> - <? echo $user_row[ugroup_name]; ?>
          </td>
        </tr>
        <? } ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" align="center">
            <? echo seiten($db_handler->sql_num_rows($db_handler->sql_query("SELECT $sql_table[user].nick, $sql_table[user].user_id, $sql_table[usergroup].name AS ugroup_name FROM $sql_table[user],$sql_table[usergroup] WHERE $sql_table[usergroup].ugroup_id=$sql_table[user].ugroup_id AND $sql_table[usergroup].ugroup_id!='1' AND $sql_table[user].user_id!='$user_details[user_id]'")),25,"","deluser.php?"); ?>
            <? if($db_handler->sql_num_rows($user_res) == 0) echo "Es sind keine löschbaren User vorhanden."; ?>
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
