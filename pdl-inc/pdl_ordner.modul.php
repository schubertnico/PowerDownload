<?php
/**
 * PowerDownload - Folder Module
 * @license MIT
 */

// Rechnet alle Subordner und Subfiles aus
if (!function_exists('sub')) {
function sub(int $ordner_id): void
{
    global $subfiles, $subdirs, $db_handler, $sql_table;
    $subfiles += $db_handler->sql_num_rows($db_handler->sql_query("SELECT release_id FROM " . $sql_table['release'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "' AND released='Y'"));
    $sordner_res = $db_handler->sql_query("SELECT ordner_id FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $db_handler->sql_escape_int($ordner_id) . "'");
    $subdirs += $db_handler->sql_num_rows($sordner_res);
    while ($sordner_row = $db_handler->sql_fetch_array($sordner_res)) {
        sub((int)($sordner_row['ordner_id'] ?? 0));
    }
}
} // end function_exists

$ordner_id_safe = $db_handler->sql_escape_int($ordner_id);
$files_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT release_id FROM " . $sql_table['release'] . " WHERE ordner_id='" . $ordner_id_safe . "' AND released='Y'"));
$ordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT ordner_id FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $ordner_id_safe . "'"));

if ($files_check == 0 && $ordner_check == 0) {
    echo "Dieser Ordner ist leer.";
} else {
    if ($ordner_check != 0) {
        $ordner_rows = "";
        $ordner_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $ordner_id_safe . "' ORDER BY name ASC");
        while ($ordner_row = $db_handler->sql_fetch_array($ordner_res)) {
            $subfiles = 0;
            $subdirs = 0;
            sub((int)($ordner_row['ordner_id'] ?? 0));
            $ordner_row['files'] = $subfiles;
            $ordner_row['subdirs'] = $subdirs;
            $ordner_row['id'] = $ordner_row['ordner_id'] ?? '';
            $ordner_row['name'] = stripslashes($ordner_row['name'] ?? '');
            $ordner_row['text'] = stripslashes($ordner_row['text'] ?? '');

            $ordner_rows .= replace($template['ordner_row'] ?? '', $ordner_row);
        }

        echo replace($template['ordner_box'] ?? '', ['rows' => $ordner_rows]);
    }
    if ($files_check != 0) {
        if ($page < 1) $page = 1;

        // perpage: URL hat Vorrang, dann Settings, Whitelist: 5..200
        $perpage_candidate = 0;
        if (isset($_GET['perpage']) || isset($_POST['perpage'])) {
            $perpage_candidate = (int)($_GET['perpage'] ?? $_POST['perpage'] ?? 0);
        }
        if ($perpage_candidate < 5 || $perpage_candidate > 200) {
            $perpage_candidate = (int)($settings['perpage'] ?? 10);
        }
        $perpage = $perpage_candidate > 0 ? $perpage_candidate : 10;
        $temp1 = $page * $perpage - $perpage;
        $limit = $temp1 . "," . $perpage;
        $total = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id='" . $ordner_id_safe . "'"));

        echo "<center>" . seiten($total, $perpage, "&ordner_id=" . $ordner_id, $settings['script_file'] ?? '') . "</center>";
        echo "<form action=\"" . htmlspecialchars($settings['script_file'] ?? '') . "change_list=1\" method=\"post\">";

        $release_rows = "";

        // orderby: nur Whitelist erlauben (verhindert SQLi, ignoriert manipulierte Settings)
        $orderby_whitelist = ['name', 'time', 'views', 'votes', 'voted'];
        $orderby_candidate = (string)($_GET['orderby'] ?? $_POST['orderby'] ?? ($settings['orderby'] ?? 'name'));
        $orderby = in_array($orderby_candidate, $orderby_whitelist, true) ? $orderby_candidate : 'name';

        $orderseq_candidate = strtoupper((string)($_GET['orderseq'] ?? $_POST['orderseq'] ?? ($settings['orderseq'] ?? 'ASC')));
        $orderseq = $orderseq_candidate === 'DESC' ? 'DESC' : 'ASC';

        $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id='" . $ordner_id_safe . "' AND released='Y' ORDER BY " . $orderby . " " . $orderseq . " LIMIT " . $limit);
        while ($files_row = $db_handler->sql_fetch_array($files_res)) {
            $release_id_safe = $db_handler->sql_escape_int($files_row['release_id'] ?? 0);
            $size = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT SUM(size) AS tsize FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' AND mirror='0'"));
            $files_row['size'] = $size['tsize'] ?? 0;
            $files_row['id'] = $files_row['release_id'] ?? '';

            $files_row['name'] = stripslashes($files_row['name'] ?? '');
            $files_row['text'] = stripslashes($files_row['text'] ?? '');
            if (!($files_row['text'] ?? '')) {
                $files_row['text'] = "N/A";
            } elseif (($settings['trenn_durch'] ?? '') == "zeichen") {
                $files_row['text'] = str_replace($settings['trenn_string'] ?? '', "", $files_row['text']);
                $trenn_zeichen = (int)($settings['trenn_zeichen'] ?? 100);
                if (strlen($files_row['text']) > $trenn_zeichen) {
                    $files_row['text'] = substr($files_row['text'], 0, $trenn_zeichen) . "...";
                }
            } elseif (($settings['trenn_durch'] ?? '') == "string") {
                $text = explode($settings['trenn_string'] ?? '', $files_row['text']);
                $files_row['text'] = $text[0];
            }
            if ($files_row['text'] != "N/A") {
                $files_row['text'] = bbcode($files_row['text'], $settings['badwords_releases'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_releases'] ?? 'N');
            }

            $release_rows .= replace($template['release_row'] ?? '', $files_row);
        }

        echo replace($template['release_box'] ?? '', ['rows' => $release_rows]) . "</form>";
    }
}
