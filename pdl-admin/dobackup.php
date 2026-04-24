<?php
include("header.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : (isset($_POST['submit']) ? (int)$_POST['submit'] : 0);
$backup = isset($_FILES['backup']['tmp_name']) ? $_FILES['backup']['tmp_name'] : '';

if($user_rights['god'] == "Y")
 {
  if($submit == 1)
   {
    if(is_uploaded_file($backup))
     {
      $lines = file($backup);
      if ($lines !== false)
       {
        $query = implode("", $lines);
        $querys = array();
        set_time_limit(300);
        split_query($querys,$query);
        for($i = 0; $i < count($querys); $i++)
         {
          $db_handler->sql_query($querys[$i]);
         }
        echo "<br>done...";
       }
      else
       { echo "<br>Fehler beim Lesen der Datei."; }
     }
    else
     { echo "<br>Bitte eine Datei auswaehlen."; }
   }
  else
   {
    ?>
<br><br>
<form action="dobackup.php?submit=1" method="post" enctype="multipart/form-data">
<table border="0" cellpadding="0" cellspacing="0" width="55%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Backup ausfuehren</b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>" colspan="2" align="center">
            Fuehren sie ein Backup nur aus, wenn es absolut notwendig ist. Jenachdem wie
            alt das Backup ist gehen Daten verloren. Also vorsicht!
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Backup Datei</b><br>
            <small>Die .sql Datei, die beim Backup erstellt wurde.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="file" name="backup" size="30">
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Los!">
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
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
