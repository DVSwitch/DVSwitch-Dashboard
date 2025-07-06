<?php
declare(strict_types=1);

if (!isset($mmdvmconfigs)) $mmdvmconfigs = [];
if (!isset($net1)) $net1 = false;
if (!isset($net2)) $net2 = false;
if (!isset($net3)) $net3 = false;
if (!isset($net4)) $net4 = false;
if (!isset($net5)) $net5 = false;
if (!isset($abinfo)) $abinfo = null;
if (!isset($lastHeard)) $lastHeard = [];
if (!isset($reverseLogLinesYSFGateway)) $reverseLogLinesYSFGateway = [];
if (!isset($logLinesP25Gateway)) $logLinesP25Gateway = [];
if (!isset($logLinesNXDNGateway)) $logLinesNXDNGateway = [];
if (!isset($configdmrgateway)) $configdmrgateway = [];

include_once dirname(dirname(__FILE__)).'/include/tools.php';
include_once dirname(dirname(__FILE__)).'/include/config.php';
include_once dirname(dirname(__FILE__)).'/include/functions.php';

// Initialize abinfo to null. It will be populated only if the JSON file exists and is valid.
// This prevents undefined variable errors in later sections (like TRX Info) that use it.
$abinfo = null;
?>
<span style="font-weight: bold;font-size:14px;">Status</span>
<fieldset style="background-color:#e8e8e8e8;width:160px;margin-top:6px;;margin-bottom:0px;margin-left:0px;margin-right:3px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
<?php
$testMMDVModeDMR = getConfigItem("DMR", "Enable", $mmdvmconfigs);
if ( $testMMDVModeDMR == 1 ) { //Hide the DMR information when DMR mode not enabled.

$dmrMasterFile = fopen("/var/lib/mmdvm/DMR_Hosts.txt", "r");
$dmrMasterHost = getConfigItem("DMR Network", "Address", $mmdvmconfigs);
$dmrMasterPort = getConfigItem("DMR Network", "Port", $mmdvmconfigs);

// Initialize variables to prevent undefined variable errors if the config file can't be parsed.
$configdmrgateway = [];
$xlxMasterHost1 = "";
$dmrMasterHost1 = "";
$dmrMasterHost2 = "";
$dmrMasterHost3 = "";
$dmrMasterHost4 = "";
$dmrMasterHost5 = "";

if ($dmrMasterHost == '127.0.0.1' AND file_exists('/opt/DMRGateway/DMRGateway.ini')) {
    $dmrGatewayConfigFile = '/opt/DMRGateway/DMRGateway.ini';
    // parse_ini_file returns false on failure, so we check the result.
    $configdmrgateway = parse_ini_file($dmrGatewayConfigFile, true);
    if ($configdmrgateway !== false) { // Proceed only if the INI file was parsed successfully.
        // Use null coalescing operator (??) for safe access to array keys.
        $xlxMasterHost1 = $configdmrgateway['XLX Network 1']['Address'] ?? "";
        $dmrMasterHost1 = $configdmrgateway['DMR Network 1']['Address'] ?? "";
        $dmrMasterHost2 = $configdmrgateway['DMR Network 2']['Address'] ?? "";
        $dmrMasterHost3 = str_replace('_', ' ', $configdmrgateway['DMR Network 3']['Name'] ?? "");
        $dmrMasterHost4 = str_replace('_', ' ', $configdmrgateway['DMR Network 4']['Name'] ?? "");
        $dmrMasterHost5 = str_replace('_', ' ', $configdmrgateway['DMR Network 5']['Name'] ?? "");

        while (!feof($dmrMasterFile)) {
            $dmrMasterLine = fgets($dmrMasterFile);
            $dmrMasterHostF = preg_split('/\s+/', $dmrMasterLine);
            		if ((count($dmrMasterHostF) >= 2) && isset($dmrMasterHostF[0]) && (strpos($dmrMasterHostF[0], '#') === FALSE) && ($dmrMasterHostF[0] != '')) {
			if ((strpos($dmrMasterHostF[0], 'XLX_') === 0) && isset($dmrMasterHostF[2]) && ($xlxMasterHost1 == $dmrMasterHostF[2])) { $xlxMasterHost1 = str_replace('_', ' ', $dmrMasterHostF[0]); }
			if ((strpos($dmrMasterHostF[0], 'BM_') === 0) && isset($dmrMasterHostF[2]) && ($dmrMasterHost1 == $dmrMasterHostF[2])) { $dmrMasterHost1 = str_replace('_', ' ', $dmrMasterHostF[0]); }
			if ((strpos($dmrMasterHostF[0], 'DMR+_') === 0) && isset($dmrMasterHostF[2]) && ($dmrMasterHost2 == $dmrMasterHostF[2])) { $dmrMasterHost2 = str_replace('_', ' ', $dmrMasterHostF[0]); }
            }
        }
        if (strlen($xlxMasterHost1) > 19) { $xlxMasterHost1 = substr($xlxMasterHost1, 0, 17) . '..'; }
    }
} else {
    while (!feof($dmrMasterFile)) {
        $dmrMasterLine = fgets($dmrMasterFile);
        $dmrMasterHostF = preg_split('/\s+/', $dmrMasterLine);
        		if ((count($dmrMasterHostF) >= 4) && isset($dmrMasterHostF[0]) && (strpos($dmrMasterHostF[0], '#') === FALSE) && ($dmrMasterHostF[0] != '')) {
            if (($dmrMasterHost == $dmrMasterHostF[2]) && ($dmrMasterPort == $dmrMasterHostF[4])) { $dmrMasterHost = str_replace('_', ' ', $dmrMasterHostF[0]); }
        }
    }
}
fclose($dmrMasterFile);

$ip = Get_User_IP();
$net1= cidr_match($ip,"192.168.0.0/16");
$net2= cidr_match($ip,"172.16.0.0/12");
$net3= cidr_match($ip,"127.0.0.0/8");
$net4= cidr_match($ip,"10.0.0.0/8");
$net5= cidr_match($ip,REMOTENET);

if (file_exists('/tmp/ABInfo_'.ABINFO.'.json')) {
    $abinfo = getABInfo('/tmp/ABInfo_'.ABINFO.'.json');
}

// Only display the Analog Bridge Info table if $abinfo was successfully populated.
if ($abinfo && is_array($abinfo)) {
    echo "<table style=\"margin-top:4px;\">\n";
    echo "<tr><th colspan=\"2\">";
    if ($net1 == TRUE || $net2 == TRUE || $net3 == TRUE || $net4 == TRUE || $net5 == TRUE) {
        // Use isset() or null coalescing operator (??) for safer access to potentially missing keys.
        echo "<div class=\"tooltip\" style=\"font-size:12px;\">Analog Bridge Info<span class=\"tooltiptext\" style=\"font-size:11px;\">";
        echo "<br> decoderFallBack: ".($abinfo['use_fallback'] ?? 'N/A');
        echo "<br> useEmulator: ".($abinfo['use_emulator'] ?? 'N/A');
        echo "<br> Mute: ".($abinfo['mute'] ?? 'N/A');
        echo "<br> [TLV]";
        echo "<br>   address: ".($abinfo['tlv']['ip'] ?? 'N/A');
        echo "<br>   txPort: ".($abinfo['tlv']['tx_port'] ?? 'N/A');
        echo "<br>   rxPort: ".($abinfo['tlv']['rx_port'] ?? 'N/A');
        echo "<br>   ambeMode: ".($abinfo['tlv']['ambe_mode'] ?? 'N/A');
        echo "<br>   AMBE Size: ".($abinfo['tlv']['ambe_size'] ?? 'N/A');
        echo "<br> [Digital]<br/>";
        echo "   Callsign: ".($abinfo['digital']['call'] ?? 'N/A');
        echo "<br>   gatewayID: ".($abinfo['digital']['gw'] ?? 'N/A');
        echo "<br>   repeaterID: ".($abinfo['digital']['rpt'] ?? 'N/A');
        echo "<br>   txTG: ".($abinfo['digital']['tg'] ?? 'N/A');
        $last_tune_val = $abinfo['last_tune'] ?? '';
        if (strlen($last_tune_val) > 8) { $lasttune = "<br>    ".$last_tune_val; }
        else {$lasttune = $last_tune_val;}
        echo "<br>   Last tune: ".$lasttune;
        echo "<br>   txTS: ".($abinfo['digital']['ts'] ?? 'N/A');
        echo "<br>   colorCode: ".($abinfo['digital']['cc'] ?? 'N/A');
        echo "<br> [USRP]<br/>";
        echo "   address: ".($abinfo['usrp']['ip'] ?? 'N/A');
        echo "<br>   txPort: ".($abinfo['usrp']['tx_port'] ?? 'N/A');
        echo "<br>   rxPort: ".($abinfo['usrp']['rx_port'] ?? 'N/A');
        echo "<br>   Ping: ".($abinfo['usrp']['ping'] ?? 'N/A');
        echo "<br>   [To PCM]";;
        echo "<br>    usrpA: ".($abinfo['usrp']['to_pcm']['shape'] ?? 'N/A')." ";
        echo "<br>    Gain: ".($abinfo['usrp']['to_pcm']['gain'] ?? 'N/A');
        echo "<br>   [To AMBE]";;
        echo "<br>    tlvA: ".($abinfo['usrp']['to_ambe']['shape'] ?? 'N/A')." ";
        echo "<br>    Gain: ".($abinfo['usrp']['to_ambe']['gain'] ?? 'N/A');
        echo "<br> [DV3000]<br/>";
        echo "   address: ".($abinfo['dv3000']['ip'] ?? 'N/A');
        echo "<br>   rxPort: ".($abinfo['dv3000']['port'] ?? 'N/A');
        echo "<br>   Serial: ".($abinfo['dv3000']['use_serial'] ?? 'N/A');
        echo "<br> [Analog Bridge]";
        echo "<br>   Version: ".($abinfo['ab']['version'] ?? 'N/A');
        echo "<br/></span></div></th></tr>\n";
        $call_val = $abinfo['digital']['call'] ?? '';
        if (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $call_val)) { $call="";
        } else { $call = $call_val; }
        echo "<tr><th width=50%>Callsign</th><td style=\"background: #f9f9f9f9;color:#b44010;font-weight: bold;\">".$call."</td></tr>\n";
        echo "<tr><th width=50%>GW ID</th><td style=\"background: #f9f9f9;\">".($abinfo['digital']['gw'] ?? 'N/A')."</td></tr>\n";
        echo "<tr><th width=50%>RPT ID</th><td style=\"background: #f9f9f9;\">".($abinfo['digital']['rpt'] ?? 'N/A')."</td></tr>\n";
        echo "<tr><th width=50%>Mode</th><td style=\"background: #f9f9f9;font-weight: bold;color:#b44010;\">".($abinfo['tlv']['ambe_mode'] ?? 'N/A')."</td></tr>\n";
        echo "<tr><th width=50%>Tx TG</th><td style=\"background: #f9f9f9;font-weight: bold;color:#ef7215;\">".($abinfo['digital']['tg'] ?? 'N/A')."</td></tr>\n";
        echo "<tr><th width=50%>AB ver</th><td style=\"background: #f9f9f9;\">".($abinfo['ab']['version'] ?? 'N/A')."</td></tr>\n";
        echo "</table>\n";
    } else {
        echo "<span style=\"font-size:13px;\">Analog Bridge Info</span></th></tr>\n";
        $call_val = $abinfo['digital']['call'] ?? '';
        if (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $call_val)) { $call="";
        } else { $call = $call_val; }
        echo "<tr><th width=50%>Callsign</th><td style=\"background: #f9f9f9f9;color:#b44010;font-weight: bold;\">".$call."</td></tr>\n";
        echo "<tr><th width=50%>Mode</th><td style=\"background: #f9f9f9;font-weight: bold;color:#b44010;\">".($abinfo['tlv']['ambe_mode'] ?? 'N/A')."</td></tr>\n";
        echo "<tr><th width=50%>Tx TG</th><td style=\"background: #f9f9f9;font-weight: bold;color:#ef7215;\">".($abinfo['digital']['tg'] ?? 'N/A')."</td></tr>\n";
        echo "<tr><th width=50%>AB ver</th><td style=\"background: #f9f9f9;\">".($abinfo['ab']['version'] ?? 'N/A')."</td></tr>\n";
        echo "</table>\n";
    }
}

