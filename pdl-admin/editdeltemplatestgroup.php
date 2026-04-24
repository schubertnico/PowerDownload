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

if($user_rights['god'] == "Y")
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
    echo "<br>Templates/Gruppen geaendert/geloescht.<br>";
   }
  echo "
<br>
<form action=\"editdeltemplatestgroup.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"75%\">
  <tr>
    <td bgcolor=\"".htmlspecialchars($template['table_border'])."\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['header_bg'])."\" align=\"center\" colspan=\"3\">
            <b>Templates/Gruppen aendern/loeschen</b>
          </td>
        </tr>";
  $tgroup_count = -1;
  $templates_count = -1;
  $tgroup_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['templategroup']." ORDER BY reihenfolge ASC");
  while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res))
   {
    $tgroup_count++;
    echo "
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\">
            <input type=\"text\" name=\"tgroup[".$tgroup_count."][reihenfolge]\" value=\"".htmlspecialchars($tgroup_row['reihenfolge'])."\" size=\"1\">
          </td>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\" colspan=\"2\">
            <input type=\"hidden\" name=\"tgroup[".$tgroup_count."][tgroup_id]\" value=\"".htmlspecialchars($tgroup_row['tgroup_id'])."\">
            <input type=\"text\" name=\"tgroup[".$tgroup_count."][name]\" value=\"".htmlspecialchars($tgroup_row['name'])."\" size=\"35\">";
    $dodelete = true;
    foreach($prot_tgroups as $prot_tgroup)
     {
      if($prot_tgroup == $tgroup_row['tgroup_id'])
       {
        $dodelete = false;
        break;
       }
     }
    if($dodelete == true)
     {
      echo "
      ( <input type=\"checkbox\" name=\"tgroup[".$tgroup_count."][delete]\" value=\"Y\"> Loeschen )
      ";
     }
    echo "      </td>
        </tr>
    ";
    $templates_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['template']." WHERE tgroup_id='".$db_handler->sql_escape_int($tgroup_row['tgroup_id'])."' ORDER BY reihenfolge ASC");
    while($templates_row = $db_handler->sql_fetch_array($templates_res))
     {
      $templates_count++;
      $alt = alt_switch();
      echo "
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;&nbsp;<input type=\"text\" name=\"templates[".$templates_count."][reihenfolge]\" value=\"".htmlspecialchars($templates_row['reihenfolge'])."\" size=\"1\">
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\" colspan=\"2\">
            <input type=\"hidden\" name=\"templates[".$templates_count."][template_id]\" value=\"".htmlspecialchars($templates_row['template_id'])."\">
            <input type=\"text\" name=\"templates[".$templates_count."][name]\" value=\"".htmlspecialchars($templates_row['name'])."\" size=\"35\">";
      $dodelete = true;
      foreach($prot_templates as $prot_template)
       {
        if($prot_template == $templates_row['variablenname'])
         {
          $dodelete = false;
          break;
         }
       }
      if($dodelete == true)
       {
        echo "
        ( <input type=\"checkbox\" name=\"templates[".$templates_count."][delete]\" value=\"Y\"> Loeschen )
        ";
       }
      echo "</td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            Beschreibung<br>
            <small>Beschreibung des Templates.</small>
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <textarea cols=\"50\" rows=\"5\" name=\"templates[".$templates_count."][bez]\">".htmlspecialchars($templates_row['bez'])."</textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            Eingabeart<br>
            <small>Waehlen sie aus folgender Liste aus auf welche Art das Template eingegeben wird.</small>
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <select name=\"templates[".$templates_count."][eingabe]\">
            <option value=\"textarea\">Textarea</option>
            <option value=\"input\"".pdlif($templates_row['eingabe'] == "input"," selected","").">Input</option>
            <option value=\"farbe\"".pdlif($templates_row['eingabe'] == "farbe"," selected","").">Farbauswahl</option>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            Variablenname<br>
            <small>Wird im System als \$template[variablenname] verfuegbar</small>
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">";
      if($dodelete == true) echo "<input type=\"text\" name=\"templates[".$templates_count."][variablenname]\" value=\"".htmlspecialchars($templates_row['variablenname'])."\" size=\"35\">";
      else echo "<input type=\"hidden\" name=\"templates[".$templates_count."][variablenname]\" value=\"".htmlspecialchars($templates_row['variablenname'])."\">".htmlspecialchars($templates_row['variablenname'])." - Kann nicht geaendert werden.";
      echo "
          </td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            Templategruppe<br>
            <small>zu welcher Templategruppe gehoert das Template?</small>
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <select name=\"templates[".$templates_count."][tgroup_id]\">";
      $tgroup2_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['templategroup']." ORDER BY reihenfolge ASC");
      while($tgroup2_row = $db_handler->sql_fetch_array($tgroup2_res))
       {
        echo "<option value=\"".htmlspecialchars($tgroup2_row['tgroup_id'])."\"".pdlif($templates_row['tgroup_id'] == $tgroup2_row['tgroup_id']," selected","").">".htmlspecialchars($tgroup2_row['name'])."</option>";
       }
      echo "        </select>
          </td>
        </tr>
      ";
     }

   }
  echo "      <tr>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\" align=\"center\" colspan=\"3\">
            <input type=\"submit\" value=\"Templates/Gruppen aendern/loeschen\">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>";

 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
