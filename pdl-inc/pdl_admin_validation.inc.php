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
        '_db'             => 'Datenbank',
        '_file'           => 'Dateisystem',
        'name'            => 'Name',
        'ordner_id'       => 'Ordner',
        'release_id'      => 'Zugehöriges Release',
        'file_id'         => 'Datei',
        'comment_id'      => 'Kommentar',
        'url'             => 'URL zur Datei',
        'size'            => 'Dateigröße',
        'downloads'       => 'Downloads',
        'views'           => 'Aufrufe',
        'mirror'          => 'Spiegel-Server',
        'upload_file'     => 'Datei hochladen',
        'autor_type'      => 'Autor',
        'autor_nick'      => 'Name des Autors',
        'autor_email'     => 'E-Mail des Autors',
        'autor_homepage'  => 'Homepage des Autors',
        'autor_id'        => 'Registrierter Benutzer',
        'screen_g'        => 'Screenshot',
        'screen_k'        => 'Vorschaubild',
        'thumb_size'      => 'Größe des Vorschaubilds',
        'sordner_id'      => 'Übergeordneter Ordner',
        'release_to'      => 'Ziel für die Releases',
        'subdirs_to'      => 'Ziel für die Unterordner',
        'text'            => 'Beschreibung',
        'titel'           => 'Titel',
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
 * Verhindert Ordner-Kreise, auch indirekte (A → B → A).
 *
 * Läuft die Vorfahrenkette des neuen Elternordners bis zum Index hoch. Taucht
 * dabei der bearbeitete Ordner auf, läge der neue Elternordner in dessen
 * eigenem Teilbaum. Ein bereits bestehender Kreis in der Datenbank wird
 * erkannt und führt ebenfalls zu einer Fehlermeldung statt zu einer
 * Endlosschleife.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sqlTable
 */
function pdl_validate_ordner_no_cycle($db, array $sqlTable, int $ordnerId, int $newParentId): ?string
{
    $direct = pdl_validate_ordner_parent($ordnerId, $newParentId);
    if ($direct !== null) {
        return $direct;
    }
    $current = $newParentId;
    $visited = [];
    while ($current > 0) {
        if ($current === $ordnerId) {
            return 'Ein Ordner kann nicht in einen seiner eigenen Unterordner verschoben werden. '
                . 'Bitte wählen Sie einen Ordner außerhalb dieses Zweigs oder „Index“.';
        }
        if (isset($visited[$current]) || count($visited) >= 1000) {
            return 'Der gewählte Ordner hängt in einem Kreis aus Ordnern. '
                . 'Bitte wählen Sie „Index“ oder einen anderen Ordner.';
        }
        $visited[$current] = true;
        $row = $db->sql_fetch_array($db->sql_query(
            "SELECT sordner_id FROM " . $sqlTable['ordner'] . " WHERE ordner_id='" . $db->sql_escape_int($current) . "' LIMIT 1"
        ));
        if (!is_array($row)) {
            break;
        }
        $current = (int) ($row['sordner_id'] ?? 0);
    }
    return null;
}

/**
 * Prüft die Höchstlänge eines Textfelds (in Zeichen, UTF-8).
 */
function pdl_validate_max_length(string $value, int $max): ?string
{
    if (mb_strlen($value, 'UTF-8') > $max) {
        return 'Höchstens ' . $max . ' Zeichen erlaubt (eingegeben: ' . mb_strlen($value, 'UTF-8') . ').';
    }
    return null;
}

/**
 * Prüft die Adresse einer Download-Datei: entweder eine vollständige
 * http(s)-URL oder ein Pfad zu einer hochgeladenen Datei unter `pdl-files/`.
 */
function pdl_validate_file_url(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return 'Pflichtfeld';
    }
    $lenErr = pdl_validate_max_length($value, 255);
    if ($lenErr !== null) {
        return $lenErr;
    }
    if (preg_match('#^pdl-files/#', $value) === 1) {
        $decoded = rawurldecode($value);
        if (strpos($decoded, '..') !== false || strpos($decoded, "\0") !== false || strpos($decoded, '\\') !== false) {
            return 'Der Pfad enthält unzulässige Zeichen.';
        }
        return null;
    }
    return pdl_validate_url_optional($value);
}

