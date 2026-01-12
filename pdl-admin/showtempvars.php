<?php
include("header.inc.php");
if($user_rights['adminaccess'] == "Y")
 {
?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="3" align="center">
            <b>Templates</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>">
            <b>Variable</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>">
            <b>Beschreibung</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>">
            <b>Verwendung in</b>
          </td>
        </tr>
<?php
function variable($var,$text,$in)
 {
  $alt = alt_switch();
  echo "
        <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            " . htmlspecialchars($var) . "
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            " . htmlspecialchars($text) . "
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            " . htmlspecialchars($in) . "
          </td>
        </tr>
  ";
 }

variable("{script_file}","Wird durch die URL zum Script ersetzt. ? bzw & am ende werden automatisch erzeugt.","alle Templates");
variable("{header_bg}","Wird durch die Header Farbe die eingegeben wurde ersetzt.","alle Templates ausser Emails");
variable("{footer_bg}","Wird durch die Footer Farbe die eingegeben wurde ersetzt.","alle Templates ausser Emails");
variable("{table_border}","Wird durch die Rahmen Farbe die eingegeben wurde ersetzt.","alle Templates ausser Emails");
variable("{alt_1}","Wird durch die 1. Alternativ Farbe die eingegeben wurde ersetzt.","alle Templates ausser Emails");
variable("{alt_2}","Wird durch die 2. Alternativ Farbe die eingegeben wurde ersetzt.","alle Templates ausser Emails");
variable("{alt}","Wechselt die 1. und 2. Alternativfarbe ab.","Nur in \"Zeilen\"");
variable("{name}","Gibt den Namen der Datei/Release/Ordner aus.","Nur in \"Zeilen\" der Ordner/Release Uebersicht, den Tops und bei den Dateien.");
variable("{id}","Gibt die ID der jeweiligen Datei/Release/Ordner aus.","Nur in \"Zeilen\" der Ordner/Release Uebersicht, den Tops und bei den Dateien.");
variable("{titel}","Gibt den Titel eines Kommentares aus.","Nur beim Kommentar Design");
variable("{votes}","Gibt die Anzahl Bewertungen aus.","Bei der Releaseuebersicht, der Detailseite und den Tops.");
variable("{vote}","Gibt die Durchschnittliche Bewertung aus.","Bei der Releaseuebersicht, der Detailseite und den Tops.");
variable("{vote_form}","Gibt das Formular fuer die Bewertung aus.","Bei der Detailseite.");
variable("{size}","Gibt die Groesse Der Datei aus. Die Einheit wird automatisch gesetzt. Bei der Statistik gibt es die insgesamte Groesse aller Files aus.","Bei der Releaseuebersicht, der Statistik, den Files und bei einigen Tops.");
variable("{user}","Wird durch den Nick des Kommentators ersetzt.","Bei dem Kommentar Formular");
variable("{autor}","Wird durch den Nick des Kommentators bzw dem Autor einer Datei ersetzt.","Bei dem Kommentar Design, bei der Release Uebersicht und Detailseite.");
variable("{downloads}","Wird durch Anzahl der Downloads einer Datei ersetzt. Bei der Statistik gibt es insgesamt aus wieviele Dateien gedownloadet wurden.","Bei der Release Uebersicht, der Statistik, den Files und einigen Tops.");
variable("{views}","Wird durch Anzahl der Views auf die Detailseite ersetzt.","Bei der Release Uebersicht und der Detailseite.");
variable("{text}","Wird durch den Jeweiligen Text ersetzt.","Bei der Release Uebersicht, der Detailseite, der Ordneruebersicht und den Kommentaren.");
variable("{time}","Wird durch das Datum ersetzt.","Bei der Release Uebersicht, der Detailseite und den Kommentaren.");
variable("{screens}","Wird durch die Screenshots ersetzt.","Bei der Detailseite.");
variable("{dlspeed}","Wird durch die Downloadzeit ersetzt.","Bei den Files.");
variable("{uploader}","Wird durch den Uploader ersetzt.","Bei der Releaseuebersicht, der Detailseite und den Tops.");
variable("{count}","Wird durch die Nummer ersetzt.","Bei den Tops.");
variable("{files}","Wird durch die Anzahl Release in dem Ordner ersetzt oder durch die Anzahl insgesamt vorhandener Files bei der Statistik","Bei der Ordneruebersicht oder Statistik.");
variable("{subdirs}","Wird durch die Anzahl Subordner in dem Ordner ersetzt.","Bei der Ordneruebersicht.");
variable("{filename}","Wird durch den Dateinamen ersetzt.","Bei den Files.");
variable("{traffic}","Wird durch den Verursachten Traffic der Datei ersetzt oder bei der Statik durch den Gesamttraffic.","Bei den Files und der Statistik.");
variable("{durch_traffic}","Wird durch den durchschnittlichen Traffic am Tag ersetzt.","Nur bei der Statistik.");
variable("{durch_downloads}","Wird durch die durchschnittlichen Downloads am Tag ersetzt.","Nur bei der Statistik.");
?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="3">
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
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
