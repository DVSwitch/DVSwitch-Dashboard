<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/include/config.php';         
include_once $_SERVER['DOCUMENT_ROOT'].'/include/tools.php';        
include_once $_SERVER['DOCUMENT_ROOT'].'/include/functions.php';    
?>
<span style="font-weight: bold;font-size:14px;">Gateway Activity</span>
<fieldset style="box-shadow:0 0 10px #999;background-color:#e8e8e8e8; width:660px;margin-top:10px;margin-left:0px;margin-right:0px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
  <table style="margin-top:3px;">
    <tr>
      <th>Time (<?php echo date('T')?>)</th>
      <th>Mode</th>
      <th>Callsign</th>
      <th>Target</th>
      <th>Src</th>
      <th>Dur(s)</th>
      <th>Loss</th>
      <th>BER</th>
    </tr>
<?php
$i = 0;
for ($i = 0;  ($i <= 19); $i++) { //Last 20 calls
	if (isset($lastHeard[$i])) {
		$listElem = $lastHeard[$i];
		if ( $listElem[2] ) {
			$utc_time = $listElem[0];
                        $utc_tz =  new DateTimeZone('UTC');
                        $local_tz = new DateTimeZone(date_default_timezone_get ());
                        $dt = new DateTime($utc_time, $utc_tz);
                        $dt->setTimeZone($local_tz);
                        $local_time = strftime('%H:%M:%S %b %d', $dt->getTimestamp());

		echo"<tr>";
		echo"<td align=\"left\">&nbsp;$local_time</td>";
		echo"<td align=\"left\" style=\"color:green; font-weight:bold;\">&nbsp;$listElem[1]</td>";
		if (is_numeric($listElem[2]) || strpos($listElem[2], "openSPOT") !== FALSE ) {
		    echo "<td align=\"left\" style=\"color:#464646;\">&nbsp;<a href=\"https://database.radioid.net/database/view?id=$listElem[2]\" target=\"_blank\"><span style=\"color:#464646;font-weight:bold;\">$listElem[2]</span></a></td>";
		} elseif (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $listElem[2])|| $listElem[2] == "N0CALL") {
 	                       echo "<td align=\"left\" style=\"color:#464646;\"><b>&nbsp;$listElem[2]</b></td>";
		} else {
		    if (strpos($listElem[2],"-") > 0) { $listElem[2] = substr($listElem[2], 0, strpos($listElem[2],"-")); }
			    if ( $listElem[3] && $listElem[3] != '    ' ) {
			echo "<td align=\"left\">&nbsp;<a href=\"http://www.qrz.com/db/$listElem[2]\" target=\"_blank\"><b>$listElem[2]</b></a><span style=\"color:#464646;font-weight:bold;\">/$listElem[3]</span></td>";
		    } else {
			echo "<td align=\"left\">&nbsp;<a href=\"http://www.qrz.com/db/$listElem[2]\" target=\"_blank\"><b>$listElem[2]</b></a></td>";
		    }
		}
		if (strlen($listElem[4]) == 1) { $listElem[4] = str_pad($listElem[4], 8, " ", STR_PAD_LEFT); }
		if ( substr($listElem[4], 0, 6) === 'CQCQCQ' ) {
			echo "<td align=\"left\">&nbsp;<span style=\"color:#b5651d;font-weight:bold;\">$listElem[4]</span></td>";
		} else {
			echo "<td align=\"left\">&nbsp;<span style=\"color:#b5651d;font-weight:bold;\">".str_replace(" ","&nbsp;", $listElem[4])."</span></td>";
		}


		if ($listElem[5] == "DVSM/UC"){
			echo "<td style=\"background:#1d1;\">DVSM/UC</td>";
		}else{
			echo "<td>$listElem[5]</td>";
		}
		if ($listElem[6] == null) {
                             if ($listElem[1] == "DMR Slot 2" && $listElem[5] == "Net")  {echo "<td colspan=\"3\" style=\"background:#f93;\">RX DMR</td>";}
                             if ($listElem[1] == "DMR Slot 1" && $listElem[5] == "Net")  {echo "<td colspan=\"3\" style=\"background:#f93;\">RX DMR</td>";}
                             if ($listElem[1] == "YSF" && $listElem[5] == "Net")  {echo "<td colspan=\"3\" style=\"background:#ff9;\">RX YSF</td>";}
                             if ($listElem[1] == "P25" && $listElem[5] == "Net")  {echo "<td colspan=\"3\" style=\"background:#f9f;\">RX P25</td>";}
			     if ($listElem[1] == "D-Star" && $listElem[5] == "Net")  {echo "<td colspan=\"3\" style=\"background:#ade;\">RX NXDN</td>";}
                             if ($listElem[1] == "NXDN" && $listElem[5] == "Net")  {echo "<td colspan=\"3\" style=\"background:#f9f;\">RX D-Star</td>";}
                             if ($listElem[5] == "DVSM/UC")  {echo "<td colspan=\"3\" style=\"background:#f33;\">TX</td>";}
			} else if ($listElem[6] == "SMS") {
				echo "<td colspan=\"3\" style=\"background:#1d1;\">SMS</td>";
			} else if ($listElem[6] == "GPS") {
				echo "<td colspan=\"3\" style=\"background:#1d1;\"><a style=\"display:block;\" target=\"_blank\" href=https://www.openstreetmap.org/?mlat=".floatval($listElem[9])."&mlon=".floatval($listElem[10])."><b>GPS</b></a></td>";
			} else {
			echo "<td>$listElem[6]</td>";

			// Colour the Loss Field
			if (floatval($listElem[7]) < 1) { echo "<td>$listElem[7]</td>"; }
			elseif (floatval($listElem[7]) == 1) { echo "<td style=\"background:#1d1;\">$listElem[7]</td>"; }
			elseif (floatval($listElem[7]) > 1 && floatval($listElem[7]) <= 3) { echo "<td style=\"background:#fa0;\">$listElem[7]</td>"; }
			else { echo "<td style=\"background:#f33;color:#f9f9f9;\">$listElem[7]</td>"; }

			// Colour the BER Field
			if (floatval($listElem[8]) == 0) { echo "<td>$listElem[8]</td>"; }
			elseif (floatval($listElem[8]) >= 0.0 && floatval($listElem[8]) <= 1.9) { echo "<td style=\"background:#1d1;\">$listElem[8]</td>"; }
			elseif (floatval($listElem[8]) >= 2.0 && floatval($listElem[8]) <= 4.9) { echo "<td style=\"background:#fa0;\">$listElem[8]</td>"; }
			else { echo "<td style=\"background:#f33;color:#f9f9f9;\">$listElem[8]</td>"; }
		}
		echo"</tr>\n";
		}
	}
}

?>
  </table>
</fieldset>
