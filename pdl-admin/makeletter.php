<?
include("header.inc.php");
if($user_rights[writeletter] == "Y")
 {
  if($submit == 1)
   {
    set_time_limit(300);
    $addys = array();
    if($extra_addys) $addys = explode(";", $extra_addys);

    if($ugroup_ids)
     {
      for($i = 0; $i < count($ugroup_ids); $i++)
       {
        $where.= "ugroup_id='$ugroup_ids[$i]'";
        if($i < count($ugroup_ids)-1) $where.= " OR ";
       }
      $addys_res = $db_handler->sql_query("SELECT email FROM $sql_table[user] WHERE ($where) AND get_letter='Y'");
      while($addys_row = $db_handler->sql_fetch_array($addys_res))
       {
        $addys[] = $addys_row[email];
       }
     }

    for($i = 0; $i < count($addys); $i++)
     {
      $sleep++;
      if($sleep == 10)
       {
        sleep(1);
        $sleep = 0;
       }
      mail("$addys[$i]", "Download Letter", stripslashes($text), "FROM: $settings[mail_fromname] <$settings[mail_fromaddr]>");
     }
    echo "<br>done... Download Letter wurde an ".count($addys)." Adressen verschickt.";
    if(!$settings[lastletter]) $db_handler->sql_query("INSERT INTO $sql_table[settings] (wert,variablenname) VALUES ('".time()."','lastletter')");
    else $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='".time()."' WHERE variablenname='lastletter'");
   }
  else
   {
    if(!$settings[lastletter]) $datum = $settings[installed];
    else $datum = $settings[lastletter];
    if($anfang)
     {
      $datum = explode(".",$anfang);
      $datum = mktime(0,0,0,$datum[1],$datum[0],$datum[2]);
     }

    $dls_res = $db_handler->sql_query("SELECT * FROM $sql_table[release] WHERE time>'$datum' ORDER BY time ASC");

    $text = "Download Letter vom ".date("d.m.Y").".\n";
    $text.= "Seit dem ".date("d.m.Y",$datum)." wurden ".$db_handler->sql_num_rows($dls_res)." Downloads hinzugefügt.\n\n";

    while($dls_row = $db_handler->sql_fetch_array($dls_res))
     {
      $text.= "-------------------------------------------\n";
      $text.= date("d.m.Y", $dls_row[time]).": $dls_row[name]\n";
      $text.= "-------------------------------------------\n";
      if(strlen($dls_row[text]) > 250) $text .= substr($dls_row[text],0,250)."...\n";
      else $text.= $dls_row[text]."\n";
      $text.= "-------------------------------------------\n";
      $text.= "Nähere Infos zu $dls_row[name] und den Download finden sie unter\n$settings[script_file]release_id=$dls_row[release_id]\n";
      $text.= "-------------------------------------------\n\n";
     }
    $text.= "Dies ist ein automatisch generierter Download Letter. Sie erhalten ihn weil sie sich unter $settings[script_file] angemeldet haben.\n";
    $text.= "Um den Download Letter nicht mehr zu erhalten loggen sie sich unter $settings[script_file]usercenter=login ein und ändern sie ihr Profil anschließend unter der Adresse $settings[script_file]usercenter=profil wichtig ist das sie den Punkt \"Download Letter erhalten\" deaktivieren.";
    ?>
<br><br>
<script language="JavaScript">
function loc(anfang)
 {
  document.location.href = 'makeletter.php?anfang='+anfang;
 }
</script>
<form action="makeletter.php?submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="75%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Download Letter verfassen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="2" align="center">
            <b>Inhalt</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="2" align="center">
            <textarea name="text" cols="70" rows="30"><? echo $text; ?></textarea>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Letter senden an</b><br>
            <small>Hier können sie den Letter nur bestimmten Usergruppen senden, um z.B. nur einen Internen Letter zu verschicken.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <select name="ugroup_ids[]" size="4" multiple="multiple">
            <?
            $ugroup_res = $db_handler->sql_query("SELECT * FROM $sql_table[usergroup] WHERE ugroup_id!='3'");
            while($ugroup_row = $db_handler->sql_fetch_array($ugroup_res))
             {
              echo "<option value=\"$ugroup_row[ugroup_id]\">$ugroup_row[name]</option>";
             }
            ?>
            </select>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Außerdem senden an</b><br>
            <small>Hier können sie nochmal extra Mailadressen eingeben, an die der Letter gehen soll. Mailadressen bitte mit ; trennen!</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="extra_addys" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Abschicken"> <input type="reset" value="Eingaben löschen"><br>
            Text mit Übersicht der Downloads seit <input type="text" name="anfang" value="<? echo date("d.m.Y", $datum); ?>" size="8">
            &nbsp;<button onclick="javascript:loc(form.anfang.value)">füllen</button>.
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
    <?
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
