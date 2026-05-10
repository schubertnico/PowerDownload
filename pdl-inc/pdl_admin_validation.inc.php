<?php

/**
 * PowerDownload - Admin-Validation-Helper
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

/**
 * Liefert eine deutsche Klartext-Bezeichnung für einen Feld-Bezeichner aus
 * Validierungsergebnissen. Wird vom Admin-Fehler-Banner verwendet, damit
 * Nutzer keine technischen Feld-IDs sehen.
 */
function pdl_admin_field_label(string $field): string
{
    static $labels = [
        '_csrf'           => 'Sicherheits-Token',
        'name'            => 'Name',
        'ordner_id'       => 'Ordner',
        'release_id'      => 'Zugehöriger Release',
        'url'             => 'URL zur Datei',
        'size'            => 'Dateigröße',
        'mirror'          => 'Spiegel-Server',
        'autor_type'      => 'Autor-Typ',
        'autor_email'     => 'E-Mail des Autors',
        'autor_homepage'  => 'Homepage des Autors',
        'screen_g'        => 'Screenshot-Datei',
        'sordner_id'      => 'Übergeordneter Ordner',
        'text'            => 'Beschreibung',
        'released'        => 'Sichtbarkeit',
    ];
    return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
}

/**
 * Rendert das HTML für ein Validierungs-Fehler-Banner. Erwartet ein
 * Feld-zu-Meldung-Array. Liefert leeren String bei leerer Eingabe.
 *
 * @param array<string, string> $errors
 */
function pdl_admin_render_errors(array $errors): string
{
    if (empty($errors)) {
        return '';
    }
    $items = '';
    foreach ($errors as $field => $msg) {
        $label = pdl_admin_field_label((string) $field);
        $items .= '<li>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . ': '
            . htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8')
            . '</li>';
    }
    return '<strong>Bitte korrigieren Sie folgende Eingaben:</strong>'
        . '<ul class="mb-0">' . $items . '</ul>';
}

/**
 * Prüft, ob in $data die angegebenen Felder vorhanden und nicht leer sind.
 * Liefert ein Array `feldname => fehlertext` zurück. Leeres Array = OK.
 *
 * @param array<string, mixed> $data
 * @param array<int, string>   $fields
 * @return array<string, string>
 */
function pdl_validate_required(array $data, array $fields): array
{
    $errors = [];
    foreach ($fields as $field) {
        $value = $data[$field] ?? '';
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === '' || $value === null) {
            $errors[$field] = 'Pflichtfeld';
        }
    }
    return $errors;
}

/**
 * Prüft eine optionale E-Mail-Adresse. Leerer String = OK.
 * Liefert null bei Erfolg, sonst Fehlermeldung.
 */
function pdl_validate_email_optional(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
        return 'Keine gültige E-Mail-Adresse.';
    }
    return null;
}

/**
 * Prüft eine optionale URL (Whitelist http/https). Leerer String = OK.
 * Liefert null bei Erfolg, sonst Fehlermeldung.
 */
function pdl_validate_url_optional(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (filter_var($value, FILTER_VALIDATE_URL) === false) {
        return 'Keine gültige URL.';
    }
    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return 'Nur http:// oder https:// URLs sind erlaubt.';
    }
    return null;
}

/**
 * Prüft, ob ein Wert einen ganzzahligen Minimalwert erreicht.
 */
function pdl_validate_int_min(int $value, int $min): ?string
{
    if ($value < $min) {
        return 'Wert muss mindestens ' . $min . ' sein.';
    }
    return null;
}

/**
 * Prüft per DB, ob ein Ordner mit der angegebenen ID existiert.
 * Sonderfall: id = 0 = Root-Index (immer gültig).
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sqlTable
 */
function pdl_ordner_exists($db, array $sqlTable, int $id): bool
{
    if ($id === 0) {
        return true;
    }
    if ($id < 0) {
        return false;
    }
    $res = $db->sql_query(
        "SELECT ordner_id FROM " . $sqlTable['ordner'] . " WHERE ordner_id='" . $db->sql_escape_int($id) . "' LIMIT 1"
    );
    return $db->sql_num_rows($res) > 0;
}

/**
 * Prüft per DB, ob ein Release mit der angegebenen ID existiert.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sqlTable
 */
function pdl_release_exists($db, array $sqlTable, int $id): bool
{
    if ($id <= 0) {
        return false;
    }
    $res = $db->sql_query(
        "SELECT release_id FROM " . $sqlTable['release'] . " WHERE release_id='" . $db->sql_escape_int($id) . "' LIMIT 1"
    );
    return $db->sql_num_rows($res) > 0;
}

/**
 * Verhindert Self-Parent / direkte zirkuläre Hierarchie beim Ordner-Update.
 */
