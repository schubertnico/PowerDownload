<?php
include("header.inc.php");

$ordner_id = isset($_REQUEST['ordner_id']) ? (int)$_REQUEST['ordner_id'] : 0;
$page = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;

if (!pdl_admin_require_right('addfiles', 'editfiles', 'delfiles', 'adddirs', 'editdirs', 'deldirs')) {
    include("footer.inc.php");
    return;
}

$can_add_release = pdl_admin_has_right('addfiles');
$can_add_file = pdl_admin_has_right('addfiles', 'editfiles');
$can_edit_release = pdl_admin_has_right('editfiles');
$can_del_release = pdl_admin_has_right('delfiles');
$can_add_dir = pdl_admin_has_right('adddirs');
$can_edit_dir = pdl_admin_has_right('editdirs');
$can_del_dir = pdl_admin_has_right('deldirs');

// Anzahl Releases je Ordner in einer Abfrage
$release_counts = [];
$count_res = $db_handler->sql_query("SELECT ordner_id, COUNT(*) AS c FROM " . $sql_table['release'] . " GROUP BY ordner_id");
while ($count_row = $db_handler->sql_fetch_array($count_res)) {
    $release_counts[(int) $count_row['ordner_id']] = (int) $count_row['c'];
}

$all_ordner = pdl_admin_ordner_all();
$current_ordner_name = pdl_admin_ordner_name($ordner_id);

/**
 * Eine Zeile der Ordnertabelle mit den erlaubten Aktionen.
 */
function pdl_or_list_row(int $id, string $name, int $depth, int $releases, bool $is_current, bool $is_root = false): string
{
    global $can_add_release, $can_add_dir, $can_edit_dir, $can_del_dir;
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . ($depth > 0 ? '└&nbsp;' : '');
    $actions = '';
    if ($can_add_release) {
        $actions .= '<a class="btn btn-outline-light pdl-btn-add-release" href="addrelease.php?ordner_id=' . $id . '">Release hinzufügen</a>';
    }
    if ($can_add_dir) {
        $actions .= '<a class="btn btn-outline-light pdl-btn-add-subdir" href="adddir.php?ordner_id=' . $id . '">Unterordner hinzufügen</a>';
    }
    if (!$is_root && $can_edit_dir) {
        $actions .= '<a class="btn btn-outline-light pdl-btn-edit-dir" href="editdir.php?ordner_id=' . $id . '">bearbeiten</a>';
    }
    if (!$is_root && $can_del_dir) {
        $actions .= '<a class="btn btn-outline-danger pdl-btn-del-dir" href="deldir.php?ordner_id=' . $id . '">löschen</a>';
    }
    return '<tr data-ordner-id="' . $id . '"' . ($is_current ? ' class="table-active"' : '') . '>'
        . '<td>' . $indent . '<a class="pdl-ordner-link" href="or_list.php?ordner_id=' . $id . '#pdl-aktuell"'
        . ($is_current ? ' aria-current="true"' : '') . '>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>'
        . ($is_current ? ' <span class="badge text-bg-warning ms-2">ausgewählt</span>' : '') . '</td>'
        . '<td class="text-end">' . $releases . '</td>'
        . '<td class="text-end"><div class="btn-group btn-group-sm flex-wrap" role="group">' . $actions . '</div></td>'
        . '</tr>';
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php'],
    ['title' => $current_ordner_name],
]);
echo '<h1 class="h3 pdl-page-title">Ordner und Releases</h1>';
?>
<div class="alert alert-info" role="note" id="pdlOrListHinweis">
    <strong>So arbeiten Sie hier:</strong> Klicken Sie auf einen <strong>Ordner</strong>, um darunter seine Releases zu sehen.
    Dateien und Screenshots gehören immer zu einem <strong>Release</strong> – legen Sie also zuerst das Release an und fügen Sie ihm dann Dateien hinzu.
