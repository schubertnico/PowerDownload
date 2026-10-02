<?php
/**
 * PowerDownload - Ersetzungen anzeigen (Zensur, Smileys, Glossar)
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'replacements')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('replacements'));
    include("footer.inc.php");
    return;
}

$rep_t = pdl_sys_ident($sql_table['replacements']);

/** Bildadresse eines Smileys: absolute Adressen (http/https) unverändert, sonst relativ zum Hauptverzeichnis. */
$smiley_src = static function (string $neu): string {
    return preg_match('#^https?://#i', $neu) === 1 ? $neu : '../' . ltrim($neu, '/');
};

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Vorlagen und Ersetzungen'],
    ['title' => 'Ersetzungen'],
]);
echo '<h1 class="h3 pdl-page-title">Ersetzungen</h1>';

$active = [
    'b' => ($settings['badwords_releases'] ?? 'N') === 'Y' || ($settings['badwords_comments'] ?? 'N') === 'Y',
    's' => ($settings['smilies'] ?? 'N') === 'Y',
    'g' => ($settings['glossary'] ?? 'N') === 'Y',
];
$status = static fn (bool $on): string => $on
    ? '<span class="badge text-bg-success ms-2">eingeschaltet</span>'
    : '<span class="badge text-bg-secondary ms-2">ausgeschaltet</span>';
?>
<div class="alert alert-info" role="note">
    <strong>Was sind Ersetzungen?</strong>
    Regeln, mit denen PowerDownload Textstellen in Release-Texten und Kommentaren beim Anzeigen umwandelt. Der gespeicherte Text bleibt unverändert. Es gibt drei Arten:
    <ul class="mb-2">
        <li><strong>Zensur</strong>: Unerwünschte Wörter werden durch Sternchen unkenntlich gemacht.</li>
        <li><strong>Smileys</strong>: Kürzel wie <code>:)</code> werden durch kleine Bilder ersetzt.</li>
        <li><strong>Glossar</strong>: Begriffe oder Kürzel werden durch einen anderen Text oder einen Link ersetzt.</li>
    </ul>
    Ob eine Art wirkt, schalten Sie unter <a class="alert-link" href="settings.php?gruppe=6#sgroup_6">Einstellungen → Textformatierung</a> ein oder aus.
    <div class="mt-2">
        <a class="btn btn-primary btn-sm me-2" href="addreplacement.php" id="pdlReplAdd">Neue Ersetzung hinzufügen</a>
        <a class="btn btn-outline-dark btn-sm" href="delreplacement.php" id="pdlReplDelete">Ersetzungen löschen</a>
    </div>
</div>

<section class="card pdl-card mb-4" id="pdlReplZensur">
    <header class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h5 mb-0">Zensur<?php echo $status($active['b']); ?></h2>
        <a class="btn btn-outline-light btn-sm" href="addreplacement.php?type=b">+ Zensur-Eintrag hinzufügen</a>
    </header>
    <div class="card-body">
        <p class="mb-3 form-text">Diese Wörter werden in Release-Texten und Kommentaren zensiert:</p>
        <ul class="list-group list-group-flush">
        <?php
        $badwords_res = $db_handler->sql_query('SELECT old FROM ' . $rep_t . " WHERE type = 'b' ORDER BY old ASC");
        $bw_count = 0;
        while ($badwords_row = $db_handler->sql_fetch_array($badwords_res)) {
            $bw_count++;
            echo '<li class="list-group-item bg-transparent text-body">' . htmlspecialchars((string) $badwords_row['old']) . '</li>';
        }
        if ($bw_count === 0) {
            echo '<li class="list-group-item bg-transparent text-muted">Noch keine Einträge. <a href="addreplacement.php?type=b">Jetzt einen Zensur-Eintrag hinzufügen</a>.</li>';
        }
        ?>
        </ul>
    </div>
</section>

<section class="card pdl-card mb-4" id="pdlReplSmileys">
    <header class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h5 mb-0">Smileys<?php echo $status($active['s']); ?></h2>
        <a class="btn btn-outline-light btn-sm" href="addreplacement.php?type=s">+ Smiley hinzufügen</a>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Kürzel</th><th scope="col">Bild</th></tr></thead>
            <tbody>
            <?php
            $smilies_res = $db_handler->sql_query('SELECT old, neu FROM ' . $rep_t . " WHERE type = 's' ORDER BY LENGTH(old) DESC");
            $sm_count = 0;
            while ($smilies_row = $db_handler->sql_fetch_array($smilies_res)) {
                $sm_count++;
                echo '<tr><td><code>' . htmlspecialchars((string) $smilies_row['old']) . '</code></td>'
                    . '<td><img src="' . htmlspecialchars($smiley_src((string) $smilies_row['neu'])) . '" alt="' . htmlspecialchars((string) $smilies_row['old']) . '" style="max-height: 32px"></td></tr>';
            }
            if ($sm_count === 0) {
                echo '<tr><td colspan="2" class="text-muted text-center">Noch keine Einträge. <a href="addreplacement.php?type=s">Jetzt einen Smiley hinzufügen</a>.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card pdl-card mb-4" id="pdlReplGlossar">
    <header class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h5 mb-0">Glossar<?php echo $status($active['g']); ?></h2>
        <a class="btn btn-outline-light btn-sm" href="addreplacement.php?type=g">+ Glossar-Eintrag hinzufügen</a>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Begriff im Text</th><th scope="col">wird ersetzt durch</th></tr></thead>
            <tbody>
            <?php
            $glossary_res = $db_handler->sql_query('SELECT old, neu FROM ' . $rep_t . " WHERE type = 'g' ORDER BY LENGTH(old) DESC");
            $gl_count = 0;
            while ($glossary_row = $db_handler->sql_fetch_array($glossary_res)) {
                $gl_count++;
                echo '<tr><td><code>' . htmlspecialchars((string) $glossary_row['old']) . '</code></td><td>' . htmlspecialchars((string) $glossary_row['neu']) . '</td></tr>';
            }
            if ($gl_count === 0) {
                echo '<tr><td colspan="2" class="text-muted text-center">Noch keine Einträge. <a href="addreplacement.php?type=g">Jetzt einen Glossar-Eintrag hinzufügen</a>.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
</section>
<?php
include("footer.inc.php");
