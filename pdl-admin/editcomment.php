<?php
include("header.inc.php");

// Extract variables from GET/POST
$comment_id = isset($_REQUEST['comment_id']) ? $_REQUEST['comment_id'] : (isset($_GET['comment_id']) ? $_GET['comment_id'] : (isset($_POST['comment_id']) ? $_POST['comment_id'] : ''));
$titel = isset($_POST['titel']) ? $_POST['titel'] : '';
$text = isset($_POST['text']) ? $_POST['text'] : '';
$submit = isset($_GET['submit']) ? $_GET['submit'] : '';

if($user_rights['editfiles'] == "Y")
 {
  $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT release_id FROM `".$sql_table['comments']."` WHERE comment_id='".$db_handler->sql_escape_string($comment_id)."'"));
  $release_id = $release['release_id'] ?? '';
  if(!$release_id)
   {
    echo pdl_admin_alert('warning', 'Bitte einen Kommentar auswählen.');
   }
  else
   {
    if($submit == 1)
     {
      $text .= "\n\nEditiert von ".$user_details['nick']." am ".date($settings['date_format']);
      $db_handler->sql_query("UPDATE `".$sql_table['comments']."` SET titel='".$db_handler->sql_escape_string($titel)."', text='".$db_handler->sql_escape_string($text)."' WHERE comment_id='".$db_handler->sql_escape_string($comment_id)."'");
      echo pdl_admin_alert('success', '<strong>Kommentar wurde geändert.</strong>');
      echo '<a class="btn btn-primary" href="editrelease.php?release_id=' . htmlspecialchars((string)$release_id) . '">Zurück zum Release</a>';
     }
    else
     {
      $comment = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM `".$sql_table['comments']."` WHERE comment_id='".$db_handler->sql_escape_string($comment_id)."'"));
      pdl_admin_breadcrumb([
          ['title' => 'Admin-Center', 'href' => 'index.php'],
          ['title' => 'Kommentare'],
          ['title' => 'Kommentar editieren'],
      ]);
      echo '<h1 class="h3 pdl-page-title">Kommentar editieren</h1>';
?>
<form action="editcomment.php?submit=1" method="post" novalidate>
    <input type="hidden" name="comment_id" value="<?php echo htmlspecialchars($comment_id); ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Kommentar-Daten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlCmTitel" class="form-label">Titel</label>
                <input type="text" id="pdlCmTitel" name="titel" class="form-control" required value="<?php echo htmlspecialchars(stripslashes($comment['titel'] ?? '')); ?>">
                <div class="form-text">Titel des Kommentars.</div>
            </div>
            <div class="mb-3">
                <label for="pdlCmText" class="form-label">Text</label>
                <textarea id="pdlCmText" name="text" class="form-control" rows="10" required><?php echo htmlspecialchars(stripslashes($comment['text'] ?? '')); ?></textarea>
                <div class="form-text">Der Kommentar selbst.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editrelease.php?release_id=<?php echo htmlspecialchars((string)$release_id); ?>" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Änderungen speichern</button>
    </div>
</form>
<?php
     }
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
