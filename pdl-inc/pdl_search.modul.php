<?php
/**
 * PowerDownload - Search Module
 * @license MIT
 */

if (($settings['enable_search'] ?? 'N') == "Y") {
    if ($submit == 1) {
        $text = $db_handler->sql_escape_string($text ?? '');
        $in = $in ?? 'texttitel';

        $query = "SELECT * FROM " . $sql_table['release'] . " WHERE ";

        $stucke = explode(" ", $text);
        $conditions = [];
        $patterns = [];
        for ($i = 0; $i < count($stucke); $i++) {
            $escaped = $db_handler->sql_escape_string($stucke[$i]);
            if ($in == "text") {
                $conditions[] = "text LIKE '%" . $escaped . "%'";
            } elseif ($in == "titel") {
                $conditions[] = "name LIKE '%" . $escaped . "%'";
            } else {
                $conditions[] = "(name LIKE '%" . $escaped . "%" . "' OR text LIKE '%" . $escaped . "%')";
            }
            $patterns[$i] = "/" . preg_quote($stucke[$i], '/') . "/i";
        }

        $query .= implode(" OR ", $conditions);
        $query .= " AND released='Y'";

        $orderby = $db_handler->sql_escape_string($settings['orderby'] ?? 'name');
        $orderseq = ($settings['orderseq'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY " . $orderby . " " . $orderseq;

        if ($page < 1) $page = 1;
        $perpage = (int)($settings['perpage'] ?? 10);
        $temp1 = $page * $perpage - $perpage;
        $limit = $temp1 . "," . $perpage;
        $total = $db_handler->sql_num_rows($db_handler->sql_query($query));
        echo "<br><center>Ihre Suchanfrage ergab <b>" . $total . "</b> Treffer</center><br>";

        if ($total > 0) {
            $text_encoded = urlencode($text);
            $in_encoded = urlencode($in);
            echo "<center>" . seiten($total, $perpage, "&show_search=1&submit=1&text=" . $text_encoded . "&in=" . $in_encoded, $settings['script_file'] ?? '') . "<br></center>";
            echo "<form action=\"" . htmlspecialchars($settings['script_file'] ?? '') . "change_list=1\" method=\"post\">";

            $release_rows = "";
            $files_res = $db_handler->sql_query($query . " LIMIT " . $limit);
            while ($files_row = $db_handler->sql_fetch_array($files_res)) {
                $release_id_safe = $db_handler->sql_escape_int($files_row['release_id'] ?? 0);
                $size = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT SUM(size) AS tsize FROM " . $sql_table['files'] . " WHERE release_id='" . $release_id_safe . "' AND mirror='0'"));
                $files_row['size'] = $size['tsize'] ?? 0;
                $files_row['id'] = $files_row['release_id'] ?? '';

                $files_row['name'] = stripslashes($files_row['name'] ?? '');
                $files_row['text'] = stripslashes($files_row['text'] ?? '');
                $files_row['text'] = preg_replace($patterns, "<b>$0</b>", $files_row['text']);
                $files_row['name'] = preg_replace($patterns, "<b>$0</b>", $files_row['name']);

                if (!($files_row['text'] ?? '')) {
                    $files_row['text'] = "N/A";
                } elseif (($settings['trenn_durch'] ?? '') == "zeichen") {
                    $files_row['text'] = str_replace($settings['trenn_string'] ?? '', "", $files_row['text']);
                    $trenn_zeichen = (int)($settings['trenn_zeichen'] ?? 100);
                    if (strlen($files_row['text']) > $trenn_zeichen) {
                        $files_row['text'] = substr($files_row['text'], 0, $trenn_zeichen) . "...";
                    }
                } elseif (($settings['trenn_durch'] ?? '') == "string") {
                    $text_parts = explode($settings['trenn_string'] ?? '', $files_row['text']);
                    $files_row['text'] = $text_parts[0];
                }
                if ($files_row['text'] != "N/A") {
                    $files_row['text'] = bbcode($files_row['text'], $settings['badwords_releases'] ?? 'N', $settings['smilies'] ?? 'N', $settings['glossary'] ?? 'N', $settings['bb_code'] ?? 'N', $settings['html_releases'] ?? 'N');
                }

                $release_rows .= replace($template['release_row'] ?? '', $files_row);
            }

            echo replace($template['release_box'] ?? '', ['rows' => $release_rows]) . "</form>";
        }
    } else {
        $script_file = htmlspecialchars($settings['script_file'] ?? '');
        $table_border = htmlspecialchars($template['table_border'] ?? '#000000');
        $header_bg = htmlspecialchars($template['header_bg'] ?? '#CCCCCC');
        $footer_bg = htmlspecialchars($template['footer_bg'] ?? '#CCCCCC');
        $alt = alt_switch();
        ?>
<center>
<form action="<?php echo $script_file; ?>show_search=1&submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="45%">
  <tr>
    <td bgcolor="<?php echo $table_border; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo $header_bg; ?>" colspan="2" align="center">
            <b>Suche</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Suchbegriff</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="text" size="30"><br>
            Einzelne Suchwoerter trennen durch Leerzeichen.
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Suchen in</b>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <select name="in">
            <option value="text">Beschreibung</option>
            <option value="texttitel">Beschreibung und Titel</option>
            <option value="titel">Titel</option>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo $footer_bg; ?>" colspan="2" align="center">
            <input type="submit" value="Suche starten">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</center>
</form>
        <?php
    }
} else {
    echo "<br><center>Die Suche wurde global deaktiviert.</center>";
}
