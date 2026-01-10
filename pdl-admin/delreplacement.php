<?
include("header.inc.php");
if($user_rights[replacement] == "Y")
 {
  if($submit == 1)
   {
    $getrep = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[replacements] WHERE rep_id='$rep_id'"));
    $db_handler->sql_query("DELETE FROM $sql_table[replacements] WHERE rep_id='$rep_id'");
    if($getrep[type] == "s" && !preg_match("/http:\/\//siU",$getrep[neu]))
     { unlink("../".$getrep[neu]); }
    echo "<br>done..."; 
   }
  else
   {
?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="3" align="center">
            <b>Zensur</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="2" align="center">
            Zensiert werden folgende Wörter:
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            Löschen
          </td>
        </tr>
        <?
        $badwords_res = $db_handler->sql_query("SELECT * FROM $sql_table[replacements] WHERE type='b' ORDER BY old ASC");
        while($badwords_row = $db_handler->sql_fetch_array($badwords_res))
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" colspan="2" align="center">
            <? echo $badwords_row[old]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <a href="delreplacement.php?submit=1&rep_id=<? echo $badwords_row[rep_id] ?>">Löschen</a>
          </td>
        </tr>
        <?
         }
        ?>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="3" align="center">
            <b>Smilies</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" align="center">
            Smiliecode
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            Smiliebild
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            Löschen
          </td>
        </tr>
        <?
        $smilies_res = $db_handler->sql_query("SELECT * FROM $sql_table[replacements] WHERE type='s' ORDER BY LENGTH(old) DESC");
        while($smilies_row = $db_handler->sql_fetch_array($smilies_res))
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <? echo $smilies_row[old]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <?
            if(preg_match("/http:\/\//siU",$smilies_row[neu]))
             { echo "<img src=\"$smilies_row[neu]\" border=\"0\">"; }
            else
             { echo "<img src=\"../$smilies_row[neu]\" border=\"0\">"; }
            ?>
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <a href="delreplacement.php?submit=1&rep_id=<? echo $smilies_row[rep_id] ?>">Löschen</a>
          </td>
        </tr>
        <?
         }
        ?>
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="3" align="center">
            <b>Glossar</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" align="center">
            Vorher
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            Nachher
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            Löschen
          </td>
        </tr>
        <?
        $glossary_res = $db_handler->sql_query("SELECT * FROM $sql_table[replacements] WHERE type='g' ORDER BY LENGTH(old) DESC");
        while($glossary_row = $db_handler->sql_fetch_array($glossary_res))
         {
          $alt = alt_switch();
        ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <? echo $glossary_row[old]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <? echo $glossary_row[neu]; ?>
          </td>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <a href="delreplacement.php?submit=1&rep_id=<? echo $glossary_row[rep_id] ?>">Löschen</a>
          </td>
        </tr>
        <?
         }
        ?>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="3">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<?
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
