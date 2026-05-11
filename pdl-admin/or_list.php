<?php
include("header.inc.php");

$ordner_id = isset($_REQUEST['ordner_id']) ? (int)$_REQUEST['ordner_id'] : 0;
$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;

/**
 * Ermittelt den Namen eines Ordners (für Anzeige). Index = ID 0.
 */
function pdl_admin_ordner_name(int $id): string
{
    global $db_handler, $sql_table;
    if ($id === 0) {
        return 'Index (oberste Ebene)';
    }
    $row = $db_handler->sql_fetch_array($db_handler->sql_query(
        "SELECT name FROM " . $sql_table['ordner'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($id) . "' LIMIT 1"
    ));
    return $row ? (string) ($row['name'] ?? '') : '(unbekannter Ordner)';
}

function treeview_admin($ordner, $head, $current_id)
 {
  global $db_handler, $sql_table, $user_rights;
  if(!$head) $head = "&nbsp;&nbsp;&nbsp;";
  $treeview_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $db_handler->sql_escape_int($ordner) . "'");
  while($treeview_row = $db_handler->sql_fetch_array($treeview_res))
   {
    $row_id = (int) $treeview_row['ordner_id'];
    $releases = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($row_id) . "'"));
    $is_current = $row_id === $current_id;
    $row_class = $is_current ? ' class="table-active"' : '';
    $current_label = $is_current ? ' <span class="badge text-bg-warning ms-2">aktuell ausgewählt</span>' : '';
    echo '<tr' . $row_class . '>'
        . '<td>' . $head . '<a href="or_list.php?ordner_id=' . $row_id . '#pdl-aktuell">'
        . htmlspecialchars($treeview_row['name']) . '</a>'
        . $current_label . '</td>'
        . '<td>' . htmlspecialchars((string)$releases) . '</td>'
        . '<td><a class="btn btn-sm btn-outline-light" href="addrelease.php?ordner_id=' . $row_id . '">Release hinzufügen</a></td>'
        . '<td>' . pdlif($user_rights['adddirs'] == "Y",
            '<a class="btn btn-sm btn-outline-light" href="adddir.php?ordner_id=' . $row_id . '">Sub-Ordner hinzufügen</a>',
            '&nbsp;') . '</td>'
        . '<td>' . pdlif($user_rights['editdirs'] == "Y",
            '<a class="btn btn-sm btn-outline-light" href="editdir.php?ordner_id=' . $row_id . '">ändern</a>',
            '&nbsp;') . '</td>'
        . '<td>' . pdlif($user_rights['deldirs'] == "Y",
            '<a class="btn btn-sm btn-outline-danger" href="deldir.php?ordner_id=' . $row_id . '">löschen</a>',
            '&nbsp;') . '</td>'
        . '</tr>';
    $head2 = "&nbsp;&nbsp;&nbsp;".$head;
    treeview_admin($row_id, $head2, $current_id);
   }
 }

if($user_rights['editdirs'] == "Y" || $user_rights['deldirs'] == "Y" || $user_rights['editfiles'] == "Y" || $user_rights['delfiles'] == "Y")
 {
    $current_ordner_name = pdl_admin_ordner_name($ordner_id);

    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Ordner & Releases'],
        ['title' => $current_ordner_name],
    ]);
    echo '<h1 class="h3 pdl-page-title">Ordner und Releases</h1>';
    echo pdl_admin_alert('info', 'Diese Übersicht zeigt alle Ordner inkl. der enthaltenen Releases. Beide Menüpunkte „Releases ändern/löschen" und „Ordner ändern/löschen" führen hierher.');
?>
<div class="alert alert-info" role="alert">
    <strong>So funktioniert dieser Bereich:</strong>
    <ol class="mb-2 ps-3">
        <li>Wählen Sie unten einen <strong>Ordner</strong> aus (Klick auf den Namen).</li>
        <li>Im Block <em>"Releases im ausgewählten Ordner"</em> sehen Sie alle Releases dieses Ordners.</li>
        <li>Fügen Sie ein <strong>Release</strong> hinzu (Knopf "Release hinzufügen") und legen Sie anschließend <strong>Dateien oder Screenshots</strong> in dieses Release.</li>
    </ol>
    Eine Datei kann nur innerhalb eines Releases existieren. Wenn Sie eine Datei
    hochladen möchten, brauchen Sie also zuerst ein Release.
