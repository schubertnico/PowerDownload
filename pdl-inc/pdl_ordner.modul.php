<?php
/**
 * PowerDownload - Folder Module
 * @license MIT
 */

// Rechnet alle Unterordner und Releases eines Ordners aus (rekursiv,
// mit Tiefenbegrenzung gegen versehentliche Ordner-Kreise).
if (!function_exists('sub')) {
function sub(int $ordner_id, int $depth = 0): void
{
    global $subfiles, $subdirs, $db_handler, $sql_table;
    if ($depth > 50) {
        return;
    }
    $subfiles += $db_handler->sql_num_rows($db_handler->sql_query("SELECT release_id FROM " . $sql_table['release'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "' AND released='Y'"));
    $sordner_res = $db_handler->sql_query("SELECT ordner_id FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $db_handler->sql_escape_int($ordner_id) . "'");
    $subdirs += $db_handler->sql_num_rows($sordner_res);
    while ($sordner_row = $db_handler->sql_fetch_array($sordner_res)) {
        sub((int)($sordner_row['ordner_id'] ?? 0), $depth + 1);
    }
}
} // end function_exists

/**
 * Vom Header gesetzt; in Tests kann die Variable fehlen.
 *
 * @var int|null $ordner_id
 */
$ordner_id ??= 0;
$ordner_id_safe = $db_handler->sql_escape_int($ordner_id);
$script_file_attr = htmlspecialchars((string) ($settings['script_file'] ?? 'downloads.php?'), ENT_QUOTES, 'UTF-8');

// Unbekannte Ordner-ID: "Ordner nicht gefunden" statt "Dieser Ordner ist noch leer."
if ($ordner_id > 0) {
    if (!isset($pdl_current_ordner)) {
        $pdl_current_ordner = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT ordner_id, name FROM " . $sql_table['ordner'] . " WHERE ordner_id='" . $ordner_id_safe . "'")) ?? false;
    }
    if ($pdl_current_ordner === false) {
        if (!headers_sent()) {
            http_response_code(404);
        }
        echo '<section class="card pdl-card mb-4" id="pdlOrdnerNotFound" role="alert"><div class="card-body">'
            . '<h2 class="h5">Ordner nicht gefunden</h2>'
            . '<p class="mb-3">Diesen Ordner gibt es nicht (mehr). Bitte wählen Sie einen Ordner auf der Startseite.</p>'
            . '<a class="btn btn-outline-light" href="' . $script_file_attr . '">Zur Startseite</a>'
            . '</div></section>';
        return;
    }
}

$files_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT release_id FROM " . $sql_table['release'] . " WHERE ordner_id='" . $ordner_id_safe . "' AND released='Y'"));
$ordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT ordner_id FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $ordner_id_safe . "'"));