/**
 * Ermittelt zu einer Datei-Adresse die lokale Datei unter `pdl-files/`,
 * falls sie auf diesem Server liegt. Akzeptiert relative Pfade
 * (`pdl-files/3/a.pdf`) und absolute URLs mit demselben Host (auch bei
 * Installation in einem Unterverzeichnis). Liefert den echten Dateipfad
 * oder null. Dateien außerhalb von `pdl-files/` werden nie geliefert.
 *
 * @param string $baseDir     Wurzelverzeichnis der Installation.
 * @param string $currentHost Host der aktuellen Anfrage (HTTP_HOST), leer = nur relative Pfade.
 */
function pdl_local_file_path(string $url, string $baseDir, string $currentHost = ''): ?string
{
    $url = trim($url);
    if ($url === '' || $baseDir === '') {
        return null;
    }
    $path = $url;
    if (preg_match('#^https?://#i', $url) === 1) {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $port = parse_url($url, PHP_URL_PORT);
        $hostPort = $host . (is_int($port) ? ':' . $port : '');
        $current = strtolower($currentHost);
        if ($current === '' || ($hostPort !== $current && $host !== $current)) {
            return null;
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        $pos = strpos($path, '/pdl-files/');
        if ($pos === false) {
            return null;
        }
        $path = substr($path, $pos + 1);
    } elseif (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) === 1) {
        return null;
    }
    $path = rawurldecode(ltrim((string) preg_replace('#^\./#', '', $path), '/'));
    if (strpos($path, 'pdl-files/') !== 0 || strpos($path, "\0") !== false || preg_match('#(^|/)\.\.(/|$)#', $path) === 1) {
        return null;
    }
    $root = realpath($baseDir . DIRECTORY_SEPARATOR . 'pdl-files');
    $real = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    if ($root === false || $real === false || !is_file($real)) {
        return null;
    }
    if (strpos($real, $root . DIRECTORY_SEPARATOR) !== 0) {
        return null;
    }
    return $real;
}

/**
 * Einheiten für die Größeneingabe (1024er-Schritte wie size()).
 *
 * @return array<string, int>
 */
function pdl_size_units(): array
{
    return ['B' => 1, 'KB' => 1024, 'MB' => 1024 * 1024, 'GB' => 1024 * 1024 * 1024];
}

/**
 * Wandelt eine Größeneingabe (Zahl mit Komma oder Punkt plus Einheit) in Byte um.
 * Leere Eingabe = 0. Liefert null bei ungültiger Eingabe.
 */
function pdl_parse_size_input(string $value, string $unit): ?int
{
    $units = pdl_size_units();
    $unit = strtoupper(trim($unit));
    if (!isset($units[$unit])) {
        return null;
    }
    $value = str_replace([' ', "\u{00A0}"], '', trim($value));
    if ($value === '') {
        return 0;
    }
    if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
        // 1.234,5 → Punkt als Tausendertrenner
        $value = str_replace('.', '', $value);
    }
    $value = str_replace(',', '.', $value);
    if (preg_match('/^\d+(\.\d+)?$/', $value) !== 1) {
        return null;
    }
    $bytes = (float) $value * $units[$unit];
    if ($bytes > 1024 ** 5) {
        return null;
    }
    return (int) round($bytes);
}

/**
 * Bereitet eine Bytezahl für das Eingabefeld auf: größte Einheit mit Wert
 * ab 1, höchstens zwei Nachkommastellen, deutsches Komma.
 *
 * @return array{value: string, unit: string}
 */
