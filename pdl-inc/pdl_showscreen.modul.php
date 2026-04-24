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

    echo "
<center>
<a href=\"" . $script_file . "release_id=" . $release_id . "\"><img src=\"pdl-gfx/screens/release" . $release_id . "screen" . $screen_id . "g.jpg\" border=\"0\" alt=\"Screenshot\"></a><br>
" . $text . "<br>
Screen wurde " . $views . " mal angeschaut
</center>";
} else {
    echo "<center>Screenshot nicht gefunden.</center>";
}