// N4IRS Something is causing Tx TG to be 0 which the above does not like.

// TRX Status code
// Get the ambe_mode safely to avoid errors in the logic below.
$ambe_mode = $abinfo['tlv']['ambe_mode'] ?? '';

echo '<br><table><tr><th colspan="2">TRX Info</th></tr><tr>';
if (isProcessRunning("MMDVM_Bridge")) {
if (isset($lastHeard[0])) {
    $listElem = $lastHeard[0];
    if ( $listElem[2] && $listElem[6] == null && $listElem[5] == 'LNet') {
            echo "<td style=\"background:#f33;\">TX $listElem[1]</td>";
            }
            else {
            if (getActualMode($lastHeard, $mmdvmconfigs) === 'idle') {
                    echo "<td style=\"background:#0b0; color:#030;\">Listening</td>";
                    }
            elseif (getActualMode($lastHeard, $mmdvmconfigs) === NULL) {
                    if (isProcessRunning("MMDVM_Bridge")) { echo "<td style=\"background:#0b0; color:#030;\">Listening</td>"; 
		} else { echo "<td style=\"background:#ffffed; color:#b0b0b0;font-weight: bold\">OFFLINE</td>"; }
                    }
            // Safely check $ambe_mode
            elseif ($listElem[2] && $listElem[6] == null && $ambe_mode == "DSTAR" && getActualMode($lastHeard, $mmdvmconfigs) === 'D-Star') {
                    echo "<td style=\"background:#4aa361;\">RX D-Star</td>";
                    }
            elseif (getActualMode($lastHeard, $mmdvmconfigs) === 'D-Star') {
                    echo "<td style=\"background:#ade;\">Listening D-Star</td>";
                    }
            // Safely check $ambe_mode
            elseif ($listElem[2] && $listElem[6] == null && $ambe_mode == "DMR" && getActualMode($lastHeard, $mmdvmconfigs) === 'DMR') {
                    echo "<td style=\"background:#4aa361;\">RX DMR</td>";
                    }
            elseif (getActualMode($lastHeard, $mmdvmconfigs) === 'DMR') {
                    echo "<td style=\"background:#f93;\">Listening DMR</td>";
                    }
            // Safely check $ambe_mode
            elseif ($listElem[2] && $listElem[6] == null && ($ambe_mode == "YSFN" || $ambe_mode == "YSFW") && getActualMode($lastHeard, $mmdvmconfigs) === 'YSF') {
                    echo "<td style=\"background:#4aa361;\">RX YSF</td>";
                    }
            elseif (getActualMode($lastHeard, $mmdvmconfigs) === 'YSF') {
                    echo "<td style=\"background:#ff9;\">Listening YSF</td>";
                    }
            // Safely check $ambe_mode
            elseif ($listElem[2] && $listElem[6] == null && $ambe_mode == "P25" && getActualMode($lastHeard, $mmdvmconfigs) === 'P25') {
    	        echo "<td style=\"background:#4aa361;\">RX P25</td>";
    	        }
    	elseif (getActualMode($lastHeard, $mmdvmconfigs) === 'P25') {
    	        echo "<td style=\"background:#f9f;\">Listening P25</td>";
    	        }
            // Safely check $ambe_mode
	elseif ($listElem[2] && $listElem[6] == null && $ambe_mode == "NXDN" && getActualMode($lastHeard, $mmdvmconfigs) === 'NXDN') {
    	        echo "<td style=\"background:#4aa361;\">RX NXDN</td>";
    	        }
    	elseif (getActualMode($lastHeard, $mmdvmconfigs) === 'NXDN') {
    	        echo "<td style=\"background:#c9f;\">Listening NXDN</td>";
    	        }
	elseif (getActualMode($lastHeard, $mmdvmconfigs) === 'POCSAG') {
    	        echo "<td style=\"background:#4aa361;\">POCSAG</td>";
    	        }
    	else {
    	        echo "<td>".getActualMode($lastHeard, $mmdvmconfigs)."</td>";
    	        }
	}
    }
 else { echo "<td></td>";}
} else { echo "<td style=\"background:#ffffed; color:#b0b0b0;font-weight: bold\">OFFLINE</td>"; }
echo "</tr></table>\n";
echo "<br />\n";
echo "<table>\n";;
echo "<tr><th colspan=\"2\">DMR Master</th></tr>\n";
if (getEnabled("DMR Network", $mmdvmconfigs) == 1) {
	if ($dmrMasterHost == '127.0.0.1' && isProcessRunning("DMRGateway")) {
	    // Use !empty for safe checking of potentially undefined keys.
	    if (!empty($configdmrgateway['XLX Network 1']['Enabled'])) {
		echo "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b5651d;font-weight: bold\">".$xlxMasterHost1."</span></td></tr>\n";
	    }
        if (empty($configdmrgateway['XLX Network 1']['Enabled']) && !empty($configdmrgateway['XLX Network']['Enabled'])) {
		    if (file_exists("/var/log/mmdvm/DMRGateway-".gmdate("Y-m-d").".log")) { $xlxMasterHost1_log = exec('grep -a \'XLX, Linking\|Unlinking\' /var/log/mmdvm/DMRGateway-'.gmdate("Y-m-d").'.log | tail -1 | awk \'{print $5 " " $8 " " $9}\'');
		    } else { $xlxMasterHost1_log = exec('grep -a \'XLX, Linking\|Unlinking\' /var/log/mmdvm/DMRGateway-'.gmdate("Y-m-d", time() - 86340).'.log | tail -1 | awk \'{print $5 " " $8 " " $9}\''); }
		    if ( strpos($xlxMasterHost1_log, 'Linking') !== false ) { $xlxMasterHost1_log = str_replace('Linking ', '', $xlxMasterHost1_log); }
		    else if ( strpos($xlxMasterHost1_log, 'Unlinking') !== false ) { $xlxMasterHost1_log = "XLX Not Linked"; }
		    echo "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b5651d;font-weight: bold\">".($xlxMasterHost1_log ?: $xlxMasterHost1)."</span></td></tr>\n";
        }
	    if (!empty($configdmrgateway['DMR Network 1']['Enabled'])) {
		$dmrMasterhost1 = str_replace(' ', '_', $dmrMasterHost1);
                echo getDMRGstat($dmrMasterhost1);
	    }
	    if (!empty($configdmrgateway['DMR Network 2']['Enabled'])) {
		$dmrMasterhost2 = str_replace(' ', '_', $dmrMasterHost2);
                echo getDMRGstat($dmrMasterhost2);
	    }
	    if (!empty($configdmrgateway['DMR Network 3']['Enabled'])) {
		$dmrMasterhost3 = str_replace(' ', '_', $dmrMasterHost3);
                echo getDMRGstat($dmrMasterhost3);
	    }
	    if (isset($configdmrgateway['DMR Network 4']['Enabled'])) {
		if ($configdmrgateway['DMR Network 4']['Enabled'] == 1) {
		$dmrMasterhost4 = str_replace(' ', '_', $dmrMasterHost4);
                echo getDMRGstat($dmrMasterhost4);
	    }
	    if (isset($configdmrgateway['DMR Network 5']['Enabled'])) {
		if ($configdmrgateway['DMR Network 5']['Enabled'] == 1) {
		$dmrMasterhost5 = str_replace(' ', '_', $dmrMasterHost5);
                echo getDMRGstat($dmrMasterhost5);
		}
	      }
	    }
	}
	elseif (isProcessRunning("MMDVM_Bridge")) {
		if (file_exists("/var/log/mmdvm/MMDVM_Bridge-".gmdate("Y-m-d").".log")) { $dmrstat = exec('grep -a \'DMR, Logged\|DMR, Closing DMR\|DMR, Opening DMR\|DMR, Connection\' /var/log/mmdvm/MMDVM_Bridge-'.gmdate("Y-m-d").'.log | tail -1 | awk \'{print $5 " " $10}\'');
		} else {$dmrstat = exec('grep -a \'DMR, Logged\|DMR, Closing DMR\|DMR, Opening DMR\|DMR, Connection\' /var/log/mmdvm/MMDVM_Bridge-'.gmdate("Y-m-d", time() - 86340).'.log | tail -1 | awk \'{print $5 " " $10}\''); }
                 if (($dmrstat !="") && (strpos($dmrstat, ':') !== false) ) {
		    $dmrMasterHost = trim(substr($dmrstat,7,strpos($dmrstat,':')-strlen(trim(substr($dmrstat, strpos($dmrstat,':')-1)))));
		  $dmrMasterPort=trim(substr($dmrstat,strpos($dmrstat,":")+1));
		    $dmrMasterFile = fopen("/var/lib/mmdvm/DMR_Hosts.txt", "r");
		    while (!feof($dmrMasterFile)) {
			$dmrMasterLine = fgets($dmrMasterFile);
            		$dmrMasterHostF = preg_split('/\s+/', $dmrMasterLine);
			if ((count($dmrMasterHostF) >= 4) && isset($dmrMasterHostF[0]) && (strpos($dmrMasterHostF[0], '#') === FALSE) && ($dmrMasterHostF[0] != '')) {
			if (isset($dmrMasterHostF[2]) && isset($dmrMasterHostF[4]) && ($dmrMasterHost == $dmrMasterHostF[2]) && ($dmrMasterPort == $dmrMasterHostF[4])) { $dmrMasterHost = str_replace('_', ' ', $dmrMasterHostF[0]); }
				}
			    }
			fclose($dmrMasterFile);
		}
		$dmrMasterHost = str_replace('_', ' ', $dmrMasterHost);
    		if (strlen($dmrMasterHost) > 19) { $dmrMasterHost = substr($dmrMasterHost, 0, 17) . '..'; }
		if ( strpos($dmrstat, 'Logged') !== false ) {
                        echo "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b5651d;font-weight: bold\">".$dmrMasterHost."</span></td></tr>\n";}
		else if (strpos($dmrstat, 'Opening') !== false || strpos($dmrstat, 'Closing') !== false || strpos($dmrstat, 'Connection') !== false) {
			echo "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b0b0b0;font-weight: bold\">Not Connected</span></td></tr>\n"; }
		}
    else {
	    echo "<tr><td colspan=\"2\" style=\"background:#ffffed; color:#b0b0b0;font-weight: bold\"><b>No DMR Network</b></td></tr>\n";
        }
    }
    else {
	    echo "<tr><td colspan=\"2\" style=\"background:#ffffed; color:#b0b0b0;font-weight: bold\"><b>No DMR Network</b></td></tr>\n";
    }
