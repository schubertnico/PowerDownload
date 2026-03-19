<?php
/**
 * PowerDownload - Statistics Widget
 */
$files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files']);
$files = $db_handler->sql_num_rows($files_res);

$size = 0;
$traffic = 0;
$downloads = 0;

while ($files_row = $db_handler->sql_fetch_array($files_res)) {
    if (($files_row['mirror'] ?? 0) > 0) {
        $mirror_id = $db_handler->sql_escape_int($files_row['mirror']);
        $mirror_of = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE file_id='" . $mirror_id . "'"));
        $traffic += ($mirror_of['size'] ?? 0) * ($files_row['downloads'] ?? 0);
    } else {
        $size += $files_row['size'] ?? 0;
        $traffic += ($files_row['size'] ?? 0) * ($files_row['downloads'] ?? 0);
    }
    $downloads += $files_row['downloads'] ?? 0;
}

$installed = (int)($settings['installed'] ?? time());
$tage = max(1, (int)ceil((time() - $installed) / (3600 * 24)));

$durch_traffic = $traffic / $tage;
$durch_downloads = $downloads / $tage;

$size_formatted = size($size);
$traffic_formatted = size($traffic);
$durch_traffic_formatted = size($durch_traffic);
$durch_downloads = round($durch_downloads, 1);

$stats = str_replace("{files}", (string)$files, (string) ($template['stats'] ?? ''));
$stats = str_replace("{size}", $size_formatted, $stats);
$stats = str_replace("{downloads}", (string)$downloads, $stats);
$stats = str_replace("{traffic}", $traffic_formatted, $stats);
$stats = str_replace("{durch_downloads}", (string)$durch_downloads, $stats);
$stats = str_replace("{durch_traffic}", $durch_traffic_formatted, $stats);

echo replace($stats, []);
