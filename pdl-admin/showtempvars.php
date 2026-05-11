<?php
include("header.inc.php");
if($user_rights['adminaccess'] == "Y")
 {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Nützliches'],
        ['title' => 'Template-Variablen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Template-Variablen</h1>';
?>
<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h5 mb-0">Verfügbare Template-Variablen</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Variable</th>
                    <th scope="col">Beschreibung</th>
                    <th scope="col">Verwendung in</th>
                </tr>
            </thead>
            <tbody>
<?php
function variable($var,$text,$in)
 {
  echo '<tr>'
      . '<td><code>' . htmlspecialchars($var) . '</code></td>'
      . '<td>' . htmlspecialchars($text) . '</td>'
      . '<td>' . htmlspecialchars($in) . '</td>'
      . '</tr>';
 }

variable("{script_file}","Wird durch die URL zum Script ersetzt. ? bzw & am Ende werden automatisch erzeugt.","alle Templates");
variable("{header_bg}","Wird durch die Header-Farbe ersetzt.","alle Templates ausser Emails");
variable("{footer_bg}","Wird durch die Footer-Farbe ersetzt.","alle Templates ausser Emails");
variable("{table_border}","Wird durch die Rahmen-Farbe ersetzt.","alle Templates ausser Emails");
variable("{alt_1}","Wird durch die 1. Alternativ-Farbe ersetzt.","alle Templates ausser Emails");
variable("{alt_2}","Wird durch die 2. Alternativ-Farbe ersetzt.","alle Templates ausser Emails");
variable("{alt}","Wechselt die 1. und 2. Alternativfarbe ab.","Nur in \"Zeilen\"");
variable("{name}","Gibt den Namen der Datei/Release/Ordner aus.","Nur in \"Zeilen\" der Ordner/Release-Übersicht, den Tops und bei den Dateien.");
variable("{id}","Gibt die ID der jeweiligen Datei/Release/Ordner aus.","Nur in \"Zeilen\" der Ordner/Release-Übersicht, den Tops und bei den Dateien.");
variable("{titel}","Gibt den Titel eines Kommentars aus.","Nur beim Kommentar-Design");
variable("{votes}","Gibt die Anzahl Bewertungen aus.","Bei der Release-Übersicht, der Detailseite und den Tops.");
variable("{vote}","Gibt die durchschnittliche Bewertung aus.","Bei der Release-Übersicht, der Detailseite und den Tops.");
variable("{vote_form}","Gibt das Formular für die Bewertung aus.","Bei der Detailseite.");
variable("{size}","Gibt die Größe der Datei aus. Die Einheit wird automatisch gesetzt. Bei der Statistik die Gesamtgröße aller Files.","Bei der Release-Übersicht, der Statistik, den Files und einigen Tops.");
variable("{user}","Wird durch den Nick des Kommentators ersetzt.","Bei dem Kommentar-Formular");
variable("{autor}","Wird durch den Nick des Kommentators bzw. dem Autor einer Datei ersetzt.","Beim Kommentar-Design, der Release-Übersicht und Detailseite.");
variable("{downloads}","Wird durch Anzahl der Downloads einer Datei ersetzt. Bei der Statistik die Gesamtanzahl.","Release-Übersicht, Statistik, Files und einige Tops.");
variable("{views}","Wird durch Anzahl der Views auf die Detailseite ersetzt.","Release-Übersicht und Detailseite.");
variable("{text}","Wird durch den jeweiligen Text ersetzt.","Release-Übersicht, Detailseite, Ordner-Übersicht und Kommentare.");
variable("{time}","Wird durch das Datum ersetzt.","Release-Übersicht, Detailseite und Kommentare.");
variable("{screens}","Wird durch die Screenshots ersetzt.","Bei der Detailseite.");
variable("{dlspeed}","Wird durch die Download-Zeit ersetzt.","Bei den Files.");
variable("{uploader}","Wird durch den Uploader ersetzt.","Release-Übersicht, Detailseite und Tops.");
variable("{count}","Wird durch die Nummer ersetzt.","Bei den Tops.");
variable("{files}","Wird durch die Anzahl Releases bzw. Files ersetzt.","Ordner-Übersicht oder Statistik.");
variable("{subdirs}","Wird durch die Anzahl Subordner in dem Ordner ersetzt.","Bei der Ordner-Übersicht.");
variable("{filename}","Wird durch den Dateinamen ersetzt.","Bei den Files.");
variable("{traffic}","Wird durch den verursachten Traffic ersetzt bzw. Gesamttraffic in der Statistik.","Files und Statistik.");
variable("{durch_traffic}","Wird durch den durchschnittlichen Traffic am Tag ersetzt.","Nur bei der Statistik.");
variable("{durch_downloads}","Wird durch die durchschnittlichen Downloads am Tag ersetzt.","Nur bei der Statistik.");
?>
            </tbody>
        </table>
    </div>
</section>
<?php
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
