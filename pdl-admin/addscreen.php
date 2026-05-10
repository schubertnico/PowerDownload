<?php
include("header.inc.php");

check_gd();

$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$release_id = isset($_REQUEST['release_id']) ? (int) $_REQUEST['release_id'] : 0;
$text = isset($_POST['text']) ? (string) $_POST['text'] : '';
$width = isset($_POST['width']) ? (int) $_POST['width'] : 0;
$height = isset($_POST['height']) ? (int) $_POST['height'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

$has_right = (($user_rights['editfiles'] ?? '') === 'Y') || (($user_rights['adminaccess'] ?? '') === 'Y');

if (!$has_right) {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
    include("footer.inc.php");
    return;
}

$errors = [];

$screen_g = isset($_FILES['screen_g']['tmp_name']) ? $_FILES['screen_g']['tmp_name'] : '';
$screen_g_file = isset($_FILES['screen_g']) && is_array($_FILES['screen_g']) ? $_FILES['screen_g'] : [];
$screen_k = isset($_FILES['screen_k']['tmp_name']) ? $_FILES['screen_k']['tmp_name'] : '';
$screen_k_file = isset($_FILES['screen_k']) && is_array($_FILES['screen_k']) ? $_FILES['screen_k'] : [];

if ($submit === 1) {
    if (!csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen.';
    }
    if (empty($errors) && !pdl_release_exists($db_handler, $sql_table, $release_id)) {
        $errors['release_id'] = 'Release existiert nicht.';
    }

    if (empty($errors)) {
        $needs_small = ($settings['gdversion'] === 0 || ($settings['screen_autosize'] ?? '') !== 'Y');
        if (!is_uploaded_file($screen_g)) {
            $errors['screen_g'] = 'Großer Screen wurde nicht hochgeladen.';
        } else {
            $err = pdl_validate_screen_upload($screen_g_file);
            if ($err !== null) {
                $errors['screen_g'] = $err;
            }
        }
        if ($needs_small) {
            if (!is_uploaded_file($screen_k)) {
                $errors['screen_k'] = 'Kleiner Screen wurde nicht hochgeladen.';
            } else {
                $err = pdl_validate_screen_upload($screen_k_file);
                if ($err !== null) {
                    $errors['screen_k'] = $err;
                }
            }
        }
    }

    if (empty($errors)) {
        $escaped_text = $db_handler->sql_escape_string($text);
        $db_handler->sql_query(
            "INSERT INTO `" . $sql_table['screens'] . "` (release_id, text) VALUES ("
            . $db_handler->sql_escape_int($release_id) . ", '$escaped_text')"
        );
        $screen_id = (int) $db_handler->sql_insert_id();

        // Pfad-Konstruktion: $release_id und $screen_id sind sicher (int)
        $target_g = "../pdl-gfx/screens/release" . $release_id . "screen" . $screen_id . "g.jpg";
        $target_k = "../pdl-gfx/screens/release" . $release_id . "screen" . $screen_id . "k.jpg";

        if (is_uploaded_file($screen_k)) {
            move_uploaded_file($screen_g, $target_g);
            move_uploaded_file($screen_k, $target_k);
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'screen', $screen_id);
            echo pdl_admin_alert('success', '<strong>Screen hochgeladen.</strong>');
            echo '<a class="btn btn-primary" href="editrelease.php?release_id=' . $release_id . '">Zurück zum Release</a>';
        } else {
            move_uploaded_file($screen_g, $target_g);
            $full = imagecreatefromjpeg($target_g);
            if ($full === false) {
                echo pdl_admin_alert('danger', 'Fehler beim Laden des Bildes.');
            } else {
                $full_size = getimagesize($target_g);
                if ($full_size === false) {
                    echo pdl_admin_alert('danger', 'Fehler beim Ermitteln der Bildgröße.');
                    imagedestroy($full);
                } else {
                    if (($settings['screen_verhalt'] ?? '') === 'width') {
                        $verhalt = $full_size[0] / max(1, $width);
                        $height = (int) ($full_size[1] / max(0.0001, $verhalt));
                    } else {
                        $verhalt = $full_size[1] / max(1, $height);
                        $width = (int) ($full_size[0] / max(0.0001, $verhalt));
                    }
                    $thumb_width = max(1, (int) $width);
                    $thumb_height = max(1, (int) $height);
                    $thumb = ($settings['gdversion'] == 2)
                        ? imagecreatetruecolor($thumb_width, $thumb_height)
                        : imagecreate($thumb_width, $thumb_height);
                    if ($thumb === false) {
                        echo pdl_admin_alert('danger', 'Fehler beim Erstellen des Thumbnails.');
                        imagedestroy($full);
                    } else {
                        if ($settings['gdversion'] == 2) {
                            imagecopyresampled($thumb, $full, 0, 0, 0, 0, $thumb_width, $thumb_height, $full_size[0], $full_size[1]);
                        } else {
                            imagecopyresized($thumb, $full, 0, 0, 0, 0, $thumb_width, $thumb_height, $full_size[0], $full_size[1]);
                        }
                        imagejpeg($thumb, $target_k, 60);
                        imagedestroy($thumb);
                        imagedestroy($full);
                        pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'screen', $screen_id);
                        echo pdl_admin_alert('success', '<strong>Screen hochgeladen und Thumbnail erstellt.</strong>');
                        echo '<a class="btn btn-primary" href="editrelease.php?release_id=' . $release_id . '">Zurück zum Release</a>';
                    }
                }
            }
        }
        include("footer.inc.php");
        return;
    }
}

