<?
include("header.inc.php");
if($user_rights[adminaccess] == "Y")
 {
?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="3" align="center">
            <b>Templates</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>">
            <b>Variable</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>">
            <b>Beschreibung</b>
          </td>
          <td bgcolor="<? echo $template[header_bg]; ?>">
            <b>Verwendung in</b>
          </td>
        </tr>
<?
function variable($var,$text,$in)
 {
  $alt = alt_switch();
  echo "
        <tr>
          <td bgcolor=\"$alt\">
            $var
          </td>
          <td bgcolor=\"$alt\">
            $text
          </td>
          <td bgcolor=\"$alt\">
            $in
          </td>
        </tr>
  ";
 }

variable("{script_file}","Wird durch die URL zum Script ersetzt. ? bzw & am ende werden automatisch erzeugt.","alle Templates");
variable("{header_bg}","Wird durch die Header Farbe die eingegeben wurde ersetzt.","alle Templates außer Emails");
variable("{footer_bg}","Wird durch die Footer Farbe die eingegeben wurde ersetzt.","alle Templates außer Emails");
variable("{table_border}","Wird durch die Rahmen Farbe die eingegeben wurde ersetzt.","alle Templates außer Emails");
variable("{alt_1}","Wird durch die 1. Alternativ Farbe die eingegeben wurde ersetzt.","alle Templates außer Emails");
variable("{alt_2}","Wird durch die 2. Alternativ Farbe die eingegeben wurde ersetzt.","alle Templates außer Emails");
variable("{alt}","Wechselt die 1. und 2. Alternativfarbe ab.","Nur in \"Zeilen\"");
variable("{name}","Gibt den Namen der Datei/Release/Ordner aus.","Nur in \"Zeilen\" der Ordner/Release Übersicht, den Tops und bei den Dateien.");
variable("{id}","Gibt die ID der jeweiligen Datei/Release/Ordner aus.","Nur in \"Zeilen\" der Ordner/Release Übersicht, den Tops und bei den Dateien.");
variable("{titel}","Gibt den Titel eines Kommentares aus.","Nur beim Kommentar Design");
variable("{votes}","Gibt die Anzahl Bewertungen aus.","Bei der Releaseübersicht, der Detailseite und den Tops.");
variable("{vote}","Gibt die Durchschnittliche Bewertung aus.","Bei der Releaseübersicht, der Detailseite und den Tops.");
variable("{vote_form}","Gibt das Formular für die Bewertung aus.","Bei der Detailseite.");
variable("{size}","Gibt die Größe Der Datei aus. Die Einheit wird automatisch gesetzt. Bei der Statistik gibt es die insgesamte Größe aller Files aus.","Bei der Releaseübersicht, der Statistik, den Files und bei einigen Tops.");
variable("{user}","Wird durch den Nick des Kommentators ersetzt.","Bei dem Kommentar Formular");
variable("{autor}","Wird durch den Nick des Kommentators bzw dem Autor einer Datei ersetzt.","Bei dem Kommentar Design, bei der Release Übersicht und Detailseite.");
variable("{downloads}","Wird durch Anzahl der Downloads einer Datei ersetzt. Bei der Statistik gibt es insgesamt aus wieviele Dateien gedownloadet wurden.","Bei der Release Übersicht, der Statistik, den Files und einigen Tops.");
variable("{views}","Wird durch Anzahl der Views auf die Detailseite ersetzt.","Bei der Release Übersicht und der Detailseite.");
variable("{text}","Wird durch den Jeweiligen Text ersetzt.","Bei der Release Übersicht, der Detailseite, der Ordnerübersicht und den Kommentaren.");
variable("{time}","Wird durch das Datum ersetzt.","Bei der Release Übersicht, der Detailseite und den Kommentaren.");
variable("{screens}","Wird durch die Screenshots ersetzt.","Bei der Detailseite.");
variable("{dlspeed}","Wird durch die Downloadzeit ersetzt.","Bei den Files.");
variable("{uploader}","Wird durch den Uploader ersetzt.","Bei der Releaseübersicht, der Detailseite und den Tops.");
variable("{count}","Wird durch die Nummer ersetzt.","Bei den Tops.");
variable("{files}","Wird durch die Anzahl Release in dem Ordner ersetzt oder durch die Anzahl insgesamt vorhandener Files bei der Statistik","Bei der Ordnerübersicht oder Statistik.");
variable("{subdirs}","Wird durch die Anzahl Subordner in dem Ordner ersetzt.","Bei der Ordnerübersicht.");
variable("{filename}","Wird durch den Dateinamen ersetzt.","Bei den Files.");
variable("{traffic}","Wird durch den Verursachten Traffic der Datei ersetzt oder bei der Statik durch den Gesamttraffic.","Bei den Files und der Statistik.");
variable("{durch_traffic}","Wird durch den durchschnittlichen Traffic am Tag ersetzt.","Nur bei der Statistik.");
variable("{durch_downloads}","Wird durch die durchschnittlichen Downloads am Tag ersetzt.","Nur bei der Statistik.");
?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="3">
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
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
