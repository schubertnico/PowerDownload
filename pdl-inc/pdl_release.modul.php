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
$release_exists = (bool) $release_row;

if (!$release_row) {
    echo pdl_alert('warning', 'Release nicht gefunden.');
} elseif ($release_row['released'] === "N") {
    echo pdl_alert('warning', 'Der Release ist zwar vorhanden aber versteckt und kann deswegen nicht angesehen werden.');
} else {
    // Views erhöhen
    $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET views=views+1 WHERE release_id='" . $release_id_safe . "'");

    // Release ausgeben
    echo '<form action="downloads.php" method="post">';
    echo '<input type="hidden" name="release_id" value="' . (int) $release_id . '">';
    echo '<input type="hidden" name="vote" value="1">';

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

    // Fallback-Erkennung: wenn das DB-Template 'dfiles_row' keinen
    // sinnvollen Platzhalter enthält, bauen wir eine eigene Bootstrap-Liste.
    $tpl_dfiles_usable = isset($template['dfiles_row']) && (
        strpos((string) $template['dfiles_row'], '{filename}') !== false
        || strpos((string) $template['dfiles_row'], '{url}') !== false
        || strpos((string) $template['dfiles_row'], '{id}') !== false
    );
    $files_fallback_rows = [];

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
        if ($tpl_dfiles_usable) {
            $files .= replace((string) $template['dfiles_row'], $files_row);
        } else {
            $files_fallback_rows[] = $files_row;
        }
    }

    if (!$tpl_dfiles_usable) {
        if (empty($files_fallback_rows)) {
            $files = '<p class="text-muted mb-0 small">Es gibt für dieses Release noch keine Dateien.</p>';
        } else {
            $files = '<ul class="list-group list-group-flush" aria-label="Dateien zu diesem Release">';
            foreach ($files_fallback_rows as $f) {
                $fname = (string) ($f['name'] ?: $f['filename'] ?: ('Datei #' . (int) $f['id']));
                $furl = (string) ($f['url'] ?? '');
                $fsize = (int) $f['size'];
                $fdl = (int) ($f['downloads'] ?? 0);
                $is_mirror = ((int) ($f['mirror'] ?? 0)) > 0;
                $size_human = $fsize > 0 ? size($fsize) : '–';
                $download_url = $furl;
                if ($download_url !== '' && !preg_match('#^https?://#i', $download_url)) {
                    // relative URL (z.B. pdl-files/...) — direkt verlinken
                    $download_url = $download_url;
                }
                $files .= '<li class="list-group-item bg-transparent text-body">';
                $files .= '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">';
                $files .= '<div class="flex-grow-1">';
                if ($download_url !== '') {
                    $files .= '<a class="link-light fw-bold" href="' . htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8') . '">'
                        . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . '</a>';
                } else {
                    $files .= '<strong>' . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . '</strong>';
                }
                if ($is_mirror) {
                    $files .= ' <span class="badge text-bg-secondary ms-2">Spiegel-Server</span>';
                }
                $files .= '</div>';
                $files .= '<div class="text-end small text-muted">'
                    . htmlspecialchars($size_human) . ' &middot; ' . $fdl . ' ' . ($fdl === 1 ? 'Download' : 'Downloads')
                    . '</div>';
                $files .= '</div></li>';
            }
            $files .= '</ul>';
        }
    }

    $release_row['files'] = $files;
    $votes = (int)($release_row['votes'] ?? 0);
    $voted = (int)($release_row['voted'] ?? 0);
    $release_row['vote'] = $votes > 0 ? round($voted / $votes, 1) : 0;

    if ($iplocked == 0 && ($user_rights['vote'] ?? 'N') == "Y") {
        $release_row['vote_form'] = '
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <label for="pdlVoteId" class="form-label mb-0">Bewerten:</label>
            <select id="pdlVoteId" name="vote_id" class="form-select form-select-sm w-auto">
                <option value="10">10 Sehr gut</option>
                <option value="9">9</option>
                <option value="8">8</option>
                <option value="7">7</option>
                <option value="6">6</option>
                <option value="5">5</option>
                <option value="4">4</option>
                <option value="3">3</option>
                <option value="2">2</option>
                <option value="1">1 Sehr schlecht</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Vote!</button>
        </div>';
    } else {
        $release_row['vote_form'] = '';
    }
    if (($user_rights['vote'] ?? 'N') == "N") {
        $release_row['vote_form'] = '<p class="small text-muted mt-3 mb-0">Sie haben keine Berechtigung, dieses Release zu bewerten.</p>';
    }

    $tpl_file_detail_raw = (string) ($template['file_detail'] ?? '');
    $tpl_file_detail_usable = $tpl_file_detail_raw !== '' && (
        strpos($tpl_file_detail_raw, '{name}') !== false
        || strpos($tpl_file_detail_raw, '{files}') !== false
        || strpos($tpl_file_detail_raw, '{text}') !== false
    );

    $template['file_detail'] = str_replace("{total_size}", size($total_size), $tpl_file_detail_raw);
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

    if ($tpl_file_detail_usable) {
        echo replace((string) $template['file_detail'], $release_row);
    } else {
        // Fallback: Bootstrap-Card mit Name, Beschreibung, Autor, Statistik,
        // Datei-Liste, Screenshots, Bewertung. Wird genutzt, wenn das
        // DB-Template 'file_detail' nicht konfiguriert ist.
        $r_name = htmlspecialchars((string) $release_row['name'], ENT_QUOTES, 'UTF-8');
        $r_text_html = (string) ($release_row['text'] ?? 'N/A');
        $r_time = (int) ($release_row['time'] ?? 0);
        $r_views = (int) ($release_row['views'] ?? 0);
        $r_downloads = (int) $total_downloads;
        $r_size = size($total_size);
        $r_files_count = (int) $total_files;
        $r_autor = (string) $release_row['autor'];
        $r_vote = (string) $release_row['vote'];
        $r_vote_form = (string) $release_row['vote_form'];
        $r_screens = (string) $release_row['screens'];
        $r_date = $r_time > 0 ? date((string) ($settings['date_format'] ?? 'd.m.Y'), $r_time) : '';

        echo '<article class="card pdl-card mb-4">';
        echo '<header class="card-header pdl-card-header">';
        echo '<h2 class="h5 mb-0">' . $r_name . '</h2>';
        echo '</header>';
        echo '<div class="card-body">';

        // Beschreibung
        if ($r_text_html !== '' && $r_text_html !== 'N/A') {
            echo '<div class="mb-3">' . $r_text_html . '</div>';
        }

        // Eckdaten in 2 Spalten
        echo '<div class="row g-3 small mb-3">';
        echo '<div class="col-12 col-md-6"><strong>Autor:</strong> ' . ($r_autor !== '' ? $r_autor : 'Unbekannt') . '</div>';
        if ($r_date !== '') {
            echo '<div class="col-12 col-md-6"><strong>Veröffentlicht:</strong> ' . htmlspecialchars($r_date) . '</div>';
        }
        echo '<div class="col-12 col-md-6"><strong>Gesamtgröße:</strong> ' . htmlspecialchars($r_size) . '</div>';
        echo '<div class="col-12 col-md-6"><strong>Aufrufe:</strong> ' . $r_views . '</div>';
        echo '<div class="col-12 col-md-6"><strong>Downloads:</strong> ' . $r_downloads . '</div>';
        echo '<div class="col-12 col-md-6"><strong>Anzahl Dateien:</strong> ' . $r_files_count . '</div>';
        if ($r_vote !== '' && $r_vote !== '0') {
            echo '<div class="col-12 col-md-6"><strong>Bewertung:</strong> ' . htmlspecialchars($r_vote) . ' / 10</div>';
        }
        echo '</div>';

        // Dateien (vorher gerendert in $files)
        echo '<h3 class="h6 mt-3">Dateien zum Herunterladen</h3>';
        echo $files;

        // Screenshots
        if ($r_screens !== '' && $r_screens !== 'keine Screens vorhanden...') {
            echo '<h3 class="h6 mt-4">Screenshots</h3>';
            echo '<div class="d-flex flex-wrap gap-2">' . $r_screens . '</div>';
        }

        // Bewerten-Formular
        if ($r_vote_form !== '') {
            echo $r_vote_form;
        }

        echo '</div></article>';
    }
    echo "</form>
    " . ($template['own_footer'] ?? '') . "<br><br>";

    if (($settings['enable_comments'] ?? 'N') == "Y") {
        $comments_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['comments'] . " WHERE release_id='" . $release_id_safe . "' ORDER BY time DESC");
        $comments_num = $db_handler->sql_num_rows($comments_res);
        $showcomments = (int)($showcomments ?? 0);
        $sf = htmlspecialchars($settings['script_file'] ?? '');

        echo '<section class="card pdl-card mt-4">';
        echo '<header class="card-header pdl-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">';
        echo '<h2 class="h5 mb-0">Kommentare <span class="badge text-bg-secondary">' . (int) $comments_num . '</span></h2>';
        echo '<div class="d-flex flex-wrap gap-2">';
        if ($showcomments == 1) {
            echo '<a class="btn btn-sm btn-outline-light" href="' . $sf . 'release_id=' . $release_id . '">Verbergen</a>';
        } else {
            echo '<a class="btn btn-sm btn-outline-light" href="' . $sf . 'release_id=' . $release_id . '&amp;showcomments=1">Anzeigen</a>';
        }
        if ($user_details ?? null) {
            echo '<a class="btn btn-sm btn-primary" href="' . $sf . 'usercenter=comments&amp;release_id=' . $release_id . '">Kommentar schreiben</a>';
        } else {
            echo '<a class="btn btn-sm btn-outline-light" href="' . $sf . 'usercenter=register">Registrieren</a>';
            echo '<a class="btn btn-sm btn-outline-light" href="' . $sf . 'usercenter=login">Login</a>';
            echo '<a class="btn btn-sm btn-primary" href="' . $sf . 'usercenter=comments&amp;release_id=' . $release_id . '">Anonym posten</a>';
        }
        echo '</div></header>';
        echo '<div class="card-body">';

        if ($comments_num == 0) {
            echo '<div class="text-center py-4">';
            echo '<p class="text-muted mb-2"><strong>Noch keine Kommentare.</strong></p>';
            if ($user_details ?? null) {
                if (($user_rights['addcomments'] ?? 'N') == "Y") {
                    echo '<p class="mb-0">Schreibe den ersten Kommentar zu diesem Release!</p>';
                    echo '</div>';
                    $tpl_form_raw = (string) ($template['comments_form'] ?? '');
                    $html_setting = ($settings['html_comments'] ?? 'N') == "Y" ? "An" : "Aus";
                    $zensur_setting = ($settings['badwords_comments'] ?? 'N') == "Y" ? "An" : "Aus";
                    $bbcode_setting = ($settings['bb_code'] ?? 'N') == "Y" ? "An" : "Aus";
                    $smilies_setting = ($settings['smilies'] ?? 'N') == "Y" ? "An" : "Aus";
                    $glossar_setting = ($settings['glossary'] ?? 'N') == "Y" ? "An" : "Aus";
                    $user_nick = htmlspecialchars($user_details['nick'] ?? '');
                    $tpl_form = str_replace('{html}', $html_setting, $tpl_form_raw);
                    $tpl_form = str_replace('{zensur}', $zensur_setting, $tpl_form);
                    $tpl_form = str_replace('{bbcode}', $bbcode_setting, $tpl_form);
                    $tpl_form = str_replace('{smilies}', $smilies_setting, $tpl_form);
                    $tpl_form = str_replace('{glossar}', $glossar_setting, $tpl_form);
                    $tpl_form = str_replace('{user}', $user_nick, $tpl_form);
                    $tpl_form_usable = $tpl_form_raw !== '' && (
                        strpos($tpl_form_raw, '{titel}') !== false
                        || strpos($tpl_form_raw, '{text}') !== false
                        || strpos($tpl_form_raw, 'name=') !== false
                    );
                    echo '<div class="border-top pt-3 mt-3">';
                    echo '<h3 class="h6 mb-3">Kommentar schreiben</h3>';
                    echo '<form action="downloads.php" method="post" novalidate>';
                    echo csrf_input();
                    echo '<input type="hidden" name="usercenter" value="comments">';
                    echo '<input type="hidden" name="submit" value="1">';
                    echo '<input type="hidden" name="release_id" value="' . (int) $release_id . '">';
                    if ($tpl_form_usable) {
                        echo replace($tpl_form, []);
                    } else {
                        echo '<div class="mb-3"><label for="pdlCommentTitel" class="form-label">Titel</label>';
                        echo '<input type="text" class="form-control" id="pdlCommentTitel" name="titel" required></div>';
                        echo '<div class="mb-3"><label for="pdlCommentText" class="form-label">Kommentar</label>';
                        echo '<textarea class="form-control" id="pdlCommentText" name="text" rows="5" required></textarea></div>';
                        echo '<button type="submit" class="btn btn-primary">Kommentar absenden</button>';
                    }
                    echo '</form>';
                    echo '</div>';
                } else {
                    echo '<p class="mb-0">Noch keine Kommentare zu diesem Release.</p>';
                    echo '</div>';
                }
            } else {
                echo '<p class="mb-2">Melde dich an, um einen Kommentar zu hinterlassen.</p>';
                echo '<a class="btn btn-outline-primary btn-sm" href="downloads.php?usercenter=login">Anmelden</a>';
                echo '</div>';
            }
        } elseif ($showcomments == 1) {
            $tpl_comments_raw = (string) ($template['comments'] ?? '');
            $tpl_comments_usable = $tpl_comments_raw !== '' && (
                strpos($tpl_comments_raw, '{titel}') !== false
                || strpos($tpl_comments_raw, '{text}') !== false
                || strpos($tpl_comments_raw, '{autor}') !== false
            );
            $comments_fallback_rows = [];
            while ($comments_row = $db_handler->sql_fetch_array($comments_res)) {
                if (($comments_row['user_id'] ?? 0) == 0) {
                    $comments_row['autor'] = "Gast";
                } else {
                    $comments_row['autor'] = user((int)$comments_row['user_id']);
                }
                $comments_row['titel'] = stripslashes($comments_row['titel'] ?? '');
                $comments_row['text'] = stripslashes($comments_row['text'] ?? '');
                $comments_row['text'] = bbcode($comments_row['text'], $settings['badwords_comments'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_comments'] ?? 'N');

                if ($tpl_comments_usable) {
                    echo replace($tpl_comments_raw, $comments_row);
                } else {
                    $comments_fallback_rows[] = $comments_row;
                }
            }
            if (!$tpl_comments_usable) {
                foreach ($comments_fallback_rows as $c) {
                    $c_time = (int) ($c['time'] ?? 0);
                    $c_date = $c_time > 0 ? date((string) ($settings['date_format'] ?? 'd.m.Y'), $c_time) : '';
                    echo '<article class="border-bottom pb-3 mb-3">';
                    echo '<header class="d-flex justify-content-between flex-wrap gap-2 mb-2">';
                    echo '<strong>' . htmlspecialchars((string) $c['titel'], ENT_QUOTES, 'UTF-8') . '</strong>';
                    echo '<span class="small text-muted">' . (string) $c['autor']
                        . ($c_date !== '' ? ' &middot; ' . htmlspecialchars($c_date) : '') . '</span>';
                    echo '</header>';
                    echo '<div>' . (string) $c['text'] . '</div>';
                    echo '</article>';
                }
            }
        } else {
            echo '<p class="text-muted mb-0 small">Bitte oben "Anzeigen" klicken, um die Kommentare einzublenden.</p>';
        }
        echo '</div></section>';
    }
}
