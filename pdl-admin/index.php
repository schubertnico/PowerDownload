<?php
include("header.inc.php");
if($user_details)
 {
  $resetactive = isset($_GET['resetactive']) ? (int)$_GET['resetactive'] : 0;
  if($resetactive == 1)
   {
    $user_details['lastactive'] = time();
    $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET lastactive='" . $db_handler->sql_escape_int($user_details['lastactive']) . "' WHERE user_id='" . $db_handler->sql_escape_int($user_details['user_id']) . "'");
   }
  echo "<br>Seit ihrem letzten Login am ".date($settings['date_format'],$user_details['lastactive'])." ist folgendes passiert:<br><br>";

  if($user_rights['editfiles'] == "Y" || $user_rights['delfiles'] == "Y")
   {
    $release_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE time>'" . $db_handler->sql_escape_int($user_details['lastactive']) . "'");
    if($db_handler->sql_num_rows($release_res) > 0)
     {
  ?>
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="5" align="center">
            <b>neue Release</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>versteckt?</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Uploader</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Optionen</b>
          </td>
        </tr>
  <?php
  while($release_row = $db_handler->sql_fetch_array($release_res))
   {
    $alt = alt_switch();
    ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($release_row['name']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo pdlif($release_row['released'] == "Y","sichtbar","versteckt"); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo user($release_row['uploader']); ?>
          </td>
          <?php
          if($user_rights['editfiles'] == "Y")
           { ?>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>"<?php echo pdlif($user_rights['delfiles'] == "N"," colspan=\"2\"","") ?>>
            <a href="editrelease.php?release_id=<?php echo htmlspecialchars($release_row['release_id']); ?>">ändern</a>
          </td>
          <?php
           }
          if($user_rights['delfiles'] == "Y")
           { ?>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>"<?php echo pdlif($user_rights['editfiles'] == "N"," colspan=\"2\"","") ?>>
            <a href="delrelease.php?release_id=<?php echo htmlspecialchars($release_row['release_id']); ?>">löschen</a>
          </td>
          <?php } ?>
        </tr>
    <?php
   }
  ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="5">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
  <?php }
   }
  if($user_rights['editfiles'] == "Y")
   {
    $comments_res = $db_handler->sql_query("SELECT " . $sql_table['comments'] . ".*, " . $sql_table['release'] . ".name AS release_name FROM " . $sql_table['comments'] . ", " . $sql_table['release'] . " WHERE " . $sql_table['release'] . ".release_id=" . $sql_table['comments'] . ".release_id AND " . $sql_table['comments'] . ".time>'" . $db_handler->sql_escape_int($user_details['lastactive']) . "'");
    if($db_handler->sql_num_rows($comments_res) > 0)
     {
  ?>
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="5" align="center">
            <b>neue Kommentare</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Titel</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Release</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Autor</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?php
        while($comments_row = $db_handler->sql_fetch_array($comments_res))
         {
          $alt = alt_switch();
          ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($comments_row['titel']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="editrelease.php?release_id=<?php echo htmlspecialchars($comments_row['release_id'])?>"><?php echo htmlspecialchars($comments_row['release_name']); ?></a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php if($comments_row['user_id'] == 0) echo "Gast"; else echo user($comments_row['user_id']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="editcomment.php?comment_id=<?php echo htmlspecialchars($comments_row['comment_id']) ?>">ändern</a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="delcomment.php?comment_id=<?php echo htmlspecialchars($comments_row['comment_id']) ?>">löschen</a>
          </td>
        </tr>
          <?php
         }
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="5">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
  <?php
     }
   }
  if($db_handler->sql_num_rows($release_res) == 0 && $db_handler->sql_num_rows($comments_res) == 0)
   { echo "Leider war seit dem letzten login nichts los! :("; }
  else
   { echo "<a href=\"index.php?resetactive=1\">Login bestätigen</a>"; }
 }
else
 {
  echo "
<form action=\"index.php?login=1\" method=\"post\">
<br><br>
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"450\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border']) . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg']) . "\" align=\"center\">
            <b>Bitte einloggen.</b>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['alt_1']) . "\" align=\"center\">
            <small>Bitte die Zugangsdaten eingeben. Cookies müssen an sein.</small>
            <table border=\"0\">
              <tr>
                <td>
                  <input name=\"nick\" size=\"25\">
                </td>
                <td>
                  <input name=\"pw\" size=\"25\" type=\"password\">
                </td>
                <td>
                  <input type=\"submit\" value=\"Login\">
                </td>
              </tr>
              <tr>
                <td>
                  <small>Dein Nick</small>
                </td>
                <td colspan=\"2\">
                  <small>Dein Passwort</small>
                </td>
              </tr>
            </table>
            <a href=\"" . htmlspecialchars($settings['script_file']) . "usercenter=lost\">Zugangsdaten vergessen?</a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>";
 }
include("footer.inc.php");
?>
