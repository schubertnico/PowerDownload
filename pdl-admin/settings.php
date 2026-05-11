<?php
include("header.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;

if($user_rights['settings'] == "Y")
 {
  if($submit == 1)
   {
    foreach($_POST as $variablenname => $wert)
     {
      $variablenname_escaped = $db_handler->sql_escape_string($variablenname);
      $wert_escaped = $db_handler->sql_escape_string($wert);
      $db_handler->sql_query("UPDATE " . $sql_table['settings'] . " SET wert='" . $wert_escaped . "' WHERE variablenname='" . $variablenname_escaped . "'");
     }
    echo pdl_admin_alert('success', '<strong>Settings uebernommen.</strong>');
    echo '<a class="btn btn-secondary" href="settings.php">Zurück zu den Settings</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System', 'href' => '#'],
        ['title' => 'Settings'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Settings</h1>';

    // Sprungnavigation
    $sgroup_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['settingsgroup'] . " ORDER BY reihenfolge ASC");
    echo '<nav aria-label="Settings-Gruppen" class="mb-4"><ul class="nav nav-pills flex-wrap gap-2">';
    while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
     {
      echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#'
          . htmlspecialchars($sgroup_row['sgroup_id'], ENT_QUOTES, 'UTF-8') . '">'
          . htmlspecialchars($sgroup_row['name'], ENT_QUOTES, 'UTF-8') . '</a></li>';
     }
    echo '</ul></nav>';

    echo '<form action="settings.php?submit=1" method="post">';

    $sgroup_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['settingsgroup'] . " ORDER BY reihenfolge ASC");
    while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
     {
      $sgroup_id_html = htmlspecialchars($sgroup_row['sgroup_id'], ENT_QUOTES, 'UTF-8');
      echo '<section class="card pdl-card mb-4" id="' . $sgroup_id_html . '">';
      echo '<header class="card-header"><h2 class="h5 mb-0">'
          . htmlspecialchars($sgroup_row['name'], ENT_QUOTES, 'UTF-8') . '</h2></header>';
      echo '<div class="card-body">';

      $sgroup_id_escaped = $db_handler->sql_escape_string($sgroup_row['sgroup_id']);
      $settings_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['settings'] . " WHERE sgroup_id='" . $sgroup_id_escaped . "' ORDER BY reihenfolge ASC");
      while($settings_row = $db_handler->sql_fetch_array($settings_res))
       {
        $field_id = 'setting_' . htmlspecialchars($settings_row['variablenname'] ?? '', ENT_QUOTES, 'UTF-8');
        $help_id = $field_id . '_help';
        echo '<div class="mb-4">';
        echo '<label for="' . $field_id . '" class="form-label fw-bold">'
            . htmlspecialchars($settings_row['name'], ENT_QUOTES, 'UTF-8') . '</label>';

        if($settings_row['eingabe'] == "anaus") {
          $is_off = ($settings_row['wert'] == "N");
          echo '<select id="' . $field_id . '" name="' . htmlspecialchars($settings_row['variablenname'], ENT_QUOTES, 'UTF-8')
              . '" class="form-select" aria-describedby="' . $help_id . '">';
          echo '<option value="Y"' . ($is_off ? '' : ' selected') . '>An</option>';
          echo '<option value="N"' . ($is_off ? ' selected' : '') . '>Aus</option>';
          echo '</select>';
        } elseif($settings_row['eingabe'] == "input") {
          echo '<input type="text" id="' . $field_id . '" name="' . htmlspecialchars($settings_row['variablenname'], ENT_QUOTES, 'UTF-8')
              . '" class="form-control" value="' . htmlspecialchars($settings_row['wert'], ENT_QUOTES, 'UTF-8')
              . '" aria-describedby="' . $help_id . '">';
        } elseif($settings_row['eingabe'] == "textarea") {
          echo '<textarea id="' . $field_id . '" name="' . htmlspecialchars($settings_row['variablenname'], ENT_QUOTES, 'UTF-8')
              . '" class="form-control" rows="5" aria-describedby="' . $help_id . '">'
              . htmlspecialchars($settings_row['wert'], ENT_QUOTES, 'UTF-8') . '</textarea>';
        } else {
          // WARNING: Original code used eval() here which is a security risk.
          // The 'eingabe' field may contain custom HTML/form elements from the database.
          // For safety, we now output the content escaped. If you need to render HTML,
          // ensure the database content is sanitized or use a whitelist approach.
          echo '<div class="form-control-plaintext text-muted small">'
              . htmlspecialchars($settings_row['eingabe'], ENT_QUOTES, 'UTF-8') . '</div>';
        }

        if (!empty($settings_row['bez'])) {
            echo '<div id="' . $help_id . '" class="form-text">'
                . htmlspecialchars($settings_row['bez'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        echo '</div>';
       }
      echo '</div></section>';
     }
    echo '<div class="d-grid d-md-flex justify-content-md-end mb-4">';
    echo '<button type="submit" class="btn btn-primary">Settings ändern</button>';
    echo '</div></form>';
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
