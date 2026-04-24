<?php
/**
 * PowerDownload - Statistics Module
 * @license MIT
 */

$tables = 0;
$size = 0;
$rows = 0;
$tables_res = $db_handler->sql_query("SHOW TABLE STATUS");
while ($tables_row = $db_handler->sql_fetch_array($tables_res)) {
    $tables++;
    $size += ($tables_row['Data_length'] ?? 0) + ($tables_row['Index_length'] ?? 0);
    $rows += $tables_row['Rows'] ?? 0;
}

$mysqlversion_row = $db_handler->sql_fetch_array($db_handler->sql_query("SHOW VARIABLES LIKE 'version'"));
$mysqlversion = $mysqlversion_row[1] ?? $mysqlversion_row['Value'] ?? 'Unknown';

$table_border = htmlspecialchars($template['table_border'] ?? '#000000');
$header_bg = htmlspecialchars($template['header_bg'] ?? '#CCCCCC');
$footer_bg = htmlspecialchars($template['footer_bg'] ?? '#CCCCCC');
$script_file = htmlspecialchars($settings['script_file'] ?? '');
?>
<table width="100%" border="0">
  <tr>
    <td width="50%" valign="top">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="2" align="center">
            <b>Server & DB Stats</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>DB Version</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($mysqlversion); ?>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>DB Groesse</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo round($size / 1024 / 1024, 2); ?> MB
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Tabellen in der DB</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $tables; ?>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>DB Eintraege</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $rows; ?>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Server Software</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="2">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="2" align="center">
            <b>User & Gruppen</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Usergruppe</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>User</b>
          </td>
        </tr>
<?php
$ugroup_res = $db_handler->sql_query("SELECT " . $sql_table['usergroup'] . ".name AS ugroup_name, COUNT(" . $sql_table['user'] . ".user_id) AS ugroup_user FROM " . $sql_table['usergroup'] . ", " . $sql_table['user'] . " WHERE " . $sql_table['user'] . ".ugroup_id = " . $sql_table['usergroup'] . ".ugroup_id AND " . $sql_table['usergroup'] . ".ugroup_id != '3' GROUP BY " . $sql_table['user'] . ".ugroup_id");
while ($ugroup_row = $db_handler->sql_fetch_array($ugroup_res)) {
    $alt = alt_switch();
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($ugroup_row['ugroup_name'] ?? ''); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo (int)($ugroup_row['ugroup_user'] ?? 0); ?>
          </td>
        </tr>
<?php } ?>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="2">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="3" align="center">
            <b>Top 10 Kommentare Poster</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>#</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Nick</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Kommentare</b>
          </td>
        </tr>
<?php
$user_res = $db_handler->sql_query("SELECT " . $sql_table['user'] . ".user_id, COUNT(" . $sql_table['comments'] . ".comment_id) AS kommentare FROM " . $sql_table['user'] . ", " . $sql_table['comments'] . " WHERE " . $sql_table['user'] . ".user_id = " . $sql_table['comments'] . ".user_id GROUP BY " . $sql_table['user'] . ".user_id ORDER BY kommentare DESC LIMIT 0,10");
$count = 0;
while ($user_row = $db_handler->sql_fetch_array($user_res)) {
    $count++;
    $alt = alt_switch();
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $count; ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo user((int)($user_row['user_id'] ?? 0)); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo (int)($user_row['kommentare'] ?? 0); ?>
          </td>
        </tr>
<?php } ?>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="3" align="center">
            <b>Top 10 Uploader</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>#</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Nick</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Uploads</b>
          </td>
        </tr>
<?php
$user_res = $db_handler->sql_query("SELECT " . $sql_table['user'] . ".user_id, COUNT(" . $sql_table['release'] . ".release_id) AS releases FROM " . $sql_table['user'] . ", " . $sql_table['release'] . " WHERE " . $sql_table['user'] . ".user_id = " . $sql_table['release'] . ".uploader GROUP BY " . $sql_table['user'] . ".user_id ORDER BY releases DESC LIMIT 0,10");
$count = 0;
while ($user_row = $db_handler->sql_fetch_array($user_res)) {
    $count++;
    $alt = alt_switch();
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $count; ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo user((int)($user_row['user_id'] ?? 0)); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo (int)($user_row['releases'] ?? 0); ?>
          </td>
        </tr>
<?php } ?>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="3" align="center">
            <b>Top 10 Ordner</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>#</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Name</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Release</b>
          </td>
        </tr>