function pdl_validate_ordner_parent(int $ordnerId, int $newParentId): ?string
{
    if ($ordnerId !== 0 && $ordnerId === $newParentId) {
        return 'Ein Ordner kann sich nicht selbst als übergeordneten Ordner haben.';
    }
    return null;
}

/**
 * Validiert eine Screen-Upload-Information.
 * Erwartet das vollständige Upload-Array (z.B. $_FILES['screen_g']).
 * Liefert null = OK, sonst Fehlermeldung.
 *
 * @param array<string, mixed> $file
 */
function pdl_validate_screen_upload(array $file): ?string
{
    if (!isset($file['error'])) {
        return 'Kein Upload erkannt.';
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return 'Upload-Fehler (Code ' . (int) $file['error'] . ').';
    }
    if (empty($file['tmp_name']) || !is_string($file['tmp_name'])) {
        return 'Temporärer Upload-Pfad fehlt.';
    }
    // MIME-Check via finfo (echte Inhaltsprüfung, nicht Client-MIME)
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (is_string($detected)) {
                $mime = $detected;
            }
        }
    }
    if ($mime === '' && function_exists('mime_content_type')) {
        $detected = mime_content_type($file['tmp_name']);
        if (is_string($detected)) {
            $mime = $detected;
        }
    }
    if ($mime === '' && function_exists('exif_imagetype')) {
        $imgType = @exif_imagetype($file['tmp_name']);
        if ($imgType === IMAGETYPE_JPEG) {
            $mime = 'image/jpeg';
        } elseif ($imgType !== false) {
            $mime = 'image/' . image_type_to_extension($imgType, false);
        }
    }
    // Fallback: Magic-Bytes des Datei-Headers prüfen
    if ($mime === '') {
        $fh = @fopen($file['tmp_name'], 'rb');
        if ($fh !== false) {
            $header = (string) fread($fh, 12);
            fclose($fh);
            if (strncmp($header, "\xFF\xD8\xFF", 3) === 0) {
                $mime = 'image/jpeg';
            } elseif (strncmp($header, "\x89PNG\r\n\x1a\n", 8) === 0) {
                $mime = 'image/png';
            } elseif (strncmp($header, 'GIF87a', 6) === 0 || strncmp($header, 'GIF89a', 6) === 0) {
                $mime = 'image/gif';
            } elseif (strncmp($header, 'BM', 2) === 0) {
                $mime = 'image/bmp';
            } elseif (substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') {
                $mime = 'image/webp';
            }
        }
    }
    if ($mime === '') {
        return 'Dateityp konnte nicht zuverlässig ermittelt werden. Bitte JPG-Bild hochladen.';
    }
    if (!in_array($mime, ['image/jpeg', 'image/pjpeg'], true)) {
        return 'Screen muss im JPG-Format sein (erkannt: ' . htmlspecialchars($mime) . ').';
    }
    return null;
}

/**
 * Validiert einen normalen Datei-Upload (z.B. Setup.exe, archiv.zip) für den
 * Download-Bereich. Gibt null = OK zurück, sonst Fehlertext (deutsch).
 *
 * Sicherheitsmaßnahmen:
 * - Lehnt gefährliche Endungen wie .php, .phtml, .phar, .htaccess ab.
 * - Lehnt Dateien ohne Endung ab.
 * - Lehnt Dateinamen mit Pfad-Bestandteilen ab (Path-Traversal-Schutz).
 * - Prüft Dateigröße gegen Maximalgröße (Standard 100 MB, konfigurierbar).
 *
 * @param array<string, mixed> $file       Eintrag aus $_FILES.
 * @param int                  $maxBytes   Maximalgröße in Bytes (>0).
 */
