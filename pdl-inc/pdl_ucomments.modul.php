<?php
/**
 * PowerDownload - User Comments Module
 * @license MIT
 */

if (($user_rights['addcomments'] ?? 'N') == "Y" && ($settings['enable_comments'] ?? 'N') == "Y") {
    $submit = (int)($submit ?? 0);
    $release_id = (int)($release_id ?? 0);

    if ($submit == 1) {
        $titel = $titel ?? '';
        $text = $text ?? '';
        if (!$titel || !$text) {
            echo "<br><center>Bitte Titel und Text eingeben.<br><a href=\"javascript:history.back()\">Zurueck</a></center>";
        } else {
            $user_id_safe = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);
            $release_id_safe = $db_handler->sql_escape_int($release_id);
            $titel_safe = $db_handler->sql_escape_string($titel);
            $text_safe = $db_handler->sql_escape_string($text);
            $db_handler->sql_query("INSERT INTO " . $sql_table['comments'] . " (user_id,release_id,titel,text,time) VALUES ('" . $user_id_safe . "','" . $release_id_safe . "','" . $titel_safe . "','" . $text_safe . "','" . time() . "')");
            echo "<br><center>Ihr Kommentar wurde gepostet.<br><a href=\"" . htmlspecialchars($settings['script_file'] ?? '') . "release_id=" . $release_id . "\">Zurueck zum Release</a></center>";
        }
    } else {
        $html = ($settings['html_comments'] ?? 'N') == "Y" ? "An" : "Aus";
        $zensur = ($settings['badwords_comments'] ?? 'N') == "Y" ? "An" : "Aus";
        $bbcode = ($settings['bb_code'] ?? 'N') == "Y" ? "An" : "Aus";
        $smilies = ($settings['smilies'] ?? 'N') == "Y" ? "An" : "Aus";
        $glossar = ($settings['glossary'] ?? 'N') == "Y" ? "An" : "Aus";

        if ($user_details ?? null) {
            $user = htmlspecialchars($user_details['nick'] ?? '');
        } else {
            $script_file = htmlspecialchars($settings['script_file'] ?? '');
            $user = "Gast - <a href=\"" . $script_file . "usercenter=login\">Login</a> - <a href=\"" . $script_file . "usercenter=register\">Anmelden</a>";
        }

        $form = str_replace("{html}", $html, (string) ($template['comments_form'] ?? ''));
        $form = str_replace("{zensur}", $zensur, $form);
        $form = str_replace("{bbcode}", $bbcode, $form);
        $form = str_replace("{smilies}", $smilies, $form);
        $form = str_replace("{glossar}", $glossar, $form);
        $form = str_replace("{user}", $user, $form);

        echo "<form action=\"" . htmlspecialchars($settings['script_file'] ?? '') . "usercenter=comments&submit=1&release_id=" . $release_id . "\" method=\"post\">" . replace($form, []) . "</form>";
    }
} else {
    echo "<br><center>Sie haben keine Rechte ein Kommentar zu posten. Ihnen oder ihrer Benutzergruppe wurde das Recht entzogen oder die Kommentare sind Global ausgeschaltet.</center>";
}
