<?php
/**
 * PowerDownload - Statistics Module
 *
 * Versteckte Releases (released = 'N') erscheinen in keiner Liste.
 *
 * @license MIT
 */

$script_file = htmlspecialchars((string) ($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8');
$pdl_is_admin = (($user_rights['adminaccess'] ?? 'N') === 'Y');

/**
 * Helper: render eine generische Bootstrap-Top-Tabelle.
 *
 * @param string                            $title  Card-Titel.
 * @param array<int, string>                $cols   Spaltenköpfe.
 * @param array<int, array<int, string>>    $rows   Bereits formatierte Zellen (HTML erlaubt).
 */
$pdl_render_top_card = function (string $title, array $cols, array $rows): void {
    echo '<section class="card pdl-card mb-4">';
    echo '<header class="card-header pdl-card-header"><h2 class="h6 mb-0">'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2></header>';
    echo '<div class="card-body p-0">';
    echo '<div class="pdl-table-wrapper"><table class="table table-striped table-hover mb-0 align-middle"><thead><tr>';
    foreach ($cols as $col) {
        echo '<th scope="col">' . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . $cell . '</td>';
        }
        echo '</tr>';
    }
    if (empty($rows)) {
        echo '<tr><td colspan="' . count($cols) . '" class="text-center text-muted">Keine Daten verfügbar.</td></tr>';
    }
    echo '</tbody></table></div></div></section>';
};

$release_link = static function (array $row) use ($script_file): string {
    return '<a href="' . $script_file . 'release_id=' . (int) ($row['release_id'] ?? 0) . '">'
        . htmlspecialchars(stripslashes((string) ($row['name'] ?? '')), ENT_QUOTES, 'UTF-8') . '</a>';
};

$t_release = $sql_table['release'];
$t_files = $sql_table['files'];
$t_user = $sql_table['user'];
$t_comments = $sql_table['comments'];
$t_ordner = $sql_table['ordner'];
$t_ugroup = $sql_table['usergroup'];

echo '<div class="row g-4" id="pdlStats">';
echo '<div class="col-12 col-lg-6">';

if ($pdl_is_admin) {
    $tables = 0;
    $size = 0;
    $rows = 0;
    $tables_res = $db_handler->sql_query("SHOW TABLE STATUS");
    while ($tables_row = $db_handler->sql_fetch_array($tables_res)) {
        $tables++;
        $size += ($tables_row['Data_length'] ?? 0) + ($tables_row['Index_length'] ?? 0);
        $rows += $tables_row['Rows'] ?? 0;
    }
    $mysqlversion_row = $db_handler->sql_fetch_array($db_handler->sql_query("SHOW VARIABLES LIKE 'version'"));
    $mysqlversion = $mysqlversion_row[1] ?? $mysqlversion_row['Value'] ?? 'unbekannt';

    echo '<section class="card pdl-card mb-4">';
    echo '<header class="card-header pdl-card-header"><h2 class="h6 mb-0">Server und Datenbank</h2></header>';
    echo '<ul class="list-group list-group-flush">';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>Datenbank-Version</strong><span>' . htmlspecialchars((string) $mysqlversion, ENT_QUOTES, 'UTF-8') . '</span></li>';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>Datenbankgröße</strong><span>' . htmlspecialchars(size($size), ENT_QUOTES, 'UTF-8') . '</span></li>';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>Tabellen</strong><span>' . $tables . '</span></li>';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>Datensätze</strong><span>' . number_format((int) $rows, 0, ',', '.') . '</span></li>';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>Server-Software</strong><span>' . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'unbekannt', ENT_QUOTES, 'UTF-8') . '</span></li>';
    echo '</ul></section>';
} else {
    echo '<section class="card pdl-card mb-4">';
    echo '<header class="card-header pdl-card-header"><h2 class="h6 mb-0">Server und Datenbank</h2></header>';
    echo '<div class="card-body text-muted">Die Server-Statistik ist nur für Administratoren sichtbar.</div>';
    echo '</section>';
}

// Benutzer und Gruppen (ohne die Gruppe für nicht angemeldete Besucher)
$guest_group_id = (int)($settings['guest_group_id'] ?? 3);
$ugroup_rows = [];
$ugroup_res = $db_handler->sql_query("SELECT " . $t_ugroup . ".name AS ugroup_name, COUNT(" . $t_user . ".user_id) AS ugroup_user FROM " . $t_ugroup . ", " . $t_user . " WHERE " . $t_user . ".ugroup_id = " . $t_ugroup . ".ugroup_id AND " . $t_ugroup . ".ugroup_id != '" . $guest_group_id . "' GROUP BY " . $t_user . ".ugroup_id");
while ($ugroup_row = $db_handler->sql_fetch_array($ugroup_res)) {
    $ugroup_rows[] = [
        htmlspecialchars((string) ($ugroup_row['ugroup_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
        (string)(int)($ugroup_row['ugroup_user'] ?? 0),
    ];
}
$pdl_render_top_card('Benutzer und Gruppen', ['Benutzergruppe', 'Anzahl'], $ugroup_rows);

// Top 10 Kommentatoren (nur Kommentare zu öffentlichen Releases)
$user_rows = [];
$user_res = $db_handler->sql_query("SELECT " . $t_user . ".user_id, COUNT(" . $t_comments . ".comment_id) AS kommentare FROM " . $t_user . ", " . $t_comments . ", " . $t_release . " WHERE " . $t_user . ".user_id = " . $t_comments . ".user_id AND " . $t_release . ".release_id = " . $t_comments . ".release_id AND " . $t_release . ".released='Y' GROUP BY " . $t_user . ".user_id ORDER BY kommentare DESC LIMIT 0,10");
$count = 0;
while ($user_row = $db_handler->sql_fetch_array($user_res)) {
    $count++;
    $user_rows[] = [
        (string) $count,
        user((int)($user_row['user_id'] ?? 0)),
        (string)(int)($user_row['kommentare'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Kommentatoren', ['#', 'Benutzer', 'Kommentare'], $user_rows);

// Top 10 Uploader
$user_rows = [];
$user_res = $db_handler->sql_query("SELECT " . $t_user . ".user_id, COUNT(" . $t_release . ".release_id) AS releases FROM " . $t_user . ", " . $t_release . " WHERE " . $t_user . ".user_id = " . $t_release . ".uploader AND " . $t_release . ".released='Y' GROUP BY " . $t_user . ".user_id ORDER BY releases DESC LIMIT 0,10");
$count = 0;
while ($user_row = $db_handler->sql_fetch_array($user_res)) {
    $count++;
    $user_rows[] = [
        (string) $count,
        user((int)($user_row['user_id'] ?? 0)),
        (string)(int)($user_row['releases'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Uploader', ['#', 'Benutzer', 'Releases'], $user_rows);

// Top 10 Ordner
$ordner_rows = [];
$ordner_res = $db_handler->sql_query("SELECT " . $t_ordner . ".ordner_id, " . $t_ordner . ".name, COUNT(" . $t_release . ".release_id) AS releases FROM " . $t_ordner . ", " . $t_release . " WHERE " . $t_ordner . ".ordner_id = " . $t_release . ".ordner_id AND " . $t_release . ".released='Y' GROUP BY " . $t_release . ".ordner_id ORDER BY releases DESC LIMIT 0,10");
$count = 0;
while ($ordner_row = $db_handler->sql_fetch_array($ordner_res)) {
    $count++;
    $ordner_rows[] = [
        (string) $count,
        '<a href="' . $script_file . 'ordner_id=' . (int)($ordner_row['ordner_id'] ?? 0) . '">' . htmlspecialchars(stripslashes((string) ($ordner_row['name'] ?? '')), ENT_QUOTES, 'UTF-8') . '</a>',
        (string)(int)($ordner_row['releases'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Ordner', ['#', 'Name', 'Releases'], $ordner_rows);

echo '</div>';
echo '<div class="col-12 col-lg-6">';

// Top 10 Releases nach Größe (ohne Spiegel-Server, sonst zählen Dateien doppelt)
$release_rows_size = [];
$release_res = $db_handler->sql_query("SELECT " . $t_release . ".release_id, " . $t_release . ".name, SUM(" . $t_files . ".size) AS size FROM " . $t_release . ", " . $t_files . " WHERE " . $t_release . ".release_id = " . $t_files . ".release_id AND " . $t_release . ".released='Y' AND " . $t_files . ".mirror=0 GROUP BY " . $t_release . ".release_id ORDER BY size DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_rows_size[] = [
        (string) $count,
        $release_link($release_row),
        htmlspecialchars(size((int)($release_row['size'] ?? 0)), ENT_QUOTES, 'UTF-8'),
    ];
}
$pdl_render_top_card('Top 10 Releases nach Größe', ['#', 'Release', 'Größe'], $release_rows_size);

// Top 10 Releases nach Anzahl Dateien (ohne Spiegel-Server)
$release_rows_files = [];
$release_res = $db_handler->sql_query("SELECT " . $t_release . ".release_id, " . $t_release . ".name, COUNT(" . $t_files . ".file_id) AS files FROM " . $t_release . ", " . $t_files . " WHERE " . $t_release . ".release_id = " . $t_files . ".release_id AND " . $t_release . ".released='Y' AND " . $t_files . ".mirror=0 GROUP BY " . $t_release . ".release_id ORDER BY files DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_rows_files[] = [
        (string) $count,
        $release_link($release_row),
        (string)(int)($release_row['files'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Releases nach Dateien', ['#', 'Release', 'Dateien'], $release_rows_files);

// Top 10 Releases nach Kommentaren
$release_rows_comments = [];
$release_res = $db_handler->sql_query("SELECT " . $t_release . ".release_id, " . $t_release . ".name, COUNT(" . $t_comments . ".comment_id) AS comments FROM " . $t_release . ", " . $t_comments . " WHERE " . $t_release . ".release_id = " . $t_comments . ".release_id AND " . $t_release . ".released='Y' GROUP BY " . $t_release . ".release_id ORDER BY comments DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_rows_comments[] = [
        (string) $count,
        $release_link($release_row),
        (string)(int)($release_row['comments'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Releases nach Kommentaren', ['#', 'Release', 'Kommentare'], $release_rows_comments);

// Top 10 Releases nach Bewertungen
$release_rows_votes = [];
$release_res = $db_handler->sql_query("SELECT * FROM " . $t_release . " WHERE votes > 0 AND released='Y' ORDER BY votes DESC, name ASC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_rows_votes[] = [
        (string) $count,
        $release_link($release_row),
        (string)(int)($release_row['votes'] ?? 0),
        htmlspecialchars(pdl_format_vote((int) ($release_row['voted'] ?? 0), (int) ($release_row['votes'] ?? 0)), ENT_QUOTES, 'UTF-8') . '/10',
    ];
}
$pdl_render_top_card('Top 10 Releases nach Bewertungen', ['#', 'Release', 'Stimmen', 'Durchschnitt'], $release_rows_votes);

echo '</div></div>';
