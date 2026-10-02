<?php
/**
 * PowerDownload - Benutzerliste mit Suche und Filter
 *
 *   - Suche über Benutzername und E-Mail-Adresse
 *   - Filter nach Benutzergruppe
 *   - Sortierung nach Benutzername, Gruppe, letzter Anmeldung
 *   - 25 Benutzer pro Seite
 *   - bearbeiten / löschen (Löschen mit Bestätigungsseite)
 */

include("header.inc.php");
include_once("system_helpers.inc.php");

$can_edit   = pdl_sys_can($user_rights, 'edituser');
$can_delete = pdl_sys_can($user_rights, 'deluser');

if (!$can_edit && !$can_delete) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('edituser') . ' Zum Löschen genügt das Recht „Benutzer löschen“.');
    include("footer.inc.php");
    return;
}

// Parameter
$q          = trim(pdl_sys_get('q'));
$ugroup_id  = (int) pdl_sys_get('ugroup_id', '0');
$orderby    = pdl_sys_get('orderby', 'nick');
$orderseq   = strtoupper(pdl_sys_get('orderseq', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
$page       = max(1, (int) pdl_sys_get('page', '1'));
$perpage    = 25;

// Sortierung nur nach bekannten Spalten
$orderby_sql = [
    'nick' => 'u.nick',
    'ugroup_name' => 'ugroup_name',
    'lastactive' => 'u.lastactive',
];
if (!isset($orderby_sql[$orderby])) {
    $orderby = 'nick';
}

$guest_group_id = pdl_sys_guest_group_id($settings);
$groups = pdl_sys_groups($db_handler, $sql_table);
$group_names = [];
foreach ($groups as $group) {
    $group_names[$group['ugroup_id']] = $group['name'];
}
$current_user_id = (int) ($user_details['user_id'] ?? 0);

$user_t = pdl_sys_ident($sql_table['user']);
$group_t = pdl_sys_ident($sql_table['usergroup']);
$where_parts = ['1 = 1'];
if ($q !== '') {
    $q_safe = $db_handler->sql_escape_string(addcslashes($q, '%_'));
    $where_parts[] = "(u.nick LIKE '%" . $q_safe . "%' OR u.email LIKE '%" . $q_safe . "%')";
}
if ($ugroup_id > 0) {
    $where_parts[] = 'u.ugroup_id = ' . $ugroup_id;
}
$where_sql = implode(' AND ', $where_parts);
$from_sql = ' FROM ' . $user_t . ' AS u LEFT JOIN ' . $group_t . ' AS g ON g.ugroup_id = u.ugroup_id';

$total = pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*)' . $from_sql . ' WHERE ' . $where_sql);
$pages = max(1, (int) ceil($total / $perpage));
$page = min($page, $pages);
$offset = ($page - 1) * $perpage;
$user_res = $db_handler->sql_query(
    'SELECT u.user_id, u.nick, u.email, u.lastactive, u.get_letter, u.ugroup_id, g.name AS ugroup_name'
    . $from_sql
    . ' WHERE ' . $where_sql
    . ' ORDER BY ' . $orderby_sql[$orderby] . ' ' . $orderseq . ', u.user_id ASC'
    . ' LIMIT ' . $offset . ',' . $perpage
);

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Benutzer', 'href' => 'users.php'],
    ['title' => 'Benutzerliste'],
]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 pdl-page-title flex-grow-1 mb-0">Benutzerliste</h1>
    <?php if (pdl_sys_can($user_rights, 'edituser', 'deluser')) { ?>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="editdelugroup.php" id="pdlUsersGroups">Benutzergruppen bearbeiten</a>
        <a class="btn btn-outline-light btn-sm" href="addugroup.php" id="pdlUsersNewGroup">Neue Benutzergruppe</a>
    </div>
    <?php } ?>
</div>

<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h6 mb-0">Suche und Filter</h2></header>
    <div class="card-body">
        <form action="users.php" method="get" class="row g-3" id="pdlUserSearch">
            <div class="col-12 col-md-6">
                <label for="pdlUserQ" class="form-label">Suchbegriff</label>
                <input type="search" id="pdlUserQ" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Benutzername oder E-Mail-Adresse" aria-describedby="pdlUserQHelp">
                <div id="pdlUserQHelp" class="form-text">Findet auch Teile von Benutzernamen und E-Mail-Adressen.</div>
            </div>
            <div class="col-12 col-md-3">
                <label for="pdlUserGroup" class="form-label">Benutzergruppe</label>
                <select id="pdlUserGroup" name="ugroup_id" class="form-select">
                    <option value="0">– alle Gruppen –</option>
                    <?php foreach ($groups as $group) {
                        if ($group['ugroup_id'] === $guest_group_id) {
                            continue;
                        }
                        $sel = $group['ugroup_id'] === $ugroup_id ? ' selected' : '';
                        echo '<option value="' . $group['ugroup_id'] . '"' . $sel . '>' . htmlspecialchars($group['name'])
                            . ' (' . pdl_sys_num($group['members']) . ')</option>';
                    } ?>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" id="pdlUserSearchSubmit">Suchen</button>
                <a href="users.php" class="btn btn-outline-light">Filter zurücksetzen</a>
            </div>
        </form>
    </div>
