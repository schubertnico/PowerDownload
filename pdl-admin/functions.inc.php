<?php
/**
 * PowerDownload - Hilfsfunktionen für den Adminbereich.
 */

function pdlif(bool $bedingung, string $true, string $false): string
 {
  if($bedingung) return $true;
  else return $false;
 }

/**
 * Bestätigungsseite für zerstörende Aktionen (Löschen usw.).
 *
 * Das Formular wird per POST an "$action?submit=1" geschickt und enthält
 * immer ein CSRF-Token. Stabile Selektoren für Drehbücher und Tests:
 * #pdlConfirmForm, #pdlConfirmTitle, #pdlConfirmSubmit, #pdlConfirmCancel.
 *
 * @param string $text      HTML für den Kartenrumpf (versteckte Felder, Erklärung).
 * @param string $abbrechen Ziel des Knopfs „Abbrechen“ (leer = vorherige Seite bzw. Übersicht).
 */
function makedialog(string $titel, string $text, string $button, string $action, string $abbrechen = ''): string
 {
  $sep = strpos($action, '?') === false ? '?' : '&';
  if ($abbrechen === '') {
      $abbrechen = pdl_admin_back_href();
  }
  $token = function_exists('csrf_input') ? csrf_input() : '';
  return '
<form action="' . htmlspecialchars($action . $sep . 'submit=1') . '" method="post" class="pdl-confirm-form" id="pdlConfirmForm">
' . $token . '
<div class="card pdl-card mx-auto pdl-danger-action pdl-confirm-card">
    <header class="card-header bg-danger text-white">
        <h2 class="h5 mb-0" id="pdlConfirmTitle">' . htmlspecialchars($titel) . '</h2>
    </header>
    <div class="card-body">
        ' . $text . '
    </div>
    <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
        <a class="btn btn-outline-light" id="pdlConfirmCancel" href="' . htmlspecialchars($abbrechen) . '">Abbrechen</a>
        <button type="submit" class="btn btn-danger" id="pdlConfirmSubmit">' . htmlspecialchars($button) . '</button>
    </div>
</div>
</form>
  ';
 }

/**
 * Rücksprungziel für „Abbrechen“: die vorherige Seite, wenn sie aus dem
 * Adminbereich derselben Website stammt, sonst die Übersicht.
 */
function pdl_admin_back_href(): string
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($referer === '' || $host === '') {
        return 'index.php';
    }
    $parts = parse_url($referer);
    if (!is_array($parts)) {
        return 'index.php';
    }
    $refHost = strtolower(($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : ''));
    $path = $parts['path'] ?? '';
    if ($refHost !== strtolower($host) || strpos($path, '/pdl-admin/') === false) {
        return 'index.php';
    }
    $file = basename($path);
    if (preg_match('/^[a-z0-9_]+\.php$/i', $file) !== 1) {
        return 'index.php';
    }
    return $file . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

/**
 * Prüft, ob der angemeldete Benutzer den Adminbereich nutzen darf und
 * mindestens eines der genannten Rechte hat (ohne Angabe: nur Admin-Zugang).
 *
 * Zuordnung Aktion → Recht (einheitlich in allen Seiten):
 * - Release anlegen: addfiles
 * - Datei hinzufügen, Screenshot hochladen, FTP-Browser: addfiles oder editfiles
 * - Release/Datei bearbeiten: editfiles
 * - Release/Datei/Screenshot löschen: delfiles
 * - Ordner anlegen/bearbeiten/löschen: adddirs/editdirs/deldirs
 * - Kommentare bearbeiten/löschen: comment („Kommentare moderieren“)
 */
function pdl_admin_has_right(string ...$rights): bool
{
    global $user_rights, $user_details;
    if (!$user_details || ($user_rights['adminaccess'] ?? '') !== 'Y') {
        return false;
    }
    if ($rights === []) {
        return true;
    }
    foreach ($rights as $right) {
        if (($user_rights[$right] ?? '') === 'Y') {
            return true;
        }
    }
    return false;
}

/**
 * Wie pdl_admin_has_right(), gibt bei fehlendem Recht aber einen Hinweis aus.
 * Aufrufer beenden danach die Seite: include("footer.inc.php"); return;
 */
