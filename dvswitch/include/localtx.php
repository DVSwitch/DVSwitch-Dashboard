<?php
include_once dirname(dirname(__FILE__)).'/include/strftime.php';
include_once dirname(dirname(__FILE__)).'/include/config.php';          
include_once dirname(dirname(__FILE__)).'/include/tools.php';       
include_once dirname(dirname(__FILE__)).'/include/functions.php';    

// Populate lastHeard data for localtx.php when called independently
if (!isset($lastHeard) || empty($lastHeard)) {
    $logLinesMMDVM = getMMDVMLog();
    $reverseLogLinesMMDVM = $logLinesMMDVM;
    array_multisort($reverseLogLinesMMDVM, SORT_DESC);
    $lastHeard = getLastHeard($reverseLogLinesMMDVM);
}

$localTXList = $lastHeard;
?>
<div>
<span class="section-header" style="font-weight: bold;font-size:14px;">Local Activity</span>
<fieldset style="box-shadow:0 0 10px #999;background-color:#e8e8e8e8; width:640px;margin-top:8px;margin-left:0px;margin-right:0px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
  <table style="margin-top:2px;">
    <tr>
      <th>Time (<?php echo date('T')?>)</th>
      <th>Mode</th>
      <th>Callsign</th>
      <th>Target</th>
      <th>Src</th>
      <th>Dur(s)</th>
    </tr>
<?php
$counter = 0;
$i = 0;
for ($i = 0; $i < count($localTXList); $i++) {
		$listElem = $localTXList[$i];
		if ($listElem[5] == "LNet" && ($listElem[1] == "D-Star" || startsWith($listElem[1], "DMR") || $listElem[1] == "YSF" || $listElem[1]== "P25" || $listElem[1]== "NXDN")) {
			if ($counter <= 19) { //last 20 calls
				$utc_time = $listElem[0];
                        	$utc_tz =  new DateTimeZone('UTC');
                        	$local_tz = new DateTimeZone(date_default_timezone_get ());
                        	$dt = new DateTime($utc_time, $utc_tz);
                        	$dt->setTimeZone($local_tz);
                                $local_time = xstrftime('%H:%M:%S %b %d', $dt->getTimestamp());

			echo"<tr>";
			echo "<td align=\"left\" class=\"lh-time\">".htmlspecialchars($local_time, ENT_QUOTES, 'UTF-8')."</td>";
			echo "<td align=\"left\" style=\"color:green; font-weight:bold;\">".htmlspecialchars($listElem[1], ENT_QUOTES, 'UTF-8')."</td>";
			    if (is_numeric($listElem[2]) || strpos($listElem[2], "openSPOT") !== FALSE) {
				echo "<td align=\"left\" style=\"color:#464646;\"><b>&nbsp;".htmlspecialchars($listElem[2], ENT_QUOTES, 'UTF-8')."</b></td>";
			    } elseif (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $listElem[2])) {
				echo "<td align=\"left\" style=\"color:#464646;\"><b>&nbsp;".htmlspecialchars($listElem[2], ENT_QUOTES, 'UTF-8')."</b></td>";
	    			} else {
			if (strpos($listElem[2],"-") > 0) { $listElem[2] = substr($listElem[2], 0, strpos($listElem[2],"-")); }
			if ($listElem[3] && $listElem[3] != '    ' ) {
			    echo "<td align=\"left\">&nbsp;<a href=\"http://www.qrz.com/db/".htmlspecialchars($listElem[2], ENT_QUOTES, 'UTF-8')."\" target=\"_blank\"><b>".htmlspecialchars($listElem[2], ENT_QUOTES, 'UTF-8')."</b></a><b>/".htmlspecialchars($listElem[3], ENT_QUOTES, 'UTF-8')."</b></td>";
			} else {
			    echo "<td align=\"left\">&nbsp;<a href=\"http://www.qrz.com/db/".htmlspecialchars($listElem[2], ENT_QUOTES, 'UTF-8')."\" target=\"_blank\"><b>".htmlspecialchars($listElem[2], ENT_QUOTES, 'UTF-8')."</b></a></td>";
			}
		    }
			if (strlen($listElem[4]) == 1) { $listElem[4] = str_pad($listElem[4], 8, " ", STR_PAD_LEFT); }
			echo "<td align=\"left\"><span style=\"color:#b5651d;font-weight:bold;\">".htmlspecialchars($listElem[4], ENT_QUOTES, 'UTF-8')."</span></td>";
			if ($listElem[5] == "LNet"){
				echo "<td style=\"background:#1d1;\">LNet</td>";
			} else {
				echo "<td>".htmlspecialchars($listElem[5], ENT_QUOTES, 'UTF-8')."</td>";
			}
			if ($listElem[6] == null) {
				echo "<td colspan=\"1\" style=\"background:#f33;\" class=\"lh-duration\">TX</td>";
			} else if ($listElem[6] == "DMR Data") {
				echo "<td colspan=\"1\" style=\"background:#1d1;\">DMR Data</td>";
			}  else {
		echo"<td class=\"lh-duration\">".htmlspecialchars($listElem[6], ENT_QUOTES, 'UTF-8')."</td>"; //duration
		}
			echo"</tr>\n";
			$counter++; }
		}
	}

?>
  </table>
</fieldset>
</div>
<br>
