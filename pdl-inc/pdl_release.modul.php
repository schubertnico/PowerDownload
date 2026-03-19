<?php
/**
 * PowerDownload - Release Detail Module
 * @license MIT
 */

$release_id = (int)($release_id ?? 0);
$release_id_safe = $db_handler->sql_escape_int($release_id);
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ip_safe = $db_handler->sql_escape_string($ip);

// Vote eintragen
$iplocked_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['iplock'] . " WHERE file_id='" . $release_id_safe . "' AND ip='" . $ip_safe . "' AND art='vote'");
$iplocked = $db_handler->sql_num_rows($iplocked_res) > 0 ? 1 : 0;
$vote = (int)($vote ?? 0);
$vote_id = (int)($vote_id ?? 0);

if ($vote == 1 && $iplocked == 0 && $vote_id > 0 && $vote_id <= 10) {
    $user_id_safe = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);
    $db_handler->sql_query("INSERT INTO " . $sql_table['iplock'] . " VALUES ('" . $ip_safe . "', '" . time() . "', '" . $release_id_safe . "', '" . $user_id_safe . "', 'vote')");
    $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET votes=votes+1, voted=voted+" . $vote_id . " WHERE release_id='" . $release_id_safe . "'");
    $iplocked = 1;
}

// Release Daten auslesen
$release_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE release_id='" . $release_id_safe . "'"));