function pdl_admin_require_right(string ...$rights): bool
{
    global $user_details;
    if (pdl_admin_has_right(...$rights)) {
        return true;
    }
    if (!$user_details) {
        echo pdl_admin_alert('warning', 'Bitte melden Sie sich zuerst an. <a class="alert-link" href="index.php">Zur Anmeldung</a>');
    } else {
        echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung für diese Seite. Wenden Sie sich bitte an einen Administrator, wenn Sie Zugriff benötigen.');
    }
    return false;
}

/**
 * Fehlermeldung für eine fehlgeschlagene Datenbankänderung inklusive des
 * MySQL-Fehlertexts (der ausführliche Eintrag steht im PHP-Fehlerprotokoll).
 */
function pdl_admin_db_error(string $was): string
{
    global $db_handler;
    /** @var object|null $db_handler in Tests ein Ersatzobjekt, vor dem Verbindungsaufbau null */
    $detail = (is_object($db_handler) && method_exists($db_handler, 'sql_error')) ? (string) $db_handler->sql_error() : '';
    return pdl_admin_alert(
        'danger',
        '<strong>' . htmlspecialchars($was) . '</strong> Die Datenbank hat die Änderung abgelehnt; es wurde nichts gespeichert. '
        . 'Bitte prüfen Sie Ihre Eingaben und versuchen Sie es erneut.'
        . ($detail !== '' ? '<br><small>Technische Meldung: ' . htmlspecialchars($detail) . '</small>' : '')
    );
}

/**
 * Ergebnisseite nach einer Aktion: Überschrift, Meldung und die sinnvollen
 * nächsten Schritte als Knöpfe (#pdlResultActions, Knopf-IDs frei wählbar).
 *
 * @param array<int, array{label: string, href: string, id?: string, primary?: bool}> $actions
 */
function pdl_admin_result(string $type, string $message, array $actions = []): string
{
    $html = pdl_admin_alert($type, $message);
    if ($actions !== []) {
        $html .= '<div class="d-flex flex-wrap gap-2" id="pdlResultActions">';
        foreach ($actions as $action) {
            $class = !empty($action['primary']) ? 'btn btn-primary' : 'btn btn-outline-light';
            $id = isset($action['id']) ? ' id="' . htmlspecialchars($action['id']) . '"' : '';
            $html .= '<a class="' . $class . '"' . $id . ' href="' . htmlspecialchars($action['href']) . '">'
                . htmlspecialchars($action['label']) . '</a>';
        }
        $html .= '</div>';
    }
    return $html;
}

/**
 * Zahl mit Einheit in Einzahl oder Mehrzahl, z. B. „1 Datei“, „1.234 Dateien“.
 */
function pdl_admin_count_label(int $count, string $singular, string $plural): string
{
    return number_format($count, 0, ',', '.') . ' ' . ($count === 1 ? $singular : $plural);
}

/**
 * ORDER-BY-Klausel für Releases aus den Einstellungen, nur mit erlaubten Spalten.
 */
