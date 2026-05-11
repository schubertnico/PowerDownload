<?php
/**
 * PowerDownload - User Comments Module
 * @license MIT
 */

$script_file = htmlspecialchars($settings['script_file'] ?? '');

if (!(($user_rights['addcomments'] ?? 'N') == "Y" && ($settings['enable_comments'] ?? 'N') == "Y")) {
    echo pdl_alert('warning', 'Sie haben keine Rechte einen Kommentar zu posten. Ihnen oder Ihrer Benutzergruppe wurde das Recht entzogen oder die Kommentare sind global ausgeschaltet.');
    return;
}

if (!($user_details ?? null)) {
    echo pdl_alert('info', 'Bitte <a href="' . $script_file . 'usercenter=login" class="alert-link">einloggen</a>, um zu kommentieren.');
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
        echo pdl_alert('success', '<strong>Ihr Kommentar wurde gepostet.</strong> <a href="' . $script_file . 'release_id=' . $release_id . '" class="alert-link">Zurück zum Release</a>');
        return;
    }
}

if ($errors) {
    $err_html = '<strong>Bitte prüfen Sie die folgenden Eingaben:</strong><ul class="mb-0 mt-2">';
    foreach ($errors as $err) {
        $err_html .= '<li>' . htmlspecialchars($err) . '</li>';
    }
    $err_html .= '</ul>';
    echo pdl_alert('danger', $err_html);
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

echo '<section class="card pdl-card mx-auto" style="max-width: 720px;">';
echo '<header class="card-header pdl-card-header"><h2 class="h5 mb-0">Kommentar schreiben</h2></header>';
echo '<div class="card-body">';
echo '<form action="downloads.php" method="post" novalidate>';
echo csrf_input();
echo '<input type="hidden" name="usercenter" value="comments">';
echo '<input type="hidden" name="submit" value="1">';
echo '<input type="hidden" name="release_id" value="' . (int) $release_id . '">';
echo replace($form, []);
echo '</form>';
echo '</div></section>';
