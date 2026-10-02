<?php
/**
 * PowerDownload - Latest Releases Widget (Startseite)
 */
if (function_exists('pdl_show_dashboard_widgets') && !pdl_show_dashboard_widgets()) {
    return;
}
$top_count = max(1, (int)($settings['top_count'] ?? 5));
$release_res = $db_handler->sql_query("SELECT SUM(files.downloads) AS downloads, SUM(CASE WHEN files.mirror=0 THEN files.size ELSE 0 END) AS size, rel.* FROM " . $sql_table['files'] . " AS files, " . $sql_table['release'] . " AS rel WHERE rel.release_id=files.release_id AND rel.released='Y' GROUP BY rel.release_id ORDER BY rel.time DESC, rel.release_id DESC LIMIT 0," . $top_count);
echo pdl_render_release_widget($release_res, 'latest', 'Neueste Releases', 'Noch keine Releases.');
