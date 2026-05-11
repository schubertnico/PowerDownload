<?php
include("header.inc.php");

$prot_settings = array("referer_check", "allowed_referer", "enable_comments",
"enable_search", "enable_treeview", "enable_extrernadmin", "date_format", "spages",
"perpage", "orderby", "orderseq", "dlspeed", "trenn_durch", "trenn_zeichen",
"trenn_string", "bb_code", "smilies", "badwords_comments", "badwords_releases",
"glossary", "html_releases", "html_comments", "mail_fromname", "mail_fromaddr",
"screen_autosize", "screen_size", "screen_verhalt", "ftp_on", "script_file",
"ftp_server", "ftp_user", "ftp_passwort", "ftp_server_url", "top_count");
$prot_sgroups = array(1,2,3,4,5,6,7,8,9);

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : (isset($_POST['submit']) ? (int)$_POST['submit'] : 0);
$sgroup = isset($_POST['sgroup']) ? $_POST['sgroup'] : array();
$setting = isset($_POST['setting']) ? $_POST['setting'] : array();

if($user_rights['settings'] == "Y")
 {
  if($submit == 1)
   {
    for($i = 0; $i < count($sgroup); $i++)
     {
      $sgroup_id = $db_handler->sql_escape_int($sgroup[$i]['sgroup_id']);
      $reihenfolge = $db_handler->sql_escape_string($sgroup[$i]['reihenfolge']);
      $name = $db_handler->sql_escape_string($sgroup[$i]['name']);
      $db_handler->sql_query("UPDATE ".$sql_table['settingsgroup']." SET reihenfolge='".$reihenfolge."', name='".$name."' WHERE sgroup_id='".$sgroup_id."'");
      if(isset($sgroup[$i]['delete']) && $sgroup[$i]['delete'] == "Y") $db_handler->sql_query("DELETE FROM ".$sql_table['settingsgroup']." WHERE sgroup_id='".$sgroup_id."'");
     }
    for($i = 0; $i < count($setting); $i++)
     {
      $settings_id = $db_handler->sql_escape_int($setting[$i]['settings_id']);
      $reihenfolge = $db_handler->sql_escape_string($setting[$i]['reihenfolge']);
      $name = $db_handler->sql_escape_string($setting[$i]['name']);
      $bez = $db_handler->sql_escape_string($setting[$i]['bez']);
      $eingabe = $db_handler->sql_escape_string($setting[$i]['eingabe']);
      $variablenname = $db_handler->sql_escape_string($setting[$i]['variablenname']);
      $sgroup_id = $db_handler->sql_escape_int($setting[$i]['sgroup_id']);
      $db_handler->sql_query("UPDATE ".$sql_table['settings']." SET reihenfolge='".$reihenfolge."', name='".$name."', bez='".$bez."', eingabe='".$eingabe."', variablenname='".$variablenname."', sgroup_id='".$sgroup_id."' WHERE settings_id='".$settings_id."'");
      if(isset($setting[$i]['delete']) && $setting[$i]['delete'] == "Y") $db_handler->sql_query("DELETE FROM ".$sql_table['settings']." WHERE settings_id='".$settings_id."'");
     }
    echo pdl_admin_alert('success', '<strong>Settings/Gruppen geändert/gelöscht.</strong>');
   }

  pdl_admin_breadcrumb([
      ['title' => 'Admin-Center', 'href' => 'index.php'],
      ['title' => 'System-Erweiterungen'],
      ['title' => 'Settings/Gruppen ändern/löschen'],
  ]);
  echo '<h1 class="h3 pdl-page-title">Settings/Gruppen ändern/löschen</h1>';
?>
<form action="editdelsettingssgroup.php?submit=1" method="post" novalidate>
<?php
  $sgroup_count = -1;
  $setting_count = -1;
  $sgroup_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['settingsgroup']." ORDER BY reihenfolge ASC");
  while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
   {
    $sgroup_count++;
    $is_protected_sg = false;
    foreach($prot_sgroups as $prot_sgroup) {
        if($prot_sgroup == $sgroup_row['sgroup_id']) { $is_protected_sg = true; break; }
    }
?>
    <section class="card pdl-card mb-4">
        <header class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Settings-Gruppe</h2>
            <span class="badge text-bg-secondary">#<?php echo (int)$sgroup_row['sgroup_id']; ?></span>
        </header>
        <div class="card-body">
            <input type="hidden" name="sgroup[<?php echo $sgroup_count; ?>][sgroup_id]" value="<?php echo (int)$sgroup_row['sgroup_id']; ?>">
            <div class="row g-3">
                <div class="col-12 col-md-2">
                    <label class="form-label">Reihenfolge</label>
                    <input type="number" name="sgroup[<?php echo $sgroup_count; ?>][reihenfolge]" class="form-control" value="<?php echo htmlspecialchars($sgroup_row['reihenfolge']); ?>">
                </div>
                <div class="col-12 col-md-10">
                    <label class="form-label">Name</label>
                    <input type="text" name="sgroup[<?php echo $sgroup_count; ?>][name]" class="form-control" value="<?php echo htmlspecialchars($sgroup_row['name']); ?>">
                </div>
                <?php if (!$is_protected_sg) { ?>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sgroup[<?php echo $sgroup_count; ?>][delete]" value="Y" id="sgrp_del_<?php echo $sgroup_count; ?>">
                        <label class="form-check-label fw-bold text-danger" for="sgrp_del_<?php echo $sgroup_count; ?>">Settings-Gruppe löschen</label>
                    </div>
                </div>
                <?php } ?>
            </div>

            <h3 class="h6 mt-4">Settings dieser Gruppe</h3>
<?php
    $settings_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['settings']." WHERE sgroup_id='".$db_handler->sql_escape_int($sgroup_row['sgroup_id'])."' ORDER BY reihenfolge ASC");
    while($settings_row = $db_handler->sql_fetch_array($settings_res))
     {
      $setting_count++;
      $is_protected_set = false;
      foreach($prot_settings as $prot_setting) {
        if($prot_setting == $settings_row['variablenname']) { $is_protected_set = true; break; }
      }
?>
            <div class="card pdl-card mb-3 <?php echo $is_protected_set ? '' : 'pdl-danger-action'; ?>">
                <div class="card-body">
                    <input type="hidden" name="setting[<?php echo $setting_count; ?>][settings_id]" value="<?php echo (int)$settings_row['settings_id']; ?>">
                    <div class="row g-3">
                        <div class="col-12 col-md-2">
                            <label class="form-label">Reihenfolge</label>
                            <input type="number" name="setting[<?php echo $setting_count; ?>][reihenfolge]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($settings_row['reihenfolge']); ?>">
                        </div>
                        <div class="col-12 col-md-10">
                            <label class="form-label">Name</label>
                            <input type="text" name="setting[<?php echo $setting_count; ?>][name]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($settings_row['name']); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Beschreibung</label>
                            <textarea name="setting[<?php echo $setting_count; ?>][bez]" class="form-control form-control-sm" rows="3"><?php echo htmlspecialchars($settings_row['bez']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Eingabeart</label>
                            <textarea name="setting[<?php echo $setting_count; ?>][eingabe]" class="form-control form-control-sm" rows="3"><?php echo htmlspecialchars($settings_row['eingabe']); ?></textarea>
                            <div class="form-text"><code>input</code>, <code>textarea</code>, <code>anaus</code> oder beliebiger Text.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Variablenname</label>
                            <?php if(!$is_protected_set) { ?>
                                <input type="text" name="setting[<?php echo $setting_count; ?>][variablenname]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($settings_row['variablenname']); ?>">
                            <?php } else { ?>
                                <input type="hidden" name="setting[<?php echo $setting_count; ?>][variablenname]" value="<?php echo htmlspecialchars($settings_row['variablenname']); ?>">
                                <p class="form-control-plaintext"><code><?php echo htmlspecialchars($settings_row['variablenname']); ?></code> <small class="text-muted">(geschuetzt)</small></p>
                            <?php } ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Settingsgruppe</label>
                            <select name="setting[<?php echo $setting_count; ?>][sgroup_id]" class="form-select form-select-sm">
                                <?php
                                $sgroup2_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['settingsgroup']." ORDER BY reihenfolge ASC");
                                while($sgroup2_row = $db_handler->sql_fetch_array($sgroup2_res)) {
                                    echo '<option value="'.htmlspecialchars($sgroup2_row['sgroup_id']).'"'.pdlif($settings_row['sgroup_id'] == $sgroup2_row['sgroup_id'],' selected','').'>'.htmlspecialchars($sgroup2_row['name']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <?php if (!$is_protected_set) { ?>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="setting[<?php echo $setting_count; ?>][delete]" value="Y" id="set_del_<?php echo $setting_count; ?>">
                                <label class="form-check-label fw-bold text-danger" for="set_del_<?php echo $setting_count; ?>">Setting löschen</label>
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
        <a href="settings.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Settings/Gruppen speichern</button>
    </div>
</form>
<?php
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
