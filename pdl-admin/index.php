<?php
include("header.inc.php");

if ($user_details) {
    $resetactive = isset($_GET['resetactive']) ? (int)$_GET['resetactive'] : 0;
    if ($resetactive == 1) {
        $user_details['lastactive'] = time();
        $db_handler->sql_query("UPDATE " . $sql_table['user'] . " SET lastactive='" . $db_handler->sql_escape_int($user_details['lastactive']) . "' WHERE user_id='" . $db_handler->sql_escape_int($user_details['user_id']) . "'");
    }

    echo '<h1 class="h3 pdl-page-title">Dashboard</h1>';
    echo '<p class="text-muted">Seit Ihrem letzten Login am '
        . htmlspecialchars(date($settings['date_format'], (int)$user_details['lastactive']))
        . ' ist folgendes passiert:</p>';

    $release_res = null;
    $comments_res = null;

    if ($user_rights['editfiles'] == "Y" || $user_rights['delfiles'] == "Y") {
        $release_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE time>'" . $db_handler->sql_escape_int($user_details['lastactive']) . "'");
        if ($db_handler->sql_num_rows($release_res) > 0) {
            ?>
<section class="card pdl-card mb-4">
    <header class="card-header">
        <h2 class="h5 mb-0">Neue Releases</h2>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Status</th>
                    <th scope="col">Uploader</th>
                    <th scope="col" class="text-end">Optionen</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($release_row = $db_handler->sql_fetch_array($release_res)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($release_row['name']); ?></td>
                    <td>
                        <?php if ($release_row['released'] == "Y") { ?>
                            <span class="badge text-bg-success">sichtbar</span>
                        <?php } else { ?>
                            <span class="badge text-bg-warning text-dark">versteckt</span>
                        <?php } ?>
                    </td>
                    <td><?php echo user($release_row['uploader']); ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <?php if ($user_rights['editfiles'] == "Y") { ?>
                                <a class="btn btn-outline-light" href="editrelease.php?release_id=<?php echo (int)$release_row['release_id']; ?>">ändern</a>
                            <?php }
                            if ($user_rights['delfiles'] == "Y") { ?>
                                <a class="btn btn-outline-danger" href="delrelease.php?release_id=<?php echo (int)$release_row['release_id']; ?>">löschen</a>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
            <?php
        }
    }

    if ($user_rights['editfiles'] == "Y") {
        $comments_res = $db_handler->sql_query("SELECT " . $sql_table['comments'] . ".*, " . $sql_table['release'] . ".name AS release_name FROM " . $sql_table['comments'] . ", " . $sql_table['release'] . " WHERE " . $sql_table['release'] . ".release_id=" . $sql_table['comments'] . ".release_id AND " . $sql_table['comments'] . ".time>'" . $db_handler->sql_escape_int($user_details['lastactive']) . "'");
        if ($db_handler->sql_num_rows($comments_res) > 0) {
            ?>
<section class="card pdl-card mb-4">
    <header class="card-header">
        <h2 class="h5 mb-0">Neue Kommentare</h2>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Titel</th>
                    <th scope="col">Release</th>
                    <th scope="col">Autor</th>
                    <th scope="col" class="text-end">Optionen</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($comments_row = $db_handler->sql_fetch_array($comments_res)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($comments_row['titel']); ?></td>
                    <td><a href="editrelease.php?release_id=<?php echo (int)$comments_row['release_id']; ?>"><?php echo htmlspecialchars($comments_row['release_name']); ?></a></td>
                    <td>
                        <?php if ($comments_row['user_id'] == 0) {
                            echo "Gast";
                        } else {
                            echo user($comments_row['user_id']);
                        } ?>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <a class="btn btn-outline-light" href="editcomment.php?comment_id=<?php echo (int)$comments_row['comment_id']; ?>">ändern</a>
                            <a class="btn btn-outline-danger" href="delcomment.php?comment_id=<?php echo (int)$comments_row['comment_id']; ?>">löschen</a>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
            <?php
        }
    }

    $no_releases = $release_res === null || $db_handler->sql_num_rows($release_res) == 0;
    $no_comments = $comments_res === null || $db_handler->sql_num_rows($comments_res) == 0;

    if ($no_releases && $no_comments) {
        echo pdl_admin_alert('info', 'Leider war seit dem letzten Login nichts los.');
    } else {
        echo '<a class="btn btn-primary" href="index.php?resetactive=1">Login bestätigen</a>';
    }

    $qa_release   = (($user_rights['adminaccess'] ?? '') === 'Y');
    $qa_dir       = (($user_rights['adddirs'] ?? '') === 'Y');
    $qa_users     = (($user_rights['edituser'] ?? '') === 'Y');
    $qa_templates = (($user_rights['templates'] ?? '') === 'Y');
    if ($qa_release || $qa_dir || $qa_users || $qa_templates) {
        ?>
<section class="card pdl-card mt-4 mb-4"><header class="card-header"><h2 class="h6 mb-0">Schnellzugriff</h2></header>
<div class="card-body d-flex flex-wrap gap-2">
    <?php if ($qa_release) { ?><a class="btn btn-primary" href="addrelease.php">Neues Release</a><?php } ?>
    <?php if ($qa_dir) { ?><a class="btn btn-outline-primary" href="adddir.php">Neuer Ordner</a><?php } ?>
    <?php if ($qa_users) { ?><a class="btn btn-outline-primary" href="users.php">Benutzer verwalten</a><?php } ?>
    <?php if ($qa_templates) { ?><a class="btn btn-outline-primary" href="templates.php">Templates bearbeiten</a><?php } ?>
</div></section>
        <?php
    }
} else {
    ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <section class="card pdl-card">
            <header class="card-header">
                <h1 class="h4 mb-0">Bitte einloggen</h1>
            </header>
            <div class="card-body">
                <p class="small text-muted">Bitte die Zugangsdaten eingeben. Cookies müssen aktiviert sein.</p>
                <form action="index.php?login=1" method="post" novalidate>
                    <div class="mb-3">
                        <label for="pdlAdminLoginNick" class="form-label">Nick</label>
                        <input type="text" id="pdlAdminLoginNick" name="nick" class="form-control" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="pdlAdminLoginPw" class="form-label">Passwort</label>
                        <input type="password" id="pdlAdminLoginPw" name="pw" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <p class="small mt-3 mb-0">
                    <a href="../<?php echo htmlspecialchars($settings['script_file']); ?>usercenter=lost">Zugangsdaten vergessen?</a>
                </p>
            </div>
        </section>
    </div>
</div>
    <?php
}

include("footer.inc.php");