</div>
<?php
$unreachable = pdl_admin_ordner_unreachable($all_ordner);
if ($unreachable !== []) {
    $links = [];
    foreach ($unreachable as $uid => $uname) {
        $links[] = $can_edit_dir
            ? '<a class="alert-link" href="editdir.php?ordner_id=' . $uid . '">' . htmlspecialchars($uname, ENT_QUOTES, 'UTF-8') . '</a>'
            : htmlspecialchars($uname, ENT_QUOTES, 'UTF-8');
    }
    echo '<div id="pdlOrListVerwaist">' . pdl_admin_alert(
        'warning',
        '<strong>Diese Ordner hängen nicht am Index</strong> (sie verweisen im Kreis aufeinander oder auf einen gelöschten Ordner) '
        . 'und sind deshalb für Besucher unsichtbar: ' . implode(', ', $links)
        . '. Öffnen Sie einen davon und wählen Sie als übergeordneten Ordner „Index“ oder einen sichtbaren Ordner.'
    ) . '</div>';
}
?>
<section class="card pdl-card mb-4" id="pdlOrdnerListe">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h5 mb-0">Ordner</h2>
        <?php if ($can_add_dir) { ?>
        <a class="btn btn-sm btn-primary" id="pdlOrListAddDir" href="adddir.php">Ordner hinzufügen</a>
        <?php } ?>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <caption class="visually-hidden">Alle Ordner mit Anzahl der Releases und Aktionen.</caption>
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col" class="text-end">Releases</th>
                    <th scope="col" class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php
                echo pdl_or_list_row(0, 'Index (oberste Ebene)', 0, $release_counts[0] ?? 0, $ordner_id === 0, true);
                foreach (pdl_admin_ordner_flat($all_ordner) as $ordner) {
                    echo pdl_or_list_row($ordner['id'], $ordner['name'], $ordner['depth'] + 1, $release_counts[$ordner['id']] ?? 0, $ordner['id'] === $ordner_id);
                }
                ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$total = $release_counts[$ordner_id] ?? 0;
$perpage = max(1, (int) ($settings['perpage'] ?? 10));
echo '<section id="pdl-aktuell" class="card pdl-card" tabindex="-1">';
echo '<header class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">'
    . '<h2 class="h5 mb-0">Releases im Ordner <span class="badge text-bg-secondary">' . htmlspecialchars($current_ordner_name, ENT_QUOTES, 'UTF-8') . '</span></h2>'
    . ($can_add_release ? '<a class="btn btn-sm btn-primary" id="pdlOrListAddRelease" href="addrelease.php?ordner_id=' . $ordner_id . '">Release hinzufügen</a>' : '')
    . '</header>';
if ($total == 0) {
    echo '<div class="card-body">';
    echo '<p class="mb-3">In diesem Ordner gibt es noch <strong>keine Releases</strong>.</p>';
    if ($can_add_release) {
        echo '<a class="btn btn-primary" href="addrelease.php?ordner_id=' . $ordner_id . '">Erstes Release in diesem Ordner anlegen</a>';
    }
    echo '</div>';
} else {
    $offset = ($page - 1) * $perpage;
    $files_res = $db_handler->sql_query(
        "SELECT r.release_id, r.name, r.released, (SELECT COUNT(*) FROM " . $sql_table['files'] . " f WHERE f.release_id=r.release_id AND f.mirror='0') AS file_count"
        . " FROM " . $sql_table['release'] . " r WHERE r.ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "'"
        . " ORDER BY r." . pdl_admin_release_order() . " LIMIT " . $offset . "," . $perpage
    );
?>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Dateien</th>
                    <th scope="col" class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            while ($files_row = $db_handler->sql_fetch_array($files_res)) {
                $rid = (int) $files_row['release_id'];
?>
                <tr data-release-id="<?php echo $rid; ?>">
                    <td><?php echo $can_edit_release
                        ? '<a href="editrelease.php?release_id=' . $rid . '">' . htmlspecialchars((string) $files_row['name'], ENT_QUOTES, 'UTF-8') . '</a>'
                        : htmlspecialchars((string) $files_row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo ($files_row['released'] ?? 'Y') === 'N'
                        ? '<span class="badge text-bg-warning">versteckt</span>'
                        : '<span class="badge text-bg-success">sichtbar</span>'; ?></td>
                    <td class="text-end"><?php echo (int) ($files_row['file_count'] ?? 0); ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm flex-wrap" role="group">
                            <?php if ($can_add_file) { ?>
                            <a class="btn btn-outline-light pdl-btn-add-file" href="addfile.php?release_id=<?php echo $rid; ?>">Datei hinzufügen</a>
                            <a class="btn btn-outline-light pdl-btn-add-screen" href="addscreen.php?release_id=<?php echo $rid; ?>">Screenshot hochladen</a>
                            <?php } ?>
                            <?php if ($can_edit_release) { ?>
                            <a class="btn btn-outline-light pdl-btn-edit-release" href="editrelease.php?release_id=<?php echo $rid; ?>">bearbeiten</a>
                            <?php } ?>
                            <?php if ($can_del_release) { ?>
                            <a class="btn btn-outline-danger pdl-btn-del-release" href="delrelease.php?release_id=<?php echo $rid; ?>">löschen</a>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
<?php } ?>
            </tbody>
        </table>
    </div>
    <?php if ($total > $perpage) { ?>
    <div class="card-footer text-center">
        <?php echo seiten($total, $perpage, "&ordner_id=" . (int) $ordner_id, "or_list.php?"); ?>
    </div>
    <?php } ?>
<?php }
echo '</section>';
include("footer.inc.php");
