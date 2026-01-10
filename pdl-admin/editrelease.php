<?
include("header.inc.php");
if($user_rights[editfiles] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("UPDATE $sql_table[release] SET name='".addslashes($name)."', text='".addslashes($text)."', ordner_id='$ordner_id', released='$released', views='$views', autor='', autor_nick='', autor_email='', autor_homepage='', autor_icq='' WHERE release_id='$release_id'");
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
    if($refresh == "Y")
     {
      $db_handler->sql_query("UPDATE $sql_table[release] SET time='".time()."' WHERE release_id='$release_id'");
     }
    echo "<br>done...<br><a href=\"editrelease.php?release_id=$release_id\">Zurück zum Release</a>";
   }
  else
   {
    $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[release] WHERE release_id='$release_id'"));
    ?>
<br><br>
<form action="editrelease.php?submit=1" method="post">
<input type="hidden" name="release_id" value="<? echo $release_id; ?>">
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Release bearbeiten</b> - <a href="#files">Files</a> - <a href="#screens">Screenshots</a> - <a href="#comments">Kommentare</a>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Name<br>
            <small>Wie der Release heist.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="name" size="35" value="<? echo stripslashes($release[name]); ?>">
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
            <?
            $ordner_id = $release[ordner_id];
            echo treeview_select(0,"-");
            ?>
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
            <option value="N"<? echo pdlif($release[released] == "N"," selected","") ?>>Versteckt</option>
            </select>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Views<br>
            <small>Wie oft die Detailseite des Releases aufgerufen wurde.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="views" size="35" value="<? echo $release[views]; ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Autor<br>
            <small>Daten über den Autor der Datei</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="radio" name="autor_type" value="-1"<? echo pdlif($release[autor] == -1," checked","") ?>> Unbekannt
            <hr>
            <input type="radio" name="autor_type" value="0"<? echo pdlif($release[autor] == 0," checked","") ?>> Daten eingeben:
            <ul>
              <table border="0">
                <tr>
                  <td>
                    Nickname:
                  </td>
                  <td>
                    <input type="text" name="autor_nick" size="35" value="<? echo $release[autor_nick]; ?>">
                  </td>
                </tr>
                <tr>
                  <td>
                    E-Mail:
                  </td>
                  <td>
                    <input type="text" name="autor_email" size="35" value="<? echo $release[autor_email]; ?>">
                  </td>
                </tr>
                <tr>
                  <td>
                    Homepage:
                  </td>
                  <td>
                    <input type="text" name="autor_homepage" size="35" value="<? echo $release[autor_homepage]; ?>">
                  </td>
                </tr>
                <tr>
                  <td>
                    ICQ:
                  </td>
                  <td>
                    <input type="text" name="autor_icq" size="35" value="<? if($release[autor_icq] > 0) echo $release[autor_icq]; ?>">
                  </td>
                </tr>
              </table>
            </ul>
            <hr>
            <input type="radio" name="autor_type" value="1"<? echo pdlif($release[autor] > 0," checked","") ?>> Angemeldeten User wählen:<br>
            <ul><select name="autor_id">
<?
$user_res = $db_handler->sql_query("SELECT user_id, nick FROM $sql_table[user] ORDER BY nick ASC");
while($user_row = $db_handler->sql_fetch_array($user_res))
 {
  echo "<option value=\"$user_row[user_id]\"".pdlif($release[autor] == $user_row[user_id]," selected","").">$user_row[nick]</option>";
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
            <textarea cols="60" rows="10" name="text"><? echo stripslashes($release[text]); ?></textarea>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Datum refreshen<br>
            <small>Aktivieren sie diese Option, wird das Datum der Datei auf Heute gesetzt.</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="checkbox" name="refresh" value="Y"> Datum refreshen
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Release editieren">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<br>
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="5" align="center">
            <a name="files"></a>
            <b>Files</b> - <a href="addfile.php?release_id=<? echo $release_id; ?>">Datei hinzufügen</a>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Größe</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Downloads</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?
        $files_res = $db_handler->sql_query("SELECT * FROM $sql_table[files] WHERE release_id='$release_id'");
        if($db_handler->sql_num_rows($files_res) == 0)
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="5" align="center">
            Keine Files vorhanden
          </td>
        </tr>
        <?
         }
        else
         {
          while($files_row = $db_handler->sql_fetch_array($files_res))
           {
            if($files_row[mirror] > 0)
             {
              $mirror_of = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[files] WHERE file_id='$files_row[mirror]'"));
              $files_row[size] = $mirror_of[size];
             }
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <? echo $files_row[name]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? echo size($files_row[size]); ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? echo $files_row[downloads]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="editfile.php?file_id=<? echo $files_row[file_id]; ?>">Datei ändern</a>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="delfile.php?file_id=<? echo $files_row[file_id]; ?>">Datei löschen</a>
          </td>
        </tr>
            <?
           }
         }
        ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="5">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="4" align="center">
            <a name="screens"></a>
            <b>Screenshots</b> - <a href="addscreen.php?release_id=<? echo $release_id; ?>">Screenshot hochladen</a>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Screen</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Titel</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Views</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?
        $screens_res = $db_handler->sql_query("SELECT * FROM $sql_table[screens] WHERE release_id='$release_id'");
        if($db_handler->sql_num_rows($screens_res) == 0)
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="4" align="center">
            Keine Screenshots vorhanden
          </td>
        </tr>
        <?
         }
        else
         {
          while($screens_row = $db_handler->sql_fetch_array($screens_res))
           {
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <img src="../pdl-gfx/screens/release<? echo $release_id; ?>screen<? echo $screens_row[screen_id]; ?>k.jpg" border="0">
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? echo $screens_row[text]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? echo $screens_row[views]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="delscreen.php?screen_id=<? echo $screens_row[screen_id]; ?>">Screenshot löschen</a>
          </td>
        </tr>
            <?
           }
         }
        ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="4">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="5" align="center">
            <a name="comments"></a>
            <b>Kommentare</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Titel</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Autor</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Datum</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?
        $comments_res = $db_handler->sql_query("SELECT * FROM $sql_table[comments] WHERE release_id='$release_id'");
        if($db_handler->sql_num_rows($comments_res) == 0)
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="5" align="center">
            Keine Kommentare vorhanden
          </td>
        </tr>
        <?
         }
        else
         {
          while($comments_row = $db_handler->sql_fetch_array($comments_res))
           {
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <? echo $comments_row[titel]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? if($comments_row[user_id] == 0) echo "Gast"; else echo user($comments_row[user_id]); ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <? echo date($settings[date_format],$comments_row[time]); ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="editcomment.php?comment_id=<? echo $comments_row[comment_id]; ?>">Kommentar ändern</a>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <a href="delcomment.php?comment_id=<? echo $comments_row[comment_id]; ?>">Kommentar löschen</a>
          </td>
        </tr>
            <?
           }
         }
        ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="5">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
    <?
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
