<?php
/**
 * PowerDownload - Search Module
 *
 * - Suchbereich (Titel, Beschreibung, beides) wirkt, Whitelist.
 * - Alle Wörter müssen vorkommen; versteckte Releases erscheinen nie.
 * - Begriff und Bereich bleiben beim Blättern erhalten (GET-Parameter).
 * - Sortierung nur über die Whitelist aus pdl_release_order_sql().
 * - Hervorhebung UTF-8-sicher, ohne Tags oder Umlaute zu zerstören.
 *
 * @license MIT
 */

if (($settings['enable_search'] ?? 'N') != "Y") {
    echo pdl_alert('info', 'Die Suche ist deaktiviert.');
    return;
}

$search_in_options = [
    'text' => 'Beschreibung',
    'texttitel' => 'Beschreibung und Titel',
    'titel' => 'Titel',
];
$search_text = trim((string) ($text ?? ''));
$search_in = isset($search_in_options[(string) ($in ?? '')]) ? (string) $in : 'texttitel';
$script_file = htmlspecialchars((string) ($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8');

// Suchwörter: durch Leerraum getrennt, mindestens 2 Zeichen, höchstens 10 Wörter
$terms = [];
foreach (preg_split('/\s+/u', $search_text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
    if (mb_strlen($word, 'UTF-8') >= 2 && !in_array($word, $terms, true)) {
        $terms[] = $word;
    }
}
$terms = array_slice($terms, 0, 10);

// Suchformular (auch über den Ergebnissen, vorbefüllt)
$options_html = '';
foreach ($search_in_options as $value => $label) {
    $options_html .= '<option value="' . $value . '"' . ($value === $search_in ? ' selected' : '') . '>' . $label . '</option>';
}
?>
<section class="card pdl-card mb-4" id="pdlSearch">
    <header class="card-header pdl-card-header">
        <h2 class="h5 mb-0">Suche</h2>
    </header>
    <div class="card-body">
        <form action="<?php echo $script_file; ?>show_search=1&amp;submit=1" method="post" id="pdlSearchForm" class="row g-3 align-items-end" role="search" novalidate>
            <div class="col-12 col-md-6">
                <label for="pdlSearchText" class="form-label">Suchbegriff</label>
                <input type="text" id="pdlSearchText" name="text" class="form-control" value="<?php echo htmlspecialchars($search_text, ENT_QUOTES, 'UTF-8'); ?>" required aria-describedby="pdlSearchTextHelp">
            </div>
            <div class="col-12 col-md-3">
                <label for="pdlSearchIn" class="form-label">Suchen in</label>
                <select id="pdlSearchIn" name="in" class="form-select"><?php echo $options_html; ?></select>
            </div>
            <div class="col-12 col-md-3 d-grid">
                <button type="submit" class="btn btn-primary" id="pdlSearchSubmit">Suche starten</button>
            </div>
            <div id="pdlSearchTextHelp" class="form-text col-12 mt-1">Mehrere Suchwörter durch Leerzeichen trennen. Gefunden werden Releases, die alle Wörter enthalten.</div>
        </form>
    </div>
</section>
<?php

/**
 * Vom Header gesetzt; in Tests können die Variablen fehlen.
 *
 * @var int|null $submit
 * @var int|null $page
 */
if (($submit ?? 0) !== 1) {
    return;
}

if ($terms === []) {
    echo pdl_alert('warning', 'Bitte geben Sie einen Suchbegriff mit mindestens 2 Zeichen ein.');
    return;
}

$conditions = [];
foreach ($terms as $term) {
    $like = "'%" . $db_handler->sql_escape_string(addcslashes($term, '%_\\')) . "%'";
    if ($search_in === 'text') {
        $conditions[] = "text LIKE " . $like;
    } elseif ($search_in === 'titel') {
        $conditions[] = "name LIKE " . $like;
    } else {
        $conditions[] = "(name LIKE " . $like . " OR text LIKE " . $like . ")";
    }
}
$where = "(" . implode(" AND ", $conditions) . ") AND released='Y'";

$count_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT COUNT(*) AS c FROM " . $sql_table['release'] . " WHERE " . $where));
$total = (int) ($count_row['c'] ?? 0);

echo '<p class="mb-3" id="pdlSearchSummary" role="status">Ihre Suche nach „' . htmlspecialchars($search_text, ENT_QUOTES, 'UTF-8') . '“ ergab <strong>'
    . number_format($total, 0, ',', '.') . '</strong> Treffer.</p>';

if ($total === 0) {
    return;
}

$perpage = (int) ($settings['perpage'] ?? 10);
if ($perpage < 1 || $perpage > 200) {
    $perpage = 10;
}
$pages = (int) ceil($total / $perpage);
$page = max(1, min($page ?? 1, $pages));
$offset = ($page - 1) * $perpage;

$page_link = "&show_search=1&submit=1&text=" . urlencode($search_text) . "&in=" . urlencode($search_in);
$pagination = seiten($total, $perpage, $page_link, (string) ($settings['script_file'] ?? ''));

$order_sql = pdl_release_order_sql((string) ($settings['orderby'] ?? 'name'), (string) ($settings['orderseq'] ?? 'ASC'));
$files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE " . $where . $order_sql . " LIMIT " . $offset . "," . $perpage);

$tpl_release_box_usable = isset($template['release_box']) && str_contains((string) $template['release_box'], '{rows}') && !pdl_template_is_legacy((string) $template['release_box']);
$tpl_release_row_usable = isset($template['release_row']) && !pdl_template_is_legacy((string) $template['release_row']) && (
    str_contains((string) $template['release_row'], '{name}')
    || str_contains((string) $template['release_row'], '{id}')
);

$release_rows = "";
$release_data = [];
while ($files_row = $db_handler->sql_fetch_array($files_res)) {
    $release_id_safe = $db_handler->sql_escape_int($files_row['release_id'] ?? 0);
    $size = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT COALESCE(SUM(size),0) AS tsize, COUNT(*) AS cnt FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' AND mirror='0'"));
    $files_row['size'] = (int) ($size['tsize'] ?? 0);
    $files_row['file_count'] = (int) ($size['cnt'] ?? 0);
    $files_row['id'] = $files_row['release_id'] ?? '';

    $files_row['name'] = stripslashes((string) ($files_row['name'] ?? ''));
    $teaser = pdl_teaser(stripslashes((string) ($files_row['text'] ?? '')), $settings);
    $files_row['text'] = $teaser === '' ? 'N/A' : $teaser;
    $files_row['text_html'] = $teaser === ''
        ? ''
        : pdl_highlight(bbcode($teaser, $settings['badwords_releases'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_releases'] ?? 'N'), $terms);
    $files_row['name_html'] = pdl_highlight(htmlspecialchars($files_row['name'], ENT_QUOTES, 'UTF-8'), $terms);

    if ($tpl_release_row_usable) {
        $release_rows .= replace((string) $template['release_row'], $files_row);
    }
    $release_data[] = $files_row;
}

if ($pagination !== '') {
    echo '<nav class="pdl-pagination d-flex justify-content-center my-3" aria-label="Seitenwahl">' . $pagination . '</nav>';
}

if ($tpl_release_box_usable && $tpl_release_row_usable) {
    echo replace((string) $template['release_box'], ['rows' => $release_rows]);
} else {
    echo '<section class="card pdl-card mb-4" id="pdlSearchResults" aria-label="Suchergebnisse">';
    echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Suchergebnisse</h2></header>';
    echo '<ul class="list-group list-group-flush">';
    foreach ($release_data as $rrow) {
        $rid = (int) $rrow['id'];
        $rsize = $rrow['size'];
        $rfiles = $rrow['file_count'];
        echo '<li class="list-group-item bg-transparent text-body pdl-search-hit" data-release-id="' . $rid . '">';
        echo '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">';
        echo '<div class="flex-grow-1">';
        echo '<a class="link-light fw-bold" href="' . $script_file . 'release_id=' . $rid . '">' . $rrow['name_html'] . '</a>';
        if ($rrow['text_html'] !== '') {
            echo '<div class="form-text mb-0">' . $rrow['text_html'] . '</div>';
        }
        echo '</div>';
        echo '<div class="text-end small text-muted">'
            . pdl_count_label($rfiles, 'Datei', 'Dateien')
            . ' &middot; ' . htmlspecialchars($rsize > 0 ? size($rsize) : '–', ENT_QUOTES, 'UTF-8')
            . '</div>';
        echo '</div></li>';
    }
    echo '</ul></section>';
}

if ($pagination !== '') {
    echo '<nav class="pdl-pagination d-flex justify-content-center my-3" aria-label="Seitenwahl (unten)">' . $pagination . '</nav>';
}
