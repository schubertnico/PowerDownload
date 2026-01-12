<?php
include("header.inc.php");
if($user_rights['adminaccess'] == "Y")
 {
?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Zensur</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="2" align="center">
            Zensiert werden folgende W&ouml;rter:
          </td>
        </tr>
        <?php
        $badwords_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='b' ORDER BY old ASC");
        while($badwords_row = $db_handler->sql_fetch_array($badwords_res))
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="2" align="center">
            <?php echo htmlspecialchars($badwords_row['old']); ?>
          </td>
        </tr>
        <?php
         }
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Smilies</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            Smiliecode
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            Smiliebild
          </td>
        </tr>
        <?php
        $smilies_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='s' ORDER BY LENGTH(old) DESC");
        while($smilies_row = $db_handler->sql_fetch_array($smilies_res))
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            <?php echo htmlspecialchars($smilies_row['old']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            <?php
            if(preg_match("/http:\/\//siU",$smilies_row['neu']))
             { echo "<img src=\"" . htmlspecialchars($smilies_row['neu']) . "\" border=\"0\">"; }
            else
             { echo "<img src=\"../" . htmlspecialchars($smilies_row['neu']) . "\" border=\"0\">"; }
            ?>
          </td>
        </tr>
        <?php
         }
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Glossar</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            Vorher
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            Nachher
          </td>
        </tr>
        <?php
        $glossary_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='g' ORDER BY LENGTH(old) DESC");
        while($glossary_row = $db_handler->sql_fetch_array($glossary_res))
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            <?php echo htmlspecialchars($glossary_row['old']); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            <?php echo htmlspecialchars($glossary_row['neu']); ?>
          </td>
        </tr>
        <?php
         }
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<?php
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
