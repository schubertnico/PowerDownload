<?php
include("header.inc.php");

// Extract POST variables for PHP 8.4 compatibility
$name = $_POST['name'] ?? '';
$text = $_POST['text'] ?? '';
$ordner_id = isset($_POST['ordner_id']) ? (int)$_POST['ordner_id'] : 0;
$released = $_POST['released'] ?? 'Y';
$views = isset($_POST['views']) ? (int)$_POST['views'] : 0;
$refresh = isset($_POST['refresh']) ? (int)$_POST['refresh'] : 0;
$autor_type = isset($_POST['autor_type']) ? (int)$_POST['autor_type'] : -1;
$autor_nick = $_POST['autor_nick'] ?? '';
$autor_email = $_POST['autor_email'] ?? '';
$autor_homepage = $_POST['autor_homepage'] ?? '';
$autor_icq = $_POST['autor_icq'] ?? '';
$autor_id = isset($_POST['autor_id']) ? (int)$_POST['autor_id'] : 0;
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$release_id = isset($_GET['release_id']) ? (int)$_GET['release_id'] : (isset($_POST['release_id']) ? (int)$_POST['release_id'] : 0);

if($user_rights['editfiles'] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("UPDATE ".$sql_table['release']." SET name='".$db_handler->sql_escape_string($name)."', text='".$db_handler->sql_escape_string($text)."', ordner_id='".$ordner_id."', released='".$db_handler->sql_escape_string($released)."', views='".$views."', autor='', autor_nick='', autor_email='', autor_homepage='', autor_icq='' WHERE release_id='".$release_id."'");
    if($autor_type == -1)
     {
      $db_handler->sql_query("UPDATE ".$sql_table['release']." SET autor='-1' WHERE release_id='".$release_id."'");
     }
    elseif($autor_type == 0)
     {
      $db_handler->sql_query("UPDATE ".$sql_table['release']." SET autor='0', autor_nick='".$db_handler->sql_escape_string($autor_nick)."', autor_email='".$db_handler->sql_escape_string($autor_email)."', autor_homepage='".$db_handler->sql_escape_string($autor_homepage)."', autor_icq='".$db_handler->sql_escape_string($autor_icq)."' WHERE release_id='".$release_id."'");
     }
    elseif($autor_type == 1)
     {
      $db_handler->sql_query("UPDATE ".$sql_table['release']." SET autor='".$autor_id."' WHERE release_id='".$release_id."'");
     }
    if($refresh == "Y")
     {
      $db_handler->sql_query("UPDATE ".$sql_table['release']." SET time='".time()."' WHERE release_id='".$release_id."'");
     }
    echo "<br>done...<br><a href=\"editrelease.php?release_id=".htmlspecialchars($release_id)."\">Zur&uuml;ck zum Release</a>";
   }
  else
   {
    $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM ".$sql_table['release']." WHERE release_id='".$release_id."'"));
    ?>
<br><br>
<form action="editrelease.php?submit=1" method="post">
<input type="hidden" name="release_id" value="<?php echo htmlspecialchars($release_id); ?>">
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Release bearbeiten</b> - <a href="#files">Files</a> - <a href="#screens">Screenshots</a> - <a href="#comments">Kommentare</a>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Name<br>
            <small>Wie der Release heist.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="name" size="35" value="<?php echo htmlspecialchars(stripslashes($release['name'])); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Ordner<br>
            <small>Welchem Ordner ist die Datei untergeordnet</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <select name="ordner_id">
            <option value="0">Index</option>
            <?php
            $ordner_id = $release['ordner_id'];
            echo treeview_select(0,"-");
            ?>
            </select>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Datei sichtbar<br>
            <small>Soll die Datei in der &Uuml;bersicht sichtbar sein oder versteckt werden?</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <select name="released">
            <option value="Y">Sichtbar</option>
            <option value="N"<?php echo pdlif($release['released'] == "N"," selected","") ?>>Versteckt</option>
            </select>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Views<br>
            <small>Wie oft die Detailseite des Releases aufgerufen wurde.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="views" size="35" value="<?php echo htmlspecialchars($release['views']); ?>">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Autor<br>
            <small>Daten &uuml;ber den Autor der Datei</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="radio" name="autor_type" value="-1"<?php echo pdlif($release['autor'] == -1," checked","") ?>> Unbekannt
            <hr>
            <input type="radio" name="autor_type" value="0"<?php echo pdlif($release['autor'] == 0," checked","") ?>> Daten eingeben:
            <ul>
              <table border="0">
                <tr>
                  <td>
                    Nickname:
                  </td>
                  <td>
                    <input type="text" name="autor_nick" size="35" value="<?php echo htmlspecialchars($release['autor_nick']); ?>">
                  </td>
                </tr>
                <tr>
                  <td>
                    E-Mail:
                  </td>
                  <td>
                    <input type="text" name="autor_email" size="35" value="<?php echo htmlspecialchars($release['autor_email']); ?>">
                  </td>
                </tr>
                <tr>
                  <td>
                    Homepage:
                  </td>
                  <td>
                    <input type="text" name="autor_homepage" size="35" value="<?php echo htmlspecialchars($release['autor_homepage']); ?>">
                  </td>
                </tr>
                <tr>
                  <td>
                    ICQ:
                  </td>
                  <td>
                    <input type="text" name="autor_icq" size="35" value="<?php if($release['autor_icq'] > 0) echo htmlspecialchars($release['autor_icq']); ?>">
                  </td>
                </tr>
              </table>
            </ul>
            <hr>
            <input type="radio" name="autor_type" value="1"<?php echo pdlif($release['autor'] > 0," checked","") ?>> Angemeldeten User w&auml;hlen:<br>
            <ul><select name="autor_id">
<?php
$user_res = $db_handler->sql_query("SELECT user_id, nick FROM ".$sql_table['user']." ORDER BY nick ASC");
while($user_row = $db_handler->sql_fetch_array($user_res))
 {
  echo "<option value=\"".htmlspecialchars($user_row['user_id'])."\"".pdlif($release['autor'] == $user_row['user_id']," selected","").">".htmlspecialchars($user_row['nick'])."</option>";
 }
?>
            </select></ul>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Beschreibung<br>
            <small>Geben sie die Beschreibung zur Datei an.<br>
            Beachten sie die <a href="showreplacements.php">Replacements</a>.<br>
            HTML ist <?php echo pdlif($settings['html_releases'] == "Y","An","Aus"); ?><br>
            BB Code ist <?php echo pdlif($settings['bb_code'] == "Y","An","Aus"); ?><br>
            Glossar ist <?php echo pdlif($settings['glossary'] == "Y","An","Aus"); ?><br>
            Smilies sind <?php echo pdlif($settings['smilies'] == "Y","An","Aus"); ?><br>
            Zensur ist <?php echo pdlif($settings['badwords_releases'] == "Y","An","Aus"); ?><br>
            </small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <textarea cols="60" rows="10" name="text"><?php echo htmlspecialchars(stripslashes($release['text'])); ?></textarea>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Datum refreshen<br>
            <small>Aktivieren sie diese Option, wird das Datum der Datei auf Heute gesetzt.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="checkbox" name="refresh" value="Y"> Datum refreshen
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
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
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="5" align="center">
            <a name="files"></a>
            <b>Files</b> - <a href="addfile.php?release_id=<?php echo htmlspecialchars($release_id); ?>">Datei hinzuf&uuml;gen</a>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Name</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Gr&ouml;&szlig;e</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Downloads</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?php
        $files_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['files']." WHERE release_id='".$release_id."'");
        if($db_handler->sql_num_rows($files_res) == 0)
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="5" align="center">
            Keine Files vorhanden
          </td>
        </tr>
        <?php
         }
        else
         {
          while($files_row = $db_handler->sql_fetch_array($files_res))
           {
            if($files_row['mirror'] > 0)
             {
              $mirror_of = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM ".$sql_table['files']." WHERE file_id='".$files_row['mirror']."'"));
              $files_row['size'] = $mirror_of['size'];
             }
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($files_row['name']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo size($files_row['size']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($files_row['downloads']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="editfile.php?file_id=<?php echo htmlspecialchars($files_row['file_id']); ?>">Datei &auml;ndern</a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="delfile.php?file_id=<?php echo htmlspecialchars($files_row['file_id']); ?>">Datei l&ouml;schen</a>
          </td>
        </tr>
            <?php
           }
         }
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="5">
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
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="4" align="center">
            <a name="screens"></a>
            <b>Screenshots</b> - <a href="addscreen.php?release_id=<?php echo htmlspecialchars($release_id); ?>">Screenshot hochladen</a>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Screen</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Titel</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Views</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?php
        $screens_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['screens']." WHERE release_id='".$release_id."'");
        if($db_handler->sql_num_rows($screens_res) == 0)
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="4" align="center">
            Keine Screenshots vorhanden
          </td>
        </tr>
        <?php
         }
        else
         {
          while($screens_row = $db_handler->sql_fetch_array($screens_res))
           {
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <img src="../pdl-gfx/screens/release<?php echo htmlspecialchars($release_id); ?>screen<?php echo htmlspecialchars($screens_row['screen_id']); ?>k.jpg" border="0">
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($screens_row['text']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($screens_row['views']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="delscreen.php?screen_id=<?php echo htmlspecialchars($screens_row['screen_id']); ?>">Screenshot l&ouml;schen</a>
          </td>
        </tr>
            <?php
           }
         }
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="4">
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
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="5" align="center">
            <a name="comments"></a>
            <b>Kommentare</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Titel</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Autor</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Datum</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Optionen</b>
          </td>
        </tr>
        <?php
        $comments_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['comments']." WHERE release_id='".$release_id."'");
        if($db_handler->sql_num_rows($comments_res) == 0)
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="5" align="center">
            Keine Kommentare vorhanden
          </td>
        </tr>
        <?php
         }
        else
         {
          while($comments_row = $db_handler->sql_fetch_array($comments_res))
           {
            $alt = alt_switch();
            ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo htmlspecialchars($comments_row['titel']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php if($comments_row['user_id'] == 0) echo "Gast"; else echo user($comments_row['user_id']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <?php echo date($settings['date_format'],$comments_row['time']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="editcomment.php?comment_id=<?php echo htmlspecialchars($comments_row['comment_id']); ?>">Kommentar &auml;ndern</a>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <a href="delcomment.php?comment_id=<?php echo htmlspecialchars($comments_row['comment_id']); ?>">Kommentar l&ouml;schen</a>
          </td>
        </tr>
            <?php
           }
         }
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="5">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br><br>
    <?php
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