function pdl_validate_file_upload(array $file, int $maxBytes = 104857600): ?string
{
    if (!isset($file['error'])) {
        return 'Kein Upload erkannt.';
    }
    $err = (int) $file['error'];
    if ($err === UPLOAD_ERR_NO_FILE) {
        return 'Bitte eine Datei auswählen.';
    }
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return 'Die Datei ist zu groß. Bitte eine kleinere Datei wählen.';
    }
    if ($err !== UPLOAD_ERR_OK) {
        return 'Upload-Fehler (Code ' . $err . '). Bitte erneut versuchen.';
    }
    if (empty($file['tmp_name']) || !is_string($file['tmp_name'])) {
        return 'Temporärer Upload-Pfad fehlt.';
    }
    $size = isset($file['size']) ? (int) $file['size'] : 0;
    if ($size <= 0) {
        return 'Die Datei ist leer.';
    }
    if ($size > $maxBytes) {
        return 'Die Datei überschreitet das erlaubte Maximum von '
            . htmlspecialchars(pdl_format_bytes($maxBytes)) . '.';
    }
    $name = isset($file['name']) ? (string) $file['name'] : '';
    if ($name === '') {
        return 'Der Datei fehlt ein Name.';
    }
    // Path-Traversal-Schutz: keine Slashes, Backslashes oder Nullbytes.
    if (preg_match('/[\\/\\\\\\x00]/', $name) === 1) {
        return 'Der Dateiname enthält unzulässige Zeichen.';
    }
    if (strpos($name, '..') !== false) {
        return 'Der Dateiname enthält unzulässige Zeichenfolgen.';
    }
    // Endung prüfen.
    $dotPos = strrpos($name, '.');
    if ($dotPos === false || $dotPos === strlen($name) - 1) {
        return 'Der Dateiname benötigt eine Endung (z.&nbsp;B. .zip).';
    }
    $ext = strtolower(substr($name, $dotPos + 1));
    $blockedExt = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'phar',
        'htaccess', 'htpasswd', 'cgi', 'pl', 'py', 'sh', 'bat', 'cmd',
    ];
    if (in_array($ext, $blockedExt, true)) {
        return 'Dateien mit der Endung „.' . htmlspecialchars($ext)
            . '" dürfen aus Sicherheitsgründen nicht hochgeladen werden.';
    }
    return null;
}

/**
 * Wandelt einen Dateinamen in eine sichere Variante um, die direkt im
 * Dateisystem verwendet werden darf. Behält nur ASCII-Buchstaben, Ziffern,
 * Punkt, Bindestrich und Unterstrich. Mehrere Punkte werden zu einem
 * zusammengezogen, führende Punkte entfernt.
 */
function pdl_sanitize_upload_filename(string $name): string
{
    $name = basename($name);
    // ASCII-Whitelist
    $clean = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    if ($clean === null) {
        $clean = '';
    }
    // Mehrfache Punkte oder Unterstriche zusammenziehen
    $clean = (string) preg_replace('/_+/', '_', $clean);
    $clean = ltrim($clean, '.');
    // Maximalgröße sicherheitshalber
    if (strlen($clean) > 120) {
        $clean = substr($clean, 0, 120);
    }
    if ($clean === '') {
        $clean = 'upload-' . bin2hex(random_bytes(4));
    }
    return $clean;
}

/**
 * Wandelt einen Wert aus der PHP-INI (z.B. "8M", "2G", "512K") in Bytes um.
 * Liefert 0 für ungültige oder negative Werte.
 */
function pdl_ini_size_in_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $num = (float) $value;
    if ($num < 0) {
        return 0;
    }
    switch ($unit) {
        case 'g':
            return (int) ($num * 1024 * 1024 * 1024);
        case 'm':
            return (int) ($num * 1024 * 1024);
        case 'k':
            return (int) ($num * 1024);
        default:
            return (int) $num;
    }
}

/**
 * Formatiert eine Bytegröße in eine kompakte deutsche Darstellung
 * (z.B. "10 MB"). Wird in Fehlertexten verwendet.
 */
function pdl_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    if ($bytes < 1024 * 1024 * 1024) {
        return round($bytes / 1024 / 1024, 1) . ' MB';
    }
    return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
}

/**
 * Sicheres Mapping der drei Autor-Typen.
 * autor_type: -1 = unbekannt, 0 = manuell, 1 = registriert
 */
function pdl_validate_autor_type(int $type): bool
{
    return in_array($type, [-1, 0, 1], true);
}

/**
 * Sammelt Validierungsergebnisse einer Release-Eingabe.
 *
 * @param array<string, mixed> $post
 * @param pdl_db_class $db
 * @param array<string, string> $sqlTable
 * @return array{errors: array<string, string>, autor_type: int}
 */
function pdl_validate_release_input(array $post, $db, array $sqlTable): array
{
    $errors = pdl_validate_required($post, ['name']);

    $ordnerId = isset($post['ordner_id']) ? (int) $post['ordner_id'] : 0;
    if (!pdl_ordner_exists($db, $sqlTable, $ordnerId)) {
        $errors['ordner_id'] = 'Zielordner existiert nicht.';
    }

    $autorType = isset($post['autor_type']) ? (int) $post['autor_type'] : -1;
    if (!pdl_validate_autor_type($autorType)) {
        $errors['autor_type'] = 'Unbekannter Autor-Typ.';
    }

    if ($autorType === 0) {
        $emailErr = pdl_validate_email_optional((string) ($post['autor_email'] ?? ''));
        if ($emailErr !== null) {
            $errors['autor_email'] = $emailErr;
        }
        $urlErr = pdl_validate_url_optional((string) ($post['autor_homepage'] ?? ''));
        if ($urlErr !== null) {
            $errors['autor_homepage'] = $urlErr;
        }
    }

    return ['errors' => $errors, 'autor_type' => $autorType];
}
