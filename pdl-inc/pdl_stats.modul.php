<?php
/**
 * PowerDownload - Statistics Module
 * @license MIT
 */

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
$mysqlversion = $mysqlversion_row[1] ?? $mysqlversion_row['Value'] ?? 'Unknown';
$script_file = htmlspecialchars($settings['script_file'] ?? '');

/**
 * Helper: render eine generische Bootstrap-Top-Tabelle.
 *
 * @param string                            $title  Card-Titel.
 * @param array<int, string>                $cols   Spaltenkoepfe.
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

$pdl_is_admin = (($user_rights['adminaccess'] ?? 'N') === 'Y');

echo '<div class="row g-4">';
echo '<div class="col-12 col-lg-6">';

if ($pdl_is_admin) {
    echo '<section class="card pdl-card mb-4">';
    echo '<header class="card-header pdl-card-header"><h2 class="h6 mb-0">Server &amp; DB Stats</h2></header>';
    echo '<ul class="list-group list-group-flush">';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>DB Version</strong><span>' . htmlspecialchars((string) $mysqlversion) . '</span></li>';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>DB Größe</strong><span>' . htmlspecialchars((string) round($size / 1024 / 1024, 2)) . ' MB</span></li>';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>Tabellen in der DB</strong><span>' . (int) $tables . '</span></li>';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>DB Einträge</strong><span>' . (int) $rows . '</span></li>';
    echo '<li class="list-group-item d-flex justify-content-between bg-transparent text-body"><strong>Server Software</strong><span>' . htmlspecialchars((string)($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown')) . '</span></li>';
    echo '</ul></section>';
} else {
    echo '<section class="card pdl-card mb-4">';
    echo '<header class="card-header pdl-card-header"><h2 class="h6 mb-0">Server &amp; DB Stats</h2></header>';
    echo '<div class="card-body text-muted">Server-Statistik ist nur für Administratoren sichtbar.</div>';
    echo '</section>';
}

// User & Gruppen
$ugroup_rows = [];
$ugroup_res = $db_handler->sql_query("SELECT " . $sql_table['usergroup'] . ".name AS ugroup_name, COUNT(" . $sql_table['user'] . ".user_id) AS ugroup_user FROM " . $sql_table['usergroup'] . ", " . $sql_table['user'] . " WHERE " . $sql_table['user'] . ".ugroup_id = " . $sql_table['usergroup'] . ".ugroup_id AND " . $sql_table['usergroup'] . ".ugroup_id != '3' GROUP BY " . $sql_table['user'] . ".ugroup_id");
while ($ugroup_row = $db_handler->sql_fetch_array($ugroup_res)) {
    $ugroup_rows[] = [
        htmlspecialchars($ugroup_row['ugroup_name'] ?? ''),
        (string)(int)($ugroup_row['ugroup_user'] ?? 0),
    ];
}
$pdl_render_top_card('User & Gruppen', ['Usergruppe', 'Anzahl User'], $ugroup_rows);

// Top 10 Kommentare Poster
$user_rows = [];
$user_res = $db_handler->sql_query("SELECT " . $sql_table['user'] . ".user_id, COUNT(" . $sql_table['comments'] . ".comment_id) AS kommentare FROM " . $sql_table['user'] . ", " . $sql_table['comments'] . " WHERE " . $sql_table['user'] . ".user_id = " . $sql_table['comments'] . ".user_id GROUP BY " . $sql_table['user'] . ".user_id ORDER BY kommentare DESC LIMIT 0,10");
$count = 0;
while ($user_row = $db_handler->sql_fetch_array($user_res)) {
    $count++;
    $user_rows[] = [
        (string) $count,
        user((int)($user_row['user_id'] ?? 0)),
        (string)(int)($user_row['kommentare'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Kommentar-Poster', ['#', 'Nick', 'Kommentare'], $user_rows);

// Top 10 Uploader
$user_rows = [];
$user_res = $db_handler->sql_query("SELECT " . $sql_table['user'] . ".user_id, COUNT(" . $sql_table['release'] . ".release_id) AS releases FROM " . $sql_table['user'] . ", " . $sql_table['release'] . " WHERE " . $sql_table['user'] . ".user_id = " . $sql_table['release'] . ".uploader GROUP BY " . $sql_table['user'] . ".user_id ORDER BY releases DESC LIMIT 0,10");
$count = 0;
while ($user_row = $db_handler->sql_fetch_array($user_res)) {
    $count++;
    $user_rows[] = [
        (string) $count,
        user((int)($user_row['user_id'] ?? 0)),
        (string)(int)($user_row['releases'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Uploader', ['#', 'Nick', 'Uploads'], $user_rows);

// Top 10 Ordner
$ordner_rows = [];
$ordner_res = $db_handler->sql_query("SELECT " . $sql_table['ordner'] . ".ordner_id, " . $sql_table['ordner'] . ".name, COUNT(" . $sql_table['release'] . ".release_id) AS releases FROM " . $sql_table['ordner'] . ", " . $sql_table['release'] . " WHERE " . $sql_table['ordner'] . ".ordner_id = " . $sql_table['release'] . ".ordner_id GROUP BY " . $sql_table['release'] . ".ordner_id ORDER BY releases DESC LIMIT 0,10");
$count = 0;
while ($ordner_row = $db_handler->sql_fetch_array($ordner_res)) {
    $count++;
    $ordner_rows[] = [
        (string) $count,
        '<a href="' . $script_file . 'ordner_id=' . (int)($ordner_row['ordner_id'] ?? 0) . '">' . htmlspecialchars(stripslashes($ordner_row['name'] ?? '')) . '</a>',
        (string)(int)($ordner_row['releases'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Ordner', ['#', 'Name', 'Releases'], $ordner_rows);

echo '</div>';
echo '<div class="col-12 col-lg-6">';

// Top 10 Release nach Größe
$release_rows_size = [];
$release_res = $db_handler->sql_query("SELECT " . $sql_table['release'] . ".release_id, " . $sql_table['release'] . ".name, SUM(" . $sql_table['files'] . ".size) AS size FROM " . $sql_table['release'] . ", " . $sql_table['files'] . " WHERE " . $sql_table['release'] . ".release_id = " . $sql_table['files'] . ".release_id GROUP BY " . $sql_table['release'] . ".release_id ORDER BY size DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_rows_size[] = [
        (string) $count,
        '<a href="' . $script_file . 'release_id=' . (int)($release_row['release_id'] ?? 0) . '">' . htmlspecialchars(stripslashes($release_row['name'] ?? '')) . '</a>',
        size((int)($release_row['size'] ?? 0)),
    ];
}
$pdl_render_top_card('Top 10 Release nach Größe', ['#', 'Release', 'Größe'], $release_rows_size);

// Top 10 Release nach Files
$release_rows_files = [];
$release_res = $db_handler->sql_query("SELECT " . $sql_table['release'] . ".release_id, " . $sql_table['release'] . ".name, COUNT(" . $sql_table['files'] . ".file_id) AS files FROM " . $sql_table['release'] . ", " . $sql_table['files'] . " WHERE " . $sql_table['release'] . ".release_id = " . $sql_table['files'] . ".release_id GROUP BY " . $sql_table['release'] . ".release_id ORDER BY files DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_rows_files[] = [
        (string) $count,
        '<a href="' . $script_file . 'release_id=' . (int)($release_row['release_id'] ?? 0) . '">' . htmlspecialchars(stripslashes($release_row['name'] ?? '')) . '</a>',
        (string)(int)($release_row['files'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Release nach Files', ['#', 'Release', 'Files'], $release_rows_files);

// Top 10 Release nach Kommentaren
$release_rows_comments = [];
$release_res = $db_handler->sql_query("SELECT " . $sql_table['release'] . ".release_id, " . $sql_table['release'] . ".name, COUNT(" . $sql_table['comments'] . ".comment_id) AS comments FROM " . $sql_table['release'] . ", " . $sql_table['comments'] . " WHERE " . $sql_table['release'] . ".release_id = " . $sql_table['comments'] . ".release_id GROUP BY " . $sql_table['release'] . ".release_id ORDER BY comments DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_rows_comments[] = [
        (string) $count,
        '<a href="' . $script_file . 'release_id=' . (int)($release_row['release_id'] ?? 0) . '">' . htmlspecialchars(stripslashes($release_row['name'] ?? '')) . '</a>',
        (string)(int)($release_row['comments'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Release nach Kommentaren', ['#', 'Release', 'Kommentare'], $release_rows_comments);

// Top 10 Release nach Bewertungen
$release_rows_votes = [];
$release_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE votes > 0 ORDER BY votes DESC LIMIT 0,10");
$count = 0;
while ($release_row = $db_handler->sql_fetch_array($release_res)) {
    $count++;
    $release_rows_votes[] = [
        (string) $count,
        '<a href="' . $script_file . 'release_id=' . (int)($release_row['release_id'] ?? 0) . '">' . htmlspecialchars(stripslashes($release_row['name'] ?? '')) . '</a>',
        (string)(int)($release_row['votes'] ?? 0),
    ];
}
$pdl_render_top_card('Top 10 Release nach Bewertungen', ['#', 'Release', 'Bewertungen'], $release_rows_votes);

echo '</div></div>';
