<?php
include("header.inc.php");
if($user_rights['adminaccess'] == "Y")
 {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Vorlagen und Ersetzungen'],
        ['title' => 'Ersetzungen anzeigen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Ersetzungen anzeigen</h1>';
?>
<div class="alert alert-info" role="alert">
    <strong>Was sind Ersetzungen?</strong>
    Ersetzungen (auf Englisch: <em>Replacements</em>) sind Regeln, mit denen
    bestimmte Textstellen in Releases und Kommentaren automatisch umgewandelt
    werden. Es gibt drei Arten:
    <ul class="mb-2">
        <li><strong>Zensur</strong>: Diese Wörter werden in öffentlichen Texten unkenntlich gemacht.</li>
        <li><strong>Smilies</strong>: Kurzcodes wie <code>:)</code> werden durch kleine Bilder ersetzt.</li>
        <li><strong>Glossar</strong>: Begriffe werden durch eine andere Schreibweise oder einen Link ersetzt.</li>
    </ul>
    <a class="btn btn-primary btn-sm me-2" href="addreplacement.php"><strong>Neue Ersetzung hinzufügen</strong></a>
    <a class="btn btn-outline-light btn-sm" href="delreplacement.php">Ersetzungen löschen</a>
</div>

<section class="card pdl-card mb-4">
    <header class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h5 mb-0">Zensur</h2>
        <a class="btn btn-outline-light btn-sm" href="addreplacement.php?type=b">+ Zensur-Eintrag hinzufügen</a>
    </header>
    <div class="card-body">
        <p class="mb-3 form-text">Folgende Wörter werden in öffentlichen Texten zensiert:</p>
        <ul class="list-group list-group-flush">
        <?php
        $badwords_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='b' ORDER BY old ASC");
        $bw_count = 0;
        while($badwords_row = $db_handler->sql_fetch_array($badwords_res)) {
            $bw_count++;
            echo '<li class="list-group-item bg-transparent text-body">' . htmlspecialchars($badwords_row['old']) . '</li>';
        }
        if ($bw_count == 0) {
            echo '<li class="list-group-item bg-transparent text-muted">Keine Einträge bisher. <a href="addreplacement.php?type=b" class="alert-link">Jetzt einen Zensur-Eintrag hinzufügen</a>.</li>';
        }
        ?>
        </ul>
    </div>
</section>

<section class="card pdl-card mb-4">
    <header class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h5 mb-0">Smilies</h2>
        <a class="btn btn-outline-light btn-sm" href="addreplacement.php?type=s">+ Smilie hinzufügen</a>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Code</th><th scope="col">Bild</th></tr></thead>
            <tbody>
            <?php
            $smilies_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='s' ORDER BY LENGTH(old) DESC");
            $sm_count = 0;
            while($smilies_row = $db_handler->sql_fetch_array($smilies_res)) {
                $sm_count++;
            ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($smilies_row['old']); ?></code></td>
                    <td>
                    <?php
                    if(preg_match("/http:\/\//siU",$smilies_row['neu']))
                     { echo '<img src="' . htmlspecialchars($smilies_row['neu']) . '" alt="">'; }
                    else
                     { echo '<img src="../' . htmlspecialchars($smilies_row['neu']) . '" alt="">'; }
                    ?>
                    </td>
                </tr>
            <?php } if ($sm_count == 0) { ?>
                <tr><td colspan="2" class="text-muted text-center">Keine Einträge bisher. <a href="addreplacement.php?type=s" class="alert-link">Jetzt ein Smilie hinzufügen</a>.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card pdl-card mb-4">
    <header class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h5 mb-0">Glossar</h2>
        <a class="btn btn-outline-light btn-sm" href="addreplacement.php?type=g">+ Glossar-Eintrag hinzufügen</a>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Vorher</th><th scope="col">Nachher</th></tr></thead>
            <tbody>
            <?php
            $glossary_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='g' ORDER BY LENGTH(old) DESC");
            $gl_count = 0;
            while($glossary_row = $db_handler->sql_fetch_array($glossary_res)) {
                $gl_count++;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($glossary_row['old']); ?></td>
                    <td><?php echo htmlspecialchars($glossary_row['neu']); ?></td>
                </tr>
            <?php } if ($gl_count == 0) { ?>
                <tr><td colspan="2" class="text-muted text-center">Keine Einträge bisher. <a href="addreplacement.php?type=g" class="alert-link">Jetzt einen Glossar-Eintrag hinzufügen</a>.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
