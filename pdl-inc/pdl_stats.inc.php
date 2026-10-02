<?php
/**
 * PowerDownload - Statistics Widget (Startseite)
 *
 * Zählt nur Dateien öffentlicher Releases. Spiegel-Server zählen bei
 * Downloads und Traffic mit, nicht bei Anzahl und Gesamtgröße.
 */
if (function_exists('pdl_show_dashboard_widgets') && !pdl_show_dashboard_widgets()) {
    return;
}
$files_res = $db_handler->sql_query(
    "SELECT f.file_id, f.size, f.downloads, f.mirror FROM " . $sql_table['files'] . " AS f"
    . " JOIN " . $sql_table['release'] . " AS r ON r.release_id = f.release_id"
    . " WHERE r.released='Y'"
);

$stat_rows = [];
$stat_sizes = [];
while ($files_row = $db_handler->sql_fetch_array($files_res)) {
    $stat_rows[] = $files_row;
    $stat_sizes[(int) ($files_row['file_id'] ?? 0)] = (int) ($files_row['size'] ?? 0);
}

$files = 0;
$size = 0;
$traffic = 0;
$downloads = 0;
foreach ($stat_rows as $files_row) {
    $mirror_id = (int) ($files_row['mirror'] ?? 0);
    if ($mirror_id > 0) {
        if (!array_key_exists($mirror_id, $stat_sizes)) {
            $mirror_of = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT size FROM " . $sql_table['files'] . " WHERE file_id='" . $db_handler->sql_escape_int($mirror_id) . "'"));
            $stat_sizes[$mirror_id] = (int) ($mirror_of['size'] ?? 0);
        }
        $traffic += $stat_sizes[$mirror_id] * (int) ($files_row['downloads'] ?? 0);
    } else {
        $files++;
        $size += (int) ($files_row['size'] ?? 0);
        $traffic += (int) ($files_row['size'] ?? 0) * (int) ($files_row['downloads'] ?? 0);
    }
    $downloads += (int) ($files_row['downloads'] ?? 0);
}

// Tage seit der Installation. Ist "installed" nicht gesetzt (ältere
// Installationen), zählt das Datum des ältesten Releases.
$installed = (int) ($settings['installed'] ?? 0);
if ($installed <= 0) {
    $oldest = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT MIN(time) AS t FROM " . $sql_table['release'] . " WHERE time > 0"));
    $installed = (int) ($oldest['t'] ?? 0);
}
if ($installed <= 0 || $installed > time()) {
    $installed = time();
}
$tage = max(1, (int) ceil((time() - $installed) / 86400));

$durch_downloads = number_format($downloads / $tage, 1, ',', '.');
if (str_ends_with($durch_downloads, ',0')) {
    $durch_downloads = substr($durch_downloads, 0, -2);
}

echo '<section class="card pdl-card h-100" id="pdlWidgetStats"><header class="card-header pdl-card-header"><h2 class="h6 mb-0">Statistik</h2></header><div class="card-body p-2 small">';
if ($files === 0) {
    echo '<p class="text-muted small mb-0 px-1">Noch keine Dateien.</p>';
} else {
    $stats = str_replace(
        ['{files}', '{size}', '{downloads}', '{traffic}', '{durch_downloads}', '{durch_traffic}'],
        [
            number_format($files, 0, ',', '.'),
            size($size),
            number_format($downloads, 0, ',', '.'),
            size($traffic),
            $durch_downloads,
            size($traffic / $tage),
        ],
        pdl_template('stats')
    );
    echo replace($stats, []);
}
echo '</div></section>';
