<?php
include("pdl-inc/pdl_header.inc.php");
include("pdl-admin/functions.inc.php");
$install = 1;

$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
$PHP_SELF = $_SERVER['PHP_SELF'] ?? '';
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerDownload <?php echo htmlspecialchars($settings['pdlversion']); ?> - Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="pdl-admin/admin.css" rel="stylesheet">
</head>
<body class="pdl-admin">
<nav class="navbar navbar-expand-lg pdl-admin-navbar">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">PowerDownload <?php echo htmlspecialchars($settings['pdlversion']); ?> - Update von 3.0.1</span>
    </div>
</nav>
<main class="container py-4">
<?php
if($step == 0)
 {
?>
<h1 class="h3 pdl-page-title">Update von 3.0.1 auf <?php echo htmlspecialchars($settings['pdlversion']); ?></h1>
<form action="<?php echo htmlspecialchars($PHP_SELF); ?>?step=1" method="post">
    <section class="card pdl-card mb-4 mx-auto" style="max-width: 720px;">
        <header class="card-header"><h2 class="h5 mb-0">Installations-Hinweise</h2></header>
        <div class="card-body">
            <p class="mb-2">Hiermit wird PowerDownload 3.0.1 auf die Version <?php echo htmlspecialchars($settings['pdlversion']); ?> aktualisiert.</p>
            <p class="mb-0">Es werden dabei einige Einträge in der Datenbank gelöscht und hinzugefuegt.</p>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Update starten</button>
        </div>
    </section>
</form>
<?php
 }
if($step == 1)
 {
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='bg'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='ordner_header'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='ordner_close'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='files_header'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='files_close'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='top_header'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='top_footer'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='latest_header'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='latest_footer'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='flop_header'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='flop_footer'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='rated_header'");
  $db_handler->sql_query("DELETE FROM " . $sql_table['template'] . " WHERE variablenname='rated_footer'");

  $db_handler->sql_query("UPDATE " . $sql_table['template'] . " SET variablenname='release_row' WHERE variablenname='files_row'");
  $db_handler->sql_query("UPDATE " . $sql_table['template'] . " SET reihenfolge='2' WHERE variablenname='top_row'");
  $db_handler->sql_query("UPDATE " . $sql_table['template'] . " SET reihenfolge='4' WHERE variablenname='flop_row'");
  $db_handler->sql_query("UPDATE " . $sql_table['template'] . " SET reihenfolge='6' WHERE variablenname='latest_row'");
  $db_handler->sql_query("UPDATE " . $sql_table['template'] . " SET reihenfolge='8' WHERE variablenname='rated_row'");

  $db_handler->sql_query("INSERT INTO " . $sql_table['template'] . " VALUES ('', 'Box Ordner Übersicht', 'Die Box für die Ordner Übersicht.\\n{rows} wird durch die Zeilen ersetz.', 'ordner_box', '$template[ordner_header]\\n{rows}\\n$template[ordner_close]', 'textarea', '4', '1')");
  $db_handler->sql_query("INSERT INTO " . $sql_table['template'] . " VALUES ('', 'Box Release Übersicht', 'Die Box für die Release Übersicht.\n{rows} wird durch die Zeilen ersetz.', 'release_box', '$template[files_header]\n{rows}\n$template[files_close]', 'textarea', 5, 1)");
  $db_handler->sql_query("INSERT INTO " . $sql_table['template'] . " VALUES ('', 'Box Top Downloads', 'Die Box für die Top Downloads.\n{rows} wird durch die Zeilen ersetz.', 'top_box', '$template[top_header]\n{rows}\n$template[top_footer]', 'textarea', 8, 1)");
  $db_handler->sql_query("INSERT INTO " . $sql_table['template'] . " VALUES ('', 'Box Flop Downloads', 'Die Box für die Flop Downloads.\n{rows} wird durch die Zeilen ersetz.', 'flop_box', '$template[flop_header]\n{rows}\n$template[flop_footer]', 'textarea', 8, 3)");
  $db_handler->sql_query("INSERT INTO " . $sql_table['template'] . " VALUES ('', 'Box Latest Downloads', 'Die Box für die Neuesten Downloads.\n{rows} wird durch die Zeilen ersetz.', 'latest_box', '$template[latest_header]\n{rows}\n$template[latest_footer]', 'textarea', 8, 5)");
  $db_handler->sql_query("INSERT INTO " . $sql_table['template'] . " VALUES ('', 'Box Rated Downloads', 'Die Box für die Best Bewertetsten Downloads.\n{rows} wird durch die Zeilen ersetz.', 'rated_box', '$template[rated_header]\n{rows}\n$template[rated_footer]', 'textarea', 8, 7)");

  $db_handler->sql_query("UPDATE " . $sql_table['templategroup'] . " SET reihenfolge='6' WHERE tgroup_id='10'");
  $db_handler->sql_query("UPDATE " . $sql_table['templategroup'] . " SET reihenfolge='7' WHERE tgroup_id='9'");
  $db_handler->sql_query("UPDATE " . $sql_table['templategroup'] . " SET reihenfolge='8' WHERE tgroup_id='7'");
  $db_handler->sql_query("UPDATE " . $sql_table['templategroup'] . " SET reihenfolge='9' WHERE tgroup_id='8'");
  $db_handler->sql_query("UPDATE " . $sql_table['templategroup'] . " SET reihenfolge='10' WHERE tgroup_id='6'");

  echo '<div class="alert alert-success" role="alert"><strong>Update erfolgreich durchgeführt.</strong> Löschen Sie nun diese Update-Datei.</div>';
 }

$rendertime2=microtime();
$rendertimetemp=explode(" ",$rendertime2);
$rendertime2=$rendertimetemp[0]+$rendertimetemp[1];
$rendertime=$rendertime2-$rendertime1;
$rendertime=round($rendertime,3);
?>
    <hr>
    <p class="text-center small text-muted">
        Renderzeit: <?php echo htmlspecialchars((string)$rendertime); ?>s &middot;
        <?php echo htmlspecialchars((string)$db_handler->querys); ?> SQL-Anfragen
    </p>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
