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

// Normales Ordner Treeview
function treeview_ordner(int $ordner, string $head): void
{
    global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;

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
        treeview_ordner($row_ordner_id, $head2);
    }
}

// Treeview mit Pfeil: ordner > ordner1 ...
function treeview_pfeil(int $ordner): void
{
    global $db_handler, $settings, $sql_table, $release_id, $screen_id, $ordner_id;

    $ordner_escaped = $db_handler->sql_escape_int($ordner);
    $subdir_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['ordner'] . " WHERE ordner_id='" . $ordner_escaped . "'");
    while ($subdir_row = $db_handler->sql_fetch_array($subdir_res)) {
        $script_file = htmlspecialchars($settings['script_file'] ?? '', ENT_QUOTES, 'UTF-8');
        $row_sordner_id = (int) $subdir_row['sordner_id'];
        $row_ordner_id = (int) $subdir_row['ordner_id'];
        $row_name = htmlspecialchars($subdir_row['name'] ?? '', ENT_QUOTES, 'UTF-8');

        if ($row_sordner_id != 0) {
            treeview_pfeil($row_sordner_id);
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

// Byte Werte werden gerundet und automatisch in KB,MB,GB und TB umgewandelt
function size(int|float $size): string
{
    $size = round($size / 1024, 1);
    if ($size >= 1024) {
        $size2 = round($size / 1024, 1);
        if ($size2 >= 1024) {
            $size3 = round($size2 / 1024, 1);
            if ($size3 >= 1024) {
                $size4 = round($size3 / 1024, 1);
                return $size4 . " TB";
            } else {
                return $size3 . " GB";
            }
        } else {
            return $size2 . " MB";
        }
    } else {
        return $size . " KB";
    }
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

// Macht aus einer Userid den User mit Nick/ICQ/Homepage
function user(int $user_id): string
{
    global $users, $inadmin;

    if (!isset($users[$user_id]['nick']) || !$users[$user_id]['nick']) {
        return "Gelöscht";
    }

    $email = $users[$user_id]['email'] ?? '';
    $nick = htmlspecialchars($users[$user_id]['nick'] ?? '', ENT_QUOTES, 'UTF-8');
    $user = '<a href="mailto:' . $email . '">' . $nick . '</a>';

    $icq = (int) ($users[$user_id]['icq'] ?? 0);
    if ($icq > 0) {
        $user .= ' <a href="https://icq.im/' . $icq . '"><img src="pdl-gfx/icq.gif" border="0" alt="ICQ"></a>';
    }

    $homepage = $users[$user_id]['homepage'] ?? '';
    if ($homepage) {
        $user .= ' <a href="' . htmlspecialchars($homepage, ENT_QUOTES, 'UTF-8') . '"><img src="';
        if ($inadmin == 1) {
            $user .= "../";
        }
        $user .= 'pdl-gfx/www.gif" border="0" alt="Homepage"></a>';
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
            $text = str_replace($old, '<img src="' . htmlspecialchars($neu, ENT_QUOTES, 'UTF-8') . '" border="0" alt="">', $text);
        }
    }
    return $text;
}

// Helper: Apply BBCode tags to text
function bbcode_apply_tags(string $text): string
{
    $regex = [
        "/\[b\](.*)\[\/b\]/siU",
        "/\[i\](.*)\[\/i\]/siU",
        "/\[u\](.*)\[\/u\]/siU",
        "/([\n ])([a-z0-9]+?):\/\/([^\t <\n\r]+)/si",
        "/([\n ])(www\.)([^\t <\n\r]+)/si",
        "/([\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)?[\w]+)/si",
        "/\[url\](.*)\[\/url\]/siU",
        "/\[email\](.*)\[\/email\]/siU",
        "/\[url=(['\"]?)([a-z0-9]+?):\/\/(.*)(['\"]?)\](.*)\[\/url\]/siU",
        "/\[url=(['\"]?)(.*)(['\"]?)\](.*)\[\/url\]/siU",
        "/\[img\]([a-z0-9]+?):\/\/(.*)\[\/img\]/siU",
        "/\[img\](.*)\[\/img\]/siU",
    ];
    $repwith = [
        "<b>\\1</b>",
        "<i>\\1</i>",
        "<u>\\1</u>",
        "\\1[url]\\2://\\3[/url]",
        "\\1[url]http://www.\\3[/url]",
        "\\1[email]\\2@\\3[/email]",
        '<a href="\\1" target="_blank" rel="noopener">\\1</a>',
        '<a href="mailto:\\1">\\1</a>',
        '<a href="\\2://\\3" target="_blank" rel="noopener">\\5</a>',
        '<a href="http://\\2" target="_blank" rel="noopener">\\4</a>',
        '<img src="\\1://\\2" border="0" alt="">',
        '<img src="http://\\1" border="0" alt="">',
    ];
    return preg_replace($regex, $repwith, $text) ?? $text;
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

// Helper: Censor bad words
function bbcode_apply_badwords(string $text, array $badwords): string
{
    foreach ($badwords as $badword) {
        if ($badword !== '') {
            $pattern = "/" . preg_quote($badword, '/') . "/i";
            if (preg_match($pattern, $text, $match)) {
                $replacement = substr($match[0], 0, 1) . str_repeat("*", strlen($badword) - 1);
                $text = preg_replace($pattern, $replacement, $text) ?? $text;
            }
        }
    }
    return $text;
}

// Helper: Protect email addresses with ASCII encoding
function bbcode_protect_emails(string $text): string
{
    $pattern = "/([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)?[\w]+)/si";
    if (preg_match($pattern, $text, $match)) {
        $encoded = ascii_encode($match[1] . "@" . $match[2]);
        $text = preg_replace($pattern, $encoded, $text) ?? $text;
    }
    return $text;
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
function treeview_select(int $ordner, string $head): string
{
    global $db_handler, $sql_table;

    $output = '';
    $ordner_escaped = $db_handler->sql_escape_int($ordner);
    $treeview_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $ordner_escaped . "'");

    while ($treeview_row = $db_handler->sql_fetch_array($treeview_res)) {
        $row_ordner_id = (int) $treeview_row['ordner_id'];
        $row_name = htmlspecialchars($treeview_row['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $output .= '<option value="' . $row_ordner_id . '">' . $head . $row_name . '</option>';
        $output .= treeview_select($row_ordner_id, $head . "-");
    }

    return $output;
}
