<?php
function menu_topic(bool $rechte, string $titel): void
 {
  global $master_if,$template;
  if($rechte)
   {
    $master_if = true;
    echo "
    <tr>
      <td align=\"center\" bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\">
        <b>" . htmlspecialchars($titel) . "</b>
      </td>
    </tr>
    ";
   }
 }

function menu_link(bool $rechte, string $titel, string $link): void
 {
  global $master_if;
  if($rechte && $master_if === true)
   {
    echo "
    <tr>
      <td>
        <a href=\"" . htmlspecialchars($link) . "\">" . htmlspecialchars($titel) . "</a>
      </td>
    </tr>
    ";
   }
 }

function menu_close(): void
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

function pdlif(bool $bedingung, string $true, string $false): string
 {
  if($bedingung == 1) return $true;
  else return $false;
 }

function makedialog(string $titel, string $text, string $button, string $action): string
 {
  global $template;
  return "
<br><br>
<form action=\"" . htmlspecialchars($action) . "?submit=1\" method=\"post\">
<center>
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border']) . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg']) . "\" align=\"center\">
            <b>" . htmlspecialchars($titel) . "</b>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['alt_1']) . "\">
            $text
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\" align=\"center\">
            <input type=\"submit\" value=\"" . htmlspecialchars($button) . "\">
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

// treeview_select() is now defined in pdl_functions.inc.php

function delrelease(int $id): void
 {
  global $sql_table,$db_handler;
  $id_escaped = $db_handler->sql_escape_int($id);
  $delscreens_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['screens'] . " WHERE release_id='$id_escaped'");
  while($delscreens_row = $db_handler->sql_fetch_array($delscreens_res))
   {
    unlink("../pdl-gfx/screens/release".$id."screen".$delscreens_row['screen_id']."g.jpg");
    unlink("../pdl-gfx/screens/release".$id."screen".$delscreens_row['screen_id']."k.jpg");
   }
  $db_handler->sql_query("DELETE FROM " . $sql_table['screens'] . " WHERE release_id='$id_escaped'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['comments'] . " WHERE release_id='$id_escaped'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['files'] . " WHERE release_id='$id_escaped'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['release'] . " WHERE release_id='$id_escaped'");
 }

function check_gd(): void
 {
  global $settings;
  $settings['gdversion'] = 0;
  if(!extension_loaded("gd")) $settings['gdversion'] = 0;
  elseif(function_exists("gd_info"))
   {
    $gd_info = gd_info();
    if(strstr($gd_info['GD Version'],"2.")) $settings['gdversion'] = 2;
    elseif(strstr($gd_info['GD Version'],"1.")) $settings['gdversion'] = 1;
   }
  else
   {
    ob_start();
    phpinfo(INFO_MODULES);
    $phpinfo = strip_tags((string) ob_get_contents());
    ob_end_clean();
    preg_match("/gd version\s*(.*)/i",$phpinfo,$version);
    if(strstr($version[1],"2.")) $settings['gdversion'] = 2;
    elseif(strstr($version[1],"1.")) $settings['gdversion'] = 1;
   }
 }
?>
