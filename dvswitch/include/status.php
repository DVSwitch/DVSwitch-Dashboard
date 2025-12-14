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

$ip = Get_User_IP();
$net1= cidr_match($ip,"192.168.0.0/16");
$net2= cidr_match($ip,"172.16.0.0/12");
$net3= cidr_match($ip,"127.0.0.0/8");
$net4= cidr_match($ip,"10.0.0.0/8");
$net5= cidr_match($ip,$config['REMOTENET'] ?? '');

// Load ABInfo - match original structure exactly
$abinfoId = $config['ABINFO'] ?? '';
$abinfo = null;
if (!empty($abinfoId) && is_string($abinfoId)) {
    $abinfoPath = '/tmp/ABInfo_' . $abinfoId . '.json';
    if (file_exists($abinfoPath)) {
        $realPath = realpath($abinfoPath);
        if ($realPath !== false && strpos($realPath, '/tmp/') === 0 && is_file($realPath)) {
            $abinfo = getABInfo($realPath);
            if ($abinfo === null || !is_array($abinfo)) {
                $json = @file_get_contents($realPath);
                if ($json !== false) {
                    $json_data = json_decode($json, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                        $abinfo = $json_data;
                    }
                }
            }
        }
    }
}

// Display ABInfo table FIRST, BEFORE Status header (wrapped in fieldset to match Status styling)
if ($abinfo && is_array($abinfo)) {
    echo "<fieldset style=\"background-color:#e8e8e8e8;width:160px;margin-top:6px;margin-bottom:6px;margin-left:0px;margin-right:3px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;\">\n";
    echo "<table style=\"margin-top:4px;\">\n";
    echo "<tr><th colspan=\"2\">";
    if ($net1 == TRUE || $net2 == TRUE || $net3 == TRUE || $net4 == TRUE || $net5 == TRUE) {
        // Use isset() or null coalescing operator (??) for safer access to potentially missing keys.
        echo "<div class=\"tooltip\" style=\"font-size:12px;\">Analog Bridge Info<span class=\"tooltiptext\" style=\"font-size:11px;\">";
        echo "<br>&nbsp;decoderFallBack: ".htmlspecialchars($abinfo['use_fallback'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;useEmulator: ".htmlspecialchars($abinfo['use_emulator'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;Mute: ".htmlspecialchars($abinfo['mute'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;[TLV]";
        echo "<br>&nbsp;&nbsp;&nbsp;address: ".htmlspecialchars($abinfo['tlv']['ip'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;txPort: ".htmlspecialchars($abinfo['tlv']['tx_port'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;rxPort: ".htmlspecialchars($abinfo['tlv']['rx_port'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;ambeMode: ".htmlspecialchars($abinfo['tlv']['ambe_mode'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;AMBE Size: ".htmlspecialchars($abinfo['tlv']['ambe_size'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;[Digital]<br/>";
        echo "&nbsp;&nbsp;&nbsp;Callsign: ".htmlspecialchars($abinfo['digital']['call'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;gatewayID: ".htmlspecialchars($abinfo['digital']['gw'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;repeaterID: ".htmlspecialchars($abinfo['digital']['rpt'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;txTG: ".htmlspecialchars($abinfo['digital']['tg'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $last_tune_val = $abinfo['last_tune'] ?? '';
        if (strlen($last_tune_val) > 8) { 
            $lasttune = "<br>&nbsp;&nbsp;&nbsp;&nbsp;".htmlspecialchars($last_tune_val, ENT_QUOTES, 'UTF-8'); 
        } else { 
            $lasttune = htmlspecialchars($last_tune_val, ENT_QUOTES, 'UTF-8');
        }
        echo "<br>&nbsp;&nbsp;&nbsp;Last tune: ".$lasttune;
        echo "<br>&nbsp;&nbsp;&nbsp;txTS: ".htmlspecialchars($abinfo['digital']['ts'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;colorCode: ".htmlspecialchars($abinfo['digital']['cc'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;[USRP]<br/>";
        echo "&nbsp;&nbsp;&nbsp;address: ".htmlspecialchars($abinfo['usrp']['ip'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;txPort: ".htmlspecialchars($abinfo['usrp']['tx_port'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;rxPort: ".htmlspecialchars($abinfo['usrp']['rx_port'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;Ping: ".htmlspecialchars($abinfo['usrp']['ping'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;[To PCM]";
        echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;usrpA: ".htmlspecialchars($abinfo['usrp']['to_pcm']['shape'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."&nbsp;";
        echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;Gain: ".htmlspecialchars($abinfo['usrp']['to_pcm']['gain'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;[To AMBE]";
        echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;tlvA: ".htmlspecialchars($abinfo['usrp']['to_ambe']['shape'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."&nbsp;";
        echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;Gain: ".htmlspecialchars($abinfo['usrp']['to_ambe']['gain'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;[DV3000]<br/>";
        echo "&nbsp;&nbsp;&nbsp;address: ".htmlspecialchars($abinfo['dv3000']['ip'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;rxPort: ".htmlspecialchars($abinfo['dv3000']['port'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;&nbsp;&nbsp;Serial: ".htmlspecialchars($abinfo['dv3000']['use_serial'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br>&nbsp;[Analog Bridge]";
        echo "<br>&nbsp;&nbsp;&nbsp;Version: ".htmlspecialchars($abinfo['ab']['version'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        echo "<br/></span></div></th></tr>\n";
        $call_val = $abinfo['digital']['call'] ?? '';
        if (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $call_val)) { $call="";
        } else { $call = $call_val; }
        echo "<tr><th width=50%>Callsign</th><td style=\"background: #f9f9f9f9;color:#b44010;font-weight: bold;\">".htmlspecialchars($call, ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "<tr><th width=50%>GW ID</th><td style=\"background: #f9f9f9;\">".htmlspecialchars($abinfo['digital']['gw'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "<tr><th width=50%>RPT ID</th><td style=\"background: #f9f9f9;\">".htmlspecialchars($abinfo['digital']['rpt'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "<tr><th width=50%>Mode</th><td style=\"background: #f9f9f9;font-weight: bold;color:#b44010;\">".htmlspecialchars($abinfo['tlv']['ambe_mode'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "<tr><th width=50%>Tx TG</th><td style=\"background: #f9f9f9;font-weight: bold;color:#ef7215;\">".htmlspecialchars($abinfo['digital']['tg'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "<tr><th width=50%>AB ver</th><td style=\"background: #f9f9f9;\">".htmlspecialchars($abinfo['ab']['version'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "</table>\n";
    } else {
        echo "<tr><th colspan=\"2\"><span style=\"font-size:13px;\">Analog Bridge Info</span></th></tr>\n";
        $call_val = $abinfo['digital']['call'] ?? '';
        if (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $call_val)) { $call="";
        } else { $call = $call_val; }
        echo "<tr><th width=50%>Callsign</th><td style=\"background: #f9f9f9f9;color:#b44010;font-weight: bold;\">".htmlspecialchars($call, ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "<tr><th width=50%>Mode</th><td style=\"background: #f9f9f9;font-weight: bold;color:#b44010;\">".htmlspecialchars($abinfo['tlv']['ambe_mode'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "<tr><th width=50%>Tx TG</th><td style=\"background: #f9f9f9;font-weight: bold;color:#ef7215;\">".htmlspecialchars($abinfo['digital']['tg'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "<tr><th width=50%>AB ver</th><td style=\"background: #f9f9f9;\">".htmlspecialchars($abinfo['ab']['version'] ?? 'N/A', ENT_QUOTES, 'UTF-8')."</td></tr>\n";
        echo "</table>\n";
    }
    echo "</fieldset>\n";
}

?>
<span class="section-header" style="font-weight: bold;font-size:14px;">Status</span>
<fieldset style="background-color:#e8e8e8e8;width:160px;margin-top:6px;;margin-bottom:0px;margin-left:0px;margin-right:3px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
<?php
$testMMDVModeDMR = getConfigItem("DMR", "Enable", $mmdvmconfigs);
if ( $testMMDVModeDMR == 1 ) { //Hide the DMR information when DMR mode not enabled.

// Validate DMR_Hosts.txt path
$dmrHostsPath = "/var/lib/mmdvm/DMR_Hosts.txt";
$validatedDmrHostsPath = validateFilePath($dmrHostsPath, ['/var/lib']);
$dmrMasterFile = null;
if ($validatedDmrHostsPath !== false && is_file($validatedDmrHostsPath)) {
    $dmrMasterFile = fopen($validatedDmrHostsPath, "r");
    if ($dmrMasterFile === false) {
        $dmrMasterFile = null;
    }
}
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

        if ($dmrMasterFile !== null) {
            while (!feof($dmrMasterFile)) {
                $dmrMasterLine = fgets($dmrMasterFile);
                if ($dmrMasterLine !== false) {
                    $dmrMasterHostF = preg_split('/\s+/', $dmrMasterLine);
                    if ((count($dmrMasterHostF) >= 2) && isset($dmrMasterHostF[0]) && (strpos($dmrMasterHostF[0], '#') === FALSE) && ($dmrMasterHostF[0] != '')) {
                        if ((strpos($dmrMasterHostF[0], 'XLX_') === 0) && isset($dmrMasterHostF[2]) && ($xlxMasterHost1 == $dmrMasterHostF[2])) { $xlxMasterHost1 = str_replace('_', ' ', $dmrMasterHostF[0]); }
                        if ((strpos($dmrMasterHostF[0], 'BM_') === 0) && isset($dmrMasterHostF[2]) && ($dmrMasterHost1 == $dmrMasterHostF[2])) { $dmrMasterHost1 = str_replace('_', ' ', $dmrMasterHostF[0]); }
                        if ((strpos($dmrMasterHostF[0], 'DMR+_') === 0) && isset($dmrMasterHostF[2]) && ($dmrMasterHost2 == $dmrMasterHostF[2])) { $dmrMasterHost2 = str_replace('_', ' ', $dmrMasterHostF[0]); }
                    }
                }
            }
            if (strlen($xlxMasterHost1) > 19) { /* $xlxMasterHost1 = substr($xlxMasterHost1, 0, 17) . '..'; */ }
        }
    }
} else {
    if ($dmrMasterFile !== null) {
        while (!feof($dmrMasterFile)) {
            $dmrMasterLine = fgets($dmrMasterFile);
            if ($dmrMasterLine !== false) {
                $dmrMasterHostF = preg_split('/\s+/', $dmrMasterLine);
                if ((count($dmrMasterHostF) >= 4) && isset($dmrMasterHostF[0]) && (strpos($dmrMasterHostF[0], '#') === FALSE) && ($dmrMasterHostF[0] != '')) {
                    if (($dmrMasterHost == $dmrMasterHostF[2]) && ($dmrMasterPort == $dmrMasterHostF[4])) { $dmrMasterHost = str_replace('_', ' ', $dmrMasterHostF[0]); }
                }
            }
        }
    }
}
if ($dmrMasterFile !== null) {
    fclose($dmrMasterFile);
}

// N4IRS Something is causing Tx TG to be 0 which the above does not like.

// TRX Status code
// Get the ambe_mode safely to avoid errors in the logic below.
$ambe_mode = '';
if (isset($abinfo) && is_array($abinfo) && isset($abinfo['tlv']) && is_array($abinfo['tlv'])) {
    $ambe_mode = $abinfo['tlv']['ambe_mode'] ?? '';
}

echo '<br><table><tr><th colspan="2">TRX Info</th></tr><tr>';
if (isProcessRunning("MMDVM_Bridge")) {
if (isset($lastHeard[0])) {
    $listElem = $lastHeard[0];
    if ( $listElem[2] && $listElem[6] == null && $listElem[5] == 'LNet') {
            echo "<td style=\"background:#f33;\">TX ".htmlspecialchars($listElem[1] ?? '', ENT_QUOTES, 'UTF-8')."</td>";
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
    	        $mode = getActualMode($lastHeard, $mmdvmconfigs);
    	        echo "<td>".htmlspecialchars($mode ?? '', ENT_QUOTES, 'UTF-8')."</td>";
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
		echo "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b5651d;font-weight: bold\">".htmlspecialchars($xlxMasterHost1, ENT_QUOTES, 'UTF-8')."</span></td></tr>\n";
	    }
        if (empty($configdmrgateway['XLX Network 1']['Enabled']) && !empty($configdmrgateway['XLX Network']['Enabled'])) {
            // PHP replacement for: grep -a 'XLX, Linking\|Unlinking' ... | tail -1 | awk '{print $5 " " $8 " " $9}'
            $logfile = "/var/log/mmdvm/DMRGateway-".gmdate("Y-m-d").".log";
            if (!file_exists($logfile)) {
                $logfile = "/var/log/mmdvm/DMRGateway-".gmdate("Y-m-d", time() - 86340).".log";
            }
            $xlxMasterHost1_log = "";
            if (file_exists($logfile)) {
                // Memory optimized: read file line by line from end
                $lines = array();
                $handle = fopen($logfile, 'r');
                if ($handle) {
                    // Read file into array, keeping only last 100 lines to save memory
                    while (($line = fgets($handle)) !== false) {
                        $lines[] = trim($line);
                        if (count($lines) > 100) {
                            array_shift($lines); // Remove oldest line
                        }
                    }
                    fclose($handle);
                    
                    // Search from end (most recent first)
                    for ($i = count($lines) - 1; $i >= 0; $i--) {
                        if (is_string($lines[$i]) && (strpos($lines[$i], 'XLX, Linking') !== false || strpos($lines[$i], 'Unlinking') !== false)) {
                            $fields = preg_split('/\s+/', $lines[$i]);
                            // Don't sanitize here - will be sanitized when displayed
                            $xlxMasterHost1_log = (isset($fields[4]) ? $fields[4] : '') . ' ' . (isset($fields[7]) ? $fields[7] : '') . ' ' . (isset($fields[8]) ? $fields[8] : '');
                            break;
                        }
                    }
                }
            }
            if (strpos($xlxMasterHost1_log, 'Linking') !== false) { $xlxMasterHost1_log = str_replace('Linking ', '', $xlxMasterHost1_log); }
            else if (strpos($xlxMasterHost1_log, 'Unlinking') !== false) { $xlxMasterHost1_log = "XLX Not Linked"; }
            echo "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b5651d;font-weight: bold\">".htmlspecialchars(($xlxMasterHost1_log ?: $xlxMasterHost1), ENT_QUOTES, 'UTF-8')."</span></td></tr>\n";
        }
	    if (!empty($configdmrgateway['DMR Network 1']['Enabled'])) {
		$dmrMasterhost1 = str_replace(' ', '_', $dmrMasterHost1);
                $dmrStat1 = getDMRGstat($dmrMasterhost1);
                if ($dmrStat1 !== null) {
                    // getDMRGstat returns HTML, but we need to sanitize the dynamic content within it
                    echo $dmrStat1;
                }
	    }
	    if (!empty($configdmrgateway['DMR Network 2']['Enabled'])) {
		$dmrMasterhost2 = str_replace(' ', '_', $dmrMasterHost2);
                $dmrStat2 = getDMRGstat($dmrMasterhost2);
                if ($dmrStat2 !== null) {
                    echo $dmrStat2;
                }
	    }
	    if (!empty($configdmrgateway['DMR Network 3']['Enabled'])) {
		$dmrMasterhost3 = str_replace(' ', '_', $dmrMasterHost3);
                $dmrStat3 = getDMRGstat($dmrMasterhost3);
                if ($dmrStat3 !== null) {
                    echo $dmrStat3;
                }
	    }
	    if (isset($configdmrgateway['DMR Network 4']['Enabled'])) {
		if ($configdmrgateway['DMR Network 4']['Enabled'] == 1) {
		$dmrMasterhost4 = str_replace(' ', '_', $dmrMasterHost4);
                $dmrStat4 = getDMRGstat($dmrMasterhost4);
                if ($dmrStat4 !== null) {
                    echo $dmrStat4;
                }
	    }
	    if (isset($configdmrgateway['DMR Network 5']['Enabled'])) {
		if ($configdmrgateway['DMR Network 5']['Enabled'] == 1) {
		$dmrMasterhost5 = str_replace(' ', '_', $dmrMasterHost5);
                $dmrStat5 = getDMRGstat($dmrMasterhost5);
                if ($dmrStat5 !== null) {
                    echo $dmrStat5;
                }
		}
	      }
	    }
	}
	elseif (isProcessRunning("MMDVM_Bridge")) {
		if (file_exists("/var/log/mmdvm/MMDVM_Bridge-".gmdate("Y-m-d").".log")) {
            // PHP replacement for: grep -a 'DMR, Logged\|DMR, Closing DMR\|DMR, Opening DMR\|DMR, Connection' ... | tail -1 | awk '{print $5 " " $10}'
            $logfile = "/var/log/mmdvm/MMDVM_Bridge-".gmdate("Y-m-d").".log";
        } else {
            $logfile = "/var/log/mmdvm/MMDVM_Bridge-".gmdate("Y-m-d", time() - 86340).".log";
        }
        $dmrstat = "";
        if (file_exists($logfile)) {
            // Memory optimized: read file line by line from end
            $lines = array();
            $handle = fopen($logfile, 'r');
            if ($handle) {
                // Read file into array, keeping only last 100 lines to save memory
                while (($line = fgets($handle)) !== false) {
                    $lines[] = trim($line);
                    if (count($lines) > 100) {
                        array_shift($lines); // Remove oldest line
                    }
                }
                fclose($handle);
                
                // Search from end (most recent first)
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    if (is_string($lines[$i]) && (strpos($lines[$i], 'DMR, Logged') !== false || strpos($lines[$i], 'DMR, Closing DMR') !== false || strpos($lines[$i], 'DMR, Opening DMR') !== false || strpos($lines[$i], 'DMR, Connection') !== false)) {
                        $fields = preg_split('/\s+/', $lines[$i]);
                        // Don't sanitize here - $dmrstat is used for parsing, final output is sanitized
                        $dmrstat = (isset($fields[4]) ? $fields[4] : '') . ' ' . (isset($fields[9]) ? $fields[9] : '');
                        break;
                    }
                }
            }
        }
                 if (($dmrstat !="") && (strpos($dmrstat, ':') !== false) ) {
		    $dmrMasterHost = trim(substr($dmrstat,7,strpos($dmrstat,':')-strlen(trim(substr($dmrstat, strpos($dmrstat,':')-1)))));
		  $dmrMasterPort=trim(substr($dmrstat,strpos($dmrstat,":")+1));
		    // Validate DMR_Hosts.txt path
		    $dmrHostsPath = "/var/lib/mmdvm/DMR_Hosts.txt";
		    $validatedDmrHostsPath = validateFilePath($dmrHostsPath, ['/var/lib']);
		    $dmrMasterFile = null;
		    if ($validatedDmrHostsPath !== false && is_file($validatedDmrHostsPath)) {
		        $dmrMasterFile = fopen($validatedDmrHostsPath, "r");
		        if ($dmrMasterFile === false) {
		            $dmrMasterFile = null;
		        }
		    }
		    if ($dmrMasterFile !== null) {
		                    while (!feof($dmrMasterFile)) {
                $dmrMasterLine = fgets($dmrMasterFile);
                if ($dmrMasterLine !== false && is_string($dmrMasterLine)) {
                    $dmrMasterHostF = preg_split('/\s+/', $dmrMasterLine);
                    if ((count($dmrMasterHostF) >= 4) && isset($dmrMasterHostF[0]) && (strpos($dmrMasterHostF[0], '#') === FALSE) && ($dmrMasterHostF[0] != '')) {
                        if (isset($dmrMasterHostF[2]) && isset($dmrMasterHostF[4]) && ($dmrMasterHost == $dmrMasterHostF[2]) && ($dmrMasterPort == $dmrMasterHostF[4])) { $dmrMasterHost = str_replace('_', ' ', $dmrMasterHostF[0]); }
                    }
                }
            }
			}
			if ($dmrMasterFile !== null) {
			    fclose($dmrMasterFile);
			}
		}
		$dmrMasterHost = str_replace('_', ' ', $dmrMasterHost);
    		if (strlen($dmrMasterHost) > 19) { /* $dmrMasterHost = substr($dmrMasterHost, 0, 17) . '..'; */ }
		if ( strpos($dmrstat, 'Logged') !== false ) {
                        echo "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b5651d;font-weight: bold\">".htmlspecialchars($dmrMasterHost, ENT_QUOTES, 'UTF-8')."</span></td></tr>\n";}
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
                $ysfLinkedToTxt = '<span style="color:#b0b0b0;"><b>'.htmlspecialchars($ysfLinkedTo, ENT_QUOTES, 'UTF-8').'</b></span>';
        } else {
                // Validate YSFHosts.txt path
                $ysfHostsPath = "/var/lib/mmdvm/YSFHosts.txt";
                $validatedYsfHostsPath = validateFilePath($ysfHostsPath, ['/var/lib']);
                $ysfHostFile = false;
                if ($validatedYsfHostsPath !== false && is_file($validatedYsfHostsPath)) {
                    $ysfHostFile = fopen($validatedYsfHostsPath, "r");
                }
                $ysfLinkedToTxt = "null";
                if ($ysfHostFile !== false) {
                    while (!feof($ysfHostFile)) {
                        $ysfHostFileLine = fgets($ysfHostFile);
                        if ($ysfHostFileLine !== false && is_string($ysfHostFileLine)) {
                            $ysfRoomTxtLine = preg_split('/;/', $ysfHostFileLine);
                            if (empty($ysfRoomTxtLine[0]) || empty($ysfRoomTxtLine[1])) continue;
                            if (($ysfRoomTxtLine[0] == $ysfLinkedTo) || ($ysfRoomTxtLine[1] == $ysfLinkedTo)) {
                                $ysfLinkedToTxt = $ysfRoomTxtLine[1];
                                break;
                            }
                        }
                    }
                    fclose($ysfHostFile);
                }
                if ($ysfLinkedToTxt != "null") { 
	    $displayTxt = $ysfLinkedToTxt;
	    if (strlen($displayTxt) > 20) { $displayTxt = substr($displayTxt, 0, 18) . '..'; }
	    $ysfLinkedToTxt = "Room<br/><span style=\"color:#b5651d;font-weight: bold;\">".htmlspecialchars($displayTxt, ENT_QUOTES, 'UTF-8')."</span>"; 
	} else { 
	    $displayTxt = $ysfLinkedTo;
	    if (strlen($displayTxt) > 20) { $displayTxt = substr($displayTxt, 0, 18) . '..'; }
	    $ysfLinkedToTxt = "Linked to<br/><span style=\"color:#b5651d;font-weight: bold;\">".htmlspecialchars($displayTxt, ENT_QUOTES, 'UTF-8')."</span>"; 
	}
	    $ysfLinkedToTxt = str_replace('_', ' ', $ysfLinkedToTxt);
        }
        echo "<br />\n";
        echo "<table>\n";
        echo "<tr><th colspan=\"2\">YSF Net</th></tr>\n";
        // $ysfLinkedToTxt already contains sanitized content within HTML tags, don't escape the HTML
        echo "<tr><td colspan=\"2\" style=\"background: #ffffed;\">".$ysfLinkedToTxt."</td></tr>\n";
        echo "</table>\n";
    }
    $testMMDVModeP25 = getConfigItem("P25 Network", "Enable", $mmdvmconfigs);
    if ( $testMMDVModeP25 == 1 ) { //Hide the P25 information when P25 Network mode not enabled.
    echo "<br />\n";
    echo "<table>\n";
    echo "<tr><th colspan=\"2\">P25 Net</th></tr>\n";
    // getActualLink() returns HTML, don't escape it
    echo "<tr><td colspan=\"2\" style=\"background: #ffffed;\">".getActualLink($logLinesP25Gateway, "P25")."</td></tr>\n";
    echo "</table>\n";
}

$testMMDVModeNXDN = getConfigItem("NXDN Network", "Enable", $mmdvmconfigs);
if ( $testMMDVModeNXDN == 1 ) { //Hide the NXDN information when NXDN Network mode not enabled.
    echo "<br />\n";
    echo "<table>\n";
    echo "<tr><th colspan=\"2\">NXDN Net</th></tr>\n";
    if (file_exists('/opt/NXDNGateway/NXDNGateway.ini')) {
	// getActualLink() returns HTML, don't escape it
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
                if ($line !== false && is_string($line)) {
                    list($key,$value) = preg_split('/=/',$line);
                    $value = trim(str_replace('"','',$value));
                    if ($key != 'ircddbPassword' && strlen($value) > 0)
                    $configs[$key] = $value;
                }
        }
}
    echo "<br />\n";
    echo "<table>\n";
    echo "<tr><th colspan=\"2\">D-Star Net</th></tr>\n";
    // Check if ircddbHostname is set before using it to prevent errors.
    if (isProcessRunning("ircddbgatewayd")) {
        $hostname = isset($configs['ircddbHostname']) ? $configs['ircddbHostname'] : 'N/A';
        echo "<tr><th width=\"20%\">IRC</th><td style=\"background: #ffffff;color:brown;\">".htmlspecialchars($hostname, ENT_QUOTES, 'UTF-8')."</td></tr>\n";
    }
    // getActualLink() returns HTML, don't escape it
    echo "<tr><td colspan=\"2\" style=\"background: #ffffed;\">".getActualLink($reverseLogLinesMMDVM, "D-Star")."</td></tr>\n";
    echo "</table>\n";
}

?>
</fieldset>
