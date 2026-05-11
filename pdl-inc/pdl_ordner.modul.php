<?php
/**
 * PowerDownload - Folder Module
 * @license MIT
 */

// Liefert eine kompakte, deutsche Bytegröße-Darstellung (z.B. "1,5 MB").
if (!function_exists('pdl_format_bytes_public')) {
    function pdl_format_bytes_public(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
        }
        return number_format($bytes / 1024 / 1024 / 1024, 2, ',', '.') . ' GB';
    }
}

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
    // Leerer Ordner: für normale Nutzer eine freundliche Erklärung,
    // für eingeloggte Admins zusätzlich Direkt-Links zum Anlegen.
    $is_admin = !empty($user_details) && (($user_rights['adminaccess'] ?? '') === 'Y');
    $can_add_release = $is_admin;
    $can_add_subdir = !empty($user_rights['adddirs']) && $user_rights['adddirs'] === 'Y';
    $ordner_id_int = (int) $ordner_id;
    echo '<section class="card pdl-card mb-4" role="status" aria-live="polite">';
    echo '<div class="card-body">';
    echo '<h2 class="h5">Dieser Ordner ist noch leer.</h2>';
    echo '<p class="mb-3">In diesem Ordner gibt es bisher keine Releases und keine Unter-Ordner.';
    if (!$is_admin) {
        echo ' Bitte schauen Sie später noch einmal vorbei oder wählen Sie im Menü einen anderen Bereich aus.';
    }
    echo '</p>';
    if ($is_admin) {
        echo '<p class="form-text mb-3">Hinweis (sichtbar nur für Admins): '
            . 'Eine Datei kann nur innerhalb eines Releases existieren. '
            . 'Bitte legen Sie zuerst ein Release an, anschließend können Sie Dateien oder Screenshots in dieses Release laden.</p>';
        echo '<div class="d-flex flex-wrap gap-2">';
        if ($can_add_release) {
            echo '<a class="btn btn-primary" href="pdl-admin/addrelease.php?ordner_id=' . $ordner_id_int . '">+ Release hier anlegen</a>';
        }
        if ($can_add_subdir) {
            echo '<a class="btn btn-outline-light" href="pdl-admin/adddir.php?ordner_id=' . $ordner_id_int . '">+ Unter-Ordner hier anlegen</a>';
        }
        echo '<a class="btn btn-outline-light" href="pdl-admin/or_list.php?ordner_id=' . $ordner_id_int . '">Zur Ordner-Übersicht im Admin</a>';
        echo '</div>';
    } else {
        // Hinweis-Link zur Hauptseite für normale Nutzer
        echo '<div class="d-flex flex-wrap gap-2">';
        echo '<a class="btn btn-outline-light" href="' . htmlspecialchars($settings['script_file'] ?? 'downloads.php?') . '">Zur Startseite</a>';
        echo '</div>';
    }
    echo '</div></section>';
} else {
    // Hilfs-Helper: Wird ein DB-Template als "brauchbar" angesehen?
    // Ein DB-Template muss mindestens einen `{rows}`- oder `{name}`-Platzhalter
    // enthalten; sonst greifen wir auf eine Bootstrap-Standardansicht zurück,
    // damit Releases auch ohne konfigurierte Templates sichtbar sind.
    $tpl_ordner_box_usable = isset($template['ordner_box']) && strpos((string) $template['ordner_box'], '{rows}') !== false;
    $tpl_ordner_row_usable = isset($template['ordner_row']) && (
        strpos((string) $template['ordner_row'], '{name}') !== false
        || strpos((string) $template['ordner_row'], '{id}') !== false
    );
    $tpl_release_box_usable = isset($template['release_box']) && strpos((string) $template['release_box'], '{rows}') !== false;
    $tpl_release_row_usable = isset($template['release_row']) && (
        strpos((string) $template['release_row'], '{name}') !== false
        || strpos((string) $template['release_row'], '{id}') !== false
    );

    $script_file_attr = htmlspecialchars($settings['script_file'] ?? 'downloads.php?', ENT_QUOTES, 'UTF-8');

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
            // Fallback: Bootstrap-Card-Liste.
            echo '<section class="card pdl-card mb-4" aria-label="Unter-Ordner">';
            echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Unter-Ordner</h2></header>';
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
                    . $ofiles . ' ' . ($ofiles === 1 ? 'Release' : 'Releases')
                    . ' &middot; ' . $osubs . ' Unter-' . ($osubs === 1 ? 'Ordner' : 'Ordner')
                    . '</div>';
                echo '</li>';
            }
            echo '</ul></section>';
        }
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

        echo '<nav class="d-flex justify-content-center my-3" aria-label="Seitenwahl">' . seiten($total, $perpage, "&ordner_id=" . $ordner_id, $settings['script_file'] ?? '') . '</nav>';
        echo '<form action="' . $script_file_attr . 'change_list=1" method="post">';

        // orderby: nur Whitelist erlauben (verhindert SQLi, ignoriert manipulierte Settings)
        $orderby_whitelist = ['name', 'time', 'views', 'votes', 'voted'];
        $orderby_candidate = (string)($_GET['orderby'] ?? $_POST['orderby'] ?? ($settings['orderby'] ?? 'name'));
        $orderby = in_array($orderby_candidate, $orderby_whitelist, true) ? $orderby_candidate : 'name';

        $orderseq_candidate = strtoupper((string)($_GET['orderseq'] ?? $_POST['orderseq'] ?? ($settings['orderseq'] ?? 'ASC')));
        $orderseq = $orderseq_candidate === 'DESC' ? 'DESC' : 'ASC';

        // Releases sammeln (Daten + Anzahl Dateien für Fallback-Anzeige).
        $release_data = [];
        $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id='" . $ordner_id_safe . "' AND released='Y' ORDER BY " . $orderby . " " . $orderseq . " LIMIT " . $limit);
        while ($files_row = $db_handler->sql_fetch_array($files_res)) {
            $release_id_safe = $db_handler->sql_escape_int($files_row['release_id'] ?? 0);
            $size = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT SUM(size) AS tsize FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' AND mirror='0'"));
            $file_count = $db_handler->sql_num_rows($db_handler->sql_query("SELECT file_id FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' AND mirror='0'"));
            $files_row['size'] = $size['tsize'] ?? 0;
            $files_row['file_count'] = (int) $file_count;
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
            $release_data[] = $files_row;
        }

        if ($tpl_release_box_usable && $tpl_release_row_usable) {
            $release_rows = "";
            foreach ($release_data as $rrow) {
                $release_rows .= replace((string) $template['release_row'], $rrow);
            }
            echo replace((string) $template['release_box'], ['rows' => $release_rows]);
        } else {
            // Fallback: Bootstrap-Card-Liste mit Name, Beschreibung, Größe,
            // Anzahl Dateien und Direkt-Link zum Release.
            echo '<section class="card pdl-card mb-4" aria-label="Releases in diesem Ordner">';
            echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Releases in diesem Ordner</h2></header>';
            echo '<ul class="list-group list-group-flush">';
            foreach ($release_data as $rrow) {
                $rid = (int) $rrow['id'];
                $rname = (string) $rrow['name'];
                $rtext = (string) $rrow['text'];
                $rsize = (int) $rrow['size'];
                $rfiles = (int) $rrow['file_count'];
                $size_human = $rsize > 0 ? pdl_format_bytes_public($rsize) : '–';
                echo '<li class="list-group-item bg-transparent text-body">';
                echo '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">';
                echo '<div class="flex-grow-1">';
                echo '<a class="link-light fw-bold fs-6" href="' . $script_file_attr . 'release_id=' . $rid . '">'
                    . htmlspecialchars($rname, ENT_QUOTES, 'UTF-8') . '</a>';
                if ($rtext !== '' && $rtext !== 'N/A') {
                    echo '<div class="form-text mb-0">' . $rtext . '</div>';
                }
                echo '</div>';
                echo '<div class="text-end small text-muted">'
                    . $rfiles . ' ' . ($rfiles === 1 ? 'Datei' : 'Dateien')
                    . ' &middot; ' . $size_human
                    . '</div>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</section>';
        }
        echo '</form>';
    }
}