echo "</table>\n";
}
$testMMDVModeYSF = getConfigItem("System Fusion Network", "Enable", $mmdvmconfigs);
if ( $testMMDVModeYSF == 1 ) { //Hide the YSF information when System Fusion Network mode not enabled.
        $ysfLinkedTo = getActualLink($reverseLogLinesYSFGateway, "YSF");
        if ($ysfLinkedTo == 'Not Linked' || $ysfLinkedTo == 'No YSF Network') {
                $ysfLinkedToTxt = '<span style="color:#b0b0b0;"><b>'.$ysfLinkedTo.'</b></span>';
        } else {
                $ysfHostFile = fopen("/var/lib/mmdvm/YSFHosts.txt", "r");
                $ysfLinkedToTxt = "null";
                while (!feof($ysfHostFile)) {
                        $ysfHostFileLine = fgets($ysfHostFile);
                        $ysfRoomTxtLine = preg_split('/;/', $ysfHostFileLine);
                        if (empty($ysfRoomTxtLine[0]) || empty($ysfRoomTxtLine[1])) continue;
                        if (($ysfRoomTxtLine[0] == $ysfLinkedTo) || ($ysfRoomTxtLine[1] == $ysfLinkedTo)) {
                                $ysfLinkedToTxt = $ysfRoomTxtLine[1];
                                break;
                        }
                }
                if ($ysfLinkedToTxt != "null") { 
	    if (strlen($ysfLinkedToTxt) > 20) { $ysfLinkedToTxt = substr($ysfLinkedToTxt, 0, 18) . '..'; }
	    $ysfLinkedToTxt = "Room<br/><span style=\"color:#b5651d;font-weight: bold;\">".$ysfLinkedToTxt."</span>"; 
	} else { 
	    if (strlen($ysfLinkedTo) > 20) { $ysfLinkedToTxt = substr($ysfLinkedTo, 0, 18) . '..'; }
	    $ysfLinkedToTxt = "Linked to<br/><span style=\"color:#b5651d;font-weight: bold\">".$ysfLinkedTo."</span>"; 
	}
	    $ysfLinkedToTxt = str_replace('_', ' ', $ysfLinkedToTxt);
        }
        echo "<br />\n";
        echo "<table>\n";
        echo "<tr><th colspan=\"2\">YSF Net</th></tr>\n";
        echo "<tr><td colspan=\"2\" style=\"background: #ffffed;\">".$ysfLinkedToTxt."</td></tr>\n";
        echo "</table>\n";
}
$testMMDVModeP25 = getConfigItem("P25 Network", "Enable", $mmdvmconfigs);
if ( $testMMDVModeP25 == 1 ) { //Hide the P25 information when P25 Network mode not enabled.
    echo "<br />\n";
    echo "<table>\n";
    echo "<tr><th colspan=\"2\">P25 Net</th></tr>\n";
    echo "<tr><td colspan=\"2\" style=\"background: #ffffed;\">".getActualLink($logLinesP25Gateway, "P25")."</td></tr>\n";
    echo "</table>\n";
}

