<?
include("header.inc.php");
if($user_rights[editfiles] == "Y")
 {
  $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT release_id FROM $sql_table[comments] WHERE comment_id='$comment_id'"));
  $release_id = $release[release_id];
  if(!$release_id)
   {
    echo "<br>Bitte ein Kommentar auswählen.";
   }
  else
   {
    if($submit == 1)
     {
      $text .= "\n\nEditiert von $user_details[nick] am ".date($settings[date_format]);
      $db_handler->sql_query("UPDATE $sql_table[comments] SET titel='".addslashes($titel)."', text='".addslashes($text)."' WHERE comment_id='$comment_id'");
      echo "<br>done...<br><a href=\"editrelease.php?release_id=$release_id\">Zurück zum Release</a>";
     }
    else
     {
      $comment = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[comments] WHERE comment_id='$comment_id'"));
      ?>
<br><br>
<form action="editcomment.php?submit=1" method="post">
<input type="hidden" name="comment_id" value="<? echo $comment_id; ?>">
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Kommentar Editieren</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Titel</b><br>
            <small>Titel des Kommentars</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="titel" size="35" value="<? echo stripslashes($comment[titel]); ?>">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Text</b><br>
            <small>Der Kommentar selbst</small>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <textarea cols="60" rows="10" name="text"><? echo stripslashes($comment[text]); ?></textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Los!">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
      <?
     }
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
