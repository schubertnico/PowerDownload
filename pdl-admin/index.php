<?php
include("header.inc.php");

$login_error = isset($_GET['login_error']) ? (int) $_GET['login_error'] : 0;

if ($user_details && !pdl_admin_has_right()) {
    // Angemeldet, aber ohne Admin-Zugang
    echo '<h1 class="h3 pdl-page-title">Adminbereich</h1>';
    echo pdl_admin_alert(
        'warning',
        'Ihr Konto hat keinen Zugang zum Adminbereich. Wenden Sie sich bitte an einen Administrator, '
        . 'wenn Sie Releases oder Ordner verwalten möchten.'
    );
    echo '<div class="d-flex flex-wrap gap-2">'
        . '<a class="btn btn-primary" href="../' . htmlspecialchars((string) ($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8') . '">Zur Webseite</a>'
        . '<a class="btn btn-outline-light" href="' . $pdl_admin_logout_href . '">Abmelden</a>'
        . '</div>';
} elseif ($user_details) {
    $date_format = (string) ($settings['date_format'] ?? 'd.m.Y');
    $since = time() - 14 * 86400;
    $fmt = static fn (int $n): string => number_format($n, 0, ',', '.');

    $count = static function (string $sql) use ($db_handler): int {
        $row = $db_handler->sql_fetch_array($db_handler->sql_query($sql));
        return (int) ($row['c'] ?? 0);
    };
    $n_ordner = $count("SELECT COUNT(*) AS c FROM " . $sql_table['ordner']);
    $n_releases = $count("SELECT COUNT(*) AS c FROM " . $sql_table['release']);
    $n_hidden = $count("SELECT COUNT(*) AS c FROM " . $sql_table['release'] . " WHERE released='N'");
    $n_files = $count("SELECT COUNT(*) AS c FROM " . $sql_table['files'] . " WHERE mirror='0'");
    $n_mirrors = $count("SELECT COUNT(*) AS c FROM " . $sql_table['files'] . " WHERE mirror<>'0'");
    $n_downloads = $count("SELECT COALESCE(SUM(downloads),0) AS c FROM " . $sql_table['files']);
    $n_users = $count("SELECT COUNT(*) AS c FROM " . $sql_table['user']);

    $can_releases = pdl_admin_has_right('addfiles', 'editfiles', 'delfiles', 'adddirs', 'editdirs', 'deldirs');
    $can_users = pdl_admin_has_right('edituser', 'deluser');

    echo '<h1 class="h3 pdl-page-title">Übersicht</h1>';

    // Installations- und Update-Skripte, die nicht auf den Server gehören
    $pdl_legacy_files = function_exists('pdl_legacy_install_files') ? pdl_legacy_install_files(dirname(__DIR__)) : [];
    if ($pdl_legacy_files !== []) {
        echo '<div class="alert alert-warning" role="alert" id="pdl_admin_legacy_files">'
            . '<strong>Bitte löschen Sie diese Dateien vom Server:</strong> '
            . implode(', ', array_map(static fn (string $file): string => '<code>' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '</code>', $pdl_legacy_files))
            . '. Installations- und Update-Skripte gehören nicht auf eine laufende Website.'
            . ' <code>update.php</code> darf bleiben, sie ist nur für Admins erreichbar.</div>';
    }
    echo '<p class="text-muted" id="pdlDashIntro">Willkommen, ' . $pdl_admin_user . '. Hier sehen Sie die wichtigsten Zahlen '
        . 'und was in den letzten 14 Tagen neu hinzugekommen ist.</p>';

    $tiles = [
        ['pdlDashOrdner', $fmt($n_ordner), 'Ordner', '', $can_releases ? 'or_list.php' : ''],
        ['pdlDashReleases', $fmt($n_releases), 'Releases', $n_hidden > 0 ? 'davon ' . $fmt($n_hidden) . ' versteckt' : 'alle sichtbar', $can_releases ? 'or_list.php' : ''],
        ['pdlDashDateien', $fmt($n_files), $n_files === 1 ? 'Datei' : 'Dateien', $n_mirrors > 0 ? 'plus ' . $fmt($n_mirrors) . ' Spiegel-Server' : '', $can_releases ? 'or_list.php' : ''],
        ['pdlDashDownloads', $fmt($n_downloads), 'Downloads', 'über alle Dateien', ''],
        ['pdlDashBenutzer', $fmt($n_users), 'Benutzer', '', $can_users ? 'users.php' : ''],
    ];
    echo '<section class="row g-3 mb-4" id="pdlDashKacheln" aria-label="Kennzahlen">';
    foreach ($tiles as [$id, $value, $label, $hint, $href]) {
        $tag = $href !== '' ? 'a' : 'div';
        echo '<div class="col-6 col-md-4 col-xxl">'
            . '<' . $tag . ' class="card pdl-card pdl-stat-tile h-100" id="' . $id . '"' . ($href !== '' ? ' href="' . $href . '"' : '') . '>'
            . '<div class="card-body">'
            . '<div class="pdl-stat-value">' . $value . '</div>'
            . '<div class="pdl-stat-label">' . htmlspecialchars($label) . '</div>'
            . ($hint !== '' ? '<div class="small text-muted mt-1">' . htmlspecialchars($hint) . '</div>' : '')
            . '</div></' . $tag . '></div>';
    }
    echo '</section>';

    echo '<div class="row g-4 mb-4">';

    // Neue Releases der letzten 14 Tage
    if ($can_releases) {
        $release_res = $db_handler->sql_query(
            "SELECT release_id, name, released, time, uploader FROM " . $sql_table['release']
            . " WHERE time>='" . $db_handler->sql_escape_int($since) . "' ORDER BY time DESC, release_id DESC LIMIT 10"
        );
        ?>
<div class="col-12 col-xxl-6">
<section class="card pdl-card h-100" id="pdlDashNeueReleases">
    <header class="card-header"><h2 class="h5 mb-0">Neue Releases (letzte 14 Tage)</h2></header>
        <?php if ($db_handler->sql_num_rows($release_res) === 0) { ?>
    <div class="card-body"><p class="mb-0 text-muted">In den letzten 14 Tagen wurden keine Releases angelegt oder als neu markiert.</p></div>
        <?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Datum</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($release_row = $db_handler->sql_fetch_array($release_res)) { $rid = (int) $release_row['release_id']; ?>
                <tr data-release-id="<?php echo $rid; ?>">
                    <td><?php echo htmlspecialchars((string) $release_row['name']); ?></td>
                    <td><?php echo htmlspecialchars(date($date_format, (int) $release_row['time'])); ?></td>
                    <td><?php echo $release_row['released'] === 'N'
                        ? '<span class="badge text-bg-warning">versteckt</span>'
                        : '<span class="badge text-bg-success">sichtbar</span>'; ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <?php if (pdl_admin_has_right('editfiles')) { ?>
                                <a class="btn btn-outline-light" href="editrelease.php?release_id=<?php echo $rid; ?>">bearbeiten</a>
                            <?php }
                            if (pdl_admin_has_right('delfiles')) { ?>
                                <a class="btn btn-outline-danger" href="delrelease.php?release_id=<?php echo $rid; ?>">löschen</a>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
        <?php } ?>
</section>
</div>
        <?php
    }

    // Neue Kommentare der letzten 14 Tage
    if (pdl_admin_has_right('comment')) {
        $c = $sql_table['comments'];
        $r = $sql_table['release'];
        $comments_res = $db_handler->sql_query(
            "SELECT $c.comment_id, $c.titel, $c.user_id, $c.time, $c.release_id, $r.name AS release_name"
            . " FROM $c INNER JOIN $r ON $r.release_id=$c.release_id"
            . " WHERE $c.time>='" . $db_handler->sql_escape_int($since) . "' ORDER BY $c.time DESC, $c.comment_id DESC LIMIT 10"
        );
        ?>
<div class="col-12 col-xxl-6">
<section class="card pdl-card h-100" id="pdlDashNeueKommentare">
    <header class="card-header"><h2 class="h5 mb-0">Neue Kommentare (letzte 14 Tage)</h2></header>
        <?php if ($db_handler->sql_num_rows($comments_res) === 0) { ?>
    <div class="card-body"><p class="mb-0 text-muted">In den letzten 14 Tagen wurden keine Kommentare geschrieben.</p></div>
        <?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
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
            <?php while ($comments_row = $db_handler->sql_fetch_array($comments_res)) { $cid = (int) $comments_row['comment_id']; ?>
                <tr data-comment-id="<?php echo $cid; ?>">
                    <td><?php echo htmlspecialchars((string) $comments_row['titel']); ?></td>
                    <td><a href="<?php echo pdl_admin_has_right('editfiles')
                        ? 'editrelease.php?release_id=' . (int) $comments_row['release_id'] . '#pdlEdComments'
                        : 'comments.php?release_id=' . (int) $comments_row['release_id']; ?>"><?php echo htmlspecialchars((string) $comments_row['release_name']); ?></a></td>
                    <td><?php echo ((int) $comments_row['user_id'] === 0) ? 'Gast' : user((int) $comments_row['user_id']); ?></td>
                    <td><?php echo htmlspecialchars(date($date_format, (int) $comments_row['time'])); ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <a class="btn btn-outline-light" href="editcomment.php?comment_id=<?php echo $cid; ?>">bearbeiten</a>
                            <a class="btn btn-outline-danger" href="delcomment.php?comment_id=<?php echo $cid; ?>">löschen</a>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
        <?php } ?>
    <footer class="card-footer small"><a href="comments.php" id="pdlDashAlleKommentare">Alle Kommentare anzeigen</a></footer>
</section>
</div>
        <?php
    }
    echo '</div>';

    $quick = [
        ['pdlDashQaRelease', 'Release hinzufügen', 'addrelease.php', pdl_admin_has_right('addfiles'), true],
        ['pdlDashQaOrdner', 'Ordner hinzufügen', 'adddir.php', pdl_admin_has_right('adddirs'), false],
        ['pdlDashQaListe', 'Ordner und Releases', 'or_list.php', $can_releases, false],
        ['pdlDashQaBenutzer', 'Benutzerliste', 'users.php', $can_users, false],
        ['pdlDashQaVorlagen', 'Vorlagen bearbeiten', 'templates.php', pdl_admin_has_right('templates'), false],
        ['pdlDashQaEinstellungen', 'Einstellungen', 'settings.php', pdl_admin_has_right('settings'), false],
    ];
    $quick = array_filter($quick, static fn (array $q): bool => (bool) $q[3]);
    if ($quick !== []) {
        echo '<section class="card pdl-card mb-4" id="pdlDashSchnellzugriff">'
            . '<header class="card-header"><h2 class="h5 mb-0">Schnellzugriff</h2></header>'
            . '<div class="card-body d-flex flex-wrap gap-2">';
        foreach ($quick as [$id, $label, $href, , $primary]) {
            echo '<a class="btn ' . ($primary ? 'btn-primary' : 'btn-outline-light') . '" id="' . $id . '" href="' . $href . '">'
                . htmlspecialchars($label) . '</a>';
        }
        echo '</div></section>';
    }
} else {
    $login_messages = [
        1 => 'Benutzername oder Passwort ist falsch. Bitte prüfen Sie Ihre Eingaben und versuchen Sie es erneut.',
        2 => 'Zu viele fehlgeschlagene Anmeldeversuche. Bitte warten Sie 15 Minuten und versuchen Sie es dann erneut.',
        3 => 'Die Anmeldung wurde aus Sicherheitsgründen abgelehnt. Bitte laden Sie die Seite neu und melden Sie sich erneut an.',
    ];
    ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <section class="card pdl-card" id="pdlAdminLogin">
            <header class="card-header">
                <h1 class="h4 mb-0">Anmeldung im Adminbereich</h1>
            </header>
            <div class="card-body">
                <?php if (isset($login_messages[$login_error])) { ?>
                <div class="alert alert-danger" role="alert" id="pdlAdminLoginError"><?php echo htmlspecialchars($login_messages[$login_error]); ?></div>
                <?php } ?>
                <p class="small text-muted">Bitte melden Sie sich mit Ihrem Benutzernamen und Passwort an. Cookies müssen aktiviert sein.</p>
                <form action="index.php?login=1" method="post" novalidate>
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label for="pdlAdminLoginNick" class="form-label">Benutzername</label>
                        <input type="text" id="pdlAdminLoginNick" name="nick" class="form-control" required autocomplete="username"<?php echo $login_error > 0 ? ' aria-describedby="pdlAdminLoginError"' : ''; ?>>
                    </div>
                    <div class="mb-3">
                        <label for="pdlAdminLoginPw" class="form-label">Passwort</label>
                        <input type="password" id="pdlAdminLoginPw" name="pw" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="pdlAdminLoginSubmit">Anmelden</button>
                    </div>
                </form>
                <p class="small mt-3 mb-0">
                    <a href="../<?php echo htmlspecialchars((string) ($settings['script_file'] ?? '')); ?>usercenter=lost">Passwort vergessen?</a>
                </p>
            </div>
        </section>
    </div>
</div>
    <?php
}

include("footer.inc.php");
