<?php
include("header.inc.php");

check_gd();

$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$release_id = isset($_POST['release_id']) ? (int) $_POST['release_id'] : (isset($_GET['release_id']) ? (int) $_GET['release_id'] : 0);
$text = isset($_POST['text']) ? (string) $_POST['text'] : '';
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (!pdl_admin_require_right('addfiles', 'editfiles')) {
    include("footer.inc.php");
    return;
}

$release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT release_id, name, ordner_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
));

$breadcrumb = [
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell'],
    ['title' => is_array($release) ? (string) $release['name'] : 'Release', 'href' => 'editrelease.php?release_id=' . $release_id],
    ['title' => 'Screenshot hochladen'],
];
pdl_admin_breadcrumb($breadcrumb);
echo '<h1 class="h3 pdl-page-title">Screenshot hochladen</h1>';

if (!is_array($release)) {
    echo pdl_admin_result('warning', 'Bitte wählen Sie zuerst ein Release aus. Screenshots gehören immer zu einem Release.', [
        ['label' => 'Zur Übersicht', 'href' => 'or_list.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}

// Welche Formate kann dieser Server verarbeiten? JPG geht immer (wird
// unverändert übernommen), PNG und WebP nur mit passender GD-Unterstützung.
$gd = (int) ($settings['gdversion'] ?? 0) > 0 && function_exists('imagecreatefromstring') && function_exists('imagejpeg');
$gd_types = $gd && function_exists('imagetypes') ? imagetypes() : 0;
$allowed_mimes = ['image/jpeg', 'image/pjpeg'];
if ($gd && ($gd_types & IMG_PNG)) {
    $allowed_mimes[] = 'image/png';
}
if ($gd && ($gd_types & IMG_WEBP) && function_exists('imagecreatefromwebp')) {
    $allowed_mimes[] = 'image/webp';
}
$format_names = array_values(array_unique(array_map(static fn (string $m): string => pdl_screen_formats()[$m] ?? $m, $allowed_mimes)));
$format_text = implode(', ', array_slice($format_names, 0, -1)) . (count($format_names) > 1 ? ' oder ' : '') . (string) end($format_names);
$accept = implode(',', array_unique(array_map(static fn (string $m): string => $m === 'image/pjpeg' ? 'image/jpeg' : $m, $allowed_mimes)));

$autosize = $gd && (($settings['screen_autosize'] ?? '') === 'Y');
$by_height = in_array(strtolower((string) ($settings['screen_verhalt'] ?? 'width')), ['height', 'hight'], true);
$thumb_default = max(16, (int) ($settings['screen_size'] ?? 120));
$thumb_size = isset($_POST['thumb_size']) ? (int) $_POST['thumb_size'] : (int) ($_POST[$by_height ? 'height' : 'width'] ?? $thumb_default);

/**
 * Liest ein hochgeladenes Bild als GD-Bild; transparente Flächen werden weiß.
 */
function pdl_screen_load(string $path): ?GdImage
{
    $data = @file_get_contents($path);
    if ($data === false) {
        return null;
    }
    $src = @imagecreatefromstring($data);
    if ($src === false) {
        return null;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $img = imagecreatetruecolor($w, $h);
    if ($img === false) {
        imagedestroy($src);
        return null;
    }
    imagefill($img, 0, 0, (int) imagecolorallocate($img, 255, 255, 255));
    imagecopy($img, $src, 0, 0, 0, 0, $w, $h);
    imagedestroy($src);
    return $img;
}

/**
 * Speichert eine hochgeladene Bilddatei als JPG unter $target. JPG-Dateien
 * werden unverändert übernommen, alle anderen Formate per GD umgewandelt.
 */
function pdl_screen_store(string $tmp, string $mime, string $target): bool
{
    if ($mime === 'image/jpeg' || $mime === 'image/pjpeg') {
        return move_uploaded_file($tmp, $target);
    }
    $img = pdl_screen_load($tmp);
    if ($img === null) {
        return false;
    }
    $ok = imagejpeg($img, $target, 90);
    imagedestroy($img);
    return $ok;
}

/**
 * Erzeugt ein Vorschaubild als JPG. $size gilt für die Breite oder (bei
 * $byHeight) für die Höhe; die andere Seite folgt dem Seitenverhältnis.
 */
function pdl_screen_thumbnail(string $source, string $target, int $size, bool $byHeight): bool
{
    $img = pdl_screen_load($source);
    if ($img === null) {
        return false;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    if ($byHeight) {
        $th = max(1, min($size, $h));
        $tw = max(1, (int) round($w * $th / $h));
    } else {
        $tw = max(1, min($size, $w));
        $th = max(1, (int) round($h * $tw / $w));
    }
    $thumb = imagecreatetruecolor($tw, $th);
    if ($thumb === false) {
        imagedestroy($img);
        return false;
    }
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
    $ok = imagejpeg($thumb, $target, 82);
    imagedestroy($thumb);
    imagedestroy($img);
    return $ok;
}

/**
 * Ermittelt den MIME-Typ einer hochgeladenen Bilddatei anhand ihres Inhalts.
 */
function pdl_screen_mime(string $path): string
{
    $info = @getimagesize($path);
    return is_array($info) ? $info['mime'] : '';
}

$errors = [];

if ($submit === 1) {
    $screen_g = $_FILES['screen_g'] ?? [];
    $screen_k = $_FILES['screen_k'] ?? [];
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors['screen_g'] = 'Das Bild ist größer, als der Server annimmt. Bitte verkleinern Sie es.';
    } elseif (!csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen. Bitte senden Sie das Formular erneut ab.';
    }

    if (empty($errors)) {
        $err = pdl_validate_screen_upload(is_array($screen_g) ? $screen_g : [], $allowed_mimes);
        if ($err === null && !is_uploaded_file((string) ($screen_g['tmp_name'] ?? ''))) {
            $err = 'Der Screenshot wurde nicht hochgeladen. Bitte wählen Sie die Datei erneut aus.';
        }
        if ($err === null) {
            $dims = @getimagesize((string) $screen_g['tmp_name']);
            if (is_array($dims) && $dims[0] * $dims[1] > 25000000) {
                $err = 'Das Bild ist mit ' . $dims[0] . ' × ' . $dims[1] . ' Pixeln zu groß. Bitte verkleinern Sie es auf höchstens 5000 × 5000 Pixel.';
            }
        }
        if ($err !== null) {
            $errors['screen_g'] = $err;
        }
        if (!$autosize) {
            $err = pdl_validate_screen_upload(is_array($screen_k) ? $screen_k : [], $allowed_mimes);
            if ($err === null && !is_uploaded_file((string) ($screen_k['tmp_name'] ?? ''))) {
                $err = 'Das Vorschaubild wurde nicht hochgeladen. Bitte wählen Sie die Datei erneut aus.';
            }
            if ($err !== null) {
                $errors['screen_k'] = $err;
            }
        } elseif ($thumb_size < 16 || $thumb_size > 1000) {
            $errors['thumb_size'] = 'Bitte eine Größe zwischen 16 und 1000 Pixeln angeben.';
        }
        $lenErr = pdl_validate_max_length($text, 255);
        if ($lenErr !== null) {
            $errors['text'] = $lenErr;
        }
    }

    $screens_dir = empty($errors) ? pdl_admin_screens_dir() : null;
    if (empty($errors) && $screens_dir === null) {
        $errors['_file'] = 'Das Verzeichnis pdl-gfx/screens/ fehlt oder ist nicht beschreibbar. Bitte legen Sie es an und geben Sie dem Webserver Schreibrechte.';
    }

    if (empty($errors) && $screens_dir !== null) {
        // 1. Bilder unter vorläufigem Namen speichern (Punkt am Anfang: per .htaccess gesperrt).
        $tmp_base = $screens_dir . DIRECTORY_SEPARATOR . '.upload-' . bin2hex(random_bytes(6));
        $tmp_g = $tmp_base . '-g.jpg';
        $tmp_k = $tmp_base . '-k.jpg';
        $ok = pdl_screen_store((string) $screen_g['tmp_name'], pdl_screen_mime((string) $screen_g['tmp_name']), $tmp_g);
        if ($ok) {
            $ok = $autosize
                ? pdl_screen_thumbnail($tmp_g, $tmp_k, $thumb_size, $by_height)
                : pdl_screen_store((string) $screen_k['tmp_name'], pdl_screen_mime((string) $screen_k['tmp_name']), $tmp_k);
        }
        if (!$ok) {
            foreach ([$tmp_g, $tmp_k] as $tmp) {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            $errors['_file'] = 'Das Bild konnte nicht verarbeitet werden. Bitte speichern Sie es erneut als ' . $format_text . ' und versuchen Sie es noch einmal.';
        } else {
            // 2. Datenbankeintrag anlegen, 3. Dateien auf den endgültigen Namen umbenennen.
            $insert_ok = $db_handler->sql_query(
                "INSERT INTO " . $sql_table['screens'] . " (release_id, text, views) VALUES ("
                . "'" . $db_handler->sql_escape_int($release_id) . "', "
                . "'" . $db_handler->sql_escape_string(trim($text)) . "', 0)"
            );
            $screen_id = $insert_ok === true ? (int) $db_handler->sql_insert_id() : 0;
            $paths = pdl_admin_screen_paths($release_id, $screen_id);
            if ($screen_id > 0 && @rename($tmp_g, $paths['g']) && @rename($tmp_k, $paths['k'])) {
                @chmod($paths['g'], 0644);
                @chmod($paths['k'], 0644);
                pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'screen', $screen_id);
                echo pdl_admin_result(
                    'success',
                    '<strong>Der Screenshot wurde hochgeladen.</strong> Er erscheint auf der Detailseite des Releases „'
                    . htmlspecialchars((string) $release['name'], ENT_QUOTES, 'UTF-8') . '“.'
                    . '<div class="mt-2"><img id="pdlScreenPreview" src="' . htmlspecialchars($paths['k']) . '?v=' . time() . '" alt="Vorschaubild des neuen Screenshots" class="img-thumbnail"></div>',
                    [
                        ['label' => 'Zurück zum Release', 'href' => 'editrelease.php?release_id=' . $release_id . '#pdlEdScreens', 'id' => 'pdlNextEditRelease', 'primary' => true],
                        ['label' => 'Weiteren Screenshot hochladen', 'href' => 'addscreen.php?release_id=' . $release_id, 'id' => 'pdlNextAddScreen'],
                        ['label' => 'Auf der Webseite ansehen', 'href' => '../' . (string) ($settings['script_file'] ?? '') . 'release_id=' . $release_id, 'id' => 'pdlNextPublic'],
                    ]
                );
                include("footer.inc.php");
                return;
            }
            // Aufräumen: nichts Halbfertiges zurücklassen.
            if ($screen_id > 0) {
                $db_handler->sql_query("DELETE FROM " . $sql_table['screens'] . " WHERE screen_id='" . $db_handler->sql_escape_int($screen_id) . "'");
                pdl_admin_delete_screen_files($release_id, $screen_id);
            }
            foreach ([$tmp_g, $tmp_k] as $tmp) {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            $errors[$screen_id > 0 ? '_file' : '_db'] = $screen_id > 0
                ? 'Die Bilddateien konnten nicht in pdl-gfx/screens/ gespeichert werden. Bitte prüfen Sie die Schreibrechte.'
                : 'Der Screenshot konnte nicht gespeichert werden: ' . ($db_handler->sql_error() ?: 'unbekannter Fehler') . '.';
        }
    }
}

echo '<p class="text-muted">Release: <strong>' . htmlspecialchars((string) $release['name'], ENT_QUOTES, 'UTF-8') . '</strong></p>';
if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
?>
<form action="addscreen.php?submit=1" method="post" enctype="multipart/form-data" novalidate id="pdlAddScreen">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="release_id" value="<?php echo $release_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Screenshot</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlScreenG" class="form-label">Bilddatei <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="file" id="pdlScreenG" name="screen_g" class="form-control<?php echo isset($errors['screen_g']) ? ' is-invalid' : ''; ?>" accept="<?php echo htmlspecialchars($accept); ?>" required aria-describedby="pdlScreenGHelp">
                <div id="pdlScreenGHelp" class="form-text">Erlaubt sind <?php echo htmlspecialchars($format_text); ?>. Das Bild wird als JPG gespeichert und erscheint auf der Detailseite des Releases.</div>
            </div>
        <?php if ($autosize) { ?>
            <div class="mb-3">
                <label for="<?php echo $by_height ? 'pdlScreenHeight' : 'pdlScreenWidth'; ?>" class="form-label"><?php echo $by_height ? 'Höhe' : 'Breite'; ?> des Vorschaubilds (Pixel)</label>
                <input type="number" min="16" max="1000" id="<?php echo $by_height ? 'pdlScreenHeight' : 'pdlScreenWidth'; ?>" name="thumb_size" class="form-control w-auto<?php echo isset($errors['thumb_size']) ? ' is-invalid' : ''; ?>" value="<?php echo $thumb_size; ?>">
                <div class="form-text">PowerDownload erstellt das kleine Vorschaubild selbst; die <?php echo $by_height ? 'Breite' : 'Höhe'; ?> folgt dem Seitenverhältnis.</div>
            </div>
        <?php } else { ?>
            <div class="mb-3">
                <label for="pdlScreenK" class="form-label">Vorschaubild <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="file" id="pdlScreenK" name="screen_k" class="form-control<?php echo isset($errors['screen_k']) ? ' is-invalid' : ''; ?>" accept="<?php echo htmlspecialchars($accept); ?>" required>
                <div class="form-text"><?php echo $gd
                    ? 'Die automatische Verkleinerung ist in den Einstellungen ausgeschaltet. Laden Sie deshalb zusätzlich ein kleines Vorschaubild hoch.'
                    : 'Auf diesem Server fehlt die PHP-Erweiterung GD. Laden Sie deshalb zusätzlich ein kleines Vorschaubild hoch.'; ?></div>
            </div>
        <?php } ?>
            <div class="mb-3">
                <label for="pdlScreenText" class="form-label">Untertitel</label>
                <input type="text" id="pdlScreenText" name="text" class="form-control<?php echo isset($errors['text']) ? ' is-invalid' : ''; ?>" maxlength="255" value="<?php echo $text_attr; ?>">
                <div class="form-text">Optional. Erscheint unter dem großen Bild, wenn Besucher den Screenshot öffnen.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editrelease.php?release_id=<?php echo $release_id; ?>#pdlEdScreens" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlScreenSubmit">Screenshot hochladen</button>
    </div>
</form>
<?php
include("footer.inc.php");