<?php
$ordner_res = $db_handler->sql_query("SELECT " . $sql_table['ordner'] . ".ordner_id, " . $sql_table['ordner'] . ".name, COUNT(" . $sql_table['release'] . ".release_id) AS releases FROM " . $sql_table['ordner'] . ", " . $sql_table['release'] . " WHERE " . $sql_table['ordner'] . ".ordner_id = " . $sql_table['release'] . ".ordner_id GROUP BY " . $sql_table['release'] . ".ordner_id ORDER BY releases DESC LIMIT 0,10");
$count = 0;
while ($ordner_row = $db_handler->sql_fetch_array($ordner_res)) {
    $count++;
    $alt = alt_switch();
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $count; ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="<?php echo $script_file; ?>ordner_id=<?php echo (int)($ordner_row['ordner_id'] ?? 0); ?>"><?php echo htmlspecialchars(stripslashes($ordner_row['name'] ?? '')); ?></a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo (int)($ordner_row['releases'] ?? 0); ?>
          </td>
        </tr>
<?php } ?>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
    </td>
    <td width="50%" valign="top">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="3" align="center">
            <b>Top 10 Release nach Groesse</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>#</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Release</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Groesse</b>
          </td>
        </tr>
<?php
$release_res = $db_handler->sql_query("SELECT " . $sql_table['release'] . ".release_id, " . $sql_table['release'] . ".name, SUM(" . $sql_table['files'] . ".size) AS size FROM " . $sql_table['release'] . ", " . $sql_table['files'] . " WHERE " . $sql_table['release'] . ".release_id = " . $sql_table['files'] . ".release_id GROUP BY " . $sql_table['release'] . ".release_id ORDER BY size DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $alt = alt_switch();
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $count; ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="<?php echo $script_file; ?>release_id=<?php echo (int)($release_row['release_id'] ?? 0); ?>"><?php echo htmlspecialchars(stripslashes($release_row['name'] ?? '')); ?></a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo size((int)($release_row['size'] ?? 0)); ?>
          </td>
        </tr>
<?php } ?>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="3" align="center">
            <b>Top 10 Release nach Files</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>#</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Release</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Files</b>
          </td>
        </tr>
<?php
$release_res = $db_handler->sql_query("SELECT " . $sql_table['release'] . ".release_id, " . $sql_table['release'] . ".name, COUNT(" . $sql_table['files'] . ".file_id) AS files FROM " . $sql_table['release'] . ", " . $sql_table['files'] . " WHERE " . $sql_table['release'] . ".release_id = " . $sql_table['files'] . ".release_id GROUP BY " . $sql_table['release'] . ".release_id ORDER BY files DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $alt = alt_switch();
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $count; ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="<?php echo $script_file; ?>release_id=<?php echo (int)($release_row['release_id'] ?? 0); ?>"><?php echo htmlspecialchars(stripslashes($release_row['name'] ?? '')); ?></a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo (int)($release_row['files'] ?? 0); ?>
          </td>
        </tr>
<?php } ?>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="3" align="center">
            <b>Top 10 Release nach Kommentaren</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>#</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Release</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Kommentare</b>
          </td>
        </tr>
<?php
$release_res = $db_handler->sql_query("SELECT " . $sql_table['release'] . ".release_id, " . $sql_table['release'] . ".name, COUNT(" . $sql_table['comments'] . ".comment_id) AS comments FROM " . $sql_table['release'] . ", " . $sql_table['comments'] . " WHERE " . $sql_table['release'] . ".release_id = " . $sql_table['comments'] . ".release_id GROUP BY " . $sql_table['release'] . ".release_id ORDER BY comments DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $alt = alt_switch();
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $count; ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="<?php echo $script_file; ?>release_id=<?php echo (int)($release_row['release_id'] ?? 0); ?>"><?php echo htmlspecialchars(stripslashes($release_row['name'] ?? '')); ?></a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo (int)($release_row['comments'] ?? 0); ?>
          </td>
        </tr>
<?php } ?>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="3" align="center">
            <b>Top 10 Release nach Bewertungen</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>#</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Release</b>
          </td>
          <td bgcolor="<?php echo $footer_bg; ?>">
            <b>Bewertungen</b>
          </td>
        </tr>
<?php
$release_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " ORDER BY votes DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $alt = alt_switch();
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo $count; ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="<?php echo $script_file; ?>release_id=<?php echo (int)($release_row['release_id'] ?? 0); ?>"><?php echo htmlspecialchars(stripslashes($release_row['name'] ?? '')); ?></a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo (int)($release_row['votes'] ?? 0); ?>
          </td>
        </tr>
<?php } ?>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
    </td>
  </tr>
</table>
