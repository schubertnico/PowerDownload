<?php

/**
 * PowerDownload - Functions
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);

// Downloadzeit Berechnung
function dlspeed(int|float $size): string
{
    global $settings;
    $dlspeed = (float) ($settings['dlspeed'] ?? 1);
    if ($dlspeed <= 0) $dlspeed = 1;

    $sekunden = round($size / 1024 / $dlspeed, 2);
    if ($sekunden >= 60) {
        $minuten = round($sekunden / 60, 2);
        if ($minuten >= 60) {
            $stunden = round($minuten / 60, 2);
            $stunden_parts = explode(".", (string) $stunden);
            $mins = (int) ceil((((int) ($stunden_parts[1] ?? 0)) * 60) / 100);
            $seks = (int) ceil(($mins * 60) / 100);
            return $stunden_parts[0] . "std, " . $mins . "min, " . $seks . "sek";
        } else {
            $mins_parts = explode(".", (string) $minuten);
            $seks = (int) ceil((((int) ($mins_parts[1] ?? 0)) * 60) / 100);
            return $mins_parts[0] . "min, " . $seks . "sek";
        }
    } else {
        $sekunden = (int) ceil($sekunden);
        return $sekunden . "sek";
    }
}

/**
 * Höchste Verschachtelungstiefe, bis zu der die Ordnerbäume gezeichnet
 * werden. Schützt vor Endlosschleifen, falls die Datenbank (etwa nach einer
 * eingespielten Sicherung) Ordner enthält, die im Kreis aufeinander zeigen.
 */
function pdl_tree_max_depth(): int
{
    return 32;
}

