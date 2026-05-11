<?php
include("header.inc.php");
if($user_rights['adminaccess'] == "Y")
 {
  $submit = isset($_REQUEST['submit']) ? (int)$_REQUEST['submit'] : 0;
  $extra_addys = isset($_POST['extra_addys']) ? $_POST['extra_addys'] : '';
  $ugroup_ids = isset($_POST['ugroup_ids']) ? $_POST['ugroup_ids'] : array();
  $text = isset($_POST['text']) ? $_POST['text'] : '';
  $anfang = isset($_REQUEST['anfang']) ? $_REQUEST['anfang'] : '';

  if($submit == 1)
   {
    set_time_limit(300);
    $addys = array();
    if($extra_addys) $addys = explode(";", $extra_addys);

    if($ugroup_ids)
     {
      $where = '';
      for($i = 0; $i < count($ugroup_ids); $i++)
       {
        $where.= "ugroup_id='" . $db_handler->sql_escape_string($ugroup_ids[$i]) . "'";
        if($i < count($ugroup_ids)-1) $where.= " OR ";
       }
      $addys_res = $db_handler->sql_query("SELECT email FROM " . $sql_table['user'] . " WHERE ($where) AND get_letter='Y'");
      while($addys_row = $db_handler->sql_fetch_array($addys_res))
       {
        $addys[] = $addys_row['email'];
       }
     }

    $sleep = 0;
    for($i = 0; $i < count($addys); $i++)
     {
      $sleep++;
      if($sleep == 10) { sleep(1); $sleep = 0; }
      mail($addys[$i], "Newsletter", stripslashes($text), "FROM: " . $settings['mail_fromname'] . " <" . $settings['mail_fromaddr'] . ">");
     }
    echo pdl_admin_alert('success', '<strong>Newsletter wurde an ' . count($addys) . ' Adressen verschickt.</strong>');
    if(!$settings['lastletter']) $db_handler->sql_query("INSERT INTO " . $sql_table['settings'] . " (wert,variablenname) VALUES ('".time()."','lastletter')");
    else $db_handler->sql_query("UPDATE " . $sql_table['settings'] . " SET wert='".time()."' WHERE variablenname='lastletter'");
   }
  else
   {
    if(!$settings['lastletter']) $datum = $settings['installed'];
    else $datum = $settings['lastletter'];
    if($anfang)
     {
      $datum = explode(".",$anfang);
      $datum = mktime(0,0,0,(int)$datum[1],(int)$datum[0],(int)$datum[2]);
     }

    $dls_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE time>'" . $db_handler->sql_escape_string((string)$datum) . "' ORDER BY time ASC");

    $text = "Newsletter vom ".date("d.m.Y").".\n";
    $text.= "Seit dem ".date("d.m.Y",(int)$datum)." wurden ".$db_handler->sql_num_rows($dls_res)." Downloads hinzugefügt.\n\n";

    while($dls_row = $db_handler->sql_fetch_array($dls_res))
     {
      $text.= "-------------------------------------------\n";
      $text.= date("d.m.Y", $dls_row['time']).": ".$dls_row['name']."\n";
      $text.= "-------------------------------------------\n";
      if(strlen($dls_row['text']) > 250) $text .= substr($dls_row['text'],0,250)."...\n";
      else $text.= $dls_row['text']."\n";
      $text.= "-------------------------------------------\n";
      $text.= "Nähere Infos zu ".$dls_row['name']." und den Download finden sie unter\n".$settings['script_file']."release_id=".$dls_row['release_id']."\n";
      $text.= "-------------------------------------------\n\n";
     }
    $text.= "Dies ist ein automatisch generierter Newsletter. Sie erhalten ihn weil sie sich unter ".$settings['script_file']." angemeldet haben.\n";
    $text.= "Um den Newsletter nicht mehr zu erhalten loggen sie sich unter ".$settings['script_file']."usercenter=login ein und ändern sie ihr Profil anschließend unter der Adresse ".$settings['script_file']."usercenter=profil wichtig ist das sie den Punkt \"Newsletter erhalten\" deaktivieren.";

    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Newsletter'],
        ['title' => 'Newsletter generieren'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Newsletter verfassen</h1>';
?>
<script>
function loc(anfang) {
    document.location.href = 'makeletter.php?anfang='+anfang;
}
</script>
<form action="makeletter.php?submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Inhalt</h2></header>
        <div class="card-body">
            <label for="pdlLetterText" class="form-label">Newsletter-Text</label>
            <textarea id="pdlLetterText" name="text" class="form-control" rows="20"><?php echo htmlspecialchars($text); ?></textarea>
        </div>
    </section>

    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Empfaenger</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlLetterUgroups" class="form-label">Newsletter senden an Usergruppen</label>
                <select id="pdlLetterUgroups" name="ugroup_ids[]" class="form-select" size="4" multiple>
                    <?php
                    $ugroup_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['usergroup'] . " WHERE ugroup_id!='3'");
                    while($ugroup_row = $db_handler->sql_fetch_array($ugroup_res)) {
                        echo '<option value="' . htmlspecialchars($ugroup_row['ugroup_id']) . '">' . htmlspecialchars($ugroup_row['name']) . '</option>';
                    }
                    ?>
                </select>
                <div class="form-text">Hier können Sie den Newsletter nur bestimmten Usergruppen senden, um z.B. nur einen internen Newsletter zu verschicken.</div>
            </div>
            <div class="mb-3">
                <label for="pdlLetterExtra" class="form-label">Außerdem senden an</label>
                <input type="text" id="pdlLetterExtra" name="extra_addys" class="form-control" aria-describedby="pdlLetterExtraHelp">
                <div id="pdlLetterExtraHelp" class="form-text">Hier können Sie zusaetzliche Mailadressen eingeben. Mehrere Adressen mit <code>;</code> trennen.</div>
            </div>
            <div class="mb-3">
                <label for="pdlLetterAnfang" class="form-label">Text mit Übersicht der Downloads seit</label>
                <div class="input-group">
                    <input type="text" id="pdlLetterAnfang" name="anfang" class="form-control" value="<?php echo htmlspecialchars(date("d.m.Y", (int)$datum)); ?>">
                    <button type="button" class="btn btn-outline-light" onclick="loc(document.getElementById('pdlLetterAnfang').value)">Füllen</button>
                </div>
                <div class="form-text">Format: TT.MM.JJJJ</div>
            </div>
        </div>
    </section>

    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <button type="reset" class="btn btn-outline-light">Eingaben zurücksetzen</button>
        <button type="submit" class="btn btn-primary">Newsletter abschicken</button>
    </div>
</form>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
