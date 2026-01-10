<?php
/**
 * PowerDownload - Add Release
 */
include("header.inc.php");

// Extract form variables
$name = $_POST['name'] ?? '';
$text = $_POST['text'] ?? '';
$ordner_id = isset($_POST['ordner_id']) ? (int)$_POST['ordner_id'] : 0;
$released = $_POST['released'] ?? 'Y';
$autor_type = isset($_POST['autor_type']) ? (int)$_POST['autor_type'] : -1;
$autor_nick = $_POST['autor_nick'] ?? '';
$autor_email = $_POST['autor_email'] ?? '';
$autor_homepage = $_POST['autor_homepage'] ?? '';
$autor_icq = $_POST['autor_icq'] ?? '';
$autor_id = isset($_POST['autor_id']) ? (int)$_POST['autor_id'] : 0;

if (($user_rights['adminaccess'] ?? '') == "Y") {
    if ($submit == 1) {
        $escaped_name = $db_handler->sql_escape_string($name);
        $escaped_text = $db_handler->sql_escape_string($text);
        $escaped_ordner_id = $db_handler->sql_escape_int($ordner_id);
        $escaped_released = $db_handler->sql_escape_string($released);
        $user_id = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);

        $db_handler->sql_query("INSERT INTO " . $sql_table['release'] . " (name,text,time,ordner_id,released,uploader) VALUES ('" . $escaped_name . "', '" . $escaped_text . "', '" . time() . "', '" . $escaped_ordner_id . "', '" . $escaped_released . "', '" . $user_id . "')");
        $release_id = $db_handler->sql_insert_id();

        if ($autor_type == -1) {
            $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET autor='-1' WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
        } elseif ($autor_type == 0) {
            $escaped_autor_nick = $db_handler->sql_escape_string($autor_nick);
            $escaped_autor_email = $db_handler->sql_escape_string($autor_email);
            $escaped_autor_homepage = $db_handler->sql_escape_string($autor_homepage);
            $escaped_autor_icq = $db_handler->sql_escape_string($autor_icq);
            $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET autor='0', autor_nick='" . $escaped_autor_nick . "', autor_email='" . $escaped_autor_email . "', autor_homepage='" . $escaped_autor_homepage . "', autor_icq='" . $escaped_autor_icq . "' WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
        } elseif ($autor_type == 1) {
            $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET autor='" . $db_handler->sql_escape_int($autor_id) . "' WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
        }
        echo '<br>done...<br><a href="addfile.php?release_id=' . (int)$release_id . '">Datei hinzufuegen</a>';
    } else {
?>
<br><br>
<form action="addrelease.php?submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" colspan="2" align="center">
            <b>Release hinzufuegen</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            Name<br>
            <small>Wie der Release heisst.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="text" name="name" size="35">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            Ordner<br>
            <small>Welchem Ordner ist die Datei untergeordnet</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            <select name="ordner_id">
            <option value="0">Index</option>
            <?php echo treeview_select(0, "-"); ?>
            </select>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            Datei sichtbar<br>
            <small>Soll die Datei in der Uebersicht sichtbar sein oder versteckt werden?</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            <select name="released">
            <option value="Y">Sichtbar</option>
            <option value="N">Versteckt</option>
            </select>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            Autor<br>
            <small>Daten ueber den Autor der Datei</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="radio" name="autor_type" value="-1" checked> Unbekannt
            <hr>
            <input type="radio" name="autor_type" value="0"> Daten eingeben:
            <ul>
              <table border="0">
                <tr>
                  <td>Nickname:</td>
                  <td><input type="text" name="autor_nick" size="35"></td>
                </tr>
                <tr>
                  <td>E-Mail:</td>
                  <td><input type="text" name="autor_email" size="35"></td>
                </tr>
                <tr>
                  <td>Homepage:</td>
                  <td><input type="text" name="autor_homepage" size="35"></td>
                </tr>
                <tr>
                  <td>ICQ:</td>
                  <td><input type="text" name="autor_icq" size="35"></td>
                </tr>
              </table>
            </ul>
            <hr>
            <input type="radio" name="autor_type" value="1"> Angemeldeten User waehlen:<br>
            <ul><select name="autor_id">
<?php
$user_res = $db_handler->sql_query("SELECT user_id, nick FROM " . $sql_table['user'] . " ORDER BY nick ASC");
while ($user_row = $db_handler->sql_fetch_array($user_res)) {
    echo '<option value="' . (int)$user_row['user_id'] . '">' . htmlspecialchars($user_row['nick'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
}
?>
            </select></ul>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            Beschreibung<br>
            <small>Geben sie die Beschreibung zur Datei an.<br>
            Beachten sie die <a href="showreplacements.php">Replacements</a>.<br>
            HTML ist <?php echo pdlif(($settings['html_releases'] ?? '') == "Y", "An", "Aus"); ?><br>
            BB Code ist <?php echo pdlif(($settings['bb_code'] ?? '') == "Y", "An", "Aus"); ?><br>
            Glossar ist <?php echo pdlif(($settings['glossary'] ?? '') == "Y", "An", "Aus"); ?><br>
            Smilies sind <?php echo pdlif(($settings['smilies'] ?? '') == "Y", "An", "Aus"); ?><br>
            Zensur ist <?php echo pdlif(($settings['badwords_releases'] ?? '') == "Y", "An", "Aus"); ?><br>
            </small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'); ?>">
            <textarea cols="60" rows="10" name="text"></textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" colspan="2" align="center">
            <input type="submit" value="Release hinzufuegen">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<?php
    }
} else {
    echo "Sie haben keine Berechtigung diese Seite zu sehen";
}
include("footer.inc.php");
?>
