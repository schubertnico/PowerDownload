<?php
/**
 * PowerDownload - User-Liste mit Suche und Filter
 *
 * Konsolidierte Userliste fuer den Admin-Bereich. Bietet:
 *   - Volltext-Suche ueber Nick und E-Mail
 *   - Filter nach Usergruppe
 *   - Sortierung nach Nick / Usergruppe / lastactive
 *   - Pagination (25 pro Seite)
 *   - Schnellzugriff auf Edit / Loeschen je User
 */

include("header.inc.php");

$can_edit   = (($user_rights['edituser'] ?? 'N') === 'Y');
$can_delete = (($user_rights['deluser'] ?? 'N') === 'Y');

if (!$can_edit && !$can_delete) {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
    include("footer.inc.php");
    exit;
}

// Parameter
$q          = trim((string)($_GET['q'] ?? ''));
$ugroup_id  = isset($_GET['ugroup_id']) ? (int)$_GET['ugroup_id'] : 0;
$orderby    = (string)($_GET['orderby'] ?? 'nick');
$orderseq   = strtoupper((string)($_GET['orderseq'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
$page       = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$perpage    = 25;

// Whitelist Sortierung
$orderby_whitelist = ['nick', 'lastactive', 'ugroup_name', 'user_id'];
if (!in_array($orderby, $orderby_whitelist, true)) {
    $orderby = 'nick';
}

// Usergruppen fuer Filter laden
$ugroups = [];
$ugroups_res = $db_handler->sql_query("SELECT ugroup_id, name FROM " . $sql_table['usergroup'] . " ORDER BY name ASC");
while ($row = $db_handler->sql_fetch_array($ugroups_res)) {
    $ugroups[(int)$row['ugroup_id']] = (string)$row['name'];
}

$where_parts = [];
$where_parts[] = $sql_table['user'] . ".ugroup_id = " . $sql_table['usergroup'] . ".ugroup_id";
$current_user_id = (int)($user_details['user_id'] ?? 0);

if ($q !== '') {
    $q_safe = $db_handler->sql_escape_string($q);
    $where_parts[] = "(" . $sql_table['user'] . ".nick LIKE '%" . $q_safe . "%' OR " . $sql_table['user'] . ".email LIKE '%" . $q_safe . "%')";
}

if ($ugroup_id > 0) {
    $where_parts[] = $sql_table['user'] . ".ugroup_id = '" . $db_handler->sql_escape_int($ugroup_id) . "'";
}

$where_sql = implode(' AND ', $where_parts);

// Gesamtzahl
$count_res = $db_handler->sql_query("SELECT COUNT(*) AS c FROM " . $sql_table['user'] . ", " . $sql_table['usergroup'] . " WHERE " . $where_sql);
$count_row = $db_handler->sql_fetch_array($count_res);
$total = (int)($count_row['c'] ?? 0);

// Daten holen
$offset = ($page - 1) * $perpage;
$limit  = $offset . ',' . $perpage;
$user_res = $db_handler->sql_query(
    "SELECT " . $sql_table['user'] . ".user_id, " . $sql_table['user'] . ".nick, " . $sql_table['user'] . ".email, " . $sql_table['user'] . ".lastactive, " . $sql_table['usergroup'] . ".name AS ugroup_name "
    . "FROM " . $sql_table['user'] . ", " . $sql_table['usergroup']
    . " WHERE " . $where_sql
    . " ORDER BY " . $orderby . " " . $orderseq
    . " LIMIT " . $limit
);

pdl_admin_breadcrumb([
    ['title' => 'Admin-Center', 'href' => 'index.php'],
    ['title' => 'User'],
    ['title' => 'Userliste'],
]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h3 pdl-page-title flex-grow-1 mb-0">Userliste</h1>
    <div class="d-flex gap-2">
        <?php if ($can_edit) { ?>
            <a class="btn btn-outline-light btn-sm" href="addugroup.php">Neue Usergruppe</a>
        <?php } ?>
    </div>
</div>

<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h6 mb-0">Suche &amp; Filter</h2></header>
    <div class="card-body">
        <form action="users.php" method="get" class="row g-3" novalidate>
            <div class="col-12 col-md-6">
                <label for="pdlUserQ" class="form-label">Suchbegriff</label>
                <input type="search" id="pdlUserQ" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nick oder E-Mail" aria-describedby="pdlUserQHelp">
                <div id="pdlUserQHelp" class="form-text">Sucht in Nickname und E-Mail-Adresse (Teilstring-Suche).</div>
            </div>
            <div class="col-12 col-md-3">
                <label for="pdlUserGroup" class="form-label">Usergruppe</label>
                <select id="pdlUserGroup" name="ugroup_id" class="form-select">
                    <option value="0">– alle –</option>
                    <?php foreach ($ugroups as $gid => $gname) {
                        $sel = $gid === $ugroup_id ? ' selected' : '';
                        echo '<option value="' . (int)$gid . '"' . $sel . '>' . htmlspecialchars($gname) . '</option>';
                    } ?>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Suchen</button>
                <a href="users.php" class="btn btn-outline-light">Reset</a>
            </div>
        </form>
    </div>
</section>

<?php if ($total === 0) { ?>
    <div class="alert alert-info" role="alert">
        <?php if ($q !== '' || $ugroup_id > 0) { ?>
            Keine User entsprechen den Filterkriterien. <a href="users.php" class="alert-link">Filter zurücksetzen</a>.
        <?php } else { ?>
            Es sind keine editierbaren User vorhanden.
        <?php } ?>
    </div>
<?php } else { ?>
    <p class="small text-muted mb-2"><strong><?php echo (int)$total; ?></strong> User gefunden<?php if ($q !== '' || $ugroup_id > 0) echo ' (gefiltert)'; ?>.</p>
    <section class="card pdl-card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <?php
                        $cols = [
                            'nick' => 'Nick',
                            'ugroup_name' => 'Usergruppe',
                            'lastactive' => 'Letzter Login',
                        ];
                        foreach ($cols as $key => $label) {
                            $next_seq = ($orderby === $key && $orderseq === 'ASC') ? 'DESC' : 'ASC';
                            $arrow = '';
                            if ($orderby === $key) {
                                $arrow = $orderseq === 'ASC' ? ' ▲' : ' ▼';
                            }
                            $url = 'users.php?q=' . urlencode($q) . '&ugroup_id=' . (int)$ugroup_id . '&orderby=' . urlencode($key) . '&orderseq=' . $next_seq;
                            echo '<th scope="col"><a class="link-light text-decoration-none" href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . htmlspecialchars($arrow) . '</a></th>';
                        }
                        ?>
                        <th scope="col" class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $db_handler->sql_fetch_array($user_res)) {
                    $uid = (int)$row['user_id'];
                    $lastactive = (int)($row['lastactive'] ?? 0);
                    $lastactive_str = $lastactive > 0 ? date($settings['date_format'] ?? 'd.m.Y', $lastactive) : '–';
                ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars((string)$row['nick']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars((string)($row['email'] ?? '')); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars((string)$row['ugroup_name']); ?></td>
                        <td><?php echo htmlspecialchars($lastactive_str); ?></td>
                        <td class="text-end">
                            <?php if ($uid === 1) { ?>
                                <span class="badge text-bg-secondary">Super-Admin (geschützt)</span>
                            <?php } else { ?>
                            <div class="btn-group btn-group-sm" role="group" aria-label="User-Aktionen">
                                <?php if ($can_edit) { ?>
                                    <a class="btn btn-outline-light" href="edituser.php?user_id=<?php echo $uid; ?>">editieren</a>
                                <?php } ?>
                                <?php if ($can_delete) { ?>
                                    <a class="btn btn-outline-danger" href="deluser.php?submit=1&amp;user_id=<?php echo $uid; ?>"
                                       onclick="return confirm('User &quot;<?php echo htmlspecialchars(addslashes((string)$row['nick'])); ?>&quot; wirklich löschen?');">löschen</a>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($total > $perpage) {
            // Paginierung manuell, weil seiten() andere URL-Struktur erwartet
            $pages = (int)ceil($total / $perpage);
            $base = 'users.php?q=' . urlencode($q) . '&ugroup_id=' . (int)$ugroup_id . '&orderby=' . urlencode($orderby) . '&orderseq=' . $orderseq;
        ?>
        <div class="card-footer">
            <nav aria-label="Seitennavigation">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($p = 1; $p <= $pages; $p++) {
                        $active = $p === $page ? ' active' : '';
                    ?>
                        <li class="page-item<?php echo $active; ?>"><a class="page-link" href="<?php echo htmlspecialchars($base . '&page=' . $p); ?>"><?php echo $p; ?></a></li>
                    <?php } ?>
                </ul>
            </nav>
        </div>
        <?php } ?>
    </section>
<?php } ?>

<?php include("footer.inc.php"); ?>
