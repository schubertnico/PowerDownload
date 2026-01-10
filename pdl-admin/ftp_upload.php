<?php
include("header.inc.php");

// Extract POST/GET variables
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$upload_to = isset($_GET['upload_to']) ? $_GET['upload_to'] : '';
$release_id = isset($_GET['release_id']) ? (int)$_GET['release_id'] : 0;

// Handle file upload variables
$upload = isset($_FILES['upload']['tmp_name']) ? $_FILES['upload']['tmp_name'] : '';
$upload_name = isset($_FILES['upload']['name']) ? $_FILES['upload']['name'] : '';

if($user_rights['adminaccess'] == "Y")
 {
  if($settings['ftp_on'] == "Y" && function_exists("ftp_connect"))
   {
    set_time_limit(300);
    $ftp_handler = ftp_connect($settings['ftp_server']);
    if(!ftp_login($ftp_handler,$settings['ftp_user'],$settings['ftp_passwort']))
     { echo "Login Fehlgeschlagen. &Uuml;berpr&uuml;fen sie die Login Daten."; }
    else
     {
      if($submit == 1)
       {
        if(is_uploaded_file($upload))
         {
          if(ftp_size($ftp_handler,$upload_to.$upload_name) != -1) // wenn schon exisitert
           { echo "Datei mit selbem Namen existiert bereits."; }
          else
           {
            $upload_result=ftp_put($ftp_handler, $upload_to.$upload_name, $upload, FTP_BINARY);
            echo "<br>done...<br><a href=\"ftp_browser.php?chdir=" . htmlspecialchars(urlencode($upload_to)) . "&amp;release_id=" . htmlspecialchars($release_id) . "\">Zur&uuml;ck zum FTP Browser</a>";
           }
         }
        else
         {
          echo "Bitte eine Datei ausw&auml;hlen!";
         }
       }
      else
       {
        $max = get_cfg_var("upload_max_filesize");
        if(substr($max,strlen($max)-1,strlen($max)) == "M") $max = substr($max,0,strlen($max)-1)*1024*1024;
        elseif(substr($max,strlen($max)-1,strlen($max)) == "K") $max = substr($max,0,strlen($max)-1)*1024;
      ?>
<br><br>
<form enctype="multipart/form-data" action="ftp_upload.php?upload_to=<?php echo htmlspecialchars(urlencode($upload_to)); ?>&amp;release_id=<?php echo htmlspecialchars($release_id); ?>&amp;submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Upload in den Ordner <?php echo htmlspecialchars($settings['ftp_server_url'].$upload_to); ?></b>
          </td>
        </tr>
        <?php $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <b>Datei</b><br>
            W&auml;hlen sie die zu Uploadende Datei. Maximale Dateigr&ouml;&szlig;e: <?php echo size($max); ?>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($alt); ?>">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo htmlspecialchars($max); ?>">
            <input type="file" name="upload" size="35">
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
    ftp_quit($ftp_handler);
   }
  else
   {
    echo "Der Server unterst&uuml;tzt keine FTP Funktionen oder ein Admin hat den FTP Browser ausgeschaltet.";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
