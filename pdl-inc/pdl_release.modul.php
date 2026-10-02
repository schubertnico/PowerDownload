<?php
/**
 * PowerDownload - Release Detail Module
 *
 * Zeigt ein Release mit Eckdaten, Dateien (alle Links über load_file),
 * Screenshots, Bewertung und Kommentaren. Das Speichern einer Bewertung
 * erledigt pdl_header.inc.php (Post/Redirect/Get), hier steht nur das
 * Formular und die Rückmeldung. Ebenso speichert pdl_ucomments.modul.php
 * Kommentare und leitet hierher zurück (commented=1, Meldung
 * #pdlCommentSaved unter den Kommentaren).
 *
 * @license MIT
 */

/**
 * Vom Header gesetzt; in Tests kann die Variable fehlen.
 *
 * @var string|null $ip
 */
require_once __DIR__ . '/pdl_locks.inc.php';

$release_id = (int)($release_id ?? 0);
$release_id_safe = $db_handler->sql_escape_int($release_id);
$ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');
$sf = htmlspecialchars((string) ($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8');
$pdl_webroot = dirname(__DIR__);
// Anmelde-Links für Gäste führen nach der Anmeldung hierher zurück
$login_href = $sf . 'usercenter=login' . htmlspecialchars(pdl_back_release_query($release_id), ENT_QUOTES, 'UTF-8');

// Release Daten auslesen
$release_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE release_id='" . $release_id_safe . "'"));
$release_exists = (bool) $release_row;

if (!$release_row) {
    echo '<section class="card pdl-card mb-4" id="pdlReleaseMissing" role="alert"><div class="card-body">'
        . '<h2 class="h5">Release nicht gefunden</h2>'
        . '<p class="mb-3">Dieses Release gibt es nicht (mehr).</p>'
        . '<a class="btn btn-outline-light" href="' . $sf . '">Zur Startseite</a>'
        . '</div></section>';
} elseif ($release_row['released'] === "N") {
    echo '<section class="card pdl-card mb-4" id="pdlReleaseHidden" role="alert"><div class="card-body">'
        . '<h2 class="h5">Release nicht verfügbar</h2>'
        . '<p class="mb-3">Dieses Release ist nicht öffentlich.</p>'
        . '<a class="btn btn-outline-light" href="' . $sf . '">Zur Startseite</a>'
        . '</div></section>';
} else {
    // Aufrufe zählen: einmal je Sitzung und Release (Neuladen zählt nicht mit)
    if (empty($_SESSION['pdl_viewed'][$release_id])) {
        $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET views=views+1 WHERE release_id='" . $release_id_safe . "'");
        $_SESSION['pdl_viewed'][$release_id] = true;
        $release_row['views'] = (int) ($release_row['views'] ?? 0) + 1;
    }

    // Dateien: Hauptdateien und ihre Spiegel-Server
    $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' ORDER BY mirror ASC, file_id ASC");
    $files_all = [];
    while ($files_row = $db_handler->sql_fetch_array($files_res)) {
        $files_all[(int) ($files_row['file_id'] ?? 0)] = $files_row;
    }

    // Erste Datei für die Platzhalter {id}/{filename} der Vorlage file_detail
    $fileone = $files_all !== [] ? reset($files_all) : [];
    $release_row['id'] = $fileone['file_id'] ?? '';
    $release_row['filename'] = basename(rawurldecode((string) ($fileone['url'] ?? '')));

    $main_files = [];
    $mirror_files = [];
    foreach ($files_all as $fid => $f) {
        $mirror_of = (int) ($f['mirror'] ?? 0);
        if ($mirror_of > 0 && isset($files_all[$mirror_of]) && (int) ($files_all[$mirror_of]['mirror'] ?? 0) === 0) {
            $mirror_files[$mirror_of][] = $f;
        } else {
            if ($mirror_of > 0) {
                // Spiegel einer Datei aus einem anderen Release: Größe des Originals übernehmen
                $mirror_of_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT size FROM " . $sql_table['files'] . " WHERE file_id='" . $db_handler->sql_escape_int($mirror_of) . "'"));
                $f['size'] = $mirror_of_row['size'] ?? 0;
                $f['is_mirror'] = true;
            }
            $main_files[$fid] = $f;
        }
    }

    $total_files = count($main_files);
    $total_downloads = 0;
    $total_size = 0;
    $total_traffic = 0;
    foreach ($main_files as $fid => $f) {
        $fsize = (int) ($f['size'] ?? 0);
        if (empty($f['is_mirror'])) {
            $total_size += $fsize;
        }
        $total_downloads += (int) ($f['downloads'] ?? 0);
        $total_traffic += $fsize * (int) ($f['downloads'] ?? 0);
        foreach ($mirror_files[$fid] ?? [] as $m) {
            $total_downloads += (int) ($m['downloads'] ?? 0);
            $total_traffic += $fsize * (int) ($m['downloads'] ?? 0);
        }
    }

    $download_href = static fn (array $f): string => $sf . 'load_file=' . (int) ($f['file_id'] ?? 0);

    // Vorlage dfiles_row nur, wenn sie sinnvolle Platzhalter enthält
    $tpl_dfiles_raw = (string) ($template['dfiles_row'] ?? '');
    $tpl_dfiles_usable = $tpl_dfiles_raw !== '' && !pdl_template_is_legacy($tpl_dfiles_raw) && (
        str_contains($tpl_dfiles_raw, '{filename}')
        || str_contains($tpl_dfiles_raw, '{url}')
        || str_contains($tpl_dfiles_raw, '{download_url}')
        || str_contains($tpl_dfiles_raw, '{id}')
    );

    $files = '';
    if ($files_all === []) {
        $files = '<p class="text-muted mb-0 small" id="pdlFileListEmpty">Für dieses Release gibt es noch keine Dateien.</p>';
    } elseif ($tpl_dfiles_usable) {
        foreach ($files_all as $f) {
            $f['id'] = $f['file_id'] ?? '';
            $f['filename'] = basename(rawurldecode((string) ($f['url'] ?? '')));
            $f['traffic'] = (int) ($f['size'] ?? 0) * (int) ($f['downloads'] ?? 0);
            // Auch eigene Vorlagen verlinken über load_file (Zähler, Rechte, Hotlink-Schutz)
            $row_tpl = str_replace(['{download_url}', '{url}'], $download_href($f), $tpl_dfiles_raw);
            $files .= replace($row_tpl, $f);
        }
    } else {
        $files = '<ul class="list-group list-group-flush pdl-file-list" id="pdlFileList" aria-label="Dateien zu diesem Release">';
        foreach ($main_files as $fid => $f) {
            $fname = (string) (($f['name'] ?? '') ?: basename(rawurldecode((string) ($f['url'] ?? ''))) ?: ('Datei ' . $fid));
            $fname_html = htmlspecialchars($fname, ENT_QUOTES, 'UTF-8');
            $fsize = (int) ($f['size'] ?? 0);
            $fdl = (int) ($f['downloads'] ?? 0);
            $files .= '<li class="list-group-item bg-transparent text-body pdl-file" data-file-id="' . $fid . '">';
            $files .= '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">';
            $files .= '<div class="flex-grow-1">';
            $files .= '<strong class="pdl-file-name">' . $fname_html . '</strong>';
            if (!empty($f['is_mirror'])) {
                $files .= ' <span class="badge text-bg-secondary ms-1">Spiegel-Server</span>';
            }
            $files .= '<div class="small text-muted pdl-file-meta">'
                . htmlspecialchars($fsize > 0 ? size($fsize) : 'Größe unbekannt', ENT_QUOTES, 'UTF-8')
                . ' &middot; ' . pdl_count_label($fdl, 'Download', 'Downloads') . '</div>';
            $files .= '</div>';
            $files .= '<a class="btn btn-primary btn-sm pdl-download-btn" href="' . $download_href($f) . '" rel="nofollow"'
                . ' data-file-id="' . $fid . '" aria-label="' . $fname_html . ' herunterladen">Herunterladen</a>';
            $files .= '</div>';

            $mirrors = $mirror_files[$fid] ?? [];
            if ($mirrors !== []) {
                $mirror_links = [];
                $mirror_no = 0;
                foreach ($mirrors as $m) {
                    $mirror_no++;
                    $label = count($mirrors) > 1 ? 'Spiegel-Server ' . $mirror_no : 'Spiegel-Server';
                    $mirror_links[] = '<a class="pdl-mirror-link" href="' . $download_href($m) . '" rel="nofollow"'
                        . ' data-file-id="' . (int) ($m['file_id'] ?? 0) . '"'
                        . ' title="' . htmlspecialchars((string) ($m['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>'
                        . ' <span class="text-muted">(' . pdl_count_label((int) ($m['downloads'] ?? 0), 'Download', 'Downloads') . ')</span>';
                }
                $files .= '<div class="small mt-2 pdl-mirror-list">Alternativ: ' . implode(', ', $mirror_links) . '</div>';
            }
            $files .= '</li>';
        }
        $files .= '</ul>';
    }

    $release_row['files'] = $files;
    $votes = (int)($release_row['votes'] ?? 0);
    $voted = (int)($release_row['voted'] ?? 0);
    $release_row['vote'] = pdl_format_vote($voted, $votes);

    // Bewertung: Rückmeldung nach dem Speichern (aus der Sitzung, siehe Header)
    $vote_message = '';
    $vote_flash = $_SESSION['pdl_vote_flash'] ?? null;
    if (is_array($vote_flash) && (int) ($vote_flash['release_id'] ?? 0) === $release_id) {
        unset($_SESSION['pdl_vote_flash']);
        if (!empty($vote_feedback) || !empty($_GET['voted'])) {
            $vote_messages = [
                'ok' => ['success', 'Danke für Ihre Bewertung (' . (int) ($vote_flash['vote'] ?? 0) . '/10).'],
                'locked' => ['info', 'Sie haben dieses Release bereits bewertet.'],
                'rights' => ['warning', 'Sie dürfen dieses Release nicht bewerten.'],
                'csrf' => ['warning', 'Die Sitzung ist abgelaufen. Bitte bewerten Sie das Release erneut.'],
                'invalid' => ['warning', 'Bitte wählen Sie eine Note von 1 bis 10.'],
                'error' => ['danger', 'Die Bewertung konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.'],
            ];
            [$vote_type, $vote_text] = $vote_messages[(string) ($vote_flash['status'] ?? '')] ?? $vote_messages['error'];
            $vote_message = '<div class="alert alert-' . $vote_type . ' py-2 mb-2" role="status" id="pdlVoteMessage">' . htmlspecialchars($vote_text, ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }

    // Eine Bewertung je Release in 24 Stunden: angemeldet je Konto, als Gast je IP-Adresse
    $can_vote = ($user_rights['vote'] ?? 'N') === "Y";
    $vote_locked = $can_vote
        && pdl_lock_exists($db_handler, $sql_table, 'vote', $release_id, (int) ($user_details['user_id'] ?? 0), (string) $ip);

    if (!$can_vote) {
        $release_row['vote_form'] = empty($user_details)
            ? '<p class="small text-muted mb-0" id="pdlVoteHint">Bitte <a href="' . $login_href . '" id="pdlVoteLogin">melden Sie sich an</a>, um dieses Release zu bewerten.</p>'
            : '<p class="small text-muted mb-0" id="pdlVoteHint">Ihre Benutzergruppe darf keine Releases bewerten.</p>';
    } elseif ($vote_locked) {
        $release_row['vote_form'] = $vote_message === ''
            ? '<p class="small text-muted mb-0" id="pdlVoteHint">Sie haben dieses Release bereits bewertet.</p>'
            : '';
    } else {
        $vote_options = '';
        for ($note = 10; $note >= 1; $note--) {
            $label = $note === 10 ? '10 – sehr gut' : ($note === 1 ? '1 – sehr schlecht' : (string) $note);
            $vote_options .= '<option value="' . $note . '">' . $label . '</option>';
        }
        $release_row['vote_form'] = '<form action="' . $sf . '" method="post" id="pdlVoteForm" class="d-flex flex-wrap align-items-center gap-2">'
            . csrf_input()
            . '<input type="hidden" name="release_id" value="' . $release_id . '">'
            . '<input type="hidden" name="vote" value="1">'
            . '<label for="pdlVoteId" class="form-label mb-0">Ihre Note:</label>'
            . '<select id="pdlVoteId" name="vote_id" class="form-select form-select-sm w-auto">' . $vote_options . '</select>'
            . '<button type="submit" class="btn btn-primary btn-sm" id="pdlVoteSubmit">Bewerten</button>'
            . '</form>';
    }

    $vote_summary = $votes > 0
        ? 'Durchschnitt: <strong>' . htmlspecialchars((string) $release_row['vote'], ENT_QUOTES, 'UTF-8') . '</strong> von 10 (' . pdl_count_label($votes, 'Stimme', 'Stimmen') . ')'
        : 'Noch keine Bewertungen.';

    $tpl_file_detail_raw = (string) ($template['file_detail'] ?? '');
    $tpl_file_detail_usable = $tpl_file_detail_raw !== '' && !pdl_template_is_legacy($tpl_file_detail_raw) && (
        str_contains($tpl_file_detail_raw, '{name}')
        || str_contains($tpl_file_detail_raw, '{files}')
        || str_contains($tpl_file_detail_raw, '{text}')
    );

    $template['file_detail'] = str_replace("{total_size}", size($total_size), $tpl_file_detail_raw);
    $template['file_detail'] = str_replace("{total_traffic}", size($total_traffic), $template['file_detail']);
    $template['file_detail'] = str_replace("{total_downloads}", (string)$total_downloads, $template['file_detail']);
    $template['file_detail'] = str_replace("{total_files}", (string)$total_files, $template['file_detail']);
    $template['file_detail'] = str_replace("{dlspeed}", dlspeed($total_size), $template['file_detail']);
    $template['file_detail'] = str_replace("{speed}", (string) ($settings['dlspeed'] ?? ''), $template['file_detail']);

    // Autor: nur der Name, die E-Mail-Adresse wird nicht als mailto-Link veröffentlicht
    $autor = '';
    if (($release_row['autor'] ?? 0) == 0) {
        $autor = htmlspecialchars((string) ($release_row['autor_nick'] ?? ''), ENT_QUOTES, 'UTF-8');
        $autor_homepage = pdl_safe_url((string) ($release_row['autor_homepage'] ?? ''), ['http', 'https'], false);
        if ($autor_homepage !== null) {
            $autor .= ' <a href="' . htmlspecialchars($autor_homepage, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener nofollow"><img src="pdl-gfx/www.gif" class="pdl-www-icon" alt="Homepage von ' . $autor . '"></a>';
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
        $release_row['text'] = str_replace((string) ($settings['trenn_string'] ?? ''), "", $release_row['text']);
    }
    if ($release_row['text'] != "N/A") {
        $release_row['text'] = bbcode($release_row['text'], $settings['badwords_releases'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_releases'] ?? 'N');
    }

    // Screenshots (Vorschau unter pdl-gfx/screens/, fehlende Bilder ohne kaputtes Bildsymbol)
    $screens_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['screens'] . " WHERE release_id='" . $release_id_safe . "' ORDER BY screen_id ASC");
    $screens = '';
    $screen_no = 0;
    while ($screens_row = $db_handler->sql_fetch_array($screens_res)) {
        $screen_no++;
        $screens_row['release_id'] = $release_id;
        $screen_link_id = (int)($screens_row['screen_id'] ?? 0);
        $screen_caption = trim(stripslashes((string) (($screens_row['text'] ?? '') !== '' ? $screens_row['text'] : ($screens_row['name'] ?? ''))));
        $screen_alt = $screen_caption !== '' ? $screen_caption : 'Screenshot ' . $screen_no;
        $screen_img = pdl_screen_file($screens_row, 'k', $pdl_webroot);
        if ($screen_img === '') {
            $screen_img = pdl_screen_file($screens_row, 'g', $pdl_webroot);
        }
        $screens .= '<a class="pdl-screen-thumb" href="' . $sf . 'screen_id=' . $screen_link_id . '" data-screen-id="' . $screen_link_id . '">';
        if ($screen_img !== '') {
            $screens .= '<img src="' . htmlspecialchars($screen_img, ENT_QUOTES, 'UTF-8') . '" class="img-thumbnail" loading="lazy" alt="' . htmlspecialchars($screen_alt, ENT_QUOTES, 'UTF-8') . '">';
        } else {
            $screens .= '<span class="btn btn-sm btn-outline-light">' . htmlspecialchars($screen_alt, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $screens .= '</a>';
    }
    $release_row['screens'] = $screens !== '' ? $screens : 'Keine Screenshots vorhanden.';

    // Angemeldet über einen Hinweis auf dieser Seite (back_release, siehe
    // pdl_header.inc.php): Rückmeldung wie sonst auf der Anmeldeseite
    if (!empty($_GET['login_ok']) && !empty($user_details)) {
        echo '<div id="pdlLoginOk">' . pdl_alert('success', '<strong>Sie sind jetzt angemeldet.</strong> Willkommen, '
            . htmlspecialchars((string) ($user_details['nick'] ?? ''), ENT_QUOTES, 'UTF-8') . '!') . '</div>';
    }

    if ($tpl_file_detail_usable) {
        echo replace((string) $template['file_detail'], $release_row);
        echo '<section class="mt-3" id="pdlVote">' . $vote_message . '</section>';
    } else {
        // Bootstrap-Ansicht mit Name, Beschreibung, Eckdaten, Dateien,
        // Screenshots und Bewertung (Standard, solange keine eigene Vorlage
        // file_detail gepflegt ist).
        $r_name = htmlspecialchars($release_row['name'], ENT_QUOTES, 'UTF-8');
        $r_text_html = (string) ($release_row['text'] ?? 'N/A');
        $r_time = (int) ($release_row['time'] ?? 0);
        $r_date = $r_time > 0 ? date((string) ($settings['date_format'] ?? 'd.m.Y'), $r_time) : '';

        echo '<article class="card pdl-card mb-4" id="pdlRelease" data-release-id="' . $release_id . '">';
        echo '<header class="card-header pdl-card-header">';
        echo '<h2 class="h5 mb-0">' . $r_name . '</h2>';
        echo '</header>';
        echo '<div class="card-body">';

        if ($r_text_html !== '' && $r_text_html !== 'N/A') {
            echo '<div class="mb-3 pdl-release-text">' . $r_text_html . '</div>';
        }

        echo '<dl class="row g-2 small mb-3 pdl-release-facts">';
        $facts = [
            'Autor' => (string) $release_row['autor'] !== '' ? (string) $release_row['autor'] : 'Unbekannt',
        ];
        if ($r_date !== '') {
            $facts['Veröffentlicht'] = htmlspecialchars($r_date, ENT_QUOTES, 'UTF-8');
        }
        $facts['Gesamtgröße'] = htmlspecialchars(size($total_size), ENT_QUOTES, 'UTF-8');
        $facts['Aufrufe'] = number_format((int) ($release_row['views'] ?? 0), 0, ',', '.');
        $facts['Downloads'] = number_format($total_downloads, 0, ',', '.');
        $facts['Anzahl Dateien'] = (string) $total_files;
        foreach ($facts as $fact_label => $fact_value) {
            echo '<div class="col-12 col-md-6 d-flex gap-2"><dt class="mb-0">' . $fact_label . ':</dt><dd class="mb-0">' . $fact_value . '</dd></div>';
        }
        echo '</dl>';

        echo '<h3 class="h6 mt-3">Dateien zum Herunterladen</h3>';
        echo $files;

        if ($screens !== '') {
            echo '<h3 class="h6 mt-4">Screenshots</h3>';
            echo '<div class="d-flex flex-wrap gap-2 pdl-screens" id="pdlScreens">' . $screens . '</div>';
        }

        echo '<section class="mt-4 pt-3 border-top pdl-vote" id="pdlVote" aria-labelledby="pdlVoteHeading">';
        echo '<h3 class="h6" id="pdlVoteHeading">Bewertung</h3>';
        echo $vote_message;
        echo '<p class="small mb-2" id="pdlVoteSummary">' . $vote_summary . '</p>';
        echo $release_row['vote_form'];
        echo '</section>';

        echo '</div></article>';
    }
    echo (string) ($template['own_footer'] ?? '');

    if (($settings['enable_comments'] ?? 'N') == "Y") {
        $comments_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['comments'] . " WHERE release_id='" . $release_id_safe . "' ORDER BY time ASC, comment_id ASC");
        $comments_num = $db_handler->sql_num_rows($comments_res);

        echo '<section class="card pdl-card mt-4" id="pdlComments" aria-labelledby="pdlCommentsHeading">';
        echo '<header class="card-header pdl-card-header">';
        echo '<h2 class="h5 mb-0" id="pdlCommentsHeading">Kommentare <span class="badge text-bg-secondary" id="pdlCommentCount">' . $comments_num . '</span></h2>';
        echo '</header>';
        echo '<div class="card-body">';

        if ($comments_num == 0) {
            echo '<p class="text-muted mb-0" id="pdlCommentsEmpty">Noch keine Kommentare.</p>';
        } else {
            $tpl_comments_raw = (string) ($template['comments'] ?? '');
            $tpl_comments_usable = $tpl_comments_raw !== '' && !pdl_template_is_legacy($tpl_comments_raw) && (
                str_contains($tpl_comments_raw, '{titel}')
                || str_contains($tpl_comments_raw, '{text}')
                || str_contains($tpl_comments_raw, '{autor}')
            );
            echo '<ol class="list-unstyled mb-0 pdl-comment-list" id="pdlCommentList">';
            while ($comments_row = $db_handler->sql_fetch_array($comments_res)) {
                $comment_id = (int) ($comments_row['comment_id'] ?? 0);
                $comments_row['autor'] = (int) ($comments_row['user_id'] ?? 0) === 0 ? 'Gast' : user((int) $comments_row['user_id']);
                $comments_row['titel'] = stripslashes((string) ($comments_row['titel'] ?? ''));
                $comments_row['text'] = bbcode(stripslashes((string) ($comments_row['text'] ?? '')), $settings['badwords_comments'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_comments'] ?? 'N');

                echo '<li class="pdl-comment" id="pdlComment' . $comment_id . '">';
                if ($tpl_comments_usable) {
                    echo replace($tpl_comments_raw, $comments_row);
                } else {
                    $c_time = (int) ($comments_row['time'] ?? 0);
                    $c_date = $c_time > 0 ? date((string) ($settings['date_format'] ?? 'd.m.Y'), $c_time) : '';
                    echo '<article class="border-bottom pb-3 mb-3">';
                    echo '<header class="d-flex justify-content-between flex-wrap gap-2 mb-2">';
                    echo '<strong class="pdl-comment-title">' . htmlspecialchars($comments_row['titel'], ENT_QUOTES, 'UTF-8') . '</strong>';
                    echo '<span class="small text-muted pdl-comment-meta">von ' . (string) $comments_row['autor']
                        . ($c_date !== '' ? ' &middot; ' . htmlspecialchars($c_date, ENT_QUOTES, 'UTF-8') : '') . '</span>';
                    echo '</header>';
                    echo '<div class="pdl-comment-text">' . nl2br((string) $comments_row['text'], false) . '</div>';
                    echo '</article>';
                }
                echo '</li>';
            }
            echo '</ol>';
        }

        // Kommentar gespeichert: Rückmeldung nach der Weiterleitung aus
        // pdl_ucomments.modul.php, direkt unter dem neuen Kommentar
        $comment_flash = $_SESSION['pdl_comment_flash'] ?? null;
        if (is_array($comment_flash) && (int) ($comment_flash['release_id'] ?? 0) === $release_id) {
            unset($_SESSION['pdl_comment_flash']);
            if (!empty($_GET['commented'])) {
                echo '<div id="pdlCommentSaved">' . pdl_alert('success', '<strong>Ihr Kommentar wurde veröffentlicht.</strong>') . '</div>';
            }
        }

        // Kommentar schreiben: Formular, Hinweis für Gäste oder fehlendes Recht
        if (!empty($user_details)) {
            if (($user_rights['addcomments'] ?? 'N') == "Y") {
                echo '<div class="border-top pt-3 mt-3" id="pdlCommentFormArea">';
                echo '<h3 class="h6 mb-3">Kommentar schreiben</h3>';
                echo pdl_comment_form($release_id, (string) ($user_details['nick'] ?? ''));
                echo '</div>';
            } else {
                echo '<p class="small text-muted border-top pt-3 mt-3 mb-0" id="pdlCommentHint">Ihre Benutzergruppe darf keine Kommentare schreiben.</p>';
            }
        } else {
            echo '<p class="border-top pt-3 mt-3 mb-0" id="pdlCommentHint">Bitte <a href="' . $login_href . '" id="pdlCommentLogin">melden Sie sich an</a>'
                . ' oder <a href="' . $sf . 'usercenter=register">registrieren Sie sich</a>, um einen Kommentar zu schreiben.</p>';
        }

        echo '</div></section>';
    }
}
