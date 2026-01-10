<?
// Debug Infos
if($settings[debug] == true)
 {
  $rendertime2=microtime();
  $rendertimetemp=explode(" ",$rendertime2);
  $rendertime2=$rendertimetemp[0]+$rendertimetemp[1];
  $rendertime=$rendertime2-$rendertime1;
  $rendertime=round($rendertime,3);
  echo "
                </td>
              </tr>
              <tr>
                <td bgcolor=\"$template[header_bg]\" colspan=\"2\" align=\"center\">
                  Renderzeit: ".$rendertime."s; ".$db_handler->querys." SQL Anfragen";
 } ?>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>

