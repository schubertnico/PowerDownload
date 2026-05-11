<?php
/**
 * PowerDownload - Search Module
 * @license MIT
 */

if (($settings['enable_search'] ?? 'N') == "Y") {
    if ($submit == 1) {
        $text = $db_handler->sql_escape_string($text ?? '');
        $in = $in ?? 'texttitel';

        $query = "SELECT * FROM " . $sql_table['release'] . " WHERE ";

        $stucke = explode(" ", $text);
        $conditions = [];
        $patterns = [];
        for ($i = 0; $i < count($stucke); $i++) {
            $escaped = $db_handler->sql_escape_string($stucke[$i]);
            if ($in == "text") {
                $conditions[] = "text LIKE '%" . $escaped . "%'";
            } elseif ($in == "titel") {
                $conditions[] = "name LIKE '%" . $escaped . "%'";
            } else {
                $conditions[] = "(name LIKE '%" . $escaped . "%" . "' OR text LIKE '%" . $escaped . "%')";
            }
            $patterns[$i] = "/" . preg_quote($stucke[$i], '/') . "/i";
        }

        $query .= implode(" OR ", $conditions);
        $query .= " AND released='Y'";

        $orderby = $db_handler->sql_escape_string($settings['orderby'] ?? 'name');
        $orderseq = ($settings['orderseq'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY " . $orderby . " " . $orderseq;

        if ($page < 1) $page = 1;
        $perpage = (int)($settings['perpage'] ?? 10);
        $temp1 = $page * $perpage - $perpage;
        $limit = $temp1 . "," . $perpage;
        $total = $db_handler->sql_num_rows($db_handler->sql_query($query));
        echo '<p class="mb-3">Ihre Suchanfrage ergab <strong>' . (int) $total . '</strong> Treffer.</p>';

        if ($total > 0) {
            $text_encoded = urlencode($text);
            $in_encoded = urlencode($in);
            echo '<div class="mb-3">' . seiten($total, $perpage, "&show_search=1&submit=1&text=" . $text_encoded . "&in=" . $in_encoded, $settings['script_file'] ?? '') . '</div>';
            echo "<form action=\"" . htmlspecialchars($settings['script_file'] ?? '') . "change_list=1\" method=\"post\">";

            // Wie in pdl_ordner.modul.php: DB-Templates nur verwenden, wenn
            // sinnvolle Platzhalter enthalten sind. Sonst Bootstrap-Fallback.
            $tpl_release_box_usable = isset($template['release_box']) && strpos((string) $template['release_box'], '{rows}') !== false;
            $tpl_release_row_usable = isset($template['release_row']) && (
                strpos((string) $template['release_row'], '{name}') !== false
                || strpos((string) $template['release_row'], '{id}') !== false
            );

            $release_rows = "";
            $release_data = [];
            $files_res = $db_handler->sql_query($query . " LIMIT " . $limit);
            while ($files_row = $db_handler->sql_fetch_array($files_res)) {
                $release_id_safe = $db_handler->sql_escape_int($files_row['release_id'] ?? 0);
                $size = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT SUM(size) AS tsize FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' AND mirror='0'"));
                $file_count = $db_handler->sql_num_rows($db_handler->sql_query("SELECT file_id FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' AND mirror='0'"));
                $files_row['size'] = $size['tsize'] ?? 0;
                $files_row['file_count'] = (int) $file_count;
                $files_row['id'] = $files_row['release_id'] ?? '';

                $files_row['name'] = stripslashes($files_row['name'] ?? '');
                $files_row['text'] = stripslashes($files_row['text'] ?? '');
                $files_row['text'] = preg_replace($patterns, "<b>$0</b>", $files_row['text']);
                $files_row['name'] = preg_replace($patterns, "<b>$0</b>", $files_row['name']);

                if (!($files_row['text'] ?? '')) {
                    $files_row['text'] = "N/A";
                } elseif (($settings['trenn_durch'] ?? '') == "zeichen") {
                    $files_row['text'] = str_replace($settings['trenn_string'] ?? '', "", $files_row['text']);
                    $trenn_zeichen = (int)($settings['trenn_zeichen'] ?? 100);
                    if (strlen($files_row['text']) > $trenn_zeichen) {
                        $files_row['text'] = substr($files_row['text'], 0, $trenn_zeichen) . "...";
                    }
                } elseif (($settings['trenn_durch'] ?? '') == "string") {
                    $text_parts = explode($settings['trenn_string'] ?? '', $files_row['text']);
                    $files_row['text'] = $text_parts[0];
                }
                if ($files_row['text'] != "N/A") {
                    $files_row['text'] = bbcode($files_row['text'], $settings['badwords_releases'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_releases'] ?? 'N');
                }

                if ($tpl_release_row_usable) {
                    $release_rows .= replace((string) $template['release_row'], $files_row);
                }
                $release_data[] = $files_row;
            }

            if ($tpl_release_box_usable && $tpl_release_row_usable) {
                echo replace((string) $template['release_box'], ['rows' => $release_rows]);
            } else {
                $sf_attr = htmlspecialchars((string) ($settings['script_file'] ?? 'downloads.php?'), ENT_QUOTES, 'UTF-8');
                echo '<section class="card pdl-card mb-4" aria-label="Such-Ergebnisse">';
                echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Such-Ergebnisse</h2></header>';
                echo '<ul class="list-group list-group-flush">';
                foreach ($release_data as $rrow) {
                    $rid = (int) $rrow['id'];
                    $rname = (string) $rrow['name'];
                    $rtext = (string) $rrow['text'];
                    $rsize = (int) $rrow['size'];
                    $rfiles = (int) $rrow['file_count'];
                    $size_human = $rsize > 0 ? size($rsize) : '–';
                    echo '<li class="list-group-item bg-transparent text-body">';
                    echo '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">';
                    echo '<div class="flex-grow-1">';
                    echo '<a class="link-light fw-bold" href="' . $sf_attr . 'release_id=' . $rid . '">' . $rname . '</a>';
                    if ($rtext !== '' && $rtext !== 'N/A') {
                        echo '<div class="form-text mb-0">' . $rtext . '</div>';
                    }
                    echo '</div>';
                    echo '<div class="text-end small text-muted">'
                        . $rfiles . ' ' . ($rfiles === 1 ? 'Datei' : 'Dateien')
                        . ' &middot; ' . htmlspecialchars($size_human)
                        . '</div>';
                    echo '</div></li>';
                }
                echo '</ul></section>';
            }
            echo '</form>';
        }
    } else {
        $script_file = htmlspecialchars($settings['script_file'] ?? '');
        ?>
<section class="card pdl-card mx-auto" style="max-width: 540px;">
    <header class="card-header pdl-card-header">
        <h2 class="h5 mb-0">Suche</h2>
    </header>
    <div class="card-body">
        <form action="<?php echo $script_file; ?>show_search=1&amp;submit=1" method="post" novalidate>
            <div class="mb-3">
                <label for="pdlSearchText" class="form-label">Suchbegriff</label>
                <input type="text" id="pdlSearchText" name="text" class="form-control" required aria-describedby="pdlSearchTextHelp">
                <div id="pdlSearchTextHelp" class="form-text">Einzelne Suchwoerter trennen durch Leerzeichen.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSearchIn" class="form-label">Suchen in</label>
                <select id="pdlSearchIn" name="in" class="form-select">
                    <option value="text">Beschreibung</option>
                    <option value="texttitel" selected>Beschreibung und Titel</option>
                    <option value="titel">Titel</option>
                </select>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Suche starten</button>
            </div>
        </form>
    </div>
</section>
        <?php
    }
} else {
    echo pdl_alert('info', 'Die Suche wurde global deaktiviert.');
}