// Normales Ordner Treeview
function treeview_ordner(int $ordner, string $head, int $depth = 0): void
{
    global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;

    if ($depth >= pdl_tree_max_depth()) {
        return;
    }
    $ordner_escaped = $db_handler->sql_escape_int($ordner);
    $treeview_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $ordner_escaped . "'");
    while ($treeview_row = $db_handler->sql_fetch_array($treeview_res)) {
        if (!$head) {
            $head = '<img src="pdl-gfx/spacer.gif" border="0" width="15">';
        }
        $row_ordner_id = (int) $treeview_row['ordner_id'];
        $row_name = htmlspecialchars($treeview_row['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $script_file = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');

        if ($sordner_id == $row_ordner_id) {
            echo $head . '<img src="pdl-gfx/folder_open.gif" border="0"> ';
            if ($release && empty($screen_id)) {
                $release_name = htmlspecialchars($release['name'] ?? '', ENT_QUOTES, 'UTF-8');
                echo '<a href="' . $script_file . 'ordner_id=' . $row_ordner_id . '">' . $row_name . '</a> &raquo; ' . $release_name . "<br>\n";
            } elseif (!empty($screen_id)) {
                $release_name = htmlspecialchars($release['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $release_id = (int) ($release['release_id'] ?? 0);
                echo '<a href="' . $script_file . 'ordner_id=' . $row_ordner_id . '">' . $row_name . '</a> &raquo; <a href="' . $script_file . 'release_id=' . $release_id . '">' . $release_name . '</a> &raquo; Screenshot';
            } else {
                echo $row_name . "<br>\n";
            }
        } else {
            echo $head . '<img src="pdl-gfx/folder.gif" border="0"> <a href="' . $script_file . 'ordner_id=' . $row_ordner_id . '">' . $row_name . "</a><br>\n";
        }
        $head2 = '<img src="pdl-gfx/spacer.gif" border="0" width="15">' . $head;
        treeview_ordner($row_ordner_id, $head2, $depth + 1);
    }
}

/**
 * Treeview mit Pfeil: ordner > ordner1 ...
 *
 * @param array<int, true> $visited bereits ausgegebene Ordner (Schutz vor Kreisen)
 */
function treeview_pfeil(int $ordner, array $visited = []): void
{
    global $db_handler, $settings, $sql_table, $release_id, $screen_id, $ordner_id;

    $visited[$ordner] = true;
    $ordner_escaped = $db_handler->sql_escape_int($ordner);
    $subdir_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['ordner'] . " WHERE ordner_id='" . $ordner_escaped . "'");
    while ($subdir_row = $db_handler->sql_fetch_array($subdir_res)) {
        $script_file = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');
        $row_sordner_id = (int) $subdir_row['sordner_id'];
        $row_ordner_id = (int) $subdir_row['ordner_id'];
        $row_name = htmlspecialchars($subdir_row['name'] ?? '', ENT_QUOTES, 'UTF-8');

        if ($row_sordner_id != 0 && !isset($visited[$row_sordner_id]) && count($visited) < pdl_tree_max_depth()) {
            treeview_pfeil($row_sordner_id, $visited);
            echo " &raquo; ";
        } else {
            echo '<a href="' . $script_file . 'ordner_id=0">Index</a> &raquo; ';
        }
        if ($screen_id || $release_id || $ordner != $ordner_id) {
            echo '<a href="' . $script_file . 'ordner_id=' . $row_ordner_id . '">' . $row_name . '</a>';
        } else {
            echo $row_name;
        }
    }
}

// Durchschalten der Alternativfarben
function alt_switch(): string
{
    global $alt_switch, $template;

    if (!isset($alt_switch)) {
        $alt_switch = 0;
    }

    $alt_switch++;
    if ($alt_switch == 2) {
        $alt_switch = 0;
        $alt = $template['alt_2'] ?? '';
    } elseif ($alt_switch == 1) {
        $alt = $template['alt_1'] ?? '';
    } else {
        $alt = $template['alt_1'] ?? '';
    }
    return $alt;
}

// Bytewerte in deutscher Schreibweise: "53 B", "1,4 MB", "2 GB".
// Unter 1024 Byte ganze Bytes, sonst eine Nachkommastelle mit Komma
// (",0" entfällt), Tausender mit Punkt.
function size(int|float $size): string
{
    $size = max(0.0, (float) $size);
    if ($size < 1024) {
        return number_format(round($size), 0, ',', '.') . ' B';
    }

    $value = $size / 1024;
    $unit = 'KB';
    foreach (['MB', 'GB', 'TB'] as $next_unit) {
        if (round($value, 1) < 1024) {
            break;
        }
        $value /= 1024;
        $unit = $next_unit;
    }

    $formatted = number_format(round($value, 1), 1, ',', '.');
    if (str_ends_with($formatted, ',0')) {
        $formatted = substr($formatted, 0, -2);
    }
    return $formatted . ' ' . $unit;
}

// Zahl mit Einheit in Einzahl oder Mehrzahl, z. B. "1 Download", "1.234 Downloads".
function pdl_count_label(int $count, string $singular, string $plural): string
{
    return number_format($count, 0, ',', '.') . ' ' . ($count === 1 ? $singular : $plural);
}

// Helper: Simple string replacements for replace()
function replace_simple_vars(string $temp, array $table_row, array $template, array $settings, ?string $list): string
{
    $simple = [
        '{name}' => htmlspecialchars($table_row['name'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{titel}' => htmlspecialchars($table_row['titel'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{votes}' => (string) ($table_row['votes'] ?? 0),
        '{vote}' => (string) ($table_row['vote'] ?? ''),
        '{vote_form}' => $table_row['vote_form'] ?? '',
        '{downloads}' => (string) ($table_row['downloads'] ?? 0),
        '{downloads_text}' => (string) ($table_row['downloads_text'] ?? pdl_count_label((int) ($table_row['downloads'] ?? 0), 'Download', 'Downloads')),
        '{views}' => (string) ($table_row['views'] ?? 0),
        '{text}' => nl2br(htmlspecialchars($table_row['text'] ?? '', ENT_QUOTES, 'UTF-8')),
        '{screens}' => $table_row['screens'] ?? '',
        '{id}' => (string) ($table_row['id'] ?? ''),
        '{autor}' => htmlspecialchars($table_row['autor'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{alt_1}' => $template['alt_1'] ?? '',
        '{alt_2}' => $template['alt_2'] ?? '',
        '{footer_bg}' => $template['footer_bg'] ?? '',
        '{header_bg}' => $template['header_bg'] ?? '',
        '{table_border}' => $template['table_border'] ?? '',
        '{script_file}' => $settings['script_file'] ?? '',
        '{files}' => (string) ($table_row['files'] ?? ''),
        '{subdirs}' => (string) ($table_row['subdirs'] ?? ''),
        '{filename}' => htmlspecialchars($table_row['filename'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{list}' => $list ?? '',
    ];
    return str_replace(array_keys($simple), array_values($simple), $temp);
}

// Helper: Expensive replacements (only if placeholder exists)
function replace_expensive_vars(string $temp, array $table_row, array $settings): string
{
    if (str_contains($temp, '{size}')) {
        $temp = str_replace('{size}', size((int) ($table_row['size'] ?? 0)), $temp);
    }
    if (str_contains($temp, '{dlspeed}')) {
        $temp = str_replace('{dlspeed}', dlspeed((int) ($table_row['size'] ?? 0)), $temp);
    }
    if (str_contains($temp, '{time}')) {
        $date_format = $settings['date_format'] ?? 'd.m.Y';
        $temp = str_replace('{time}', date($date_format, (int) ($table_row['time'] ?? 0)), $temp);
    }
    if (str_contains($temp, '{uploader}')) {
        $temp = str_replace('{uploader}', user((int) ($table_row['uploader'] ?? 0)), $temp);
    }
    if (str_contains($temp, '{alt}')) {
        $temp = str_replace('{alt}', alt_switch(), $temp);
    }
    if (str_contains($temp, '{traffic}')) {
        $temp = str_replace('{traffic}', size((int) ($table_row['traffic'] ?? 0)), $temp);
    }
    return $temp;
}

// Ersetzt die Template Variablen durch die dazugehörigen Werte.
function replace(string $temp, array $table_row): string
{
    global $settings, $list, $template, $total;

    $temp = replace_simple_vars($temp, $table_row, $template ?? [], $settings ?? [], $list);
    $temp = replace_expensive_vars($temp, $table_row, $settings ?? []);

    // Count depends on context
    $hasRows = str_contains($temp, '{rows}');
    $temp = str_replace('{count}', (string) ($hasRows ? ($total ?? 0) : ($table_row['count'] ?? 0)), $temp);

    if ($hasRows) {
        $rows = implode(', ', array_map('strval', $table_row));
        $temp = str_replace('{rows}', $rows, $temp);
    }

    return $temp;
}

// Macht aus einer Userid den User mit Nick/Homepage.
// Im öffentlichen Bereich nur der Name (keine E-Mail-Adresse als mailto-Link),
// im Admin-Center zusätzlich der mailto-Link.
function user(int $user_id): string
{
    global $users, $inadmin;
    // Typ ausdrücklich angeben: Psalm übernähme sonst den Wert aus dem
    // einbindenden Admin-Skript und hielte die Prüfungen für überflüssig.
    /** @var int|null $inadmin 1 im Admin-Center, 0 im öffentlichen Bereich */
    if (!isset($users[$user_id]['nick']) || !$users[$user_id]['nick']) {
        return "Gelöscht";
    }

    $email = (string) ($users[$user_id]['email'] ?? '');
    $nick = htmlspecialchars($users[$user_id]['nick'] ?? '', ENT_QUOTES, 'UTF-8');
    if ($inadmin == 1 && $email !== '') {
        $user = '<a href="mailto:' . $email . '">' . $nick . '</a>';
    } else {
        $user = $nick;
    }

    $homepage = pdl_safe_url((string) ($users[$user_id]['homepage'] ?? ''), ['http', 'https'], false);
    if ($homepage !== null) {
        $user .= ' <a href="' . htmlspecialchars($homepage, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener nofollow ugc"><img src="';
        if ($inadmin == 1) {
            $user .= "../";
        }
        $user .= 'pdl-gfx/www.gif" class="pdl-www-icon" alt="Homepage von ' . $nick . '"></a>';
    }

    return $user;
}

// Helper: Generate a single page link
function seiten_page_link(string $file, int $pageNum, string $link, bool $isCurrent): string
{
    if ($isCurrent) {
        return "<b>[$pageNum]</b> ";
    }
    $safeFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    return '<a href="' . $safeFile . 'page=' . $pageNum . $safeLink . '">' . $pageNum . '</a> ';
}

// Helper: Calculate page range for limited pagination
function seiten_calc_range(int $currentPage, int $totalPages, int $maxVisible): array
{
    $half = (int) ceil(($maxVisible - 1) / 2);
    $beforePages = $half;
    $afterPages = $half;

    if ($currentPage <= $half) {
        $beforePages = $currentPage - 1;
        $afterPages = $half + ($half - $beforePages);
    } elseif ($currentPage >= $totalPages - $half) {
        $afterPages = $totalPages - $currentPage;
        $beforePages = $half + ($half - $afterPages);
    }

    return ['before' => $beforePages, 'after' => $afterPages];
}

// Helper: Render limited page range around current page
function seiten_render_limited(int $currentPage, int $totalPages, int $maxVisible, string $file, string $link): string
{
    $range = seiten_calc_range($currentPage, $totalPages, $maxVisible);
    $output = '';

    $start = (int) max(1, $currentPage - $range['before']);
    for ($j = $start; $j < $currentPage; $j++) {
        $output .= seiten_page_link($file, $j, $link, false);
    }

    $output .= "<b>[$currentPage]</b> ";

    $end = min($totalPages, $currentPage + $range['after']);
    for ($j = $currentPage + 1; $j <= $end; $j++) {
        $output .= seiten_page_link($file, $j, $link, false);
    }

    return $output;
}

// Zeigt die Seiten an.
function seiten(int $total, int $perpage, string $link, string $file): string
{
    global $page, $settings;

    if ($total <= $perpage) {
        return '';
    }

    $perpage = max(1, $perpage);
    $totalPages = (int) ceil($total / $perpage);
    $maxVisible = (int) ($settings['spages'] ?? 0);
    $showLimited = $maxVisible > 0 && $totalPages >= $maxVisible;

    $safeFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    $output = "Seiten ($totalPages): ";

    // First page link
    if ($page > 1) {
        $output .= '<a href="' . $safeFile . 'page=1' . $safeLink . '">&laquo;</a> ';
    }

    // Page numbers
    if ($showLimited) {
        $output .= seiten_render_limited($page, $totalPages, $maxVisible, $file, $link);
    } else {
        for ($j = 1; $j <= $totalPages; $j++) {
            $output .= seiten_page_link($file, $j, $link, $page == $j);
        }
    }

    // Last page link
    if ($totalPages > $page) {
        $output .= '<a href="' . $safeFile . 'page=' . $totalPages . $safeLink . '">&raquo;</a>';
    }

    return $output;
}

// Helper: Apply smilies to text
function bbcode_apply_smilies(string $text, array $smilies): string
{
    foreach ($smilies as $smiley) {
        $old = $smiley['old'] ?? '';
        $neu = $smiley['neu'] ?? '';
        if ($old !== '') {
            $text = str_replace($old, '<img src="' . htmlspecialchars($neu, ENT_QUOTES, 'UTF-8') . '" class="pdl-smilie" alt="">', $text);
        }
    }
    return $text;
}

/**
 * Prüft eine Adresse aus Benutzereingaben (BBCode, Homepage) und liefert sie
 * normalisiert zurück, oder null, wenn sie nicht verlinkt werden darf.
 *
 * - Erlaubt sind nur die Schemata aus $schemes (Vorgabe http, https, mailto).
 *   javascript:, data:, vbscript: usw. ergeben null.
 * - Entities werden vor der Prüfung aufgelöst (java&#115;cript: hilft nicht).
 * - Steuerzeichen, Leerraum und < > " ergeben null.
 * - Ohne Schema: relative Pfade (/x, ./x, ?x, #x, datei.php) bleiben, wenn
 *   $allowRelative gilt, alles andere wird als Webadresse mit http:// ergänzt.
 *
 * Die Rückgabe ist roh; vor der Ausgabe mit htmlspecialchars() maskieren.
 *
 * @param list<string> $schemes
 */
function pdl_safe_url(string $url, array $schemes = ['http', 'https', 'mailto'], bool $allowRelative = true): ?string
{
    $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $url = trim($url, "\"'");
    if ($url === '' || preg_match('/[\x00-\x20\x7F<>"]/', $url)) {
        return null;
    }

    if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $m) && !str_contains($m[1], '.')) {
        return in_array(strtolower($m[1]), $schemes, true) ? $url : null;
    }

    if (str_starts_with($url, '//')) {
        return in_array('https', $schemes, true) ? 'https:' . $url : null;
    }

    if ($allowRelative && preg_match('~^(/|\./|\.\./|\?|#|[\w\-]+\.php)~', $url)) {
        return $url;
    }

    return in_array('http', $schemes, true) ? 'http://' . $url : null;
}

// Helper: Apply BBCode tags to text
// Links und Bilder laufen über pdl_safe_url(): nur http, https und mailto
// (Bilder nur http/https). Andere Schemata bleiben als reiner Text stehen.
function bbcode_apply_tags(string $text): string
{
    $text = preg_replace(
        ['/\[b\](.*?)\[\/b\]/si', '/\[i\](.*?)\[\/i\]/si', '/\[u\](.*?)\[\/u\]/si'],
        ['<b>$1</b>', '<i>$1</i>', '<u>$1</u>'],
        $text
    ) ?? $text;

    // Freistehende Adressen in BBCode umwandeln (nur http/https, www. und E-Mail)
    $text = preg_replace_callback(
        '/([\n ])([a-z][a-z0-9+.\-]*):\/\/([^\t <\n\r]+)/i',
        static function (array $m): string {
            $scheme = strtolower($m[2]);
            if ($scheme !== 'http' && $scheme !== 'https') {
                return $m[0];
            }
            return $m[1] . '[url]' . $m[2] . '://' . $m[3] . '[/url]';
        },
        $text
    ) ?? $text;
    $text = preg_replace('/([\n ])(www\.[^\t <\n\r]+)/i', '$1[url]http://$2[/url]', $text) ?? $text;
    $text = preg_replace('/([\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)?[\w]+)/i', '$1[email]$2@$3[/email]', $text) ?? $text;

    $link = static function (string $url, string $label): string {
        $safe = pdl_safe_url($url);
        if ($safe === null) {
            return $label;
        }
        return '<a href="' . htmlspecialchars($safe, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener nofollow ugc">' . $label . '</a>';
    };

    $text = preg_replace_callback(
        '/\[url\](.*?)\[\/url\]/si',
        static fn (array $m): string => $link($m[1], $m[1]),
        $text
    ) ?? $text;
    $text = preg_replace_callback(
        '/\[url=([^\]]*)\](.*?)\[\/url\]/si',
        static fn (array $m): string => $link($m[1], $m[2]),
        $text
    ) ?? $text;
    $text = preg_replace_callback(
        '/\[email\](.*?)\[\/email\]/si',
        static function (array $m): string {
            $address = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!preg_match('/^[^\s<>"\'@]+@[^\s<>"\'@]+\.[^\s<>"\'@]+$/', $address)) {
                return $m[1];
            }
            return '<a href="mailto:' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '">' . $m[1] . '</a>';
        },
        $text
    ) ?? $text;
    $text = preg_replace_callback(
        '/\[img\](.*?)\[\/img\]/si',
        static function (array $m): string {
            $src = pdl_safe_url($m[1], ['http', 'https'], false);
            if ($src === null) {
                return $m[1];
            }
            return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="" class="img-fluid" loading="lazy">';
        },
        $text
    ) ?? $text;

    return $text;
}

// Helper: Apply glossary replacements
function bbcode_apply_glossary(string $text, array $glossary): string
{
    foreach ($glossary as $entry) {
        $old = $entry['old'] ?? '';
        $neu = $entry['neu'] ?? '';
        if ($old !== '') {
            $text = str_replace($old, $neu, $text);
        }
    }
    return $text;
}

/**
 * Zensiert Wörter bis auf den ersten Buchstaben („Beispiel“ → „B*******“),
 * auch innerhalb längerer Wörter und unabhängig von Groß- und Kleinschreibung.
 *
 * Die Zensur läuft über fertiges HTML (nach BB-Code, Smileys und Glossar).
 * Sie ändert deshalb nur Text außerhalb von HTML-Tags und Entities:
 * Link-Adressen, Bildpfade und andere Attribute bleiben unverändert.
 *
 * @param array<array-key, mixed> $badwords
 */
function bbcode_apply_badwords(string $text, array $badwords): string
{
    $words = [];
    foreach ($badwords as $badword) {
        if (is_string($badword) && $badword !== '') {
            $words[] = $badword;
        }
    }
    if ($words === [] || $text === '') {
        return $text;
    }

    // Ungerade Indizes: Tags und Entities (unverändert), gerade: Text.
    $parts = preg_split('/(<[^>]*>|&(?:#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]*);)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) {
        return $text;
    }
    foreach ($parts as $i => $part) {
        if ($i % 2 === 1 || $part === '') {
            continue;
        }
        foreach ($words as $word) {
            $quoted = preg_quote($word, '/');
            $censored = preg_replace_callback(
                '/' . $quoted . '/iu',
                static fn (array $m): string => mb_substr($m[0], 0, 1, 'UTF-8') . str_repeat('*', max(0, mb_strlen($m[0], 'UTF-8') - 1)),
                $part
            );
            // Kein gültiges UTF-8: byteweise zensieren
            $censored ??= preg_replace_callback(
                '/' . $quoted . '/i',
                static fn (array $m): string => substr($m[0], 0, 1) . str_repeat('*', max(0, strlen($m[0]) - 1)),
                $part
            );
            $part = $censored ?? $part;
        }
        $parts[$i] = $part;
    }
    return implode('', $parts);
}

// Helper: Protect email addresses with ASCII encoding
// Jede Adresse wird einzeln kodiert (früher ersetzte die erste Adresse alle weiteren).
function bbcode_protect_emails(string $text): string
{
    $pattern = "/([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)?[\w]+)/si";
    return preg_replace_callback(
        $pattern,
        static fn (array $m): string => ascii_encode($m[0]),
        $text
    ) ?? $text;
}

// BB Code, Smilies, Glossary, Bad Words...
function bbcode(string $text, string $rep_badwords, string $rep_smilies, string $rep_glossary, string $rep_bbcode, string $html): string
{
    global $smilies, $glossary, $badwords;

    if ($html == "N") {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    if ($rep_smilies == "Y" && is_array($smilies)) {
        $text = bbcode_apply_smilies($text, $smilies);
    }

    if ($rep_bbcode == "Y") {
        $text = bbcode_apply_tags($text);
    }

    if ($rep_glossary == "Y" && is_array($glossary)) {
        $text = bbcode_apply_glossary($text, $glossary);
    }

    if ($rep_badwords == "Y" && is_array($badwords)) {
        $text = bbcode_apply_badwords($text, $badwords);
    }

    return bbcode_protect_emails($text);
}

// Generiert einen String aus Buchstaben und Zahlen mit X Zeichen Länge
function generate_string(int $length): string
{
    $pwarray = [
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m",
        "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z",
        "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M",
        "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
        "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
    ];

    $pwacount = count($pwarray);
    $password = "";

    for ($i = 0; $i < $length; $i++) {
        $letter = random_int(0, $pwacount - 1);
        /** @psalm-suppress InvalidArrayOffset */
        $password .= $pwarray[$letter];
    }

    return $password;
}

// Helper: Extract a single SQL statement for install mode
function split_query_escape_statement(string $statement): string
{
    return str_replace("\"", "\\\"", str_replace("\\\"", "\\\\\"", $statement));
}

// Helper: Check if quote at position toggles string state
function split_query_is_string_toggle(string $sql, int $pos): bool
{
    return $pos > 0 && $sql[$pos] == "'" && $sql[$pos - 1] != "\\";
}

// Splittet einen langen MySQL Befehl mit Kommentaren usw. in einzelne Befehle.
function split_query(array &$return, string $sql): bool
{
    global $install;

    $sql = preg_replace("/(\n|^)#[^\n]*(\n|$)/", "\\1", trim($sql)) ?? '';
    $sql_len = strlen($sql);
    $in_string = false;

    /** @psalm-suppress LoopInvalidation */
    for ($i = 0; $i < $sql_len - 1; $i++) {
        // Handle statement terminator
        if ($sql[$i] == ";" && !$in_string) {
            $statement = substr($sql, 0, $i);
            $return[] = ($install == 1) ? split_query_escape_statement($statement) : $statement;
            $sql = substr($sql, $i + 1);
            $i = 0;
            $sql_len = strlen($sql);
            continue;
        }

        // Track string literal state
        if (split_query_is_string_toggle($sql, $i)) {
            $in_string = !$in_string;
        }
    }
    return true;
}

// Wandelt einen String in ASCII Code um. Für die Email-Adressen gegen Spam-Bots.
function ascii_encode(string $string): string
{
    $encoded = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $encoded .= '&#' . ord(substr($string, $i, 1)) . ';';
    }
    return $encoded;
}

// Treeview für Select-Dropdown
// $selected: optional vorausgewählter ordner_id; wenn 0 (Index) wird nichts vorausgewählt.
function treeview_select(int $ordner, string $head, int $selected = 0, int $depth = 0): string
{
    global $db_handler, $sql_table, $pdl_treeview_cache;

    if ($depth >= pdl_tree_max_depth()) {
        return '';
    }

    if (!isset($pdl_treeview_cache) || !is_array($pdl_treeview_cache)) {
        $pdl_treeview_cache = [];
        $res = $db_handler->sql_query(
            "SELECT ordner_id, sordner_id, name FROM " . $sql_table['ordner'] . " ORDER BY name ASC"
        );
        while ($row = $db_handler->sql_fetch_array($res)) {
            $parent = (int) $row['sordner_id'];
            $pdl_treeview_cache[$parent][] = [
                'ordner_id' => (int) $row['ordner_id'],
                'name' => (string) ($row['name'] ?? ''),
            ];
        }
    }

    $children = $pdl_treeview_cache[$ordner] ?? [];
    $output = '';
    foreach ($children as $child) {
        $rowId = (int) $child['ordner_id'];
        $rowName = htmlspecialchars($child['name'], ENT_QUOTES, 'UTF-8');
        $selAttr = ($selected !== 0 && $selected === $rowId) ? ' selected' : '';
        $output .= '<option value="' . $rowId . '"' . $selAttr . '>' . $head . $rowName . '</option>';
        $output .= treeview_select($rowId, $head . "-", $selected, $depth + 1);
    }

    return $output;
}

/**
 * Setzt den internen Cache von treeview_select zurück (für Tests / mehrfache Edit-Forms).
 */
function treeview_select_reset_cache(): void
{
    global $pdl_treeview_cache;
    $pdl_treeview_cache = null;
}

/**
 * Ermittelt, wie eine Datei ausgeliefert wird.
 *
 * - http/https/ftp-Adressen: Weiterleitung. Ausnahme: Zeigt eine
 *   http(s)-Adresse auf diesen Server unter pdl-files/, liefert PowerDownload
 *   die Datei selbst aus, denn pdl-files/ ist für direkte Aufrufe gesperrt.
 * - Lokale Pfade (relativ zum Webroot, z. B. pdl-files/3/datei.zip) werden
 *   auf Existenz geprüft und dürfen den Webroot nicht verlassen. Unter
 *   pdl-files/ liefert PowerDownload die Datei selbst aus (Content-Disposition:
 *   attachment), andere lokale Dateien per Weiterleitung.
 * - Fehlt die Datei oder ist das Schema unbekannt: null (Hinweisseite 404).
 *
 * @param string|null $currentHost Host dieser Anfrage (Vorgabe: HTTP_HOST)
 *
 * @return array{type: 'local', path: string}|array{type: 'redirect', url: string}|null
 */
function pdl_download_target(string $url, string $webroot, ?string $currentHost = null): ?array
{
    $url = trim($url);
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return null;
    }
    if (preg_match('#^(https?|ftp)://#i', $url)) {
        $currentHost ??= $_SERVER['HTTP_HOST'] ?? '';
        if ($currentHost !== '' && function_exists('pdl_local_file_path')) {
            $own = pdl_local_file_path($url, $webroot, $currentHost);
            if ($own !== null) {
                return ['type' => 'local', 'path' => $own];
            }
        }
        return ['type' => 'redirect', 'url' => $url];
    }
    if (preg_match('/^[a-z][a-z0-9+.\-]*:/i', $url) || str_starts_with($url, '//')) {
        return null;
    }

    $relative = ltrim(str_replace('\\', '/', rawurldecode((string) strtok($url, '?#'))), '/');
    $root = realpath($webroot);
    if ($root === false || $relative === '') {
        return null;
    }
    $path = realpath($root . DIRECTORY_SEPARATOR . $relative);
    if ($path === false || !is_file($path) || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
        return null;
    }

    $files_dir = realpath($root . DIRECTORY_SEPARATOR . 'pdl-files');
    if ($files_dir !== false && str_starts_with($path, $files_dir . DIRECTORY_SEPARATOR)) {
        return ['type' => 'local', 'path' => $path];
    }
    return ['type' => 'redirect', 'url' => $url];
}

/**
 * Dateiname für Content-Disposition: ASCII-Ersatz plus UTF-8-Variante (RFC 6266).
 */
function pdl_content_disposition(string $filename): string
{
    $ascii = strtr($filename, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss']);
    $ascii = preg_replace('/[^A-Za-z0-9._\-]+/', '_', $ascii) ?? 'download';
    $ascii = trim($ascii, '_') !== '' ? $ascii : 'download';
    return 'attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($filename);
}

/**
 * Liefert eine lokale Datei als Download aus und beendet das Skript.
 *
 * Immer als „attachment“ mit application/octet-stream und nosniff: Auch
 * HTML-, SVG- oder XHTML-Dateien öffnet der Browser nie als Seite dieser
 * Domain. Die Sandbox-Richtlinie sperrt Skripte zusätzlich, falls ein
 * Browser die Datei doch anzeigt.
 */
function pdl_send_file(string $path): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $size = filesize($path);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: ' . pdl_content_disposition(basename($path)));
    if ($size !== false) {
        header('Content-Length: ' . $size);
    }
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'; sandbox");
    header('Cache-Control: private, no-transform');
    readfile($path);
    exit;
}

/**
 * Liest einen Text-Parameter aus POST oder GET (POST hat Vorrang).
 * Arrays und andere Typen ergeben den Vorgabewert.
 */
function pdl_request_string(string $key, string $default = ''): string
{
    foreach ([$_POST, $_GET] as $source) {
        if (array_key_exists($key, $source)) {
            return is_string($source[$key]) ? $source[$key] : $default;
        }
    }
    return $default;
}

/**
 * Liest einen Ganzzahl-Parameter aus GET oder POST (GET hat Vorrang).
 */
function pdl_request_int(string $key, int $default = 0): int
{
    foreach ([$_GET, $_POST] as $source) {
        if (array_key_exists($key, $source)) {
            return is_scalar($source[$key]) ? (int) $source[$key] : $default;
        }
    }
    return $default;
}

/**
 * Kürzt Text UTF-8-sicher auf höchstens $limit Zeichen (plus Auslassungszeichen),
 * möglichst an einer Wortgrenze. Umlaute werden nie zerschnitten.
 */
function pdl_truncate(string $text, int $limit, string $ellipsis = '…'): string
{
    if ($limit <= 0 || mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }

    $cut = mb_substr($text, 0, $limit, 'UTF-8');
    $next = mb_substr($text, $limit, 1, 'UTF-8');
    if (!preg_match('/\s/u', $next)
        && preg_match('/^(.*)\s\S*$/su', $cut, $m)
        && mb_strlen(rtrim($m[1]), 'UTF-8') >= (int) ceil($limit * 0.5)
    ) {
        // Mitten im Wort: bis zum letzten Leerraum zurück, solange genug Text bleibt.
        $cut = $m[1];
    }

    // Halbe HTML-Entity am Ende (z. B. "&am") und Satzzeichen/Leerraum entfernen.
    $cut = preg_replace('/&[#a-zA-Z0-9]*$/u', '', $cut) ?? $cut;
    $cut = preg_replace('/[\s,;:\-–]+$/u', '', $cut) ?? $cut;

    return $cut . $ellipsis;
}

/**
 * Entfernt BBCode (und auf Wunsch HTML-Tags) aus einem Text, z. B. für Vorschauen.
 */
function pdl_plain_text(string $text, bool $stripHtml = false): string
{
    $text = preg_replace('/\[img\].*?\[\/img\]/si', '', $text) ?? $text;
    $text = preg_replace('/\[\/?(b|i|u|url|email|img)(=[^\]]*)?\]/i', '', $text) ?? $text;
    if ($stripHtml) {
        $text = strip_tags($text);
    }
    return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
}

/**
 * Kurzfassung einer Release-Beschreibung für Listen (Ordner, Suche, Widgets).
 *
 * - trenn_durch = "string": der Teil vor der Trenn-Marke, Formatierung bleibt.
 * - trenn_durch = "zeichen": Ist der Text länger als trenn_zeichen, werden
 *   BBCode (und bei HTML in Releases die Tags) entfernt und UTF-8-sicher an
 *   einer Wortgrenze gekürzt. Kürzere Texte bleiben unverändert.
 *
 * Rückgabe ist Rohtext, der wie die Beschreibung selbst durch bbcode() läuft.
 *
 * @param array<string, mixed> $settings
 */
function pdl_teaser(string $text, array $settings): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $marker = (string) ($settings['trenn_string'] ?? '');
    $mode = (string) ($settings['trenn_durch'] ?? '');

    if ($mode === 'string' && $marker !== '') {
        $pos = strpos($text, $marker);
        return $pos === false ? $text : rtrim(substr($text, 0, $pos));
    }

    if ($marker !== '') {
        $text = str_replace($marker, '', $text);
    }

    if ($mode === 'zeichen') {
        $limit = (int) ($settings['trenn_zeichen'] ?? 100);
        $plain = pdl_plain_text($text, ($settings['html_releases'] ?? 'N') === 'Y');
        if ($limit > 0 && mb_strlen($plain, 'UTF-8') > $limit) {
            return pdl_truncate($plain, $limit);
        }
    }

    return $text;
}

/**
 * Hebt Suchbegriffe in fertigem HTML mit <mark> hervor. Tags und Entities
 * bleiben unberührt, Umlaute werden korrekt behandelt.
 *
 * @param list<string> $terms Rohbegriffe (noch nicht maskiert)
 */
function pdl_highlight(string $html, array $terms): string
{
    $patterns = [];
    foreach ($terms as $term) {
        $term = htmlspecialchars($term, ENT_QUOTES, 'UTF-8');
        if ($term !== '') {
            $patterns[] = preg_quote($term, '/');
        }
    }
    if ($patterns === []) {
        return $html;
    }

    $regex = '/(' . implode('|', $patterns) . ')/iu';
    $parts = preg_split('/(<[^>]*>|&[#a-zA-Z0-9]+;)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) {
        return $html;
    }
    foreach ($parts as $i => $part) {
        if ($part === '' || $part[0] === '<' || ($part[0] === '&' && str_ends_with($part, ';'))) {
            continue;
        }
        $parts[$i] = preg_replace($regex, '<mark>$1</mark>', $part) ?? $part;
    }
    return implode('', $parts);
}

/**
 * Sichere ORDER-BY-Klausel für Release-Listen. Nur Felder aus der Whitelist,
 * Richtung nur ASC/DESC. Unbekannte Werte fallen auf den Namen zurück.
 */
function pdl_release_order_sql(string $orderby, string $orderseq, string $prefix = ''): string
{
    $p = $prefix !== '' ? $prefix . '.' : '';
    $columns = [
        'name' => $p . 'name',
        'text' => $p . 'text',
        'time' => $p . 'time',
        'date' => $p . 'time',
        'views' => $p . 'views',
        'votes' => $p . 'votes',
        'voted' => $p . 'voted',
        'voted/votes' => '(' . $p . 'voted / NULLIF(' . $p . 'votes, 0))',
    ];
    $column = $columns[$orderby] ?? $columns['name'];
    $direction = strtoupper($orderseq) === 'DESC' ? 'DESC' : 'ASC';

    $sql = ' ORDER BY ' . $column . ' ' . $direction;
    if ($column !== $columns['name']) {
        $sql .= ', ' . $p . 'name ASC';
    }
    return $sql . ', ' . $p . 'release_id ASC';
}

/**
 * Durchschnittsnote mit Komma, ",0" entfällt (z. B. "8", "7,5").
 */
function pdl_format_vote(int $voted, int $votes): string
{
    if ($votes <= 0) {
        return '0';
    }
    $formatted = number_format(round($voted / $votes, 1), 1, ',', '');
    return str_ends_with($formatted, ',0') ? substr($formatted, 0, -2) : $formatted;
}

/**
 * Eingebaute Vorlagen (Bootstrap) für die Startseiten-Widgets und das
 * Kommentarformular. Gleicher Inhalt wie in pdl-inc/pdl3_schema.sql.
 *
 * @return array<string, string>
 */
function pdl_default_templates(): array
{
    $widget_box = '<ol class="list-group list-group-flush list-group-numbered pdl-widget-list">' . "\n{rows}\n" . '</ol>';
    $row_start = '<li class="list-group-item d-flex justify-content-between align-items-start gap-2 px-1">'
        . '<a class="me-auto" href="{script_file}release_id={id}">{name}</a>';

    return [
        'stats' => '<ul class="list-unstyled mb-0 pdl-stats-list">' . "\n"
            . '<li><span>Dateien</span> <strong>{files}</strong></li>' . "\n"
            . '<li><span>Gesamtgröße</span> <strong>{size}</strong></li>' . "\n"
            . '<li><span>Downloads</span> <strong>{downloads}</strong></li>' . "\n"
            . '<li><span>Traffic</span> <strong>{traffic}</strong></li>' . "\n"
            . '<li><span>Ø Downloads pro Tag</span> <strong>{durch_downloads}</strong></li>' . "\n"
            . '<li><span>Ø Traffic pro Tag</span> <strong>{durch_traffic}</strong></li>' . "\n"
            . '</ul>',
        'top_box' => $widget_box,
        'top_row' => $row_start . '<span class="badge text-bg-secondary text-nowrap">{downloads_text}</span></li>',
        'flop_box' => $widget_box,
        'flop_row' => $row_start . '<span class="badge text-bg-secondary text-nowrap">{downloads_text}</span></li>',
        'latest_box' => $widget_box,
        'latest_row' => $row_start . '<span class="small text-muted text-nowrap">{time}</span></li>',
        'rated_box' => $widget_box,
        'rated_row' => $row_start . '<span class="badge text-bg-secondary text-nowrap">{vote}/10</span></li>',
        'comments_form' => '<p class="small text-muted mb-3">Sie schreiben als <strong>{user}</strong>.</p>' . "\n"
            . '<div class="mb-3">' . "\n"
            . '<label for="pdlCommentTitel" class="form-label">Titel</label>' . "\n"
            . '<input type="text" class="form-control" id="pdlCommentTitel" name="titel" maxlength="128" value="{titel_value}" required>' . "\n"
            . '</div>' . "\n"
            . '<div class="mb-3">' . "\n"
            . '<label for="pdlCommentText" class="form-label">Kommentar</label>' . "\n"
            . '<textarea class="form-control" id="pdlCommentText" name="text" rows="5" maxlength="5000" required aria-describedby="pdlCommentHelp">{text_value}</textarea>' . "\n"
            . '<div id="pdlCommentHelp" class="form-text">{format_hint}</div>' . "\n"
            . '</div>' . "\n"
            . '<button type="submit" class="btn btn-primary" id="pdlCommentSubmit">Kommentar absenden</button>',
    ];
}

/**
 * Hinweis unter dem Kommentarfeld, welche Formatierung erlaubt ist,
 * z. B. "BBCode und Smilies sind erlaubt, HTML nicht."
 *
 * @param array<string, mixed> $settings
 */
function pdl_comment_format_hint(array $settings): string
{
    $allowed = [];
    if (($settings['bb_code'] ?? 'N') === 'Y') {
        $allowed[] = 'BBCode';
    }
    if (($settings['smilies'] ?? 'N') === 'Y') {
        $allowed[] = 'Smilies';
    }
    $html = ($settings['html_comments'] ?? 'N') === 'Y';
    if ($html) {
        $allowed[] = 'HTML';
    }
    if ($allowed === []) {
        return 'Nur Text, HTML ist nicht erlaubt.';
    }
    $last = $allowed[array_key_last($allowed)];
    $rest = array_slice($allowed, 0, -1);
    $list = $rest === [] ? $last : implode(', ', $rest) . ' und ' . $last;
    return $list . ($rest === [] ? ' ist' : ' sind') . ' erlaubt' . ($html ? '.' : ', HTML nicht.');
}

/**
 * Kommentarformular (Release-Seite und usercenter=comments). Nutzt die
 * Vorlage comments_form, sofern sie die Felder titel und text enthält und
 * keine alte Tabellen-Vorlage ist, sonst die eingebaute Bootstrap-Variante.
 */
function pdl_comment_form(int $release_id, string $nick, string $titel = '', string $text = ''): string
{
    global $settings, $template;

    $form = (string) (is_array($template) ? ($template['comments_form'] ?? '') : '');
    if ($form === '' || pdl_template_is_legacy($form) || !str_contains($form, 'name="titel"') || !str_contains($form, 'name="text"')) {
        $form = pdl_default_templates()['comments_form'];
    }

    $onoff = static fn (string $key): string => (($settings[$key] ?? 'N') === 'Y') ? 'An' : 'Aus';
    $form = str_replace(
        ['{user}', '{titel_value}', '{text_value}', '{format_hint}', '{html}', '{zensur}', '{bbcode}', '{smilies}', '{glossar}'],
        [
            htmlspecialchars($nick, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($titel, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(pdl_comment_format_hint(is_array($settings) ? $settings : []), ENT_QUOTES, 'UTF-8'),
            $onoff('html_comments'),
            $onoff('badwords_comments'),
            $onoff('bb_code'),
            $onoff('smilies'),
            $onoff('glossary'),
        ],
        $form
    );

    $action = htmlspecialchars((string) ($settings['script_file'] ?? 'downloads.php?'), ENT_QUOTES, 'UTF-8');
    return '<form action="' . $action . '" method="post" id="pdlCommentForm" novalidate>'
        . csrf_input()
        . '<input type="hidden" name="usercenter" value="comments">'
        . '<input type="hidden" name="submit" value="1">'
        . '<input type="hidden" name="release_id" value="' . $release_id . '">'
        . $form
        . '</form>';
}

/**
 * Relativer Pfad eines Screenshots unter pdl-gfx/screens/ oder '', wenn die
 * Datei fehlt. $size: 'k' = Vorschau, 'g' = Großbild. Ein Dateiname in den
 * Spalten thumb bzw. datei hat Vorrang vor dem Schema
 * release{R}screen{S}k.jpg / …g.jpg aus dem Admin-Upload.
 *
 * @param array<string, mixed> $screen Zeile aus pdl3_screens
 */
function pdl_screen_file(array $screen, string $size, string $webroot): string
{
    $column = $size === 'k' ? 'thumb' : 'datei';
    $name = basename(str_replace('\\', '/', (string) ($screen[$column] ?? '')));
    if ($name === '' || $name === '.' || $name === '..') {
        $name = 'release' . (int) ($screen['release_id'] ?? 0) . 'screen' . (int) ($screen['screen_id'] ?? 0) . ($size === 'k' ? 'k' : 'g') . '.jpg';
    }
    if (!is_file($webroot . '/pdl-gfx/screens/' . $name)) {
        return '';
    }
    return 'pdl-gfx/screens/' . rawurlencode($name);
}

/**
 * Erkennt die alten Tabellen-Vorlagen von PowerDownload 3.x
 * (bgcolor="#333333" / "#222222" / "#444444", auch mit maskierten Anführungszeichen).
 */
function pdl_template_is_legacy(string $value): bool
{
    return (bool) preg_match('/bgcolor=\\\\?["\']?#(333333|222222|444444)/i', $value);
}

/**
 * Liefert eine Vorlage aus der Datenbank. Fehlt sie, ist sie leer oder noch
 * die alte Tabellen-Vorlage (Bestandsinstallation), greift die eingebaute
 * Bootstrap-Vorlage aus pdl_default_templates().
 */
function pdl_template(string $name): string
{
    global $template;
    $value = (string) (is_array($template) ? ($template[$name] ?? '') : '');
    if ($value !== '' && !pdl_template_is_legacy($value)) {
        return $value;
    }
    return pdl_default_templates()[$name] ?? $value;
}

/**
 * Rendert ein Release-Widget der Startseite (Top, Flop, Neueste, Bestbewertet).
 *
 * @param mixed $result Ergebnis der Release-Abfrage
 */
function pdl_render_release_widget($result, string $key, string $title, string $empty_text): string
{
    global $db_handler, $settings, $total;

    $row_tpl = pdl_template($key . '_row');
    $box_tpl = pdl_template($key . '_box');
    $usetext = str_contains($row_tpl, '{text}');
    $shortname = (int) ($settings['shortname'] ?? 0);

    $rows = '';
    $count = 0;
    while ($row = $db_handler->sql_fetch_array($result)) {
        $count++;
        $row['count'] = $count;
        $row['id'] = $row['release_id'] ?? '';
        $row['name'] = (string) ($row['name'] ?? '');
        if ($shortname > 0) {
            $row['name'] = pdl_truncate($row['name'], $shortname);
        }
        $row['downloads_text'] = pdl_count_label((int) ($row['downloads'] ?? 0), 'Download', 'Downloads');
        $row['vote'] = pdl_format_vote((int) ($row['voted'] ?? 0), (int) ($row['votes'] ?? 0));
        if ($usetext) {
            // {text} wird von replace() maskiert, deshalb hier reiner Text ohne BBCode/HTML.
            $teaser = pdl_teaser((string) ($row['text'] ?? ''), $settings);
            $row['text'] = $teaser === '' ? 'N/A' : pdl_plain_text($teaser, true);
        }
        $rows .= replace($row_tpl, $row);
    }

    $id = 'pdlWidget' . ucfirst($key);
    $html = '<section class="card pdl-card h-100" id="' . $id . '">'
        . '<header class="card-header pdl-card-header"><h2 class="h6 mb-0">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2></header>'
        . '<div class="card-body p-2 small">';
    if ($count === 0) {
        $html .= '<p class="text-muted small mb-0 px-1">' . htmlspecialchars($empty_text, ENT_QUOTES, 'UTF-8') . '</p>';
    } else {
        $total = $count;
        $html .= replace($box_tpl, ['rows' => $rows]);
    }
    return $html . '</div></section>';
}
