<?
include("header.inc.php");
if($user_rights[adminaccess] == "Y")
 {
  if($settings[ftp_on] == "Y" && function_exists("ftp_connect"))
   {
    set_time_limit(300);
    $ftp_handler = ftp_connect($settings[ftp_server]);
    if(!ftp_login($ftp_handler,$settings[ftp_user],$settings[ftp_passwort]))
     { echo "Login Fehlgeschlagen. Überprüfen sie die Login Daten."; }
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
            $upload=ftp_put($ftp_handler, $upload_to.$upload_name, $upload, FTP_BINARY);
            echo "<br>done...<br><a href=\"ftp_browser.php?chdir=$upload_to&release_id=$release_id\">Zurück zum FTP Browser</a>";
           }
         }
        else
         {
          echo "Bitte eine Datei auswählen!";
         }
       }
      else
       {
        $max = get_cfg_var("upload_max_filesize");
        if(substr($max,strlen($max)-1,strlen($max)) == "M") $max = substr($max,0,strlen($max)-1)*1024*1024;
        elseif(substr($max,strlen($max)-1,strlen($max)) == "K") $max = substr($max,0,strlen($max)-1)*1024;
      ?>
<br><br>
<form enctype="multipart/form-data" action="ftp_upload.php?upload_to=<? echo $upload_to; ?>&release_id=<? echo $release_id; ?>&submit=1" method="post">
<table border="0" cellpadding="0" cellspacing="0" width="85%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Upload in den Ordner <? echo $settings[ftp_server_url].$upload_to; ?></b>
          </td>
        </tr>
        <? $alt = alt_switch(); ?>
        <tr>
          <td bgcolor="<? echo $alt; ?>">
            <b>Datei</b><br>
            Wählen sie die zu Uploadende Datei. Maximale Dateigröße: <? echo size($max); ?>
          </td>
          <td bgcolor="<? echo $alt; ?>">
            <input type="hidden" name="MAX_FILE_SIZE" value="<? echo $max; ?>">
            <input type="file" name="upload" size="35">
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
    ftp_quit($ftp_handler);
   }
  else
   {
    echo "Der Server unterstützt keine FTP Funktionen oder ein Admin hat den FTP Browser ausgeschaltet.";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
