<?php
include("header.inc.php");

// Extract variables from GET/POST for PHP 8.4 compatibility
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0);
$nick = isset($_POST['nick']) ? $_POST['nick'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$homepage = isset($_POST['homepage']) ? $_POST['homepage'] : '';
$get_letter = isset($_POST['get_letter']) ? $_POST['get_letter'] : '';
$nugroup_id = isset($_POST['nugroup_id']) ? (int)$_POST['nugroup_id'] : 0;
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;

if($user_rights['edituser'] == "Y")
 {
  if($submit == 1)
   {
    $safe_user_id = $db_handler->sql_escape_int($user_id);
    if((int)$user_id === 1) {
        echo pdl_admin_alert('danger', 'Der Hauptadministrator (user_id 1) ist schreibgeschützt und kann nicht über dieses Formular geändert werden.');
    } else {
      if(!preg_match("!http:\/\/!",$homepage)) $homepage = "http://$homepage";
      if($get_letter != "Y") $get_letter = "N";
      $safe_nick = $db_handler->sql_escape_string($nick);
      $safe_email = $db_handler->sql_escape_string($email);
      $safe_homepage = $db_handler->sql_escape_string($homepage);
      $safe_get_letter = $db_handler->sql_escape_string($get_letter);
      $safe_nugroup_id = $db_handler->sql_escape_int($nugroup_id);
      $db_handler->sql_query("UPDATE ".$sql_table['user']." SET nick='".$safe_nick."', email='".$safe_email."', homepage='".$safe_homepage."', get_letter='".$safe_get_letter."', ugroup_id='".$safe_nugroup_id."' WHERE user_id='".$safe_user_id."'");
      echo pdl_admin_alert('success', '<strong>User wurde aktualisiert.</strong>');
    }
    echo '<a class="btn btn-outline-light" href="edituser.php">Zurück zur User-Liste</a>';
   }
  elseif($user_id)
   {
    $safe_user_id = $db_handler->sql_escape_int($user_id);
    $getuser = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM ".$sql_table['user']." WHERE user_id='".$safe_user_id."'"));
    if((int)$user_id === 1) {
        echo pdl_admin_alert('warning', 'Der Hauptadministrator (user_id 1) ist schreibgeschützt und kann nicht über dieses Formular geändert werden.');
    } else {
        pdl_admin_breadcrumb([
            ['title' => 'Admin-Center', 'href' => 'index.php'],
            ['title' => 'User', 'href' => 'edituser.php'],
            ['title' => 'User editieren'],
        ]);
        echo '<h1 class="h3 pdl-page-title">User editieren</h1>';
?>
<form action="edituser.php?submit=1" method="post" novalidate>
    <input type="hidden" name="user_id" value="<?php echo (int)$user_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">User-Daten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlEUNick" class="form-label">Nickname</label>
                <input type="text" id="pdlEUNick" name="nick" class="form-control" required value="<?php echo htmlspecialchars($getuser['nick']); ?>">
                <div class="form-text">Hier können Sie den Nickname des Users ändern.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEUEmail" class="form-label">E-Mail-Adresse</label>
                <input type="email" id="pdlEUEmail" name="email" class="form-control" value="<?php echo htmlspecialchars($getuser['email']); ?>">
                <div class="form-text">Hier können Sie die E-Mail-Adresse des Users einsehen bzw. ändern.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEUHomepage" class="form-label">Homepage</label>
                <input type="url" id="pdlEUHomepage" name="homepage" class="form-control" value="<?php echo htmlspecialchars($getuser['homepage']); ?>">
                <div class="form-text">Hier können Sie die Homepage des Users einsehen bzw. ändern.</div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="pdlEUGetLetter" name="get_letter" value="Y"<?php if($getuser['get_letter'] == "Y") echo ' checked'; ?>>
                <label class="form-check-label" for="pdlEUGetLetter">User soll den Newsletter erhalten</label>
                <div class="form-text">Wenn Sie unbedingt möchten, dass der User den Newsletter erhält, können Sie das hier setzen.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEUUGroup" class="form-label">Usergruppe</label>
                <select id="pdlEUUGroup" name="nugroup_id" class="form-select">
                    <?php
                    $ugroups_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['usergroup']." ORDER BY name ASC");
                    while($ugroups_row = $db_handler->sql_fetch_array($ugroups_res))
                     {
                      echo '<option value="'.htmlspecialchars($ugroups_row['ugroup_id']).'"'.pdlif($ugroups_row['ugroup_id'] == $getuser['ugroup_id'],' selected','').'>'.htmlspecialchars($ugroups_row['name']).'</option>';
                     }
                    ?>
                </select>
                <div class="form-text"><strong>Hinweis:</strong> Der Hauptadministrator (user_id 1) ist schreibgeschützt und kann nicht über dieses Formular bearbeitet werden. Sei vorsichtig, wenn du andere User in die Gruppe „Administrator" verschiebst – sie erhalten dann vollen Zugriff.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="edituser.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Änderungen speichern</button>
    </div>
</form>
<?php
    }
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'User'],
        ['title' => 'User editieren'],
    ]);
    echo '<h1 class="h3 pdl-page-title">User editieren</h1>';

    if(!$page) $page = 1;
    $temp1=$page * 25 - 25;
    $limit=$temp1.",25";
    $safe_user_details_id = $db_handler->sql_escape_int($user_details['user_id']);
    $count_query = "SELECT ".$sql_table['user'].".nick, ".$sql_table['user'].".user_id, ".$sql_table['usergroup'].".name AS ugroup_name FROM ".$sql_table['user'].",".$sql_table['usergroup']." WHERE ".$sql_table['usergroup'].".ugroup_id=".$sql_table['user'].".ugroup_id AND ".$sql_table['usergroup'].".ugroup_id!='1' AND ".$sql_table['user'].".user_id!='".$safe_user_details_id."'";
    $total = $db_handler->sql_num_rows($db_handler->sql_query($count_query));
    $user_res = $db_handler->sql_query($count_query . " ORDER BY ".$sql_table['user'].".nick ASC LIMIT $limit");
?>
<section class="card pdl-card">
    <header class="card-header"><h2 class="h5 mb-0">User auswählen</h2></header>
    <?php if ($db_handler->sql_num_rows($user_res) > 0) { ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr><th scope="col">Nick</th><th scope="col">Usergruppe</th><th scope="col" class="text-end">Aktion</th></tr>
            </thead>
            <tbody>
            <?php while($user_row = $db_handler->sql_fetch_array($user_res)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($user_row['nick']); ?></td>
                    <td><?php echo htmlspecialchars($user_row['ugroup_name']); ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-light" href="edituser.php?user_id=<?php echo (int)$user_row['user_id']; ?>">editieren</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } else { ?>
    <div class="card-body"><p class="text-muted mb-0">Es sind keine editierbaren User vorhanden.</p></div>
    <?php } ?>
    <?php if ($total > 25) { ?>
    <div class="card-footer text-center">
        <?php echo seiten($total, 25, "", "edituser.php?"); ?>
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
