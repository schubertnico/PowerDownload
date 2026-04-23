<?php
/**
 * PowerDownload - User Comments Module
 * @license MIT
 */

$script_file = htmlspecialchars($settings['script_file'] ?? '');

if (!(($user_rights['addcomments'] ?? 'N') == "Y" && ($settings['enable_comments'] ?? 'N') == "Y")) {
    echo "<br><center>Sie haben keine Rechte ein Kommentar zu posten. Ihnen oder Ihrer Benutzergruppe wurde das Recht entzogen oder die Kommentare sind global ausgeschaltet.</center>";
    return;
}

if (!($user_details ?? null)) {
    echo '<br><center>Bitte <a href="' . $script_file . 'usercenter=login">einloggen</a>, um zu kommentieren.</center>';
    return;
}

$submit = (int)($submit ?? 0);
$release_id = (int)($release_id ?? 0);
$errors = [];

if ($submit == 1) {
    if (!csrf_verify($csrf_token ?? null)) {
        $errors[] = "Sicherheits-Token ungültig.";
    }

    $titel_raw = (string) ($titel ?? '');
    $text_raw = (string) ($text ?? '');

    if ($titel_raw === '' || $text_raw === '') {
        $errors[] = "Bitte Titel und Text eingeben.";
    }

    if (!$errors) {
        $user_id_safe = $db_handler->sql_escape_int($user_details['user_id'] ?? 0);
        $release_id_safe = $db_handler->sql_escape_int($release_id);
        $titel_safe = $db_handler->sql_escape_string($titel_raw);
        $text_safe = $db_handler->sql_escape_string($text_raw);
        $db_handler->sql_query("INSERT INTO " . $sql_table['comments'] . " (user_id,release_id,titel,text,time) VALUES ('" . $user_id_safe . "','" . $release_id_safe . "','" . $titel_safe . "','" . $text_safe . "','" . time() . "')");
        echo '<br><center>Ihr Kommentar wurde gepostet.<br><a href="' . $script_file . 'release_id=' . $release_id . '">Zurück zum Release</a></center>';
        return;
    }
}

if ($errors) {
    echo '<div style="color:#c00;text-align:center;margin:10px"><ul style="display:inline-block;text-align:left">';
    foreach ($errors as $err) {
        echo "<li>" . htmlspecialchars($err) . "</li>";
    }
    echo '</ul></div>';
}

$html = ($settings['html_comments'] ?? 'N') == "Y" ? "An" : "Aus";
$zensur = ($settings['badwords_comments'] ?? 'N') == "Y" ? "An" : "Aus";
$bbcode = ($settings['bb_code'] ?? 'N') == "Y" ? "An" : "Aus";
$smilies_on = ($settings['smilies'] ?? 'N') == "Y" ? "An" : "Aus";
$glossar = ($settings['glossary'] ?? 'N') == "Y" ? "An" : "Aus";
$user = htmlspecialchars($user_details['nick'] ?? '');

$form = str_replace("{html}", $html, (string) ($template['comments_form'] ?? ''));
$form = str_replace("{zensur}", $zensur, $form);
$form = str_replace("{bbcode}", $bbcode, $form);
$form = str_replace("{smilies}", $smilies_on, $form);
$form = str_replace("{glossar}", $glossar, $form);
$form = str_replace("{user}", $user, $form);

echo '<form action="' . $script_file . 'usercenter=comments&submit=1&release_id=' . $release_id . '" method="post">' . csrf_input() . replace($form, []) . '</form>';
