<?
include("header.inc.php");

check_gd();

if($submit == 1)
 {
  if($settings[gdversion] == 0 || $settings[screen_autosize] == "N") $if = is_uploaded_file($screen_g) && is_uploaded_file($screen_k);
  else $if = is_uploaded_file($screen_g);
  if($if == 1)
   {
    if($settings[gdversion] == 0 || $settings[screen_autosize] == "N") $if = $screen_g_type != "image/pjpeg" || $screen_k_type != "image/pjpeg";
    else $if = $screen_g_type != "image/pjpeg";
    if($if == 1) echo "<br>Die Screens MÜSSEN im JPG Format sein.";
    else
     {
      $db_handler->sql_query("INSERT INTO $sql_table[screens] (release_id, text) VALUES ('$release_id', '".addslashes($text)."')");
      $screen_id = $db_handler->sql_insert_id();
      if(is_uploaded_file($screen_k))
       {
        move_uploaded_file($screen_g, "../pdl-gfx/screens/release".$release_id."screen".$screen_id."g.jpg");
        move_uploaded_file($screen_k, "../pdl-gfx/screens/release".$release_id."screen".$screen_id."k.jpg");
        echo "<br>done...<br><a href=\"editrelease.php?release_id=$release_id\">Zurück zum Release</a>";
       }
      else
       {
        move_uploaded_file($screen_g, "../pdl-gfx/screens/release".$release_id."screen".$screen_id."g.jpg");
        $full = imagecreatefromjpeg("../pdl-gfx/screens/release".$release_id."screen".$screen_id."g.jpg");
        $full_size = getimagesize("../pdl-gfx/screens/release".$release_id."screen".$screen_id."g.jpg");
        if($settings[screen_verhalt] == "width")
         {
          $verhalt = $full_size[0]/$width;
          $height = $full_size[1]/$verhalt;
         }
        else
         {
          $verhalt = $full_size[1]/$height;
          $width = $full_size[0]/$verhalt;
         }
        if($settings[gdversion] == 2)
         { $thumb = imagecreatetruecolor($width,$height); }
        else
         { $thumb = imagecreate($width,$height); }
        if($settings[gdversion] == 2)
         { imagecopyresampled($thumb,$full,0,0,0,0,$width,$height,$full_size[0],$full_size[1]); }
        else
         { imagecopyresized($thumb,$full,0,0,0,0,$width,$height,$full_size[0],$full_size[1]); }
        imagejpeg($thumb, "../pdl-gfx/screens/release".$release_id."screen".$screen_id."k.jpg", 60);
        imagedestroy($thumb);
        imagedestroy($full);
        echo "<br>done...<br><a href=\"editrelease.php?release_id=$release_id\">Zurück zum Release</a>";
       }
     }
   }
  else
   {
    echo "<br>Screen wurde nicht eingegeben.";
   }
 }
else
 { ?>
<br><br>
<form action="addscreen.php?submit=1" method="post" enctype="multipart/form-data">
<input type="hidden" name="release_id" value="<? echo $release_id; ?>">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<? echo $template[table_border]; ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<? echo $template[header_bg]; ?>" colspan="2" align="center">
            <b>Screen uploaden</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[alt_1]; ?>">
            Großer Screen<br>
            <small>Hier den großen Screen auswählen.</small>
          </td>
          <td bgcolor="<? echo $template[alt_1]; ?>">
            <input type="file" name="screen_g" size="35">
          </td>
        </tr>
        <?
        if($settings[gdversion] > 0 && $settings[screen_autosize] == "Y")
         {
          if($settings[screen_verhalt] == "width")
           {
        ?>
        <tr>
          <td bgcolor="<? echo $template[alt_2]; ?>">
            Breite<br>
            <small>Geben sie hier eine feste Breite ein. Die Höhe wird im Verhältniss gebildet.</small>
          </td>
          <td bgcolor="<? echo $template[alt_2]; ?>">
            <input type="text" name="width" size="35" value="<? echo $settings[screen_size]; ?>">
          </td>
        </tr>
        <?
           }
          else
           {
        ?>
        <tr>
          <td bgcolor="<? echo $template[alt_2]; ?>">
            Höhe<br>
            <small>Geben sie hier eine feste Höhe ein. Die Breite wird im Verhältniss gebildet.</small>
          </td>
          <td bgcolor="<? echo $template[alt_2]; ?>">
            <input type="text" name="height" size="35" value="<? echo $settings[screen_size]; ?>">
          </td>
        </tr>
        <?
           }
         }
        else
         {
        ?>
        <tr>
          <td bgcolor="<? echo $template[alt_2]; ?>">
            Kleiner Screen<br>
            <small>Da der Server keine Automatische verkleinerung unterstützt oder sie
            darauf bestehen den kleinen Screen selber zu gestalten müssen sie hier einen
            kleinen Screen angeben.</small>
          </td>
          <td bgcolor="<? echo $template[alt_2]; ?>">
            <input type="file" name="screen_k" size="35">
          </td>
        </tr>
        <? } ?>
        <tr>
          <td bgcolor="<? echo $template[alt_1]; ?>">
            Untertitel<br>
            <small>Zu jedem Screen kann man auch einen Untertitel eingeben. Dieser wird nur
            in der Detailansicht angezeigt.</small>
          </td>
          <td bgcolor="<? echo $template[alt_1]; ?>">
            <input type="text" name="text" size="35" maxsize="255">
          </td>
        </tr>
        <tr>
          <td bgcolor="<? echo $template[footer_bg]; ?>" colspan="2" align="center">
            <input type="submit" value="Screen uploaden">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<? }
include("footer.inc.php");
?>
