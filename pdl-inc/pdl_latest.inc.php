<?php
/**
 * PowerDownload - Latest Downloads Widget
 */
$top_count = (int)($settings['top_count'] ?? 5);
$release_res = $db_handler->sql_query("SELECT SUM(downloads) AS downloads, SUM(size) AS size, rel.* FROM " . $sql_table['files'] . " AS files, " . $sql_table['release'] . " AS rel WHERE rel.release_id=files.release_id AND rel.released='Y' GROUP BY rel.release_id ORDER BY rel.time DESC LIMIT 0," . $top_count);
$count = 0;
$total = $db_handler->sql_num_rows($release_res);

$usetext = false;
if (preg_match("/\{text\}/", $template['latest_row'] ?? '')) $usetext = true;

$release_rows = "";
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_row['count'] = $count;
    $release_row['id'] = $release_row['release_id'];
    $release_row['name'] = $release_row['name'] ?? '';
    $shortname = (int)($settings['shortname'] ?? 0);
    if ($shortname > 0 && strlen($release_row['name']) > $shortname) {
        $release_row['name'] = substr($release_row['name'], 0, $shortname - 3) . "...";
    }

    if ($usetext) {
        $release_row['text'] = $release_row['text'] ?? '';
        if (!$release_row['text']) {
            $release_row['text'] = "N/A";
        } elseif (($settings['trenn_durch'] ?? '') == "zeichen") {
            $release_row['text'] = str_replace($settings['trenn_string'] ?? '', "", $release_row['text']);
            $trenn_zeichen = (int)($settings['trenn_zeichen'] ?? 100);
            if (strlen($release_row['text']) > $trenn_zeichen) {
                $release_row['text'] = substr($release_row['text'], 0, $trenn_zeichen) . "...";
            }
        } elseif (($settings['trenn_durch'] ?? '') == "string") {
            $text = explode($settings['trenn_string'] ?? '', $release_row['text']);
            $release_row['text'] = $text[0];
        }
        if ($release_row['text'] != "N/A") {
            $release_row['text'] = bbcode($release_row['text'], $settings['badwords_releases'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_releases'] ?? 'N');
        }
    }
    $release_rows .= replace($template['latest_row'] ?? '', $release_row);
}

echo replace($template['latest_box'] ?? '', ['rows' => $release_rows]);
