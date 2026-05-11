<?php
include("header.inc.php");
if($user_rights['backup'] == "Y")
 {
  $submit = isset($_REQUEST['submit']) ? (int)$_REQUEST['submit'] : 0;

  if($submit == 1)
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System'],
        ['title' => 'Datenbank optimieren'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Datenbank optimieren</h1>';
    echo '<section class="card pdl-card mb-4">';
    echo '<header class="card-header"><h2 class="h5 mb-0">Optimierungs-Ergebnis</h2></header>';
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover mb-0 align-middle">';
    echo '<thead><tr><th scope="col">Tabelle</th><th scope="col" class="text-end">Größe</th><th scope="col" class="text-end">Ersparnis</th><th scope="col" class="text-end">Prozent</th></tr></thead>';
    echo '<tbody>';

    $tabellen = 0;
    $total_gespart = 0;
    $total_verbrauch = 0;
    $opt_res = $db_handler->sql_query("SHOW TABLE STATUS FROM " . $db_handler->sql_escape_string($config_sql_database));
    while($opt_row = $db_handler->sql_fetch_array($opt_res))
     {
      $tabellen++;
      $verbrauch = (int)$opt_row['Data_length'] + (int)$opt_row['Index_length'] + (int)$opt_row['Data_free'];
      $gespart = (int)$opt_row['Data_free'];
      $total_gespart += $gespart;
      $total_verbrauch += $verbrauch;
      $db_handler->sql_query("OPTIMIZE TABLE `" . $db_handler->sql_escape_string((string)$opt_row['Name']) . "`");
      $pct = $verbrauch > 0 ? number_format($gespart*100/$verbrauch,2,",",".") : '0,00';
      echo '<tr>';
      echo '<td>' . htmlspecialchars((string)$opt_row['Name']) . '</td>';
      echo '<td class="text-end">' . number_format($verbrauch/1024,2,",",".") . ' KB</td>';
      echo '<td class="text-end">' . number_format($gespart/1024,2,",",".") . ' KB</td>';
      echo '<td class="text-end">' . $pct . ' %</td>';
      echo '</tr>';
     }
    $total_pct = $total_verbrauch > 0 ? number_format($total_gespart*100/$total_verbrauch,2,",",".") : '0,00';
    echo '</tbody>';
    echo '<tfoot class="table-group-divider"><tr class="fw-bold">';
    echo '<th scope="row">Gesamt: ' . (int)$tabellen . '</th>';
    echo '<td class="text-end">' . number_format($total_verbrauch/1024,2,",",".") . ' KB</td>';
    echo '<td class="text-end">' . number_format($total_gespart/1024,2,",",".") . ' KB</td>';
    echo '<td class="text-end">' . $total_pct . ' %</td>';
    echo '</tr></tfoot>';
    echo '</table></div></section>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System'],
        ['title' => 'Datenbank optimieren'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Datenbank optimieren</h1>';
    echo makedialog(
        "Datenbank wirklich optimieren?",
        '<p class="mb-2">Wenn in einer Tabelle viele Speicher- und Loeschvorgaenge stattgefunden haben, wird unnoetiger Speicherplatz belegt.</p>'
        . '<p class="mb-2">Hier können Sie diesen freigeben.</p>'
        . '<p class="mb-0">Es wird empfohlen, die Optimierung jeden Monat zu wiederholen.</p>',
        "Ja, optimieren",
        "optimize.php"
    );
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