</div>

<section class="card pdl-card mb-4">
    <header class="card-header">
        <h2 class="h5 mb-0">Ordner-Übersicht</h2>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <caption class="visually-hidden">Übersicht aller Ordner mit Anzahl Releases und Aktionen.</caption>
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Releases</th>
                    <th scope="col" colspan="4">Aktionen für diesen Ordner</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $index_releases = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id='0'"));
                $index_active = ($ordner_id === 0);
                ?>
                <tr<?php echo $index_active ? ' class="table-active"' : ''; ?>>
                    <td><a href="or_list.php?ordner_id=0#pdl-aktuell">Index (oberste Ebene)</a><?php if ($index_active) echo ' <span class="badge text-bg-warning ms-2">aktuell ausgewählt</span>'; ?></td>
                    <td><?php echo htmlspecialchars((string)$index_releases); ?></td>
                    <td><a class="btn btn-sm btn-outline-light" href="addrelease.php?ordner_id=0">Release hinzufügen</a></td>
                    <td><?php echo pdlif($user_rights['adddirs'] == "Y", '<a class="btn btn-sm btn-outline-light" href="adddir.php?ordner_id=0">Sub-Ordner hinzufügen</a>', "&nbsp;"); ?></td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <?php
                treeview_admin(0, "", $ordner_id);
                ?>
            </tbody>
        </table>
    </div>
</section>
<?php
        $total = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "'"));
        echo '<section id="pdl-aktuell" class="card pdl-card" tabindex="-1">';
        echo '<header class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">'
            . '<h2 class="h5 mb-0">Releases im ausgewählten Ordner: <span class="badge text-bg-secondary">' . htmlspecialchars($current_ordner_name) . '</span></h2>'
            . '<a class="btn btn-sm btn-primary" href="addrelease.php?ordner_id=' . (int) $ordner_id . '">+ Release hinzufügen</a>'
            . '</header>';
        if($total == 0) {
            echo '<div class="card-body">';
            echo '<p class="mb-3">In diesem Ordner gibt es bisher <strong>keine Releases</strong>.</p>';
            echo '<p class="mb-3 form-text">Hinweis: Dateien (z.&nbsp;B. Setup-Programme oder Archive) gehören immer zu einem Release. '
                . 'Legen Sie deshalb zuerst ein Release an. Erst danach können Sie dem Release Dateien oder Screenshots hinzufügen.</p>';
            echo '<a class="btn btn-primary" href="addrelease.php?ordner_id=' . (int) $ordner_id . '">Jetzt erstes Release in diesem Ordner anlegen</a>';
            echo '</div>';
        } else {
          $temp1=$page * $settings['perpage'] - $settings['perpage'];
          $limit=$temp1.",".$settings['perpage'];
          $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "' ORDER BY " . $settings['orderby'] . " " . $settings['orderseq'] . " LIMIT " . $limit);
?>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col" class="text-end">Optionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
              while($files_row = $db_handler->sql_fetch_array($files_res))
               {
?>
                <tr>
                    <td><?php echo htmlspecialchars($files_row['name']); ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm flex-wrap" role="group">
                            <a class="btn btn-outline-light" href="addfile.php?release_id=<?php echo (int)$files_row['release_id']; ?>">Datei hinzufügen</a>
                            <a class="btn btn-outline-light" href="addscreen.php?release_id=<?php echo (int)$files_row['release_id']; ?>">Screenshot</a>
                            <?php echo pdlif($user_rights['editfiles'] == "Y", '<a class="btn btn-outline-light" href="editrelease.php?release_id=' . (int)$files_row['release_id'] . '">ändern</a>', ""); ?>
                            <?php echo pdlif($user_rights['delfiles'] == "Y", '<a class="btn btn-outline-danger" href="delrelease.php?release_id=' . (int)$files_row['release_id'] . '">löschen</a>', ""); ?>
                        </div>
                    </td>
                </tr>
<?php } ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-center">
        <?php echo seiten($total, $settings['perpage'], "&ordner_id=" . htmlspecialchars((string)$ordner_id), "or_list.php?"); ?>
    </div>
<?php }
        echo '</section>';
} else {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
}
include("footer.inc.php");
?>
