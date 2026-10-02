<?php
/**
 * PowerDownload - Kommentare aller Releases (neueste zuerst)
 *
 * Für das Recht „Kommentare moderieren“: Die Übersicht zeigt nur die
 * Kommentare der letzten 14 Tage, und die Kommentarliste eines Releases
 * (editrelease.php) verlangt „Releases und Dateien bearbeiten“. Hier
 * erreichen Moderatoren auch ältere Kommentare, wahlweise je Release
 * (comments.php?release_id=N).
 */
include("header.inc.php");

if (!pdl_admin_require_right('comment')) {
    include("footer.inc.php");
    return;
}

$release_id = isset($_GET['release_id']) && is_scalar($_GET['release_id']) ? max(0, (int) $_GET['release_id']) : 0;
$page = isset($_GET['page']) && is_scalar($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perpage = 25;
$can_edit_release = pdl_admin_has_right('editfiles');
$date_format = (string) ($settings['date_format'] ?? 'd.m.Y');
$c = $sql_table['comments'];
$r = $sql_table['release'];
$where = $release_id > 0 ? " WHERE $c.release_id='" . $db_handler->sql_escape_int($release_id) . "'" : '';

$release = $release_id > 0
    ? $db_handler->sql_fetch_array($db_handler->sql_query(
        "SELECT release_id, name FROM $r WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
    ))
    : null;

$crumbs = [['title' => 'Adminbereich', 'href' => 'index.php'], ['title' => 'Kommentare', 'href' => 'comments.php']];
if (is_array($release)) {
    $crumbs[] = ['title' => (string) $release['name']];
}
pdl_admin_breadcrumb($crumbs);
echo '<h1 class="h3 pdl-page-title">Kommentare</h1>';

$total_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT COUNT(*) AS c FROM $c INNER JOIN $r ON $r.release_id=$c.release_id" . $where));
$total = (int) ($total_row['c'] ?? 0);
$pages = max(1, (int) ceil($total / $perpage));
$page = min($page, $pages);

if (is_array($release)) {
    echo '<p class="text-muted" id="pdlCommentsIntro">Kommentare zum Release <strong>„' . htmlspecialchars((string) $release['name'], ENT_QUOTES, 'UTF-8') . '“</strong>, neueste zuerst. '
        . '<a href="comments.php" id="pdlCommentsAll">Alle Kommentare anzeigen</a></p>';
} else {
    echo '<p class="text-muted" id="pdlCommentsIntro">Alle Kommentare, neueste zuerst. Über den Namen des Releases sehen Sie nur dessen Kommentare.</p>';
}

$comments_res = $db_handler->sql_query(
    "SELECT $c.comment_id, $c.titel, $c.user_id, $c.time, $c.release_id, $r.name AS release_name, $r.released"
    . " FROM $c INNER JOIN $r ON $r.release_id=$c.release_id" . $where
    . " ORDER BY $c.time DESC, $c.comment_id DESC LIMIT " . (($page - 1) * $perpage) . ',' . $perpage
);
?>
<section class="card pdl-card mb-4" id="pdlCommentsList">
    <?php if ($total === 0) { ?>
    <div class="card-body"><p class="mb-0 text-muted"><?php echo is_array($release) ? 'Zu diesem Release gibt es noch keine Kommentare.' : 'Es gibt noch keine Kommentare.'; ?></p></div>
    <?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle" id="pdlCommentsTable">
            <thead>
                <tr>
                    <th scope="col">Titel</th>
                    <th scope="col">Release</th>
                    <th scope="col">Autor</th>
                    <th scope="col">Datum</th>
                    <th scope="col" class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $db_handler->sql_fetch_array($comments_res)) {
                $cid = (int) $row['comment_id'];
                $rid = (int) $row['release_id'];
                $release_href = $can_edit_release ? 'editrelease.php?release_id=' . $rid . '#pdlEdComments' : 'comments.php?release_id=' . $rid; ?>
                <tr data-comment-id="<?php echo $cid; ?>">
                    <td><?php echo htmlspecialchars((string) $row['titel'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($release_href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $row['release_name'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php if (($row['released'] ?? 'Y') === 'N') { ?><span class="badge text-bg-warning ms-1">versteckt</span><?php } ?>
                    </td>
                    <td><?php echo ((int) $row['user_id'] === 0) ? 'Gast' : user((int) $row['user_id']); ?></td>
                    <td><?php echo htmlspecialchars(date($date_format, (int) $row['time'])); ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <a class="btn btn-outline-light pdl-btn-edit-comment" href="editcomment.php?comment_id=<?php echo $cid; ?>">bearbeiten</a>
                            <a class="btn btn-outline-danger pdl-btn-del-comment" href="delcomment.php?comment_id=<?php echo $cid; ?>">löschen</a>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php if ($total > $perpage) { ?>
    <div class="card-footer text-center">
        <?php echo seiten($total, $perpage, $release_id > 0 ? '&release_id=' . $release_id : '', 'comments.php?'); ?>
    </div>
    <?php } ?>
    <?php } ?>
</section>
<?php
include("footer.inc.php");
