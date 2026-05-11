<?php
include("header.inc.php");

// Extract POST/GET variables
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$name = isset($_POST['name']) ? $db_handler->sql_escape_string($_POST['name']) : '';
$rights = isset($_POST['rights']) ? $_POST['rights'] : array();

if($user_rights['edituser'] == "Y" && $user_rights['deluser'] == "Y")
 {
  if($submit == 1)
   {
    $into = '';
    $values = '';
    for($i = 0;$i < count($rights);$i++)
     {
      $into .= ", " . $db_handler->sql_escape_string($rights[$i]['variablenname']);
      $values .= ", '" . $db_handler->sql_escape_string($rights[$i]['wert']) . "'";
     }
    $db_handler->sql_query("INSERT INTO `" . $sql_table['usergroup'] . "` (name$into) VALUES ('$name'$values)");
    echo pdl_admin_alert('success', '<strong>Usergruppe wurde eingetragen.</strong>');
    echo '<a class="btn btn-outline-light" href="addugroup.php">Weitere Usergruppe hinzufügen</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'User'],
        ['title' => 'Usergruppe hinzufügen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Usergruppe hinzufügen</h1>';
?>
<form action="addugroup.php?submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Allgemein</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlUGroupName" class="form-label">Name</label>
                <input type="text" id="pdlUGroupName" name="name" class="form-control" required>
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
    $rights_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['rights'] . "` ORDER BY reihenfolge ASC");
    if ($db_handler->sql_num_rows($rights_res) === 0) {
        echo pdl_admin_alert('warning', '<strong>Keine Rechte definiert.</strong> Die Tabelle <code>' . htmlspecialchars($sql_table['rights']) . '</code> ist leer. Bitte das Setup-Skript erneut ausfuehren oder die Migration <code>docs/superpowers/plans/2026-05-10-seed-rights.sql</code> einspielen.');
    }
    while($rights_row = $db_handler->sql_fetch_array($rights_res))
     {
      $rights_count++;
      $right_id = 'pdlRight_' . $rights_count;
      $vname = (string)$rights_row['variablenname'];
      $is_dependent = in_array($vname, $admin_dependent, true);
      $is_adminaccess = ($vname === 'adminaccess');
      $extra_attr = $is_dependent ? ' data-requires-adminaccess="1"' : '';
      $admin_attr = $is_adminaccess ? ' data-role="adminaccess"' : '';
      echo '<fieldset class="mb-3 border rounded p-2"><legend class="float-none w-auto px-2 fs-6 fw-bold">'
          . htmlspecialchars($rights_row['name']) . '</legend>';
      echo '<p class="form-text mb-2">' . htmlspecialchars($rights_row['bez']) . '</p>';
      echo '<input type="hidden" name="rights[' . $rights_count . '][variablenname]" value="'
          . htmlspecialchars($vname) . '">';
      echo '<div class="form-check form-check-inline">';
      echo '<input class="form-check-input" type="radio" name="rights[' . $rights_count . '][wert]" value="N" id="' . $right_id . '_n" checked' . $extra_attr . $admin_attr . '>';
      echo '<label class="form-check-label" for="' . $right_id . '_n">Nein</label>';
      echo '</div>';
      echo '<div class="form-check form-check-inline">';
      echo '<input class="form-check-input" type="radio" name="rights[' . $rights_count . '][wert]" value="Y" id="' . $right_id . '_y"' . $extra_attr . $admin_attr . '>';
      echo '<label class="form-check-label" for="' . $right_id . '_y">Ja</label>';
      echo '</div>';
      echo '</fieldset>';
     }
?>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdelugroup.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Usergruppe hinzufügen</button>
    </div>
</form>
<script>
(function () {
    var form = document.querySelector('form[action="addugroup.php?submit=1"]');
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
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
