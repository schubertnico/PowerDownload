<?
include("header.inc.php");
if($user_rights[adminaccess] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("INSERT INTO $sql_table[release] (name,text,time,ordner_id,released,uploader) VALUES ('".addslashes($name)."', '".addslashes($text)."', '".time()."', '$ordner_id', '$released', '$user_details[user_id]')");
    $release_id = $db_handler->sql_insert_id();
    if($autor_type == -1)
     {
      $db_handler->sql_query("UPDATE $sql_table[release] SET autor='-1' WHERE release_id='$release_id'");
     }
    elseif($autor_type == 0)
     {
      $db_handler->sql_query("UPDATE $sql_table[release] SET autor='0', autor_nick='$autor_nick', autor_email='$autor_email', autor_homepage='$autor_homepage', autor_icq='$autor_icq' WHERE release_id='$release_id'");
     }
    elseif($autor_type == 1)
     {
      $db_handler->sql_query("UPDATE $sql_table[release] SET autor='$autor_id' WHERE release_id='$release_id'");
     }
    echo "<br>done...<br><a href=\"addfile.php?release_id=$release_id\">Datei hinzufügen</a>";
   }
  else
   {
  ?>
<br><br>
<form action="addrelease.php?submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Release hinzufügen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Name<br>
            <small>Wie der Release heist.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="name" size="35">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Ordner<br>
            <small>Welchem Ordner ist die Datei untergeordnet</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <select name="ordner_id">
            <option value="0">Index</option>
            <? echo treeview_select(0,"-"); ?>
            </select>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Datei sichtbar<br>
            <small>Soll die Datei in der Übersicht sichtbar sein oder versteckt werden?</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <select name="released">
            <option value="Y">Sichtbar</option>
            <option value="N">Versteckt</option>
            </select>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Autor<br>
            <small>Daten über den Autor der Datei</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="radio" name="autor_type" value="-1" checked> Unbekannt
            <hr>
            <input type="radio" name="autor_type" value="0"> Daten eingeben:
            <ul>
              <table border="0">
                <tr>
                  <td>
                    Nickname:
                  </td>
                  <td>
                    <input type="text" name="autor_nick" size="35">
                  </td>
                </tr>
                <tr>
                  <td>
                    E-Mail:
                  </td>
                  <td>
                    <input type="text" name="autor_email" size="35">
                  </td>
                </tr>
                <tr>
                  <td>
                    Homepage:
                  </td>
                  <td>
                    <input type="text" name="autor_homepage" size="35">
                  </td>
                </tr>
                <tr>
                  <td>
                    ICQ:
                  </td>
                  <td>
                    <input type="text" name="autor_icq" size="35">
                  </td>
                </tr>
              </table>
            </ul>
            <hr>
            <input type="radio" name="autor_type" value="1"> Angemeldeten User wählen:<br>
            <ul><select name="autor_id">
<?
$user_res = $db_handler->sql_query("SELECT user_id, nick FROM $sql_table[user] ORDER BY nick ASC");
while($user_row = $db_handler->sql_fetch_array($user_res))
 {
  echo "<option value=\"$user_row[user_id]\">$user_row[nick]</option>";
 }
?>
            </select></ul>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Beschreibung<br>
            <small>Geben sie die Beschreibung zur Datei an.<br>
            Beachten sie die <a href="showreplacements.php">Replacements</a>.<br>
            HTML ist <? echo pdlif($settings[html_releases] == "Y","An","Aus"); ?><br>
            BB Code ist <? echo pdlif($settings[bb_code] == "Y","An","Aus"); ?><br>
            Glossar ist <? echo pdlif($settings[glossary] == "Y","An","Aus"); ?><br>
            Smilies sind <? echo pdlif($settings[smilies] == "Y","An","Aus"); ?><br>
            Zensur ist <? echo pdlif($settings[badwords_releases] == "Y","An","Aus"); ?><br>
            </small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <textarea cols="60" rows="10" name="text"></textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Release hinzufügen">
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
