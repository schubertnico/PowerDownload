<?
// Menü Topic und Master-if
function menu_topic($rechte,$titel)
 {
  global $master_if,$template;
  if($rechte == 1)
   {
    $master_if = true;
    echo "
    <tr>
      <td align=\"center\" bgcolor=\"$template[footer_bg]\">
        <b>$titel</b>
      </td>
    </tr>
    ";
   }
 }

// Menü Link
function menu_link($rechte,$titel,$link)
 {
  global $master_if;
  if($rechte == 1 && $master_if == true)
   {
    echo "
    <tr>
      <td>
        <a href=\"$link\">$titel</a>
      </td>
    </tr>
    ";
   }
 }

// Menü Schließen
function menu_close()
 {
  global $master_if;
  if($master_if == true)
   {
    echo "
    <tr>
      <td>
        <hr>
      </td>
    </tr>
    ";
   }
  $master_if = false;
 }

// Gibt 2 Werte je nach Bedingung aus
function pdlif($bedingung,$true,$false)
 {
  if($bedingung == 1) return $true;
  else return $false;
 }

// Erzeugt einen Dialog.
function makedialog($titel,$text,$button,$action)
 {
  global $template;
  return "
<br><br>
<form action=\"$action?submit=1\" method=\"post\">
<center>
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\">
            <b>$titel</b>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$template[alt_1]\">
            $text
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$template[footer_bg]\" align=\"center\">
            <input type=\"submit\" value=\"$button\">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</center>
</form>
  ";
 }

// Treeview im Selectfeld
function treeview_select($ordner,$head)
 {
  global $ordner_id,$sql_table,$db_handler;

  $ordner_res = $db_handler->sql_query("SELECT * FROM $sql_table[ordner] WHERE sordner_id='$ordner' ORDER BY name ASC");
  while($ordner_row = $db_handler->sql_fetch_array($ordner_res))
   {
    echo "<option value=\"$ordner_row[ordner_id]\"".pdlif($ordner_row[ordner_id] == $ordner_id," selected","").">$head> $ordner_row[name]</option>\n";
    treeview_select($ordner_row[ordner_id],"- ".$head);
   }
 }

// Release löschen. Wegen zu vielen Sachen wirds zur Funktion
function delrelease($id)
 {
  global $sql_table,$db_handler;
  $delscreens_res = $db_handler->sql_query("SELECT * FROM $sql_table[screens] WHERE release_id='$id'");
  while($delscreens_row = $db_handler->sql_fetch_array($delscreens_res))
   {
    unlink("../pdl-gfx/screens/release".$id."screen".$delscreens_row[screen_id]."g.jpg");
    unlink("../pdl-gfx/screens/release".$id."screen".$delscreens_row[screen_id]."k.jpg");
   }
  $db_handler->sql_query("DELETE FROM $sql_table[screens] WHERE release_id='$id'");
  $db_handler->sql_query("DELETE FROM $sql_table[comments] WHERE release_id='$id'");
  $db_handler->sql_query("DELETE FROM $sql_table[files] WHERE release_id='$id'");
  $db_handler->sql_query("DELETE FROM $sql_table[release] WHERE release_id='$id'");
 }
 
// Gibt in $settings['gdversion'] die GD Version aus.
function check_gd()
 {
  global $settings;
  $settings['gdversion'] = 0;
  if(!extension_loaded("gd")) $settings['gdversion'] = 0; // kein GD installiert.
  elseif(function_exists("gd_info")) // nur php >= 430... :(
   {
    $gd_info = gd_info();
    if(strstr($gd_info['GD Version'],"2.")) $settings['gdversion'] = 2;
    elseif(strstr($gd_info['GD Version'],"1.")) $settings['gdversion'] = 1;
   }
  else
   {
    ob_start();
    phpinfo(INFO_MODULES);
    $phpinfo = strip_tags(ob_get_contents());
    ob_end_clean();
    preg_match("/gd version\s*(.*)/i",$phpinfo,$version);
    if(strstr($version[1],"2.")) $settings['gdversion'] = 2;
    elseif(strstr($version[1],"1.")) $settings['gdversion'] = 1;
   }
 }
?>
