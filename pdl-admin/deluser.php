<?php
include("header.inc.php");

// Extract POST/GET variables
$user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
$submit = isset($_REQUEST['submit']) ? (int)$_REQUEST['submit'] : 0;
$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 0;

if($user_rights['deluser'] == "Y")
 {
  if($submit == 1)
   {
    if((int)$user_id === 1) {
        echo pdl_admin_alert('danger', 'Der Hauptadministrator (user_id 1) ist schreibgeschützt und kann nicht gelöscht werden.');
    } else {
        $db_handler->sql_query("DELETE FROM " . $sql_table['user'] . " WHERE user_id=" . $db_handler->sql_escape_int($user_id));
        echo pdl_admin_alert('success', '<strong>User wurde gelöscht.</strong>');
    }
    echo '<a class="btn btn-outline-light" href="deluser.php">Zurück zur User-Liste</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'User'],
        ['title' => 'User löschen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">User löschen</h1>';

    if(!$page) $page = 1;
    $temp1=$page * 25 - 25;
    $limit=$temp1.",25";
    $count_query = "SELECT " . $sql_table['user'] . ".nick, " . $sql_table['user'] . ".user_id, " . $sql_table['usergroup'] . ".name AS ugroup_name FROM " . $sql_table['user'] . "," . $sql_table['usergroup'] . " WHERE " . $sql_table['usergroup'] . ".ugroup_id=" . $sql_table['user'] . ".ugroup_id AND " . $sql_table['usergroup'] . ".ugroup_id!='1' AND " . $sql_table['user'] . ".user_id!=" . $db_handler->sql_escape_int($user_details['user_id']);
    $total = $db_handler->sql_num_rows($db_handler->sql_query($count_query));
    $user_res = $db_handler->sql_query($count_query . " ORDER BY " . $sql_table['user'] . ".nick ASC LIMIT $limit");
?>
<section class="card pdl-card">
    <header class="card-header"><h2 class="h5 mb-0">Zu löschende User</h2></header>
    <?php if ($db_handler->sql_num_rows($user_res) > 0) { ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Nick</th>
                    <th scope="col">Usergruppe</th>
                    <th scope="col" class="text-end">Aktion</th>
                </tr>
            </thead>
            <tbody>
            <?php while($user_row = $db_handler->sql_fetch_array($user_res)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($user_row['nick']); ?></td>
                    <td><?php echo htmlspecialchars($user_row['ugroup_name']); ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-danger" href="deluser.php?submit=1&amp;user_id=<?php echo (int)$user_row['user_id']; ?>"
                            onclick="return confirm('User wirklich löschen?');">Löschen</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } else { ?>
    <div class="card-body"><p class="text-muted mb-0">Es sind keine loeschbaren User vorhanden.</p></div>
    <?php } ?>
    <?php if ($total > 25) { ?>
    <div class="card-footer text-center">
        <?php echo seiten($total, 25, "", "deluser.php?"); ?>
    </div>
    <?php } ?>
</section>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
