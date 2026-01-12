<?php
include("header.inc.php");

check_gd();

// Extract POST/GET variables
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$release_id = isset($_REQUEST['release_id']) ? (int)$_REQUEST['release_id'] : 0;
$text = isset($_POST['text']) ? $_POST['text'] : '';
$width = isset($_POST['width']) ? (int)$_POST['width'] : 0;
$height = isset($_POST['height']) ? (int)$_POST['height'] : 0;

// Handle file upload variables
$screen_g = isset($_FILES['screen_g']['tmp_name']) ? $_FILES['screen_g']['tmp_name'] : '';
$screen_g_type = isset($_FILES['screen_g']['type']) ? $_FILES['screen_g']['type'] : '';
$screen_k = isset($_FILES['screen_k']['tmp_name']) ? $_FILES['screen_k']['tmp_name'] : '';
$screen_k_type = isset($_FILES['screen_k']['type']) ? $_FILES['screen_k']['type'] : '';

if($submit == 1)
 {
  if($settings['gdversion'] == 0 || $settings['screen_autosize'] == "N") $if = is_uploaded_file($screen_g) && is_uploaded_file($screen_k);
  else $if = is_uploaded_file($screen_g);
  if($if == 1)
   {
    if($settings['gdversion'] == 0 || $settings['screen_autosize'] == "N") $if = $screen_g_type != "image/pjpeg" || $screen_k_type != "image/pjpeg";
    else $if = $screen_g_type != "image/pjpeg";
    if($if == 1) echo "<br>Die Screens M&Uuml;SSEN im JPG Format sein.";
    else
     {
      $escaped_text = $db_handler->sql_escape_string($text);
      $db_handler->sql_query("INSERT INTO `" . $sql_table['screens'] . "` (release_id, text) VALUES ('" . (int)$release_id . "', '$escaped_text')");
      $screen_id = $db_handler->sql_insert_id();
      if(is_uploaded_file($screen_k))
       {
        move_uploaded_file($screen_g, "../pdl-gfx/screens/release".$release_id."screen".$screen_id."g.jpg");
        move_uploaded_file($screen_k, "../pdl-gfx/screens/release".$release_id."screen".$screen_id."k.jpg");
        echo "<br>done...<br><a href=\"editrelease.php?release_id=" . htmlspecialchars((string) $release_id) . "\">Zur&uuml;ck zum Release</a>";
       }
      else
       {
        move_uploaded_file($screen_g, "../pdl-gfx/screens/release".$release_id."screen".$screen_id."g.jpg");
        $full = imagecreatefromjpeg("../pdl-gfx/screens/release".$release_id."screen".$screen_id."g.jpg");
        if ($full === false) {
            echo "<br>Fehler beim Laden des Bildes.";
        } else {
            $full_size = getimagesize("../pdl-gfx/screens/release".$release_id."screen".$screen_id."g.jpg");
            if ($full_size === false) {
                echo "<br>Fehler beim Ermitteln der Bildgr&ouml;&szlig;e.";
                imagedestroy($full);
            } else {
                if($settings['screen_verhalt'] == "width")
                 {
                  $verhalt = $full_size[0]/$width;
                  $height = $full_size[1]/$verhalt;
                 }
                else
                 {
                  $verhalt = $full_size[1]/$height;
                  $width = $full_size[0]/$verhalt;
                 }
                $thumb_width = max(1, (int)$width);
                $thumb_height = max(1, (int)$height);
                if($settings['gdversion'] == 2)
                 { $thumb = imagecreatetruecolor($thumb_width, $thumb_height); }
                else
                 { $thumb = imagecreate($thumb_width, $thumb_height); }
                if ($thumb === false) {
                    echo "<br>Fehler beim Erstellen des Thumbnails.";
                    imagedestroy($full);
                } else {
                    if($settings['gdversion'] == 2)
                     { imagecopyresampled($thumb,$full,0,0,0,0,(int)$width,(int)$height,$full_size[0],$full_size[1]); }
                    else
                     { imagecopyresized($thumb,$full,0,0,0,0,(int)$width,(int)$height,$full_size[0],$full_size[1]); }
                    imagejpeg($thumb, "../pdl-gfx/screens/release".$release_id."screen".$screen_id."k.jpg", 60);
                    imagedestroy($thumb);
                    imagedestroy($full);
                    echo "<br>done...<br><a href=\"editrelease.php?release_id=" . htmlspecialchars((string) $release_id) . "\">Zur&uuml;ck zum Release</a>";
                }
            }
        }
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
<input type="hidden" name="release_id" value="<?php echo htmlspecialchars((string) $release_id); ?>">
<table border="0" cellpadding="0" cellspacing="0" width="65%">
  <tr>
    <td bgcolor="<?php echo htmlspecialchars($template['table_border']); ?>">
      <table border="0" cellpadding="3" cellspacing="1" width="100%">
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['header_bg']); ?>" colspan="2" align="center">
            <b>Screen uploaden</b>
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_1']); ?>">
            Gro&szlig;er Screen<br>
            <small>Hier den gro&szlig;en Screen ausw&auml;hlen.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_1']); ?>">
            <input type="file" name="screen_g" size="35">
          </td>
        </tr>
        <?php
        if($settings['gdversion'] > 0 && $settings['screen_autosize'] == "Y")
         {
          if($settings['screen_verhalt'] == "width")
           {
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_2']); ?>">
            Breite<br>
            <small>Geben sie hier eine feste Breite ein. Die H&ouml;he wird im Verh&auml;ltniss gebildet.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_2']); ?>">
            <input type="text" name="width" size="35" value="<?php echo htmlspecialchars($settings['screen_size']); ?>">
          </td>
        </tr>
        <?php
           }
          else
           {
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_2']); ?>">
            H&ouml;he<br>
            <small>Geben sie hier eine feste H&ouml;he ein. Die Breite wird im Verh&auml;ltniss gebildet.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_2']); ?>">
            <input type="text" name="height" size="35" value="<?php echo htmlspecialchars($settings['screen_size']); ?>">
          </td>
        </tr>
        <?php
           }
         }
        else
         {
        ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_2']); ?>">
            Kleiner Screen<br>
            <small>Da der Server keine Automatische verkleinerung unterst&uuml;tzt oder sie
            darauf bestehen den kleinen Screen selber zu gestalten m&uuml;ssen sie hier einen
            kleinen Screen angeben.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_2']); ?>">
            <input type="file" name="screen_k" size="35">
          </td>
        </tr>
        <?php } ?>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_1']); ?>">
            Untertitel<br>
            <small>Zu jedem Screen kann man auch einen Untertitel eingeben. Dieser wird nur
            in der Detailansicht angezeigt.</small>
          </td>
          <td bgcolor="<?php echo htmlspecialchars($template['alt_1']); ?>">
            <input type="text" name="text" size="35" maxlength="255">
          </td>
        </tr>
        <tr>
          <td bgcolor="<?php echo htmlspecialchars($template['footer_bg']); ?>" colspan="2" align="center">
            <input type="submit" value="Screen uploaden">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<?php }
include("footer.inc.php");
?>
