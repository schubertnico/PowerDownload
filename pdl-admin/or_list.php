<?
include("header.inc.php");

if(!$ordner_id) $ordner_id = 0;
if(!$page) $page = 1;
// Admin Ordner Treeview
function treeview_admin($ordner, $head)
 {
  global $db_handler,$ordner_id,$settings,$sql_table,$user_rights;
  if(!$head) $head = "&nbsp;&nbsp;&nbsp;";
  $treeview_res = $db_handler->sql_query("SELECT * FROM $sql_table[ordner] WHERE sordner_id='$ordner'");
  while($treeview_row = $db_handler->sql_fetch_array($treeview_res))
   {
    $alt = alt_switch();
    $releases = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM $sql_table[release] WHERE ordner_id='$treeview_row[ordner_id]'"));
    echo "
        <tr>
          <td bgcolor=\"$alt\">
            $head<a href=\"or_list.php?ordner_id=$treeview_row[ordner_id]\">$treeview_row[name]</a>
          </td>
          <td bgcolor=\"$alt\">
            $releases
          </td>
          <td bgcolor=\"$alt\">
            <a href=\"addrelease.php?ordner_id=$treeview_row[ordner_id]\">Release hinzufügen</a>
          </td>
          <td bgcolor=\"$alt\">
            ".pdlif($user_rights[adddirs] == "Y","<a href=\"adddir.php?ordner_id=$treeview_row[ordner_id]\">Sub-Ordner hinzufügen</a>","&nbsp;")."
          </td>
          <td bgcolor=\"$alt\">
            ".pdlif($user_rights[editdirs] == "Y","<a href=\"editdir.php?ordner_id=$treeview_row[ordner_id]\">ändern</a>","&nbsp;")."
          </td>
          <td bgcolor=\"$alt\">
            ".pdlif($user_rights[deldirs] == "Y","<a href=\"deldir.php?ordner_id=$treeview_row[ordner_id]\">löschen</a>","&nbsp;")."
          </td>
        </tr>
    ";
    $head2 = "&nbsp;&nbsp;&nbsp;".$head;
    treeview_admin($treeview_row[ordner_id], $head2);
   }
 }

if($user_rights[editdirs] == "Y" || $user_rights[deldirs] == "Y" || $user_rights[editfiles] == "Y" || $user_rights[delfiles] == "Y")
 { ?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="6" align="center">
            <b>Ordner</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Releases</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="4" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <a href="or_list.php?ordner_id=0">Index</a>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <?
            $releases = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM $sql_table[release] WHERE ordner_id='0'"));
            echo $releases;
            ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="addrelease.php?ordner_id=0">Release hinzufügen</a>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <?
            echo pdlif($user_rights[adddirs] == "Y","<a href=\"adddir.php?ordner_id=0\">Sub-Ordner hinzufügen</a>","&nbsp;");
            ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            &nbsp;
          </td>
          <td bgcolor="<? echo $alt; ?>">
            &nbsp;
          </td>
        </tr>
        <?
        treeview_admin(0, "");
        $total = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM $sql_table[release] WHERE ordner_id='$ordner_id'"));
        if($total == 0)
         { ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="6" align="center">
            Keine Release in diesem Ordner.
          </td>
        </tr>
        <? }
        else
         {
          $temp1=$page * $settings[perpage] - $settings[perpage];
          $limit=$temp1.",".$settings[perpage];
          $files_res = $db_handler->sql_query("SELECT * FROM $sql_table[release] WHERE ordner_id='$ordner_id' ORDER BY $settings[orderby] $settings[orderseq] LIMIT $limit");
        ?>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="6" align="center">
            <b>Releases</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="4" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?
          while($files_row = $db_handler->sql_fetch_array($files_res))
           {
            $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="2">
            <? echo $files_row[name]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="addfile.php?release_id=<? echo $files_row[release_id]; ?>">Datei hinzufügen</a>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="addscreen.php?release_id=<? echo $files_row[release_id]; ?>">Screenshot hochladen</a>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? echo pdlif($user_rights[editfiles] == "Y","<a href=\"editrelease.php?release_id=$files_row[release_id]\">ändern</a>","&nbsp;"); ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? echo pdlif($user_rights[delfiles] == "Y","<a href=\"delrelease.php?release_id=$files_row[release_id]\">löschen</a>","&nbsp;"); ?>
          </td>
        </tr>
        <? } ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="6" align="center">
            &nbsp;<? echo seiten($total,$settings[perpage],"&ordner_id=$ordner_id","or_list.php?"); ?>
          </td>
        </tr>
        <? } ?>
      </table>
    </td>
  </tr>
</table>
<? }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
