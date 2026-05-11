<?php
include("header.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$eugroup_id = isset($_POST['eugroup_id']) ? (int)$_POST['eugroup_id'] : (isset($_GET['eugroup_id']) ? (int)$_GET['eugroup_id'] : 0);
$name = isset($_POST['name']) ? $_POST['name'] : '';
$rights = isset($_POST['rights']) ? $_POST['rights'] : array();
$delete = isset($_POST['delete']) ? (int)$_POST['delete'] : 0;

$protected = array(1,2);
$protected_labels = array(1 => 'Gast', 2 => 'Administrator');
if($user_rights['edituser'] == "Y" && $user_rights['deluser'] == "Y")
 {
  if($submit == 1)
   {
    if(in_array((int)$eugroup_id, $protected, true)) {
        $label = $protected_labels[(int)$eugroup_id] ?? 'System';
        echo pdl_admin_alert('danger', 'Die Usergruppe „' . htmlspecialchars($label) . '" ist geschützt und kann nicht geändert oder gelöscht werden.');
    } else {
      $sets = "";
      for($i = 0;$i < count($rights);$i++)
       {
        $sets .= ", ".$db_handler->sql_escape_string($rights[$i]['variablenname'])."='".$db_handler->sql_escape_string($rights[$i]['wert'])."'";
       }
      $db_handler->sql_query("UPDATE ".$sql_table['usergroup']." SET name='".$db_handler->sql_escape_string($name)."'".$sets." WHERE ugroup_id='".$db_handler->sql_escape_int($eugroup_id)."'");
      echo pdl_admin_alert('success', '<strong>Usergruppe geändert.</strong>');
      if($delete == 1)
       {
        $dodelete = true;
        foreach($protected as $prot_id)
         {
          if($prot_id == $eugroup_id)
           { $dodelete = false; break; }
         }
        if($dodelete == true)
         {
          $db_handler->sql_query("DELETE FROM ".$sql_table['usergroup']." WHERE ugroup_id='".$db_handler->sql_escape_int($eugroup_id)."'");
          echo pdl_admin_alert('success', '<strong>Usergruppe wurde gelöscht.</strong>');
         }
       }
     }
     echo '<a class="btn btn-outline-light" href="editdelugroup.php">Zurück zur Auswahl</a>';
   }
  elseif($eugroup_id)
   {
    if(in_array((int)$eugroup_id, $protected, true)) {
        $label = $protected_labels[(int)$eugroup_id] ?? 'System';
        echo pdl_admin_alert('warning', 'Die Usergruppe „' . htmlspecialchars($label) . '" ist geschützt und kann nicht geändert oder gelöscht werden.');
    } else {
      $ugroup_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM ".$sql_table['usergroup']." WHERE ugroup_id='".$db_handler->sql_escape_int($eugroup_id)."'"));
      pdl_admin_breadcrumb([
          ['title' => 'Admin-Center', 'href' => 'index.php'],
          ['title' => 'User'],
          ['title' => 'Usergruppe ändern/löschen'],
      ]);
      echo '<h1 class="h3 pdl-page-title">Usergruppe ändern/löschen</h1>';
?>
<form action="editdelugroup.php?submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Allgemein</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlEUGName" class="form-label">Name</label>
                <input type="text" id="pdlEUGName" name="name" class="form-control" value="<?php echo htmlspecialchars($ugroup_row['name']); ?>">
                <input type="hidden" name="eugroup_id" value="<?php echo (int)$eugroup_id; ?>">
                <div class="form-text">Name der Usergruppe.</div>
            </div>
        </div>
    </section>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Rechte</h2></header>
        <div class="card-body">
<?php
      $rights_count = -1;
      $admin_dependent = ['addfiles','editfiles','delfiles','adddirs','editdirs','deldirs','adduser','edituser','deluser','settings','templates','replacements','backup','comment'];
      $rights_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['rights']." ORDER BY reihenfolge ASC");
      if ($db_handler->sql_num_rows($rights_res) === 0) {
          echo pdl_admin_alert('warning', '<strong>Keine Rechte definiert.</strong> Die Tabelle <code>' . htmlspecialchars($sql_table['rights']) . '</code> ist leer. Bitte das Setup-Skript erneut ausfuehren oder die Migration <code>docs/superpowers/plans/2026-05-10-seed-rights.sql</code> einspielen.');
      }
      while($rights_row = $db_handler->sql_fetch_array($rights_res))
       {
        $rights_count++;
        $right_id = 'pdlEDR_' . $rights_count;
        $vname = (string)$rights_row['variablenname'];
        $is_dependent = in_array($vname, $admin_dependent, true);
        $is_adminaccess = ($vname === 'adminaccess');
        $extra_attr = $is_dependent ? ' data-requires-adminaccess="1"' : '';
        $admin_attr = $is_adminaccess ? ' data-role="adminaccess"' : '';
        echo '<fieldset class="mb-3 border rounded p-2"><legend class="float-none w-auto px-2 fs-6 fw-bold">'
            . htmlspecialchars($rights_row['name']) . '</legend>';
        echo '<p class="form-text mb-2">' . htmlspecialchars($rights_row['bez']) . '</p>';
        echo '<input type="hidden" name="rights['.$rights_count.'][variablenname]" value="'.htmlspecialchars($vname).'">';
        $is_n = ($ugroup_row[$vname] == "N");
        echo '<div class="form-check form-check-inline">';
        echo '<input class="form-check-input" type="radio" name="rights['.$rights_count.'][wert]" value="N" id="'.$right_id.'_n"' . ($is_n ? ' checked' : '') . $extra_attr . $admin_attr . '>';
        echo '<label class="form-check-label" for="'.$right_id.'_n">Nein</label>';
        echo '</div>';
        echo '<div class="form-check form-check-inline">';
        echo '<input class="form-check-input" type="radio" name="rights['.$rights_count.'][wert]" value="Y" id="'.$right_id.'_y"' . ($is_n ? '' : ' checked') . $extra_attr . $admin_attr . '>';
        echo '<label class="form-check-label" for="'.$right_id.'_y">Ja</label>';
        echo '</div>';
        echo '</fieldset>';
       }
      $dodelete = true;
      $protected_reason = '';
      foreach($protected as $prot_id)
       {
        if($prot_id == $ugroup_row['ugroup_id'])
         {
          $dodelete = false;
          if ((int)$ugroup_row['ugroup_id'] === 2) {
              $protected_reason = 'Die Administrator-Gruppe kann nicht gelöscht werden';
          } elseif ((int)$ugroup_row['ugroup_id'] === 1) {
              $protected_reason = 'Die Gast-Gruppe kann nicht gelöscht werden';
          } else {
              $protected_reason = 'Diese Gruppe ist geschützt';
          }
          break;
         }
       }
?>
        </div>
    </section>
    <section class="card pdl-card mb-4 pdl-danger-action">
        <header class="card-header bg-danger text-white"><h2 class="h5 mb-0">Gefährliche Aktion</h2></header>
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="pdlDelUgroup" name="delete" value="1"<?php echo $dodelete ? '' : ' disabled'; ?><?php echo $dodelete ? '' : ' title="' . htmlspecialchars($protected_reason) . '"'; ?>>
                <label class="form-check-label fw-bold text-danger" for="pdlDelUgroup"<?php echo $dodelete ? '' : ' title="' . htmlspecialchars($protected_reason) . '"'; ?>>Usergruppe löschen</label>
                <?php if ($dodelete) { ?>
                    <div class="form-text">Soll die Usergruppe gelöscht werden? Aktion ist nicht rückgängig zu machen.</div>
                <?php } else { ?>
                    <div class="form-text text-muted"><?php echo htmlspecialchars($protected_reason); ?>.</div>
                <?php } ?>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdelugroup.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Usergruppe ändern</button>
    </div>
</form>
<script>
(function () {
    var form = document.querySelector('form[action="editdelugroup.php?submit=1"]');
    if (!form) return;
    function adminAccessIsYes() {
        var inputs = form.querySelectorAll('input[data-role="adminaccess"]');
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].checked && inputs[i].value === 'Y') return true;
        }
        return false;
    }
    var dependents = form.querySelectorAll('input[data-requires-adminaccess="1"][value="Y"]');
    dependents.forEach(function (inp) {
        inp.addEventListener('click', function () {
            if (this.checked && !adminAccessIsYes()) {
                alert("Dieses Recht benötigt 'Admin-Zugang' = Ja.");
                var name = this.getAttribute('name');
                var noBuddy = form.querySelector('input[name="' + name + '"][value="N"]');
                if (noBuddy) noBuddy.checked = true;
            }
        });
    });
})();
</script>
<?php
    }
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'User'],
        ['title' => 'Usergruppe ändern/löschen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Usergruppe auswählen</h1>';
?>
<section class="card pdl-card mx-auto" style="max-width: 540px;">
    <header class="card-header"><h2 class="h5 mb-0">Verfügbare Gruppen</h2></header>
    <div class="list-group list-group-flush">
<?php
    $ugroups_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['usergroup']." WHERE name!=''");
    $ug_count = 0;
    while($ugroups_row = $db_handler->sql_fetch_array($ugroups_res)) {
        $ug_count++;
        $row_ugroup_id = (int)$ugroups_row['ugroup_id'];
        $is_protected = in_array($row_ugroup_id, $protected, true);
        $badge = $is_protected ? ' <span class="badge text-bg-secondary ms-2">Geschützt</span>' : '';
        echo '<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="editdelugroup.php?eugroup_id='
            . $row_ugroup_id . '"><span>' . htmlspecialchars($ugroups_row['name']) . $badge . '</span></a>';
    }
    if ($ug_count == 0) {
        echo '<div class="list-group-item text-muted">Keine Usergruppen vorhanden.</div>';
    }
?>
    </div>
</section>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