</section>

<?php if ($guest_group_id > 0 && $ugroup_id === $guest_group_id) { ?>
    <div class="alert alert-info" role="alert">
        Die Gruppe „<?php echo htmlspecialchars($group_names[$guest_group_id] ?? 'Gast'); ?>“ gilt für nicht angemeldete Besucher. Sie hat keine Benutzerkonten.
    </div>
<?php } ?>

<?php if ($total === 0) { ?>
    <div class="alert alert-info" role="alert">
        <?php if ($q !== '' || $ugroup_id > 0) { ?>
            Keine Benutzer entsprechen den Filterkriterien. <a href="users.php" class="alert-link">Filter zurücksetzen</a>.
        <?php } else { ?>
            Es sind noch keine Benutzer registriert.
        <?php } ?>
    </div>
<?php } else { ?>
    <p class="small text-muted mb-2" id="pdlUserCount"><strong><?php echo pdl_sys_num($total); ?></strong> Benutzer gefunden<?php if ($q !== '' || $ugroup_id > 0) echo ' (gefiltert)'; ?>.</p>
    <section class="card pdl-card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle" id="pdlUserTable">
                <thead>
                    <tr>
                        <?php
                        $cols = [
                            'nick' => 'Benutzername',
                            'ugroup_name' => 'Benutzergruppe',
                            'lastactive' => 'Letzte Anmeldung',
                        ];
                        foreach ($cols as $key => $label) {
                            $next_seq = ($orderby === $key && $orderseq === 'ASC') ? 'DESC' : 'ASC';
                            $arrow = '';
                            $aria = '';
                            if ($orderby === $key) {
                                $arrow = $orderseq === 'ASC' ? ' ▲' : ' ▼';
                                $aria = ' aria-sort="' . ($orderseq === 'ASC' ? 'ascending' : 'descending') . '"';
                            }
                            $url = 'users.php?q=' . urlencode($q) . '&ugroup_id=' . $ugroup_id . '&orderby=' . urlencode($key) . '&orderseq=' . $next_seq;
                            echo '<th scope="col"' . $aria . '><a class="link-light text-decoration-none" href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . htmlspecialchars($arrow) . '</a></th>';
                        }
                        ?>
                        <th scope="col">Newsletter</th>
                        <th scope="col" class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $db_handler->sql_fetch_array($user_res)) {
                    $uid = (int) $row['user_id'];
                    $lastactive = (int) ($row['lastactive'] ?? 0);
                    $lastactive_str = $lastactive > 0 ? date((string) ($settings['date_format'] ?? 'd.m.Y'), $lastactive) : 'noch nie';
                    $nick = (string) $row['nick'];
                ?>
                    <tr id="pdlUserRow_<?php echo $uid; ?>" data-nick="<?php echo htmlspecialchars($nick); ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($nick); ?></strong>
                            <?php if ($uid === $current_user_id) { ?><span class="badge text-bg-info ms-1">Sie</span><?php } ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars((string) ($row['email'] ?? '')); ?></small>
                        </td>
                        <td><?php echo $row['ugroup_name'] === null ? '<span class="text-warning">(keine Gruppe)</span>' : htmlspecialchars((string) $row['ugroup_name']); ?></td>
                        <td><?php echo htmlspecialchars($lastactive_str); ?></td>
                        <td><?php echo ($row['get_letter'] ?? 'N') === 'Y' ? 'ja' : 'nein'; ?></td>
                        <td class="text-end">
                            <?php if ($uid === 1) { ?>
                                <span class="badge text-bg-secondary">Hauptadministrator (geschützt)</span>
                            <?php } else { ?>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Aktionen für <?php echo htmlspecialchars($nick); ?>">
                                <?php if ($can_edit) { ?>
                                    <a class="btn btn-outline-light" id="pdlUserEdit_<?php echo $uid; ?>" href="edituser.php?user_id=<?php echo $uid; ?>">bearbeiten</a>
                                <?php } ?>
                                <?php if ($can_delete && $uid !== $current_user_id) { ?>
                                    <a class="btn btn-outline-danger" id="pdlUserDelete_<?php echo $uid; ?>" href="deluser.php?user_id=<?php echo $uid; ?>">löschen</a>
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
            $base = 'users.php?q=' . urlencode($q) . '&ugroup_id=' . $ugroup_id . '&orderby=' . urlencode($orderby) . '&orderseq=' . $orderseq;
        ?>
        <div class="card-footer">
            <nav aria-label="Seitennavigation">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($p = 1; $p <= $pages; $p++) {
                        $active = $p === $page ? ' active' : '';
                        $current = $p === $page ? ' aria-current="page"' : '';
                    ?>
                        <li class="page-item<?php echo $active; ?>"><a class="page-link" href="<?php echo htmlspecialchars($base . '&page=' . $p); ?>"<?php echo $current; ?>><?php echo $p; ?></a></li>
                    <?php } ?>
                </ul>
            </nav>
        </div>
        <?php } ?>
    </section>
<?php } ?>

<?php include("footer.inc.php"); ?>