if (!$release_row) {
    echo "Release nicht gefunden.";
} elseif (($release_row['released'] ?? '') == "N") {
    echo "Der Release ist zwar vorhanden aber versteckt und kann deswegen nicht angesehen werden.";
} else {
    // Views erhöhen
    $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET views=views+1 WHERE release_id='" . $release_id_safe . "'");

    // Release ausgeben
    echo "<form action=\"" . htmlspecialchars($settings['script_file'] ?? '') . "release_id=" . $release_id . "&vote=1\" method=\"post\">";

    $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "'");
    // Hack: erste Datei in das Release Template mit einbeziehen
    $fileone = $db_handler->sql_fetch_array($files_res);
    $release_row['id'] = $fileone['file_id'] ?? '';
    $release_row['filename'] = basename($fileone['url'] ?? '');

    $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "'");
    $files = "";
    $total_files = $db_handler->sql_num_rows($files_res);
    $total_downloads = 0;
    $total_size = 0;
    $total_traffic = 0;

    while ($files_row = $db_handler->sql_fetch_array($files_res)) {
        $files_row['id'] = $files_row['file_id'] ?? '';
        $files_row['traffic'] = ($files_row['size'] ?? 0) * ($files_row['downloads'] ?? 0);
        $files_row['size'] = $files_row['size'] ?? 0;
        if (($files_row['mirror'] ?? 0) > 0) {
            $mirror_id_safe = $db_handler->sql_escape_int($files_row['mirror']);
            $mirror_of = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE file_id='" . $mirror_id_safe . "'"));
            $files_row['size'] = $mirror_of['size'] ?? 0;
            $files_row['traffic'] = ($mirror_of['size'] ?? 0) * ($files_row['downloads'] ?? 0);
        } else {
            $total_size += $files_row['size'];
        }
        $total_traffic += $files_row['traffic'];
        $total_downloads += $files_row['downloads'] ?? 0;

        $files_row['filename'] = basename($files_row['url'] ?? '');
        $files .= replace($template['dfiles_row'] ?? '', $files_row);
    }

    $release_row['files'] = $files;
    $votes = (int)($release_row['votes'] ?? 0);
    $voted = (int)($release_row['voted'] ?? 0);
    $release_row['vote'] = $votes > 0 ? round($voted / $votes, 1) : 0;

    if ($iplocked == 0 && ($user_rights['vote'] ?? 'N') == "Y") {
        $release_row['vote_form'] = "
        <br>Bewerten:
        <select name=\"vote_id\">
        <option value=\"10\">10 Sehr gut</option>
        <option value=\"9\">9</option>
        <option value=\"8\">8</option>
        <option value=\"7\">7</option>
        <option value=\"6\">6</option>
        <option value=\"5\">5</option>
        <option value=\"4\">4</option>
        <option value=\"3\">3</option>
        <option value=\"2\">2</option>
        <option value=\"1\">1 Sehr schlecht</option>
        </select>
        <input type=\"submit\" value=\"Vote!\">";
    } else {
        $release_row['vote_form'] = '';
    }
    if (($user_rights['vote'] ?? 'N') == "N") {
        $release_row['vote_form'] = "<br>Sie haben keine Berechtigung den Download zu bewerten.";
    }

    $template['file_detail'] = str_replace("{total_size}", size($total_size), (string) ($template['file_detail'] ?? ''));
    $template['file_detail'] = str_replace("{total_traffic}", size($total_traffic), $template['file_detail']);
    $template['file_detail'] = str_replace("{total_downloads}", (string)$total_downloads, $template['file_detail']);
    $template['file_detail'] = str_replace("{total_files}", (string)$total_files, $template['file_detail']);
    $template['file_detail'] = str_replace("{dlspeed}", dlspeed($total_size), $template['file_detail']);
    $template['file_detail'] = str_replace("{speed}", $settings['dlspeed'] ?? '', $template['file_detail']);

    $autor = '';
    if (($release_row['autor'] ?? 0) == 0) {
        if ($release_row['autor_email'] ?? '') {
            $autor = "<a href=\"mailto:" . ascii_encode($release_row['autor_email']) . "\">" . htmlspecialchars($release_row['autor_nick'] ?? '') . "</a>";
        } else {
            $autor = htmlspecialchars($release_row['autor_nick'] ?? '');
        }
        if (($release_row['autor_icq'] ?? 0) > 0) {
            $autor .= " <a href=\"https://icq.im/" . (int)$release_row['autor_icq'] . "\"><img src=\"pdl-gfx/icq.gif\" border=\"0\" alt=\"ICQ\"></a>";
        }
        if ($release_row['autor_homepage'] ?? '') {
            $autor .= " <a href=\"" . htmlspecialchars($release_row['autor_homepage']) . "\"><img src=\"pdl-gfx/www.gif\" border=\"0\" alt=\"Homepage\"></a>";
        }
    } elseif (($release_row['autor'] ?? 0) == -1) {
        $autor = "Unbekannt";
    } else {
        $autor = user((int)$release_row['autor']);
    }
    $release_row['autor'] = $autor;

    $release_row['name'] = stripslashes($release_row['name'] ?? '');
    $release_row['text'] = stripslashes($release_row['text'] ?? '');
    if (!($release_row['text'] ?? '')) {
        $release_row['text'] = "N/A";
    } else {
        $release_row['text'] = str_replace($settings['trenn_string'] ?? '', "", $release_row['text']);
    }
    if ($release_row['text'] != "N/A") {
        $release_row['text'] = bbcode($release_row['text'], $settings['badwords_releases'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_releases'] ?? 'N');
    }

    $screens_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['screens'] . " WHERE release_id='" . $release_id_safe . "'");
    $screens = '';
    if ($db_handler->sql_num_rows($screens_res) == 0) {
        $screens = "keine Screens vorhanden...";
    } else {
        while ($screens_row = $db_handler->sql_fetch_array($screens_res)) {
            $screen_id = (int)($screens_row['screen_id'] ?? 0);
            $screens .= " <a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "screen_id=" . $screen_id . "\"><img src=\"pdl-gfx/screens/release" . $release_id . "screen" . $screen_id . "k.jpg\" border=\"0\" alt=\"Screenshot\"></a> ";
        }
    }
    $release_row['screens'] = $screens;
    echo replace((string) ($template['file_detail'] ?? ''), $release_row);
    echo "</form>
    " . ($template['own_footer'] ?? '') . "<br><br>";

    if (($settings['enable_comments'] ?? 'N') == "Y") {
        echo "<center><b>Kommentare</b><br>";
        $comments_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['comments'] . " WHERE release_id='" . $release_id_safe . "' ORDER BY time DESC");
        $comments_num = $db_handler->sql_num_rows($comments_res);

        $showcomments = (int)($showcomments ?? 0);
        if ($showcomments == 1) {
            echo "<a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "release_id=" . $release_id . "\">Kommentare (" . $comments_num . ") verstecken</a>";
        } else {
            echo "<a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "release_id=" . $release_id . "&showcomments=1\">Kommentare (" . $comments_num . ") zeigen</a>";
        }
        echo " - ";
        if ($user_details ?? null) {
            echo "<a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "usercenter=comments&release_id=" . $release_id . "\">Kommentar schreiben</a>";
        } else {
            echo "
            <a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "usercenter=register\">Anmelden</a> -
            <a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "usercenter=login\">Einloggen</a> -
            <a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "usercenter=comments&release_id=" . $release_id . "\">Anonym Posten</a>
            ";
        }
        echo "<br>";

        if ($showcomments == 1) {
            while ($comments_row = $db_handler->sql_fetch_array($comments_res)) {
                if (($comments_row['user_id'] ?? 0) == 0) {
                    $comments_row['autor'] = "Gast";
                } else {
                    $comments_row['autor'] = user((int)$comments_row['user_id']);
                }
                $comments_row['titel'] = stripslashes($comments_row['titel'] ?? '');
                $comments_row['text'] = stripslashes($comments_row['text'] ?? '');
                $comments_row['text'] = bbcode($comments_row['text'], $settings['badwords_comments'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_comments'] ?? 'N');

                echo replace($template['comments'] ?? '', $comments_row);
            }
        }
        echo "</center>";
    }
}
