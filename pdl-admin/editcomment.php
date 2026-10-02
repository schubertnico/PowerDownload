<?php
include("header.inc.php");

$comment_id = isset($_GET['comment_id']) ? (int) $_GET['comment_id'] : (isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0);
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (!pdl_admin_require_right('comment')) {
    include("footer.inc.php");
    return;
}

$comment = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT * FROM `" . $sql_table['comments'] . "` WHERE comment_id='" . $db_handler->sql_escape_int($comment_id) . "' LIMIT 1"
));
$release_id = is_array($comment) ? (int) $comment['release_id'] : 0;
$release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT name, ordner_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
));
// Ohne „Releases und Dateien bearbeiten“ führt der Rückweg zur Kommentarliste.
$can_edit_release = pdl_admin_has_right('editfiles');
$back = $can_edit_release
    ? 'editrelease.php?release_id=' . $release_id . '#pdlEdComments'
    : 'comments.php?release_id=' . $release_id;

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    $can_edit_release
        ? ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell']
        : ['title' => 'Kommentare', 'href' => 'comments.php'],
    ['title' => (string) ($release['name'] ?? 'Release'), 'href' => $back],
    ['title' => 'Kommentar bearbeiten'],
]);
echo '<h1 class="h3 pdl-page-title">Kommentar bearbeiten</h1>';

if (!is_array($comment)) {
    echo pdl_admin_result('warning', 'Diesen Kommentar gibt es nicht (mehr). Bitte wählen Sie ihn über das Release aus.', [
        ['label' => 'Zur Übersicht', 'href' => 'index.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}

// Text ohne früheren Bearbeitungsvermerk; der Vermerk wird beim Speichern neu gesetzt.
$titel = (string) ($comment['titel'] ?? '');
$text = pdl_comment_strip_edit_note((string) ($comment['text'] ?? ''));
$errors = [];

if ($submit === 1) {
    $titel = (string) ($_POST['titel'] ?? '');
    $text = (string) ($_POST['text'] ?? '');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen. Bitte senden Sie das Formular erneut ab.';
    }
    if (empty($errors)) {
        $errors = pdl_validate_required(['titel' => $titel, 'text' => $text], ['titel', 'text']);
        if (!isset($errors['titel'])) {
            $lenErr = pdl_validate_max_length(trim($titel), 128);
            if ($lenErr !== null) {
                $errors['titel'] = $lenErr;
            }
        }
    }
    if (empty($errors)) {
        $new_text = pdl_comment_with_edit_note(
            rtrim($text),
            (string) ($user_details['nick'] ?? ''),
            date((string) ($settings['date_format'] ?? 'd.m.Y'))
        );
        $ok = $db_handler->sql_query(
            "UPDATE `" . $sql_table['comments'] . "` SET titel='" . $db_handler->sql_escape_string(trim($titel)) . "', "
            . "text='" . $db_handler->sql_escape_string($new_text) . "' WHERE comment_id='" . $db_handler->sql_escape_int($comment_id) . "'"
        );
        if ($ok === true) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'comment', $comment_id);
            echo pdl_admin_result('success', '<strong>Der Kommentar wurde gespeichert.</strong> Unter dem Text steht jetzt „Bearbeitet von '
                . htmlspecialchars((string) ($user_details['nick'] ?? ''), ENT_QUOTES, 'UTF-8') . '“ mit dem heutigen Datum.', [
                ['label' => 'Zurück zum Release', 'href' => $back, 'id' => 'pdlNextEditRelease', 'primary' => true],
                ['label' => 'Kommentar erneut bearbeiten', 'href' => 'editcomment.php?comment_id=' . $comment_id, 'id' => 'pdlNextEditComment'],
            ]);
            include("footer.inc.php");
            return;
        }
        $errors['_db'] = 'Der Kommentar konnte nicht gespeichert werden: ' . ($db_handler->sql_error() ?: 'unbekannter Fehler') . '.';
    }
}

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}
$author = (int) ($comment['user_id'] ?? 0) === 0 ? 'Gast' : user((int) $comment['user_id']);
?>
<p class="text-muted">Geschrieben von <?php echo $author; ?> am <?php echo htmlspecialchars(date((string) ($settings['date_format'] ?? 'd.m.Y'), (int) ($comment['time'] ?? 0))); ?>.</p>
<form action="editcomment.php?submit=1" method="post" novalidate id="pdlEdComment">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="comment_id" value="<?php echo $comment_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Kommentar</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlCmTitel" class="form-label">Titel <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlCmTitel" name="titel" maxlength="128" class="form-control<?php echo isset($errors['titel']) ? ' is-invalid' : ''; ?>" required value="<?php echo htmlspecialchars($titel, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="mb-3">
                <label for="pdlCmText" class="form-label">Text <span class="text-danger" aria-hidden="true">*</span></label>
                <textarea id="pdlCmText" name="text" class="form-control<?php echo isset($errors['text']) ? ' is-invalid' : ''; ?>" rows="10" required aria-describedby="pdlCmTextHelp"><?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div id="pdlCmTextHelp" class="form-text">Beim Speichern ergänzt PowerDownload einmalig den Vermerk „Bearbeitet von <?php echo $pdl_admin_user; ?> am …“; ein älterer Vermerk wird ersetzt.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="<?php echo htmlspecialchars($back); ?>" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlCmSave">Änderungen speichern</button>
    </div>
</form>
<?php
include("footer.inc.php");