pdl_admin_breadcrumb([
    ['title' => 'Admin-Center', 'href' => 'index.php'],
    ['title' => 'Releases', 'href' => 'or_list.php'],
    ['title' => 'Screen hochladen'],
]);
echo '<h1 class="h3 pdl-page-title">Screen hochladen</h1>';

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
?>
<form action="addscreen.php?submit=1" method="post" enctype="multipart/form-data" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="release_id" value="<?php echo $release_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Screen-Daten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlScreenG" class="form-label">Grosser Screen</label>
                <input type="file" id="pdlScreenG" name="screen_g" class="form-control<?php echo isset($errors['screen_g']) ? ' is-invalid' : ''; ?>" accept="image/jpeg" required>
                <div class="form-text">Hier den grossen Screen auswählen (JPG).</div>
            </div>
        <?php
        if (($settings['gdversion'] ?? 0) > 0 && (($settings['screen_autosize'] ?? '') === 'Y')) {
            if (($settings['screen_verhalt'] ?? '') === 'width') { ?>
            <div class="mb-3">
                <label for="pdlScreenWidth" class="form-label">Breite</label>
                <input type="number" id="pdlScreenWidth" name="width" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['screen_size'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-text">Geben Sie hier eine feste Breite ein. Die Höhe wird im Verhältnis gebildet.</div>
            </div>
            <?php } else { ?>
            <div class="mb-3">
                <label for="pdlScreenHeight" class="form-label">Höhe</label>
                <input type="number" id="pdlScreenHeight" name="height" class="form-control" value="<?php echo htmlspecialchars((string) ($settings['screen_size'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-text">Geben Sie hier eine feste Höhe ein. Die Breite wird im Verhältnis gebildet.</div>
            </div>
            <?php }
        } else { ?>
            <div class="mb-3">
                <label for="pdlScreenK" class="form-label">Kleiner Screen</label>
                <input type="file" id="pdlScreenK" name="screen_k" class="form-control<?php echo isset($errors['screen_k']) ? ' is-invalid' : ''; ?>" accept="image/jpeg" required>
                <div class="form-text">Da der Server keine automatische Verkleinerung unterstützt oder Sie den kleinen Screen selbst gestalten möchten, müssen Sie hier einen kleinen Screen angeben.</div>
            </div>
        <?php } ?>
            <div class="mb-3">
                <label for="pdlScreenText" class="form-label">Untertitel</label>
                <input type="text" id="pdlScreenText" name="text" class="form-control" maxlength="255" value="<?php echo $text_attr; ?>">
                <div class="form-text">Zu jedem Screen kann man auch einen Untertitel eingeben. Dieser wird nur in der Detailansicht angezeigt.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editrelease.php?release_id=<?php echo $release_id; ?>" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Screen uploaden</button>
    </div>
</form>
<?php
include("footer.inc.php");
