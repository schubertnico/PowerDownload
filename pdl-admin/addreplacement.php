<?
include("header.inc.php");
if($user_rights[replacement] == "Y")
 {
  if($type == "b")
   {
    if($submit == 1)
     {
      $db_handler->sql_query("INSERT INTO $sql_table[replacements] (old,type) VALUES ('$wort','b')");
      echo "<br>Zensur hinzugefügt.";
     }
    else
     {
      ?>
<br><br>
<form action="addreplacement.php?type=b&submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="40%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Zensur hinzufügen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Das zu zensierende Wort
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="wort" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Zensieren!">
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
  elseif($type == "g")
   {
    if($submit == 1)
     {
      $db_handler->sql_query("INSERT INTO $sql_table[replacements] (old,neu,type) VALUES ('$old','$neu','g')");
      echo "<br>Glossar hinzugefügt.";
     }
    else
     {
      ?>
<br><br>
<form action="addreplacement.php?type=g&submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="40%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Glossar hinzufügen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Das zu ersetzende Wort
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="old" size="30">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Ersetzen durch
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="neu" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Glossar hinzufügen">
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
  elseif($type == "s")
   {
    if($submit == 1)
     {
      if(is_uploaded_file($smilie))
       {
        move_uploaded_file($smilie, "../pdl-gfx/smilies/$smilie_name");
        $db_handler->sql_query("INSERT INTO $sql_table[replacements] (old,neu,type) VALUES ('$code','pdl-gfx/smilies/$smilie_name','s')");
        echo "<br>Smilie hinzugefügt.";
       }
      else
       {
        $db_handler->sql_query("INSERT INTO $sql_table[replacements] (old,neu,type) VALUES ('$code','$smilie_url','s')");
        echo "<br>Smilie hinzugefügt.";
       }
     }
    else
     {
      ?>
<br><br>
<form action="addreplacement.php?type=s&submit=1" method="post" enctype="multipart/form-data">
<table border="0" cellpadding="0" cellspacing="0" width="55%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Smilie hinzufügen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Smiliecode
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="code" size="30">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            Smilie Uploaden
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="file" name="smilie" size="30">
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>oder</b> URL eingeben
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="text" name="smilie_url" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Smilie hinzufügen">
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
  else
   {
    ?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="35%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" align="center">
            <b>Replacements hinzufügen</b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <a href="addreplacement.php?type=b">Zensur</a>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <a href="addreplacement.php?type=g">Glossar</a>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>" align="center">
            <a href="addreplacement.php?type=s">Smilies</a>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" align="center">
            Bitte wählen sie einen Replacement Typen den sie hinzufügen wollen.
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
