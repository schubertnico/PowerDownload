<?php
/**
 * PowerDownload - Show Screenshot Module
 * @license MIT
 */

$screen_id_safe = $db_handler->sql_escape_int($screen_id);

$db_handler->sql_query("UPDATE " . $sql_table['screens'] . " SET views=views+1 WHERE screen_id='" . $screen_id_safe . "'");
$screen = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['screens'] . " WHERE screen_id='" . $screen_id_safe . "'"));

if ($screen) {
    $release_id = (int)($screen['release_id'] ?? 0);
    $views = (int)($screen['views'] ?? 0);
    $text = htmlspecialchars(stripslashes($screen['text'] ?? ''));
    $script_file = htmlspecialchars($settings['script_file'] ?? '');

    echo '<section class="card pdl-card mx-auto" style="max-width: 960px;">';
    echo '<div class="card-body text-center">';
    echo '<a href="' . $script_file . 'release_id=' . $release_id . '">';
    echo '<img src="pdl-gfx/screens/release' . $release_id . 'screen' . (int) $screen_id . 'g.jpg" class="img-fluid mb-3" alt="Screenshot">';
    echo '</a>';
    if ($text !== '') {
        echo '<p class="mb-2">' . $text . '</p>';
    }
    echo '<p class="small text-muted mb-0">Screen wurde ' . $views . ' mal angeschaut.</p>';
    echo '</div></section>';
} else {
    echo pdl_alert('warning', 'Screenshot nicht gefunden.');
}
