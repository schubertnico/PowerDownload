<?php
/**
 * PowerDownload - Übersicht der Vorlagen-Platzhalter
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'templates')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('templates'));
    include("footer.inc.php");
    return;
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Vorlagen und Ersetzungen', 'href' => 'templates.php'],
    ['title' => 'Vorlagen-Platzhalter'],
]);
echo '<h1 class="h3 pdl-page-title">Vorlagen-Platzhalter</h1>';

/** @var list<array{0: string, 1: string, 2: string}> $placeholders Platzhalter, Bedeutung, wo verfügbar */
$placeholders = [
    ['{script_file}', 'Adresse der Download-Seite; „?“ bzw. „&“ am Ende ergänzt PowerDownload selbst.', 'alle Vorlagen'],
    ['{name}', 'Name des Releases, der Datei oder des Ordners.', 'Zeilen der Ordner- und Release-Listen, Bestenlisten, Dateien'],
    ['{id}', 'Nummer (ID) des Releases, der Datei oder des Ordners.', 'Zeilen der Ordner- und Release-Listen, Bestenlisten, Dateien'],
    ['{titel}', 'Titel eines Kommentars.', 'Kommentare'],
    ['{text}', 'Beschreibung bzw. Text.', 'Release-Liste, Detailseite, Ordnerliste, Kommentare'],
    ['{time}', 'Datum im Format aus den Einstellungen.', 'Release-Liste, Detailseite, Kommentare'],
    ['{autor}', 'Autor des Releases bzw. Verfasser des Kommentars.', 'Release-Liste, Detailseite, Kommentare'],
    ['{uploader}', 'Wer das Release angelegt hat.', 'Release-Liste, Detailseite, Bestenlisten'],
    ['{user}', 'Benutzername der angemeldeten Person.', 'Kommentarformular'],
    ['{votes}', 'Anzahl der Bewertungen.', 'Release-Liste, Detailseite, Bestenlisten'],
    ['{vote}', 'Durchschnittliche Bewertung.', 'Release-Liste, Detailseite, Bestenlisten'],
    ['{vote_form}', 'Formular zum Bewerten.', 'Detailseite'],
    ['{size}', 'Dateigröße mit passender Einheit; in der Statistik die Gesamtgröße aller Dateien.', 'Release-Liste, Statistik, Dateien, einige Bestenlisten'],
    ['{downloads}', 'Anzahl der Downloads; in der Statistik die Gesamtzahl.', 'Release-Liste, Statistik, Dateien, einige Bestenlisten'],
    ['{views}', 'Aufrufe der Detailseite.', 'Release-Liste, Detailseite'],
    ['{screens}', 'Screenshots als verlinkte Vorschaubilder.', 'Detailseite'],
    ['{dlspeed}', 'Geschätzte Downloadzeit (Geschwindigkeit aus den Einstellungen).', 'Dateien'],
    ['{filename}', 'Dateiname.', 'Dateien'],
    ['{traffic}', 'Übertragene Datenmenge (Größe × Downloads); in der Statistik die Gesamtmenge.', 'Dateien, Statistik'],
    ['{count}', 'Laufende Nummer (1, 2, 3 …).', 'Bestenlisten'],
    ['{rows}', 'Die zusammengesetzten Zeilen.', 'Rahmen-Vorlagen („Box“)'],
    ['{files}', 'Anzahl der Releases bzw. Dateien.', 'Ordnerliste, Statistik'],
    ['{subdirs}', 'Anzahl der Unterordner.', 'Ordnerliste'],
    ['{durch_traffic}', 'Durchschnittliche Datenmenge pro Tag.', 'Statistik'],
    ['{durch_downloads}', 'Durchschnittliche Downloads pro Tag.', 'Statistik'],
    ['{header_bg}', 'Farbe aus der Vorlage „header_bg“.', 'alle Vorlagen außer E-Mails'],
    ['{footer_bg}', 'Farbe aus der Vorlage „footer_bg“.', 'alle Vorlagen außer E-Mails'],
    ['{table_border}', 'Farbe aus der Vorlage „table_border“.', 'alle Vorlagen außer E-Mails'],
    ['{alt_1}', 'Farbe aus der Vorlage „alt_1“.', 'alle Vorlagen außer E-Mails'],
    ['{alt_2}', 'Farbe aus der Vorlage „alt_2“.', 'alle Vorlagen außer E-Mails'],
    ['{alt}', 'Wechselt von Zeile zu Zeile zwischen „alt_1“ und „alt_2“.', 'Zeilen-Vorlagen'],
];
?>
<p class="text-muted">Platzhalter schreiben Sie in geschweiften Klammern in eine Vorlage. PowerDownload ersetzt sie beim Anzeigen durch den passenden Wert. Nicht jeder Platzhalter ist in jeder Vorlage verfügbar.</p>
<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h5 mb-0">Verfügbare Platzhalter</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle" id="pdlTplVarTable">
            <thead>
                <tr>
                    <th scope="col">Platzhalter</th>
                    <th scope="col">Bedeutung</th>
                    <th scope="col">Verfügbar in</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($placeholders as $entry) {
                echo '<tr><td><code>' . htmlspecialchars($entry[0]) . '</code></td><td>' . htmlspecialchars($entry[1]) . '</td><td>' . htmlspecialchars($entry[2]) . '</td></tr>';
            } ?>
            </tbody>
        </table>
    </div>
</section>
<a class="btn btn-primary" href="templates.php">Zum Vorlagen-Editor</a>
<?php
include("footer.inc.php");
