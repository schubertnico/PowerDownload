<?php
/**
 * PowerDownload - Show Screenshot Module
 *
 * Großbild eines Screenshots mit Beschriftung und Link zurück zum Release.
 *
 * @license MIT
 */

$screen_id_safe = $db_handler->sql_escape_int($screen_id);
$script_file = htmlspecialchars((string) ($settings['script_file'] ?? ''), ENT_QUOTES, 'UTF-8');

$screen = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT s.*, r.name AS release_name, r.released FROM " . $sql_table['screens'] . " AS s"
    . " LEFT JOIN " . $sql_table['release'] . " AS r ON r.release_id = s.release_id"
    . " WHERE s.screen_id='" . $screen_id_safe . "'"
));

if ($screen && ($screen['released'] ?? 'N') === 'Y') {
    // Aufrufe zählen: einmal je Sitzung und Screenshot
    $views = (int)($screen['views'] ?? 0);
    if (empty($_SESSION['pdl_viewed_screen'][$screen_id])) {
        $db_handler->sql_query("UPDATE " . $sql_table['screens'] . " SET views=views+1 WHERE screen_id='" . $screen_id_safe . "'");
        $_SESSION['pdl_viewed_screen'][$screen_id] = true;
        $views++;
    }

    $release_id = (int)($screen['release_id'] ?? 0);
    $caption = trim(stripslashes((string) (($screen['text'] ?? '') !== '' ? $screen['text'] : ($screen['name'] ?? ''))));
    $release_name = htmlspecialchars(stripslashes((string) ($screen['release_name'] ?? '')), ENT_QUOTES, 'UTF-8');
    $image = pdl_screen_file($screen, 'g', dirname(__DIR__));
    if ($image === '') {
        $image = pdl_screen_file($screen, 'k', dirname(__DIR__));
    }
    $alt = $caption !== '' ? $caption : 'Screenshot zu ' . stripslashes((string) ($screen['release_name'] ?? ''));

    echo '<section class="card pdl-card mx-auto" style="max-width: 960px;" id="pdlScreen" data-screen-id="' . $screen_id . '">';
    echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Screenshot zu „' . $release_name . '“</h2></header>';
    echo '<div class="card-body">';
    echo '<figure class="figure w-100 text-center mb-3">';
    if ($image !== '') {
        echo '<a href="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">'
            . '<img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" class="figure-img img-fluid rounded" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '">'
            . '</a>';
    } else {
        echo '<p class="text-muted my-4" id="pdlScreenMissing">Die Bilddatei zu diesem Screenshot fehlt.</p>';
    }
    if ($caption !== '') {
        echo '<figcaption class="figure-caption text-body">' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>';
    }
    echo '</figure>';
    echo '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">';
    echo '<a class="btn btn-outline-light btn-sm" href="' . $script_file . 'release_id=' . $release_id . '" id="pdlScreenBack">&larr; Zurück zum Release</a>';
    echo '<span class="small text-muted">Aufrufe: ' . number_format($views, 0, ',', '.') . '</span>';
    echo '</div>';
    echo '</div></section>';
} else {
    echo '<section class="card pdl-card mb-4" id="pdlScreenNotFound" role="alert"><div class="card-body">'
        . '<h2 class="h5">Screenshot nicht gefunden</h2>'
        . '<p class="mb-3">Diesen Screenshot gibt es nicht (mehr).</p>'
        . '<a class="btn btn-outline-light" href="' . $script_file . '">Zur Startseite</a>'
        . '</div></section>';
}