function pdl_admin_release_order(): string
{
    global $settings;
    $allowed = ['name' => 'name', 'time' => 'time', 'views' => 'views', 'votes' => 'votes', 'voted' => 'voted', 'text' => 'text'];
    $field = $allowed[strtolower((string) ($settings['orderby'] ?? 'name'))] ?? 'name';
    $seq = strtoupper((string) ($settings['orderseq'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
    return $field . ' ' . $seq . ', release_id ' . $seq;
}

/**
 * Lädt alle Ordner (ID => Name, Elternordner).
 *
 * @return array<int, array{name: string, parent: int}>
 */
function pdl_admin_ordner_all(): array
{
    global $db_handler, $sql_table;
    $all = [];
    $res = $db_handler->sql_query("SELECT ordner_id, sordner_id, name FROM " . $sql_table['ordner'] . " ORDER BY name ASC");
    while ($row = $db_handler->sql_fetch_array($res)) {
        $all[(int) $row['ordner_id']] = ['name' => (string) ($row['name'] ?? ''), 'parent' => (int) ($row['sordner_id'] ?? 0)];
    }
    return $all;
}

/**
 * Ordnerbaum ab Index in Anzeigereihenfolge. Sicher gegen Kreise: Jeder
 * Ordner erscheint höchstens einmal, Ordner in einem Kreis gar nicht.
 *
 * @param array<int, array{name: string, parent: int}> $all
 * @param int $skipSubtree Diesen Ordner samt Unterordnern auslassen (0 = keinen).
 * @return list<array{id: int, name: string, depth: int}>
 */
function pdl_admin_ordner_flat(array $all, int $skipSubtree = 0): array
{
    $children = [];
    foreach ($all as $id => $ordner) {
        $children[$ordner['parent']][] = $id;
    }
    $out = [];
    $visited = [];
    $walk = static function (int $parent, int $depth) use (&$walk, &$out, &$visited, $children, $all, $skipSubtree): void {
        foreach ($children[$parent] ?? [] as $id) {
            if (isset($visited[$id]) || ($skipSubtree > 0 && $id === $skipSubtree)) {
                continue;
            }
            $visited[$id] = true;
            $out[] = ['id' => $id, 'name' => $all[$id]['name'], 'depth' => $depth];
            if ($depth < 64) {
                $walk($id, $depth + 1);
            }
        }
    };
    $walk(0, 0);
    return $out;
}

/**
 * Ordner, die vom Index aus nicht erreichbar sind (Kreis oder fehlender
 * Elternordner). Sie fehlen sonst in jeder Übersicht.
 *
 * @param array<int, array{name: string, parent: int}> $all
 * @return array<int, string> ID => Name
 */
function pdl_admin_ordner_unreachable(array $all): array
{
    $reachable = [];
    foreach (pdl_admin_ordner_flat($all) as $ordner) {
        $reachable[$ordner['id']] = true;
    }
    $out = [];
    foreach ($all as $id => $ordner) {
        if (!isset($reachable[$id])) {
            $out[$id] = $ordner['name'];
        }
    }
    return $out;
}

/**
 * <option>-Liste aller Ordner mit Einrückung (ohne „Index“-Eintrag).
 * Jede Option trägt data-name mit dem reinen Ordnernamen.
 *
 * @param int $skipSubtree Diesen Ordner samt Unterordnern nicht anbieten.
 */
function pdl_admin_ordner_options(int $selected, int $skipSubtree = 0): string
{
    $html = '';
    foreach (pdl_admin_ordner_flat(pdl_admin_ordner_all(), $skipSubtree) as $ordner) {
        $indent = str_repeat("\u{00A0}\u{00A0}\u{00A0}", $ordner['depth']) . ($ordner['depth'] > 0 ? '└ ' : '');
        $name = htmlspecialchars($ordner['name'], ENT_QUOTES, 'UTF-8');
        $html .= '<option value="' . $ordner['id'] . '" data-name="' . $name . '"'
            . ($ordner['id'] === $selected ? ' selected' : '') . '>' . $indent . $name . '</option>';
    }
    return $html;
}

/**
 * Anzeigename eines Ordners; 0 = Index.
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

/**
 * Wurzelverzeichnis der Installation (eine Ebene über pdl-admin/).
 */
function pdl_admin_base_dir(): string
{
    return dirname(__DIR__);
}

/**
 * Lokaler Dateipfad zu einer Download-Adresse unter pdl-files/ oder null.
 */
function pdl_admin_local_file(string $url): ?string
{
    return pdl_local_file_path($url, pdl_admin_base_dir(), $_SERVER['HTTP_HOST'] ?? '');
}

/**
 * Löscht die zu einer Download-Adresse gehörende Datei unter pdl-files/,
 * sofern kein anderer Dateieintrag mehr auf sie verweist. Versteckte
 * Dateien und Dateien direkt in pdl-files/ (.htaccess, index.html) bleiben
 * immer erhalten. Ein leer gewordenes Release-Verzeichnis wird entfernt.
 *
 * @return bool true, wenn eine Datei gelöscht wurde.
 */
function pdl_admin_delete_local_file(string $url): bool
{
    $path = pdl_admin_local_file($url);
    if ($path === null) {
        return false;
    }
    if (pdl_admin_local_file_in_use($path)) {
        return false;
    }
    $root = realpath(pdl_admin_base_dir() . DIRECTORY_SEPARATOR . 'pdl-files');
    $dir = dirname($path);
    if ($root === false || $dir === $root || strpos(basename($path), '.') === 0) {
        return false;
    }
    if (!@unlink($path)) {
        error_log('PowerDownload: Datei konnte nicht gelöscht werden: ' . $path);
        return false;
    }
    pdl_admin_remove_empty_dir($dir, $root);
    return true;
}

/**
 * Verweist noch ein Dateieintrag auf diese lokale Datei? Verglichen wird der
 * aufgelöste Dateipfad, nicht der Text der Adresse: „pdl-files/3/a b.zip“,
 * „pdl-files/3/a%20b.zip“, „./pdl-files/3/a%20b.zip“ und
 * „https://<dieser Server>/pdl-files/3/a%20b.zip“ sind dieselbe Datei.
 */
function pdl_admin_local_file_in_use(string $path, ?string $base_dir = null): bool
{
    global $db_handler, $sql_table;
    $base_dir ??= pdl_admin_base_dir();
    $host = $_SERVER['HTTP_HOST'] ?? '';
    // Ohne Vorfilter per LIKE: Auch „pdl%2Dfiles/…“ wäre dieselbe Datei.
    $res = $db_handler->sql_query("SELECT url FROM " . $sql_table['files']);
    if ($res === false) {
        // Im Zweifel nichts löschen.
        return true;
    }
    while ($row = $db_handler->sql_fetch_array($res)) {
        if (pdl_local_file_path((string) ($row['url'] ?? ''), $base_dir, $host) === $path) {
            return true;
        }
    }
    return false;
}

/**
 * Entfernt ein leeres Unterverzeichnis von $root (etwa pdl-files/12/).
 */
function pdl_admin_remove_empty_dir(string $dir, string $root): void
{
    if ($dir === $root || strpos($dir, $root . DIRECTORY_SEPARATOR) !== 0 || !is_dir($dir)) {
        return;
    }
    $entries = @scandir($dir);
    if (is_array($entries) && count(array_diff($entries, ['.', '..'])) === 0) {
        @rmdir($dir);
    }
}

/**
 * Dateinamen eines Screenshots (groß und Vorschaubild), relativ zu pdl-admin/.
 *
 * @return array{g: string, k: string}
 */
function pdl_admin_screen_paths(int $release_id, int $screen_id): array
{
    $base = "../pdl-gfx/screens/release" . $release_id . "screen" . $screen_id;
    return ['g' => $base . "g.jpg", 'k' => $base . "k.jpg"];
}

/**
 * Löscht die Bilddateien eines Screenshots, sofern vorhanden.
 */
function pdl_admin_delete_screen_files(int $release_id, int $screen_id): void
{
    foreach (pdl_admin_screen_paths($release_id, $screen_id) as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/**
 * Inhalt der Schutzdatei für pdl-gfx/screens/: Dort werden nur Bilder
 * ausgeliefert, Skripte nie ausgeführt.
 */
function pdl_admin_screens_htaccess(): string
{
    return <<<'HTACCESS'
# Screenshots aus dem Adminbereich von PowerDownload.
#
# Hier liegen nur Bilder (release<R>screen<S>g.jpg / k.jpg). Der Webserver
# darf in diesem Verzeichnis keinerlei serverseitigen Code ausführen, auch
# nicht bei doppelten Endungen wie bild.php.png (Webspaces mit
# „AddHandler … .php“).


HTACCESS . pdl_admin_image_dir_htaccess_rules();
}

/**
 * Inhalt der Schutzdatei für pdl-files/: Jeder Direktzugriff ist gesperrt,
 * ausgeliefert wird nur über downloads.php?load_file=….
 */
function pdl_admin_files_htaccess(): string
{
    return <<<'HTACCESS'
# Download-Dateien aus dem Adminbereich von PowerDownload.
#
# Hier liegen hochgeladene Download-Dateien (auch ZIP, RAR, 7z, TAR.GZ).
# PowerDownload liefert sie ausschließlich über downloads.php?load_file=…
# aus: Nur so greifen das Recht „Downloads erlaubt“, versteckte Releases,
# der Hotlink-Schutz und der Download-Zähler. PHP liest die Dateien direkt
# aus dem Dateisystem, ein Webzugriff ist dafür nicht nötig. Direkte
# Aufrufe von pdl-files/… beantwortet der Webserver deshalb mit 403.

# Jeden Direktzugriff sperren (Apache 2.4 bzw. 2.2).
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>

# Zusätzlich: In diesem Verzeichnis nie Code ausführen, auch nicht bei
# doppelten Endungen wie datei.php.zip (Webspaces mit „AddHandler … .php“).
<IfModule mod_mime.c>
    RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .pht .phtml .phar .phps .cgi .pl .py .sh
    RemoveType .php .php3 .php4 .php5 .php7 .php8 .pht .phtml .phar .phps .cgi .pl .py .sh
</IfModule>
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
Options -ExecCGI -Indexes -MultiViews

# Verzeichnis-Index unterdrücken, falls Apache trotzdem listen wollte.
IndexIgnore *

HTACCESS;
}

/**
 * Legt die Schutzdatei in pdl-files/ an, falls sie fehlt (FTP-Programme
 * übertragen Dateien mit Punkt am Anfang oft nicht).
 */
function pdl_admin_ensure_files_htaccess(string $files_dir): void
{
    $htaccess = $files_dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (is_dir($files_dir) && !is_file($htaccess)) {
        @file_put_contents($htaccess, pdl_admin_files_htaccess());
    }
}

/**
 * Inhalt der Schutzdatei für pdl-gfx/smilies/ (wie pdl-gfx/screens/).
 */
function pdl_admin_smilies_htaccess(): string
{
    return <<<'HTACCESS'
# Smiley-Bilder von PowerDownload (mitgeliefert oder im Adminbereich unter
# „Ersetzung hinzufügen → Smiley“ hochgeladen).
#
# Hier liegen nur Bilder. Der Webserver darf in diesem Verzeichnis keinerlei
# serverseitigen Code ausführen, auch nicht bei doppelten Endungen wie
# smiley.php.png (Webspaces mit „AddHandler … .php“).


HTACCESS . pdl_admin_image_dir_htaccess_rules();
}

/**
 * Regeln für Bildverzeichnisse: keine Skriptausführung, nur Bilder
 * ausliefern, Skript-Endungen an beliebiger Stelle im Namen sperren.
 */
function pdl_admin_image_dir_htaccess_rules(): string
{
    return <<<'HTACCESS'
Options -ExecCGI -Indexes -MultiViews
<IfModule mod_mime.c>
    RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .pht .phtml .phar .phps .cgi .pl .py .sh
    RemoveType .php .php3 .php4 .php5 .php7 .php8 .pht .phtml .phar .phps .cgi .pl .py .sh
</IfModule>
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>

# Alles sperren, nur Bilddateien ausliefern (Apache 2.4 bzw. 2.2).
<FilesMatch ".*">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>
<FilesMatch "^[^.][^/]*\.(jpe?g|png|webp|gif)$">
    <IfModule mod_authz_core.c>
        Require all granted
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Allow from all
    </IfModule>
</FilesMatch>
# Skript-Endungen an beliebiger Stelle im Namen nie ausliefern.
<FilesMatch "\.(ph(p\d?|t|tml|ar|ps)|cgi|pl|py|sh)(\.|$)">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

IndexIgnore *

HTACCESS;
}

/**
 * Stellt sicher, dass pdl-gfx/screens/ existiert, beschreibbar ist und die
 * Schutzdateien enthält. Liefert den absoluten Pfad oder null.
 */
function pdl_admin_screens_dir(): ?string
{
    $dir = pdl_admin_base_dir() . DIRECTORY_SEPARATOR . 'pdl-gfx' . DIRECTORY_SEPARATOR . 'screens';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return null;
    }
    $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, pdl_admin_screens_htaccess());
    }
    $index = $dir . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($index)) {
        @file_put_contents($index, "<!DOCTYPE html>\n<html lang=\"de\"><head><meta charset=\"UTF-8\"><title>403</title></head><body></body></html>\n");
    }
    return is_writable($dir) ? $dir : null;
}

// treeview_select() is now defined in pdl_functions.inc.php

/**
 * Löscht ein Release samt Dateien, Spiegel-Servern, Screenshots und
 * Kommentaren – in der Datenbank und lokal (pdl-files/, pdl-gfx/screens/).
 * Lokale Dateien werden erst entfernt, wenn die Datenbank zugestimmt hat.
 *
 * @return bool false, wenn eine Datenbankabfrage fehlgeschlagen ist.
 */
function delrelease(int $id): bool
 {
  global $sql_table, $db_handler;
  $id_escaped = $db_handler->sql_escape_int($id);

  $screen_ids = [];
  $screens_res = $db_handler->sql_query("SELECT screen_id FROM " . $sql_table['screens'] . " WHERE release_id='$id_escaped'");
  while ($screens_row = $db_handler->sql_fetch_array($screens_res)) {
      $screen_ids[] = (int) $screens_row['screen_id'];
  }
  $file_urls = [];
  $files_res = $db_handler->sql_query("SELECT url FROM " . $sql_table['files'] . " WHERE release_id='$id_escaped'");
  while ($files_row = $db_handler->sql_fetch_array($files_res)) {
      $file_urls[] = (string) ($files_row['url'] ?? '');
  }

  $ok = true;
  foreach (['screens', 'comments', 'files', 'release'] as $table) {
      if ($db_handler->sql_query("DELETE FROM " . $sql_table[$table] . " WHERE release_id='$id_escaped'") === false) {
          $ok = false;
      }
  }
  if (!$ok) {
      return false;
  }

  foreach ($screen_ids as $screen_id) {
      pdl_admin_delete_screen_files($id, $screen_id);
  }
  foreach (array_unique($file_urls) as $url) {
      pdl_admin_delete_local_file($url);
  }
  $root = realpath(pdl_admin_base_dir() . DIRECTORY_SEPARATOR . 'pdl-files');
  if ($root !== false) {
      pdl_admin_remove_empty_dir($root . DIRECTORY_SEPARATOR . $id, $root);
  }
  return true;
 }

function check_gd(): void
 {
  global $settings;
  $settings['gdversion'] = 0;
  if(!extension_loaded("gd")) $settings['gdversion'] = 0;
  elseif(function_exists("gd_info"))
   {
    $gd_info = gd_info();
    if(strstr($gd_info['GD Version'],"2.")) $settings['gdversion'] = 2;
    elseif(strstr($gd_info['GD Version'],"1.")) $settings['gdversion'] = 1;
   }
  else
   {
    ob_start();
    phpinfo(INFO_MODULES);
    $phpinfo = strip_tags((string) ob_get_contents());
    ob_end_clean();
    if (preg_match("/gd version\s*(.*)/i",$phpinfo,$version) === 1) {
        if(strstr($version[1],"2.")) $settings['gdversion'] = 2;
        elseif(strstr($version[1],"1.")) $settings['gdversion'] = 1;
    }
   }
 }

/**
 * Rendert eine Bootstrap-Breadcrumb für den Admin-Bereich.
 *
 * @param array<int, array{title: string, href?: string}> $items
 */
function pdl_admin_breadcrumb(array $items): void
{
    echo '<nav aria-label="Navigationspfad"><ol class="breadcrumb">';
    $count = count($items);
    foreach ($items as $i => $item) {
        $title = htmlspecialchars($item['title'] ?? '');
        if ($i === $count - 1 || empty($item['href'])) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . $title . '</li>';
        } else {
            echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['href']) . '">' . $title . '</a></li>';
        }
    }
    echo '</ol></nav>';
}

/**
 * Rendert ein Bootstrap-Alert im Admin-Bereich.
 */
function pdl_admin_alert(string $type, string $message): string
{
    $allowed = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'];
    if (!in_array($type, $allowed, true)) {
        $type = 'info';
    }
    return '<div class="alert alert-' . htmlspecialchars($type) . '" role="alert">' . $message . '</div>';
}
