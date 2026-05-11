<?php
include("header.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$rights = isset($_POST['rights']) ? $_POST['rights'] : array();

$protected = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16);
if($user_rights['adminaccess'] == "Y")
 {
  if($submit == 1)
   {
    for($i = 0; $i < count($rights); $i++)
     {
      $db_handler->sql_query("UPDATE ".$sql_table['rights']." SET name='".$db_handler->sql_escape_string($rights[$i]['name'])."', bez='".$db_handler->sql_escape_string($rights[$i]['bez'])."', reihenfolge='".$db_handler->sql_escape_int($rights[$i]['reihenfolge'])."' WHERE right_id='".$db_handler->sql_escape_int($rights[$i]['right_id'])."'");
      if(($rights[$i]['delete'] ?? 0) == 1)
       {
        $dodelete = true;
        foreach($protected as $prot_id)
         {
          if($prot_id == $rights[$i]['right_id'])
           { $dodelete = false; break; }
         }
        if($dodelete == true)
         {
          $db_handler->sql_query("ALTER TABLE ".$sql_table['usergroup']." DROP ".$db_handler->sql_escape_string($rights[$i]['variablenname']));
          $db_handler->sql_query("DELETE FROM ".$sql_table['rights']." WHERE right_id='".$db_handler->sql_escape_int($rights[$i]['right_id'])."'");
         }
       }
     }
    echo pdl_admin_alert('success', '<strong>Rechte wurden geändert.</strong>');
   }

    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System-Erweiterungen'],
        ['title' => 'Userrechte ändern/löschen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Userrechte ändern/löschen</h1>';
?>
<form action="editdeluright.php?submit=1" method="post" novalidate>
<?php
  $rights_count = -1;
  $rights_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['rights']." ORDER BY reihenfolge ASC");
  while($rights_row = $db_handler->sql_fetch_array($rights_res))
   {
    $rights_count++;
    $is_protected = false;
    foreach($protected as $prot_id) {
        if($prot_id == $rights_row['right_id']) { $is_protected = true; break; }
    }
?>
    <section class="card pdl-card mb-4 <?php echo $is_protected ? '' : 'pdl-danger-action'; ?>">
        <header class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0"><?php echo htmlspecialchars($rights_row['name']); ?></h2>
            <span class="small text-muted">#<?php echo (int)$rights_row['right_id']; ?></span>
        </header>
        <div class="card-body">
            <input type="hidden" name="rights[<?php echo $rights_count; ?>][right_id]" value="<?php echo (int)$rights_row['right_id']; ?>">
            <input type="hidden" name="rights[<?php echo $rights_count; ?>][variablenname]" value="<?php echo htmlspecialchars($rights_row['variablenname']); ?>">

            <div class="row g-3">
                <div class="col-12 col-md-2">
                    <label class="form-label">Reihenfolge</label>
                    <input type="number" name="rights[<?php echo $rights_count; ?>][reihenfolge]" class="form-control" value="<?php echo htmlspecialchars($rights_row['reihenfolge']); ?>">
                </div>
                <div class="col-12 col-md-10">
                    <label class="form-label">Name</label>
                    <input type="text" name="rights[<?php echo $rights_count; ?>][name]" class="form-control" value="<?php echo htmlspecialchars($rights_row['name']); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="rights[<?php echo $rights_count; ?>][bez]" class="form-control" rows="3"><?php echo htmlspecialchars($rights_row['bez']); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Variablenname</label>
                    <p class="form-control-plaintext"><code><?php echo htmlspecialchars($rights_row['variablenname']); ?></code> <small class="text-muted">(kann nicht geändert werden)</small></p>
                </div>
                <?php if (!$is_protected) { ?>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rights[<?php echo $rights_count; ?>][delete]" value="1" id="pdlDelR_<?php echo $rights_count; ?>">
                        <label class="form-check-label fw-bold text-danger" for="pdlDelR_<?php echo $rights_count; ?>">Recht löschen</label>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
<?php
   } ?>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Userrechte ändern</button>
    </div>
</form>
<?php
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
