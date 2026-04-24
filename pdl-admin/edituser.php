<?php
include("header.inc.php");

// Extract variables from GET/POST for PHP 8.4 compatibility
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0);
$nick = isset($_POST['nick']) ? $_POST['nick'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$homepage = isset($_POST['homepage']) ? $_POST['homepage'] : '';
$icq = isset($_POST['icq']) ? $_POST['icq'] : '';
$get_letter = isset($_POST['get_letter']) ? $_POST['get_letter'] : '';
$nugroup_id = isset($_POST['nugroup_id']) ? (int)$_POST['nugroup_id'] : 0;
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;

if($user_rights['edituser'] == "Y")
 {
  if($submit == 1)
   {
    $safe_user_id = $db_handler->sql_escape_int($user_id);
    $checkgod = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM ".$sql_table['user']." WHERE user_id='".$safe_user_id."'"));
    if($checkgod['ugroup_id'] == 1)
     { echo "<br>User ist ein Godadmin und darf nicht editiert werden."; }
    else
     {
      if(!preg_match("!http:\/\/!",$homepage)) $homepage = "http://$homepage";
      if($get_letter != "Y") $get_letter = "N";
      $safe_nick = $db_handler->sql_escape_string($nick);
      $safe_email = $db_handler->sql_escape_string($email);
      $safe_homepage = $db_handler->sql_escape_string($homepage);
      $safe_icq = $db_handler->sql_escape_string($icq);
      $safe_get_letter = $db_handler->sql_escape_string($get_letter);
      $safe_nugroup_id = $db_handler->sql_escape_int($nugroup_id);
      $db_handler->sql_query("UPDATE ".$sql_table['user']." SET nick='".$safe_nick."', email='".$safe_email."', homepage='".$safe_homepage."', icq='".$safe_icq."', get_letter='".$safe_get_letter."', ugroup_id='".$safe_nugroup_id."' WHERE user_id='".$safe_user_id."'");
      echo "<br>done...";
     }
   }
  elseif($user_id)
   {
    $safe_user_id = $db_handler->sql_escape_int($user_id);
    $getuser = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM ".$sql_table['user']." WHERE user_id='".$safe_user_id."'"));
    if($getuser['ugroup_id'] == 1)
     { echo "<br>User ist ein Godadmin und kann nicht editiert werden."; }
    else
     {
     ?>
<br><br>
<form action="edituser.php?submit=1" method="post">
<input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string) $user_id); ?>">
<table border="0" cellpadding="0" cellspacing="0" width="75%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>User Editieren</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Nickname</b><br>
            <small>Hier k&ouml;nnen sie den Nickname des Users &auml;ndern.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="nick" size="30" value="<?php echo htmlspecialchars($getuser['nick']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Email Adresse</b><br>
            <small>Hier k&ouml;nnen sie die Email Adresse des Users einsehen bzw. &auml;ndern.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="email" size="30" value="<?php echo htmlspecialchars($getuser['email']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Homepage</b><br>
            <small>Hier k&ouml;nnen sie die Homepage des Users einsehen bzw. &auml;ndern.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="homepage" size="30" value="<?php echo htmlspecialchars($getuser['homepage']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>ICQ</b><br>
            <small>Hier k&ouml;nnen sie die ICQ Nummer des Users einsehen bzw. &auml;ndern.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="icq" size="30" value="<?php if($getuser['icq'] > 0) echo htmlspecialchars($getuser['icq']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Download Letter erhalten</b><br>
            <small>Wenn sie unbedingt m&ouml;chten, das der User einen Download Letter erh&auml;lt k&ouml;nnen sie das hier extra eingeben.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="checkbox" name="get_letter" value="Y"<?php if($getuser['get_letter'] == "Y") echo " checked"; ?>> Ja, User soll den Letter erhalten.
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Usergruppe</b><br>
            <small>Hier k&ouml;nnen sie den User einer bestimmten Usergruppe zuteilen.
            <b>Achtung:</b> Passen sie gut auf wen sie zum Godadmin ernennen. Denn dieser User kann dann weder gel&ouml;scht noch ge&auml;ndert werden danach.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <select name="nugroup_id">
            <?php
            $ugroups_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['usergroup']." WHERE ugroup_id!='3'");
            while($ugroups_row = $db_handler->sql_fetch_array($ugroups_res))
             {
              echo "<option value=\"".htmlspecialchars($ugroups_row['ugroup_id'])."\"".pdlif($ugroups_row['ugroup_id'] == $getuser['ugroup_id']," selected","").">".htmlspecialchars($ugroups_row['name'])."</option>";
             }
            ?>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="User editieren">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
     <?php
     }
   }
  else
   {
    ?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="35%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>W&auml;hlen sie den User aus, den sie editieren wollen.</b>
          </td>
        </tr>
        <?php
        if(!$page) $page = 1;
        $temp1=$page * 25 - 25;
        $limit=$temp1.",25";
        $safe_user_details_id = $db_handler->sql_escape_int($user_details['user_id']);
        $user_res = $db_handler->sql_query("SELECT ".$sql_table['user'].".nick, ".$sql_table['user'].".user_id, ".$sql_table['usergroup'].".name AS ugroup_name FROM ".$sql_table['user'].",".$sql_table['usergroup']." WHERE ".$sql_table['usergroup'].".ugroup_id=".$sql_table['user'].".ugroup_id AND ".$sql_table['usergroup'].".ugroup_id!='1' AND ".$sql_table['user'].".user_id!='".$safe_user_details_id."' ORDER BY ".$sql_table['user'].".nick ASC LIMIT $limit");
        while($user_row = $db_handler->sql_fetch_array($user_res))
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="edituser.php?user_id=<?php echo htmlspecialchars($user_row['user_id']); ?>"><?php echo htmlspecialchars($user_row['nick']); ?></a> - <?php echo htmlspecialchars($user_row['ugroup_name']); ?>
          </td>
        </tr>
        <?php } ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" align="center">
            <?php echo seiten($db_handler->sql_num_rows($db_handler->sql_query("SELECT ".$sql_table['user'].".nick, ".$sql_table['user'].".user_id, ".$sql_table['usergroup'].".name AS ugroup_name FROM ".$sql_table['user'].",".$sql_table['usergroup']." WHERE ".$sql_table['usergroup'].".ugroup_id=".$sql_table['user'].".ugroup_id AND ".$sql_table['usergroup'].".ugroup_id!='1' AND ".$sql_table['user'].".user_id!='".$safe_user_details_id."'")),25,"","edituser.php?"); ?>
            <?php if($db_handler->sql_num_rows($user_res) == 0) echo "Es sind keine editierbaren User vorhanden."; ?>
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
    <?php
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