function pdl_format_size_input(int $bytes): array
{
    if ($bytes <= 0) {
        return ['value' => '', 'unit' => 'MB'];
    }
    $chosen = 'B';
    foreach (pdl_size_units() as $unit => $factor) {
        if ($bytes >= $factor) {
            $chosen = $unit;
        }
    }
    $number = $bytes / pdl_size_units()[$chosen];
    $formatted = rtrim(rtrim(number_format($number, 2, ',', ''), '0'), ',');
    return ['value' => $formatted, 'unit' => $chosen];
}

/**
 * Entfernt Bearbeitungsvermerke („Bearbeitet von … am …“, früher
 * „Editiert von … am …“) am Ende eines Kommentars.
 */
function pdl_comment_strip_edit_note(string $text): string
{
    $pattern = '/(?:\r?\n)+[ \t]*(?:Editiert|Bearbeitet) von [^\r\n]+ am [^\r\n]+\s*$/u';
    $prev = null;
    while ($prev !== $text) {
        $prev = $text;
        $text = (string) preg_replace($pattern, '', $text);
    }
    return rtrim($text);
}

/**
 * Hängt genau einen aktuellen Bearbeitungsvermerk an einen Kommentar.
 */
function pdl_comment_with_edit_note(string $text, string $nick, string $date): string
{
    return pdl_comment_strip_edit_note($text) . "\n\nBearbeitet von " . $nick . ' am ' . $date;
}

/**
 * Bildformate, die als Screenshot angenommen werden (MIME => Anzeigename).
 *
 * @return array<string, string>
 */
function pdl_screen_formats(): array
{
    return ['image/jpeg' => 'JPG', 'image/pjpeg' => 'JPG', 'image/png' => 'PNG', 'image/webp' => 'WebP'];
}

/**
 * Validiert eine Screen-Upload-Information.
 * Erwartet das vollständige Upload-Array (z.B. $_FILES['screen_g']).
 * Liefert null = OK, sonst Fehlermeldung.
 *
 * @param array<string, mixed> $file
 * @param list<string>|null    $allowedMimes Erlaubte MIME-Typen (null = JPG, PNG, WebP).
 */
function pdl_validate_screen_upload(array $file, ?array $allowedMimes = null): ?string
{
    $allowedMimes ??= array_keys(pdl_screen_formats());
    if (!isset($file['error'])) {
        return 'Kein Upload erkannt.';
    }
    if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return 'Bitte wählen Sie eine Bilddatei aus.';
    }
    if ((int) $file['error'] === UPLOAD_ERR_INI_SIZE || (int) $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return 'Das Bild ist zu groß. Bitte verkleinern Sie es und laden Sie es erneut hoch.';
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return 'Upload-Fehler (Code ' . (int) $file['error'] . '). Bitte versuchen Sie es erneut.';
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
    $formats = pdl_screen_formats();
    $names = [];
    foreach ($allowedMimes as $allowed) {
        $names[$formats[$allowed] ?? $allowed] = true;
    }
    $allowedText = implode(', ', array_keys($names));
    if ($mime === '') {
        return 'Der Dateityp konnte nicht ermittelt werden. Bitte laden Sie ein Bild im Format ' . $allowedText . ' hoch.';
    }
    if (!in_array($mime, $allowedMimes, true)) {
        return 'Dieses Bildformat wird nicht unterstützt (erkannt: ' . htmlspecialchars($mime)
            . '). Erlaubt sind: ' . $allowedText . '.';
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || $info[0] < 1 || $info[1] < 1) {
        return 'Die Datei ist kein lesbares Bild. Bitte speichern Sie den Screenshot erneut als ' . $allowedText . '.';
    }
    return null;
}

/**
 * Ist diese Dateiendung (ohne Punkt) eine, die ein Webserver als Programm
 * ausführen oder als Konfiguration lesen könnte? Groß- und Kleinschreibung
 * zählt nicht.
 *
 * Apache wertet bei „AddHandler … .php“ jede Teilendung eines Dateinamens
 * aus: „bild.php.png“ oder „paket.php.zip“ laufen dort als PHP. Die Liste
 * gilt deshalb für jede Endung, nicht nur für die letzte.
 */
