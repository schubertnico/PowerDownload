<?php
include("header.inc.php");

$prot_templates = array("all_width", "header_bg", "table_border", "alt_1", "alt_2",
"footer_bg", "ordner_row", "ordner_box", "dfiles_row", "file_detail", "release_row",
"own_footer", "ulogin_form", "ulost_form", "stats", "top_row", "top_box","flop_row",
"flop_box", "latest_row", "latest_box", "rated_row", "mail_lost1", "mail_lost2",
"uprofil_form", "uregister_form", "mail_register", "comments", "comments_form");
$prot_tgroups = array(1,2,3,4,5,6,7,8,9,10);

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : (isset($_POST['submit']) ? (int)$_POST['submit'] : 0);
$tgroup = isset($_POST['tgroup']) ? $_POST['tgroup'] : array();
$templates_post = isset($_POST['templates']) ? $_POST['templates'] : array();

if($user_rights['templates'] == "Y")
 {
  if($submit == 1)
   {
    for($i = 0; $i < count($tgroup); $i++)
     {
      $tgroup_id = $db_handler->sql_escape_int($tgroup[$i]['tgroup_id']);
      $reihenfolge = $db_handler->sql_escape_string($tgroup[$i]['reihenfolge']);
      $name = $db_handler->sql_escape_string($tgroup[$i]['name']);
      $db_handler->sql_query("UPDATE ".$sql_table['templategroup']." SET reihenfolge='".$reihenfolge."', name='".$name."' WHERE tgroup_id='".$tgroup_id."'");
      if(isset($tgroup[$i]['delete']) && $tgroup[$i]['delete'] == "Y") $db_handler->sql_query("DELETE FROM ".$sql_table['templategroup']." WHERE tgroup_id='".$tgroup_id."'");
     }
    for($i = 0; $i < count($templates_post); $i++)
     {
      $template_id = $db_handler->sql_escape_int($templates_post[$i]['template_id']);
      $reihenfolge = $db_handler->sql_escape_string($templates_post[$i]['reihenfolge']);
      $name = $db_handler->sql_escape_string($templates_post[$i]['name']);
      $bez = $db_handler->sql_escape_string($templates_post[$i]['bez']);
      $eingabe = $db_handler->sql_escape_string($templates_post[$i]['eingabe']);
      $variablenname = $db_handler->sql_escape_string($templates_post[$i]['variablenname']);
      $tgroup_id = $db_handler->sql_escape_int($templates_post[$i]['tgroup_id']);
      $db_handler->sql_query("UPDATE ".$sql_table['template']." SET reihenfolge='".$reihenfolge."', name='".$name."', bez='".$bez."', eingabe='".$eingabe."', variablenname='".$variablenname."', tgroup_id='".$tgroup_id."' WHERE template_id='".$template_id."'");
      if(isset($templates_post[$i]['delete']) && $templates_post[$i]['delete'] == "Y") $db_handler->sql_query("DELETE FROM ".$sql_table['template']." WHERE template_id='".$template_id."'");
     }
    echo pdl_admin_alert('success', '<strong>Templates/Gruppen geändert/gelöscht.</strong>');
   }

  pdl_admin_breadcrumb([
      ['title' => 'Admin-Center', 'href' => 'index.php'],
      ['title' => 'System-Erweiterungen'],
      ['title' => 'Templates/Gruppen ändern/löschen'],
  ]);
  echo '<h1 class="h3 pdl-page-title">Templates/Gruppen ändern/löschen</h1>';
?>
<form action="editdeltemplatestgroup.php?submit=1" method="post" novalidate>
<?php
  $tgroup_count = -1;
  $templates_count = -1;
  $tgroup_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['templategroup']." ORDER BY reihenfolge ASC");
  while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res))
   {
    $tgroup_count++;
    $is_protected_tg = false;
    foreach($prot_tgroups as $prot_tgroup) {
        if($prot_tgroup == $tgroup_row['tgroup_id']) { $is_protected_tg = true; break; }
    }
?>
    <section class="card pdl-card mb-4">
        <header class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Template-Gruppe</h2>
            <span class="badge text-bg-secondary">#<?php echo (int)$tgroup_row['tgroup_id']; ?></span>
        </header>
        <div class="card-body">
            <input type="hidden" name="tgroup[<?php echo $tgroup_count; ?>][tgroup_id]" value="<?php echo (int)$tgroup_row['tgroup_id']; ?>">
            <div class="row g-3">
                <div class="col-12 col-md-2">
                    <label class="form-label">Reihenfolge</label>
                    <input type="number" name="tgroup[<?php echo $tgroup_count; ?>][reihenfolge]" class="form-control" value="<?php echo htmlspecialchars($tgroup_row['reihenfolge']); ?>">
                </div>
                <div class="col-12 col-md-10">
                    <label class="form-label">Name</label>
                    <input type="text" name="tgroup[<?php echo $tgroup_count; ?>][name]" class="form-control" value="<?php echo htmlspecialchars($tgroup_row['name']); ?>">
                </div>
                <?php if (!$is_protected_tg) { ?>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tgroup[<?php echo $tgroup_count; ?>][delete]" value="Y" id="tgrp_del_<?php echo $tgroup_count; ?>">
                        <label class="form-check-label fw-bold text-danger" for="tgrp_del_<?php echo $tgroup_count; ?>">Template-Gruppe löschen</label>
                    </div>
                </div>
                <?php } ?>
            </div>

            <h3 class="h6 mt-4">Templates dieser Gruppe</h3>
<?php
    $templates_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['template']." WHERE tgroup_id='".$db_handler->sql_escape_int($tgroup_row['tgroup_id'])."' ORDER BY reihenfolge ASC");
    while($templates_row = $db_handler->sql_fetch_array($templates_res))
     {
      $templates_count++;
      $is_protected_t = false;
      foreach($prot_templates as $prot_template) {
        if($prot_template == $templates_row['variablenname']) { $is_protected_t = true; break; }
      }
?>
            <div class="card pdl-card mb-3 <?php echo $is_protected_t ? '' : 'pdl-danger-action'; ?>">
                <div class="card-body">
                    <input type="hidden" name="templates[<?php echo $templates_count; ?>][template_id]" value="<?php echo (int)$templates_row['template_id']; ?>">
                    <div class="row g-3">
                        <div class="col-12 col-md-2">
                            <label class="form-label">Reihenfolge</label>
                            <input type="number" name="templates[<?php echo $templates_count; ?>][reihenfolge]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($templates_row['reihenfolge']); ?>">
                        </div>
                        <div class="col-12 col-md-10">
                            <label class="form-label">Name</label>
                            <input type="text" name="templates[<?php echo $templates_count; ?>][name]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($templates_row['name']); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Beschreibung</label>
                            <textarea name="templates[<?php echo $templates_count; ?>][bez]" class="form-control form-control-sm" rows="3"><?php echo htmlspecialchars($templates_row['bez']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Eingabeart</label>
                            <select name="templates[<?php echo $templates_count; ?>][eingabe]" class="form-select form-select-sm">
                                <option value="textarea">Textarea</option>
                                <option value="input"<?php echo pdlif($templates_row['eingabe'] == "input"," selected",""); ?>>Input</option>
                                <option value="farbe"<?php echo pdlif($templates_row['eingabe'] == "farbe"," selected",""); ?>>Farbauswahl</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Variablenname</label>
                            <?php if (!$is_protected_t) { ?>
                                <input type="text" name="templates[<?php echo $templates_count; ?>][variablenname]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($templates_row['variablenname']); ?>">
                            <?php } else { ?>
                                <input type="hidden" name="templates[<?php echo $templates_count; ?>][variablenname]" value="<?php echo htmlspecialchars($templates_row['variablenname']); ?>">
                                <p class="form-control-plaintext"><code><?php echo htmlspecialchars($templates_row['variablenname']); ?></code> <small class="text-muted">(geschuetzt)</small></p>
                            <?php } ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Templategruppe</label>
                            <select name="templates[<?php echo $templates_count; ?>][tgroup_id]" class="form-select form-select-sm">
                            <?php
                            $tgroup2_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['templategroup']." ORDER BY reihenfolge ASC");
                            while($tgroup2_row = $db_handler->sql_fetch_array($tgroup2_res)) {
                                echo '<option value="'.htmlspecialchars($tgroup2_row['tgroup_id']).'"'.pdlif($templates_row['tgroup_id'] == $tgroup2_row['tgroup_id'],' selected','').'>'.htmlspecialchars($tgroup2_row['name']).'</option>';
                            }
                            ?>
                            </select>
                        </div>
                        <?php if (!$is_protected_t) { ?>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="templates[<?php echo $templates_count; ?>][delete]" value="Y" id="tpl_del_<?php echo $templates_count; ?>">
                                <label class="form-check-label fw-bold text-danger" for="tpl_del_<?php echo $templates_count; ?>">Template löschen</label>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
<?php
     }
?>
        </div>
    </section>
<?php
   }
?>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="templates.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Templates/Gruppen speichern</button>
    </div>
</form>
<?php
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
