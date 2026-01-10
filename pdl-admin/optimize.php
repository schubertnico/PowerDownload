<?
include("header.inc.php");
if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    echo "
<br><br>
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"75%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" colspan=\"4\" align=\"center\">
            <b>Optimieren</b>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$template[header_bg]\">
            <b>Tabelle</b>
          </td>
          <td bgcolor=\"$template[header_bg]\">
            <b>Größe</b>
          </td>
          <td bgcolor=\"$template[header_bg]\">
            <b>Ersparniss</b>
          </td>
          <td bgcolor=\"$template[header_bg]\">
            <b>Prozent</b>
          </td>
        </tr>
    ";
    $opt_res = $db_handler->sql_query("SHOW TABLE STATUS FROM $config_sql_database");
    while($opt_row = $db_handler->sql_fetch_array($opt_res))
     {
      $tabellen++;
      $verbrauch = $opt_row[Data_length]+$opt_row[Index_length]+$opt_row[Data_free];
      $gespart = $opt_row[Data_free];
      $total_gespart += $gespart;
      $total_verbrauch += $verbrauch;
      $db_handler->sql_query("OPTIMIZE TABLE $opt_row[Name]");
      $alt = alt_switch();
      echo "
        <tr>
          <td bgcolor=\"$alt\">
            $opt_row[Name]
          </td>
          <td bgcolor=\"$alt\">
            ".number_format($verbrauch/1024,2,",",".")."KB
          </td>
          <td bgcolor=\"$alt\">
            ".number_format($gespart/1024,2,",",".")."KB
          </td>
          <td bgcolor=\"$alt\">
            ".number_format($gespart*100/$verbrauch,2,",",".")."%
          </td>
        </tr>
    ";
     }
    echo "
        <tr>
          <td bgcolor=\"$template[footer_bg]\">
            <b>Gesamt: $tabellen</b>
          </td>
          <td bgcolor=\"$template[footer_bg]\">
            <b>".number_format($total_verbrauch/1024,2,",",".")."KB</b>
          </td>
          <td bgcolor=\"$template[footer_bg]\">
            <b>".number_format($total_gespart/1024,2,",",".")."KB</b>
          </td>
          <td bgcolor=\"$template[footer_bg]\">
            <b>".number_format($total_gespart*100/$total_verbrauch,2,",",".")."%</b>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
    ";
   }
  else
   {
    echo makedialog("Datenbank wirklich optimieren?","
            Wenn in einer Tabele viele Speicher- und Löschvorgänge stattgefunden haben, wird unnötiger Speicherplatz belegt.<br>
            Hier können Sie diesen freigeben.<br>
            Es wird empfohlen die Optimierung jeden Monat zu wiederholen.<br>","  Ja  ","optimize.php");
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