function pdl_upload_extension_is_executable(string $ext): bool
{
    return preg_match(
        '/^(php\d*|pht|phtm|phtml|phps|phar|pgif|shtml?|cgi|pl|py|sh|bash|bat|cmd|asp|aspx|jsp|htaccess|htpasswd)$/i',
        $ext
    ) === 1;
}

/**
 * Validiert einen normalen Datei-Upload (z.B. Setup.exe, archiv.zip) für den
 * Download-Bereich. Gibt null = OK zurück, sonst Fehlertext (deutsch).
 *
 * Sicherheitsmaßnahmen:
 * - Lehnt gefährliche Endungen wie .php, .phtml, .phar, .htaccess ab
 *   (unabhängig von Groß- und Kleinschreibung). Gefährliche Teilendungen
 *   wie in „x.php.zip“ entschärft pdl_sanitize_upload_filename() beim
 *   Speichern („x_php.zip“).
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
    if (pdl_upload_extension_is_executable($ext)) {
        return 'Dateien mit der Endung „.' . htmlspecialchars($ext)
            . '" dürfen aus Sicherheitsgründen nicht hochgeladen werden.';
    }
    return null;
}

/**
 * Wandelt einen Dateinamen in eine sichere Variante um, die direkt im
 * Dateisystem verwendet werden darf. Behält nur ASCII-Buchstaben, Ziffern,
 * Punkt, Bindestrich und Unterstrich. Mehrere Punkte werden zu einem
 * zusammengezogen, führende und abschließende Punkte entfernt.
 *
 * Jeder Punkt vor einer ausführbaren Endung (pdl_upload_extension_is_executable)
 * wird zu „_“: „x.php.zip“ → „x_php.zip“, „x.phtml“ → „x_phtml“. So entsteht
 * nie ein gespeicherter Name, den ein Webserver mit „AddHandler“ als
 * Programm ausführt.
 */
function pdl_sanitize_upload_filename(string $name): string
{
    $name = basename($name);
    // ASCII-Whitelist
    $clean = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    if ($clean === null) {
        $clean = '';
    }
    // Maximalgröße sicherheitshalber; vor dem Entschärfen, damit das Kürzen
    // keine Endung wie „.php“ freilegt
    if (strlen($clean) > 120) {
        $clean = substr($clean, 0, 120);
    }
    // Mehrfache Punkte oder Unterstriche zusammenziehen
    $clean = (string) preg_replace('/_+/', '_', $clean);
    $clean = (string) preg_replace('/\.{2,}/', '.', $clean);
    $clean = trim($clean, '.');
    // Ausführbare Teilendungen entschärfen
    $parts = explode('.', $clean);
    $clean = array_shift($parts);
    foreach ($parts as $part) {
        $clean .= (pdl_upload_extension_is_executable($part) ? '_' : '.') . $part;
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
    if (!isset($errors['name'])) {
        $lenErr = pdl_validate_max_length(trim((string) ($post['name'] ?? '')), 128);
        if ($lenErr !== null) {
            $errors['name'] = $lenErr;
        }
    }

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
        foreach (['autor_nick', 'autor_email', 'autor_homepage'] as $field) {
            if (isset($errors[$field])) {
                continue;
            }
            $lenErr = pdl_validate_max_length(trim((string) ($post[$field] ?? '')), 128);
            if ($lenErr !== null) {
                $errors[$field] = $lenErr;
            }
        }
    }

    if ($autorType === 1) {
        $autorId = isset($post['autor_id']) ? (int) $post['autor_id'] : 0;
        $exists = $autorId > 0;
        if ($exists && isset($sqlTable['user'])) {
            $exists = $db->sql_num_rows($db->sql_query(
                "SELECT user_id FROM " . $sqlTable['user'] . " WHERE user_id='" . $db->sql_escape_int($autorId) . "' LIMIT 1"
            )) > 0;
        }
        if (!$exists) {
            $errors['autor_id'] = 'Bitte wählen Sie einen vorhandenen Benutzer aus.';
        }
    }

    return ['errors' => $errors, 'autor_type' => $autorType];
}