if ($files_check == 0 && $ordner_check == 0) {
    // Leerer Ordner: für normale Besucher eine freundliche Erklärung,
    // für angemeldete Admins zusätzlich Direkt-Links zum Anlegen.
    $is_admin = !empty($user_details) && (($user_rights['adminaccess'] ?? '') === 'Y');
    $can_add_release = $is_admin && (($user_rights['addfiles'] ?? '') === 'Y');
    $can_add_subdir = !empty($user_rights['adddirs']) && $user_rights['adddirs'] === 'Y';
    echo '<section class="card pdl-card mb-4" role="status" aria-live="polite" id="pdlOrdnerEmpty">';
    echo '<div class="card-body">';
    echo '<h2 class="h5">Dieser Ordner ist noch leer.</h2>';
    echo '<p class="mb-3">In diesem Ordner gibt es bisher keine Releases und keine Unterordner.';
    if (!$is_admin) {
        echo ' Bitte schauen Sie später noch einmal vorbei oder wählen Sie im Menü einen anderen Bereich aus.';
    }
    echo '</p>';
    if ($is_admin) {
        echo '<p class="form-text mb-3">Hinweis (nur für Admins sichtbar): '
            . 'Eine Datei gehört immer zu einem Release. '
            . 'Bitte legen Sie zuerst ein Release an, anschließend können Sie Dateien oder Screenshots in dieses Release laden.</p>';
        echo '<div class="d-flex flex-wrap gap-2">';
        if ($can_add_release) {
            echo '<a class="btn btn-primary" href="pdl-admin/addrelease.php?ordner_id=' . $ordner_id . '">+ Release hier anlegen</a>';
        }
        if ($can_add_subdir) {
            echo '<a class="btn btn-outline-light" href="pdl-admin/adddir.php?ordner_id=' . $ordner_id . '">+ Unterordner hier anlegen</a>';
        }
        echo '<a class="btn btn-outline-light" href="pdl-admin/or_list.php?ordner_id=' . $ordner_id . '">Zur Ordnerübersicht im Adminbereich</a>';
        echo '</div>';
    } else {
        echo '<div class="d-flex flex-wrap gap-2">';
        echo '<a class="btn btn-outline-light" href="' . $script_file_attr . '">Zur Startseite</a>';
        echo '</div>';
    }
    echo '</div></section>';
} else {
    // Eigene DB-Vorlagen nur verwenden, wenn sie sinnvolle Platzhalter
    // enthalten und keine alten Tabellen-Vorlagen sind; sonst Bootstrap-Ansicht.
    $tpl_ordner_box_usable = isset($template['ordner_box']) && str_contains((string) $template['ordner_box'], '{rows}') && !pdl_template_is_legacy((string) $template['ordner_box']);
    $tpl_ordner_row_usable = isset($template['ordner_row']) && !pdl_template_is_legacy((string) $template['ordner_row']) && (
        str_contains((string) $template['ordner_row'], '{name}')
        || str_contains((string) $template['ordner_row'], '{id}')
    );
    $tpl_release_box_usable = isset($template['release_box']) && str_contains((string) $template['release_box'], '{rows}') && !pdl_template_is_legacy((string) $template['release_box']);
    $tpl_release_row_usable = isset($template['release_row']) && !pdl_template_is_legacy((string) $template['release_row']) && (
        str_contains((string) $template['release_row'], '{name}')
        || str_contains((string) $template['release_row'], '{id}')
    );

    if ($ordner_check != 0) {
        $ordner_data = [];
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
            $ordner_data[] = $ordner_row;
        }

        if ($tpl_ordner_box_usable && $tpl_ordner_row_usable) {
            $ordner_rows = "";
            foreach ($ordner_data as $ordner_row) {
                $ordner_rows .= replace((string) $template['ordner_row'], $ordner_row);
            }
            echo replace((string) $template['ordner_box'], ['rows' => $ordner_rows]);
        } else {
            echo '<section class="card pdl-card mb-4" aria-label="Unterordner" id="pdlSubfolders">';
            echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Unterordner</h2></header>';
            echo '<ul class="list-group list-group-flush">';
            foreach ($ordner_data as $ordner_row) {
                $oid = (int) $ordner_row['id'];
                $oname = (string) $ordner_row['name'];
                $otext = (string) $ordner_row['text'];
                $ofiles = (int) $ordner_row['files'];
                $osubs = (int) $ordner_row['subdirs'];
                echo '<li class="list-group-item bg-transparent text-body d-flex justify-content-between align-items-start flex-wrap gap-2">';
                echo '<div>';
                echo '<a class="link-light fw-bold" href="' . $script_file_attr . 'ordner_id=' . $oid . '">'
                    . '<img src="pdl-gfx/folder.gif" alt="" class="me-2"> '
                    . htmlspecialchars($oname, ENT_QUOTES, 'UTF-8') . '</a>';
                if ($otext !== '') {
                    echo '<div class="form-text mb-0">' . htmlspecialchars($otext, ENT_QUOTES, 'UTF-8') . '</div>';
                }
                echo '</div>';
                echo '<div class="text-end small text-muted">'
                    . pdl_count_label($ofiles, 'Release', 'Releases')
                    . ' &middot; ' . pdl_count_label($osubs, 'Unterordner', 'Unterordner')
                    . '</div>';
                echo '</li>';
            }
            echo '</ul></section>';
        }
    }

    if ($files_check != 0) {
        if ($page < 1) $page = 1;

        // perpage: URL hat Vorrang, dann Einstellung bzw. Cookie, erlaubt 5..200
        $perpage_candidate = 0;
        if (isset($_GET['perpage']) || isset($_POST['perpage'])) {
            $perpage_raw = $_GET['perpage'] ?? $_POST['perpage'] ?? 0;
            $perpage_candidate = is_scalar($perpage_raw) ? (int) $perpage_raw : 0;
        }
        if ($perpage_candidate < 5 || $perpage_candidate > 200) {
            $perpage_candidate = (int)($settings['perpage'] ?? 10);
        }
        $perpage = $perpage_candidate > 0 && $perpage_candidate <= 200 ? $perpage_candidate : 10;

        // Blättern zählt nur sichtbare (veröffentlichte) Releases
        $total = $files_check;
        $pages = max(1, (int) ceil($total / $perpage));
        if ($page > $pages) $page = $pages;
        $limit = (($page - 1) * $perpage) . "," . $perpage;

        $pagination = seiten($total, $perpage, "&ordner_id=" . $ordner_id, (string) ($settings['script_file'] ?? ''));
        if ($pagination !== '') {
            echo '<nav class="pdl-pagination d-flex justify-content-center my-3" aria-label="Seitenwahl">' . $pagination . '</nav>';
        }

        // Sortierung nur über die Whitelist (URL, sonst Einstellung bzw. Cookie)
        $orderby_raw = $_GET['orderby'] ?? $_POST['orderby'] ?? ($settings['orderby'] ?? 'name');
        $orderseq_raw = $_GET['orderseq'] ?? $_POST['orderseq'] ?? ($settings['orderseq'] ?? 'ASC');
        $order_sql = pdl_release_order_sql(is_string($orderby_raw) ? $orderby_raw : 'name', is_string($orderseq_raw) ? $orderseq_raw : 'ASC');

        $release_data = [];
        $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id='" . $ordner_id_safe . "' AND released='Y'" . $order_sql . " LIMIT " . $limit);
        while ($files_row = $db_handler->sql_fetch_array($files_res)) {
            $release_id_safe = $db_handler->sql_escape_int($files_row['release_id'] ?? 0);
            $size = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT COALESCE(SUM(size),0) AS tsize, COUNT(*) AS cnt FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' AND mirror='0'"));
            $files_row['size'] = (int) ($size['tsize'] ?? 0);
            $files_row['file_count'] = (int) ($size['cnt'] ?? 0);
            $files_row['id'] = $files_row['release_id'] ?? '';

            $files_row['name'] = stripslashes($files_row['name'] ?? '');
            $teaser = pdl_teaser(stripslashes((string) ($files_row['text'] ?? '')), $settings);
            if ($teaser === '') {
                $files_row['text'] = "N/A";
            } else {
                $files_row['text'] = bbcode($teaser, $settings['badwords_releases'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_releases'] ?? 'N');
            }
            $release_data[] = $files_row;
        }

        if ($tpl_release_box_usable && $tpl_release_row_usable) {
            $release_rows = "";
            foreach ($release_data as $rrow) {
                $release_rows .= replace((string) $template['release_row'], $rrow);
            }
            echo replace((string) $template['release_box'], ['rows' => $release_rows]);
        } else {
            // Bootstrap-Liste mit Name, Kurzbeschreibung, Größe und Anzahl Dateien.
            echo '<section class="card pdl-card mb-4" aria-label="Releases in diesem Ordner" id="pdlReleaseList">';
            echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Releases in diesem Ordner</h2></header>';
            echo '<ul class="list-group list-group-flush">';
            foreach ($release_data as $rrow) {
                $rid = (int) $rrow['id'];
                $rname = (string) $rrow['name'];
                $rtext = (string) $rrow['text'];
                $rsize = (int) $rrow['size'];
                $rfiles = (int) $rrow['file_count'];
                $size_human = $rsize > 0 ? size($rsize) : '–';
                echo '<li class="list-group-item bg-transparent text-body" data-release-id="' . $rid . '">';
                echo '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">';
                echo '<div class="flex-grow-1">';
                echo '<a class="link-light fw-bold fs-6" href="' . $script_file_attr . 'release_id=' . $rid . '">'
                    . htmlspecialchars($rname, ENT_QUOTES, 'UTF-8') . '</a>';
                if ($rtext !== '' && $rtext !== 'N/A') {
                    echo '<div class="form-text mb-0">' . $rtext . '</div>';
                }
                echo '</div>';
                echo '<div class="text-end small text-muted">'
                    . pdl_count_label($rfiles, 'Datei', 'Dateien')
                    . ' &middot; ' . htmlspecialchars($size_human, ENT_QUOTES, 'UTF-8')
                    . '</div>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</section>';
        }

        if ($pagination !== '') {
            echo '<nav class="pdl-pagination d-flex justify-content-center my-3" aria-label="Seitenwahl (unten)">' . $pagination . '</nav>';
        }
    }
}