$testMMDVModeNXDN = getConfigItem("NXDN Network", "Enable", $mmdvmconfigs);
if ( $testMMDVModeNXDN == 1 ) { //Hide the NXDN information when NXDN Network mode not enabled.
    echo "<br />\n";
    echo "<table>\n";
    echo "<tr><th colspan=\"2\">NXDN Net</th></tr>\n";
    if (file_exists('/opt/NXDNGateway/NXDNGateway.ini')) {
	echo "<tr><td colspan=\"2\" style=\"background: #ffffed;\">".getActualLink($logLinesNXDNGateway, "NXDN")."</td></tr>\n";
    } else {
	echo "<tr><td colspan=\"2\" style=\"background: #ffffff;\">Linked to <span style=\"color:#b5651d;font-weight: bold;\">TG65000</span></td></tr>\n";
    }
    echo "</table>\n";
}
$testMMDVModeDSTAR = getConfigItem("D-Star Network", "Enable", $mmdvmconfigs);
if ( $testMMDVModeDSTAR == 1 ) { //Hide the D-Star Reflector information when D-Star Network not enabled.
//Load the ircDDBGateway config file
$configs = array();
if ($configfile = fopen('/etc/ircddbgateway','r')) {
        while ($line = fgets($configfile)) {
                list($key,$value) = preg_split('/=/',$line);
                $value = trim(str_replace('"','',$value));
                if ($key != 'ircddbPassword' && strlen($value) > 0)
                $configs[$key] = $value;
        }
}
    echo "<br />\n";
    echo "<table>\n";
    echo "<tr><th colspan=\"2\">D-Star Net</th></tr>\n";
    // Check if ircddbHostname is set before using it to prevent errors.
    if (isProcessRunning("ircddbgatewayd")) {
        $hostname = isset($configs['ircddbHostname']) ? substr($configs['ircddbHostname'], 0, 16) : 'N/A';
        echo "<tr><th width=\"20%\">IRC</th><td style=\"background: #ffffff;color:brown;\">".$hostname."</td></tr>\n";
    }
    echo "<tr><td colspan=\"2\" style=\"background: #ffffed;\">".getActualLink($reverseLogLinesMMDVM, "D-Star")."</td></tr>\n";
    echo "</table>\n";
}

?>
</fieldset>
