<?php
include("header.inc.php");

// Extract POST/GET variables
$type = isset($_GET['type']) ? $_GET['type'] : '';
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$wort = isset($_POST['wort']) ? $db_handler->sql_escape_string($_POST['wort']) : '';
$old = isset($_POST['old']) ? $db_handler->sql_escape_string($_POST['old']) : '';
$neu = isset($_POST['neu']) ? $db_handler->sql_escape_string($_POST['neu']) : '';
$code = isset($_POST['code']) ? $db_handler->sql_escape_string($_POST['code']) : '';
$smilie_url = isset($_POST['smilie_url']) ? $db_handler->sql_escape_string($_POST['smilie_url']) : '';

// Handle file upload variables
$smilie = isset($_FILES['smilie']['tmp_name']) ? $_FILES['smilie']['tmp_name'] : '';
$smilie_name = isset($_FILES['smilie']['name']) ? $_FILES['smilie']['name'] : '';

if($user_rights['replacement'] == "Y")
 {
  if($type == "b")
   {
    if($submit == 1)
     {
      $db_handler->sql_query("INSERT INTO `" . $sql_table['replacements'] . "` (old,type) VALUES ('$wort','b')");
      echo "<br>Zensur hinzugef&uuml;gt.";
     }
    else
     {
      ?>
<br><br>
<form action="addreplacement.php?type=b&amp;submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="40%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Zensur hinzuf&uuml;gen</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Das zu zensierende Wort
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="wort" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Zensieren!">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
      <?php
     }
   }
  elseif($type == "g")
   {
    if($submit == 1)
     {
      $db_handler->sql_query("INSERT INTO `" . $sql_table['replacements'] . "` (old,neu,type) VALUES ('$old','$neu','g')");
      echo "<br>Glossar hinzugef&uuml;gt.";
     }
    else
     {
      ?>
<br><br>
<form action="addreplacement.php?type=g&amp;submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="40%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Glossar hinzuf&uuml;gen</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Das zu ersetzende Wort
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="old" size="30">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Ersetzen durch
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="neu" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Glossar hinzuf&uuml;gen">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
      <?php
     }
   }
  elseif($type == "s")
   {
    if($submit == 1)
     {
      if(is_uploaded_file($smilie))
       {
        move_uploaded_file($smilie, "../pdl-gfx/smilies/" . basename($smilie_name));
        $safe_smilie_name = $db_handler->sql_escape_string(basename($smilie_name));
        $db_handler->sql_query("INSERT INTO `" . $sql_table['replacements'] . "` (old,neu,type) VALUES ('$code','pdl-gfx/smilies/$safe_smilie_name','s')");
        echo "<br>Smilie hinzugef&uuml;gt.";
       }
      else
       {
        $db_handler->sql_query("INSERT INTO `" . $sql_table['replacements'] . "` (old,neu,type) VALUES ('$code','$smilie_url','s')");
        echo "<br>Smilie hinzugef&uuml;gt.";
       }
     }
    else
     {
      ?>
<br><br>
<form action="addreplacement.php?type=s&amp;submit=1" method="post" enctype="multipart/form-data">
<table border="0" cellpadding="0" cellspacing="0" width="55%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Smilie hinzuf&uuml;gen</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Smiliecode
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="code" size="30">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            Smilie Uploaden
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="file" name="smilie" size="30">
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>oder</b> URL eingeben
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="text" name="smilie_url" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Smilie hinzuf&uuml;gen">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
      <?php

     }
   }
  else
   {
    ?>
<br><br>
<table border="0" cellpadding="0" cellspacing="0" width="35%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" align="center">
            <b>Replacements hinzuf&uuml;gen</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            <a href="addreplacement.php?type=b">Zensur</a>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            <a href="addreplacement.php?type=g">Glossar</a>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" align="center">
            <a href="addreplacement.php?type=s">Smilies</a>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" align="center">
            Bitte w&auml;hlen sie einen Replacement Typen den sie hinzuf&uuml;gen wollen.
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
    <?php
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
