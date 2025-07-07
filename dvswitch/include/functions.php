<?php
declare(strict_types=1);

function get_string_between(string $string, string $start, string $end): string {
    $string = " ".$string;
    $ini = strpos($string,$start);
    if ($ini == 0) {
	return "";
    }
    $ini += strlen($start);
    $len = strpos($string,$end,$ini) - $ini;
    return substr($string,$ini,$len);
}

function getMMDVMConfig(): array {
	// loads MMDVM_Bridge.ini into array for further use
	$conf = array();
	if ($configs = @fopen(MMDVMINIPATH."/".MMDVMINIFILENAME, 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getYSFGatewayConfig(): array {
	// loads YSFGateway.ini into array for further use
	$conf = array();
	if ($configs = @fopen(YSFGATEWAYINIPATH."/".YSFGATEWAYINIFILENAME, 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getP25GatewayConfig(): array {
	// loads P25Gateway.ini into array for further use
	$conf = array();
	if ($configs = @fopen(P25GATEWAYINIPATH."/".P25GATEWAYINIFILENAME, 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getNXDNGatewayConfig(): array {
	// loads NXDNGateway.ini into array for further use
	$conf = array();
	if ($configs = @fopen(NXDNGATEWAYINIPATH."/".NXDNGATEWAYINIFILENAME, 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getDAPNETGatewayConfig(): array {
	// loads /etc/dapnetgateway into array for further use
	$conf = array();
	if ($configs = @fopen('/etc/dapnetgateway', 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getConfigItem(string $section, string $key, array $configs): ?string {
	// retrieves the corresponding config-entry within a [section]
	$sectionpos = array_search("[" . $section . "]", $configs) + 1;
	$len = count($configs);
	while(startsWith($configs[$sectionpos],$key."=") === false && $sectionpos <= ($len) ) {
		if (startsWith($configs[$sectionpos],"[")) {
			return null;
		}
		$sectionpos++;
	}

	return substr($configs[$sectionpos], strlen($key) + 1);
}

function getEnabled(string $mode, array $mmdvmconfigs): ?string {
	// returns enabled/disabled-State of mode
	return getConfigItem($mode, "Enable", $mmdvmconfigs);
}

function showMode(string $mode, array $mmdvmconfigs): void {
	// shows if mode is enabled or not.
	if (getEnabled($mode, $mmdvmconfigs) == 1) {
		if ($mode == "D-Star Network") {
			if (isProcessRunning(IRCDDBGATEWAY)) {
				echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">&nbsp;";
			} else {
				echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">&nbsp;";
			}
		}
		elseif ($mode == "System Fusion Network") {
			if ( (isProcessRunning("MMDVM_Bridge")) || (getConfigItem("System Fusion Network", "GatewayAddress", $mmdvmconfigs) == '127.0.0.1' && isProcessRunning("YSFGateway"))) {
					echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">&nbsp;";
				} else {
					echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">&nbsp;";
				}
			}
		elseif ($mode == "P25 Network") {
			if (isProcessRunning("P25Gateway")) {
				echo "<td style=\"background:#12AD2A; color:#030; width:10%;\">&nbsp;";
			} else {
				echo "<td style=\"background:#b00; color:#f9f9f9; width:10%;\">&nbsp;";
			}
		}
		elseif ($mode == "NXDN Network") {
			if (isProcessRunning("NXDNGateway")) {
				echo "<td style=\"background:#12AD2A; color:#030; width:10%;\">&nbsp;";
			} else {
				echo "<td style=\"background:#b00; color:#f9f9f9; width:10%;\">&nbsp;";
			}
		}
		elseif ($mode == "DMR Network") {
			if (getConfigItem("DMR Network", "Address", $mmdvmconfigs) == '127.0.0.1') {
				if (isProcessRunning("DMRGateway") || isProcessRunning("MMDVM_Bridge") ) {
					echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">&nbsp;";
				} else {
					echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">&nbsp;";
				}
			}
			else {
				if (isProcessRunning("MMDVM_Bridge")) {
					echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">&nbsp;";
				} else {
					echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">&nbsp;";
				}
			}
		}
		else {
			if ($mode == "D-Star" || $mode == "DMR" || $mode == "System Fusion" || $mode == "P25" || $mode == "NXDN" ) {
				if (isProcessRunning("MMDVM_Bridge")) {
					echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">&nbsp;";
				} else {
					echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">&nbsp;";
				}
			}
		}
	}
	else {
		echo "<td style=\"background:#606060; color:#b0b0b0; width:8%;\">&nbsp;";
    }
    $mode = str_replace("System Fusion", "YSF", $mode);
    $mode = str_replace("Network", "Net", $mode);
    if (strpos($mode, 'YSF2') > -1) { $mode = str_replace(" Net", "", $mode); }
    if (strpos($mode, 'DMR2') > -1) { $mode = str_replace(" Net", "", $mode); }
    echo $mode."&nbsp;</td>\n";
}

function getMMDVMLog(): array {
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
	// Open Logfile and copy loglines into LogLines-Array()
	$logLines = array();
	$logLines1 = array();
	$logLines2 = array();
	
	// PHP replacement for shell pipeline - memory optimized
	function parseMMDVMLog($logPath) {
		if (!file_exists($logPath)) {
			return array();
		}
		
		// Check file size to avoid processing extremely large files
		$fileSize = filesize($logPath);
		if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
			return array();
		}
		
		$filteredLines = array();
		$handle = @fopen($logPath, 'r');
		
		if ($handle) {
			$lineCount = 0;
			$maxLines = 10000; // Limit total lines processed
			
			while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
				$lineCount++;
				$line = trim($line);
				if (empty($line)) continue;
				
				// Check for patterns: Begin|state|frames|from|end|watchdog|lost
				if (preg_match('/Begin|state|frames|from|end|watchdog|lost/', $line)) {
					// Filter out CSBK, overflow, Downlink
					if (!preg_match('/CSBK|overflow|Downlink/', $line)) {
						// Replace I: with M:
						$line = str_replace('I:', 'M:', $line);
						$filteredLines[] = $line;
						
						// Keep only last 100 lines to prevent memory buildup
						if (count($filteredLines) > 100) {
							$filteredLines = array_slice($filteredLines, -100);
						}
					}
				}
			}
			fclose($handle);
		}
		
		return $filteredLines;
	}
	
	if (file_exists(LOGPATH."/".MMDVMLOGPREFIX."-".gmdate("Y-m-d").".log")) {
		$logPath = LOGPATH."/".MMDVMLOGPREFIX."-".gmdate("Y-m-d").".log";
		$logLines1 = parseMMDVMLog($logPath);
	}
	
	if (sizeof($logLines1) < 100) {
		if (file_exists(LOGPATH."/".MMDVMLOGPREFIX."-".gmdate("Y-m-d", time() - 86340).".log")) {
			$logPath = LOGPATH."/".MMDVMLOGPREFIX."-".gmdate("Y-m-d", time() - 86340).".log";
			$logLines2 = parseMMDVMLog($logPath);
		}
	}
	
	$logLines = $logLines1 + $logLines2;
	$logLines = array_slice($logLines, -100);
	return $logLines;
}

function getYSFGatewayLog(): array {
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
	// Open Logfile and copy loglines into LogLines-Array()
	$logLines = array();
	$logLines1 = array();
	$logLines2 = array();
	
	// PHP replacement for shell pipeline - memory optimized
	function parseYSFGatewayLog($logPath) {
		if (!file_exists($logPath)) {
			return array();
		}
		
		// Check file size to avoid processing extremely large files
		$fileSize = filesize($logPath);
		if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
			return array();
		}
		
		$filteredLines = array();
		$handle = @fopen($logPath, 'r');
		
		if ($handle) {
			$lineCount = 0;
			$maxLines = 5000; // Limit total lines processed
			
			while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
				$lineCount++;
				$line = trim($line);
				if (empty($line)) continue;
				
				// Check for patterns: onnection to|onnect to|Link|isconnect|Opening YSF network
				if (preg_match('/onnection to|onnect to|Link|isconnect|Opening YSF network/', $line)) {
					// Filter out unwanted patterns
					if (!preg_match('/Linked to Disconnect|Linked to MMDVM|Link successful to MMDVM|\*Link/', $line)) {
						$filteredLines[] = $line;
						
						// Keep only last line to prevent memory buildup
						if (count($filteredLines) > 1) {
							$filteredLines = array_slice($filteredLines, -1);
						}
					}
				}
			}
			fclose($handle);
		}
		
		return $filteredLines;
	}
	
	if (file_exists(LOGPATH."/".YSFGATEWAYLOGPREFIX."-".gmdate("Y-m-d").".log")) {
		$logPath1 = LOGPATH."/".YSFGATEWAYLOGPREFIX."-".gmdate("Y-m-d").".log";
		$logLines1 = parseYSFGatewayLog($logPath1);
	}
	$logLines1 = array_filter($logLines1);
	
	if (sizeof($logLines1) == 0) {
		if (file_exists(LOGPATH."/".YSFGATEWAYLOGPREFIX."-".gmdate("Y-m-d", time() - 86340).".log")) {
			$logPath2 = LOGPATH."/".YSFGATEWAYLOGPREFIX."-".gmdate("Y-m-d", time() - 86340).".log";
			$logLines1 = parseYSFGatewayLog($logPath2);
		}
		$logLines2 = array_filter($logLines2);
	}
	if (sizeof($logLines1) == 0) { $logLines = $logLines2; } else { $logLines = $logLines1; }
        return array_filter($logLines);
}

function getP25GatewayLog(): array {
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
        // Open Logfile and copy loglines into LogLines-Array()
        $logLines = array();
	$logLines1 = array();
	$logLines2 = array();
	
	// PHP replacement for shell pipeline - memory optimized
	function parseP25GatewayLog($logPath) {
		if (!file_exists($logPath)) {
			return array();
		}
		
		// Check file size to avoid processing extremely large files
		$fileSize = filesize($logPath);
		if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
			return array();
		}
		
		$filteredLines = array();
		$handle = @fopen($logPath, 'r');
		
		if ($handle) {
			$lineCount = 0;
			$maxLines = 5000; // Limit total lines processed
			
			while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
				$lineCount++;
				$line = trim($line);
				if (empty($line)) continue;
				
				// Check for patterns: Link|Starting|Unlink|unlinking
				if (preg_match('/Link|Starting|Unlink|unlinking/', $line)) {
					// Extract fields 2 onwards (skip first field)
					$fields = preg_split('/\s+/', $line);
					if (count($fields) > 1) {
						$filteredLines[] = implode(' ', array_slice($fields, 1));
						
						// Keep only last line to prevent memory buildup
						if (count($filteredLines) > 1) {
							$filteredLines = array_slice($filteredLines, -1);
						}
					}
				}
			}
			fclose($handle);
		}
		
		return $filteredLines;
	}
	
        if (file_exists(LOGPATH."/".P25GATEWAYLOGPREFIX."-".gmdate("Y-m-d").".log")) {
		$logPath1 = LOGPATH."/".P25GATEWAYLOGPREFIX."-".gmdate("Y-m-d").".log";
		$logLines1 = parseP25GatewayLog($logPath1);
        }
	$logLines1 = array_filter($logLines1);
        if (sizeof($logLines1) == 0) {
                if (file_exists(LOGPATH."/".P25GATEWAYLOGPREFIX."-".gmdate("Y-m-d", time() - 86340).".log")) {
                        $logPath2 = LOGPATH."/".P25GATEWAYLOGPREFIX."-".gmdate("Y-m-d", time() - 86340).".log";
			$logLines2 = parseP25GatewayLog($logPath2);
                }
		$logLines2 = array_filter($logLines2);
        }
	if (sizeof($logLines1) == 0) { $logLines = $logLines2; } else { $logLines = $logLines1; }
        return array_filter($logLines);
}

function getNXDNGatewayLog(): array {
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
        // Open Logfile and copy loglines into LogLines-Array()
        $logLines = array();
	$logLines1 = array();
	$logLines2 = array();
	
	// PHP replacement for shell pipeline - memory optimized
	function parseNXDNGatewayLog($logPath) {
		if (!file_exists($logPath)) {
			return array();
		}
		
		// Check file size to avoid processing extremely large files
		$fileSize = filesize($logPath);
		if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
			return array();
		}
		
		$filteredLines = array();
		$handle = @fopen($logPath, 'r');
		
		if ($handle) {
			$lineCount = 0;
			$maxLines = 5000; // Limit total lines processed
			
			while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
				$lineCount++;
				$line = trim($line);
				if (empty($line)) continue;
				
				// Check for patterns: Link|Starting|Unlink|unlinking
				if (preg_match('/Link|Starting|Unlink|unlinking/', $line)) {
					// Extract fields 2 onwards (skip first field)
					$fields = preg_split('/\s+/', $line);
					if (count($fields) > 1) {
						$filteredLines[] = implode(' ', array_slice($fields, 1));
						
						// Keep only last line to prevent memory buildup
						if (count($filteredLines) > 1) {
							$filteredLines = array_slice($filteredLines, -1);
						}
					}
				}
			}
			fclose($handle);
		}
		
		return $filteredLines;
	}
	
        if (file_exists(LOGPATH."/".NXDNGATEWAYLOGPREFIX."-".gmdate("Y-m-d").".log")) {
		$logPath1 = LOGPATH."/".NXDNGATEWAYLOGPREFIX."-".gmdate("Y-m-d").".log";
		$logLines1 = parseNXDNGatewayLog($logPath1);
        }
	$logLines1 = array_filter($logLines1);
        if (sizeof($logLines1) == 0) {
                if (file_exists(LOGPATH."/".NXDNGATEWAYLOGPREFIX."-".gmdate("Y-m-d", time() - 86340).".log")) {
			$logPath2 = LOGPATH."/".NXDNGATEWAYLOGPREFIX."-".gmdate("Y-m-d", time() - 86340).".log";
			$logLines2 = parseNXDNGatewayLog($logPath2);
                }
		$logLines2 = array_filter($logLines2);
        }
	if (sizeof($logLines1) == 0) { $logLines = $logLines2; } else { $logLines = $logLines1; }
        return array_filter($logLines);
}

function getDAPNETGatewayLog(): array {
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
        // Open Logfile and copy loglines into LogLines-Array()
        $logLines = array();
	$logLines1 = array();
	$logLines2 = array();
	
	// PHP replacement for shell pipeline - memory optimized
	function parseDAPNETGatewayLog($logPath) {
		if (!file_exists($logPath)) {
			return array();
		}
		
		// Check file size to avoid processing extremely large files
		$fileSize = filesize($logPath);
		if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
			return array();
		}
		
		$filteredLines = array();
		$handle = @fopen($logPath, 'r');
		
		if ($handle) {
			$lineCount = 0;
			$maxLines = 5000; // Limit total lines processed
			
			while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
				$lineCount++;
				$line = trim($line);
				if (empty($line)) continue;
				
				// Check for pattern: Sending message
				if (preg_match('/Sending message/', $line)) {
					// Extract fields 2 onwards (skip first field)
					$fields = preg_split('/\s+/', $line);
					if (count($fields) > 1) {
						$filteredLines[] = implode(' ', array_slice($fields, 1));
						
						// Keep only last 20 lines to prevent memory buildup
						if (count($filteredLines) > 20) {
							$filteredLines = array_slice($filteredLines, -20);
						}
					}
				}
			}
			fclose($handle);
		}
		
		// Return in reverse order (like tac)
		return array_reverse($filteredLines);
	}
	
        if (file_exists("/var/log/mmdvm/DAPNETGateway-".gmdate("Y-m-d").".log")) {
		$logPath1 = "/var/log/mmdvm/DAPNETGateway-".gmdate("Y-m-d").".log";
		$logLines1 = parseDAPNETGatewayLog($logPath1);
        }
	$logLines1 = array_filter($logLines1);
        if (sizeof($logLines1) == 0) {
                if (file_exists("/var/log/mmdvm/DAPNETGateway-".gmdate("Y-m-d", time() - 86340).".log")) {
			$logPath2 = "/var/log/mmdvm/DAPNETGateway-".gmdate("Y-m-d", time() - 86340).".log";
			$logLines2 = parseDAPNETGatewayLog($logPath2);
                }
		$logLines2 = array_filter($logLines2);
        }
	$logLines = $logLines1 + $logLines2;
	$logLines = array_slice($logLines, -20);
	return array_filter($logLines);
}

function getHeardList(array $logLines): array {
	//array_multisort($logLines,SORT_DESC);
	$heardList = array();
	$ts1duration	= "";
	$ts1loss	= "";
	$ts1ber		= "";
	$ts1rssi	= "";
	$ts2duration	= "";
	$ts2loss	= "";
	$ts2ber		= "";
	$ts2rssi	= "";
	$dstarduration	= "";
	$dstarloss	= "";
	$dstarber	= "";
	$dstarrssi	= "";
	$ysfduration	= "";
        $ysfloss	= "";
        $ysfber		= "";
	$ysfrssi	= "";
	$p25duration	= "";
        $p25loss	= "";
        $p25ber		= "";
	$p25rssi	= "";
	$nxdnduration	= "";
        $nxdnloss	= "";
        $nxdnber	= "";
	$nxdnrssi	= "";
	$pocsagduration	= "";
	$lat		= "";
	$long		= "";
        $mode           = "";
        $callsign       = "";
        $id             = "";
        $target         = "";
        $source         = "";
        $timestamp      = "";
	$dmrstatus	= "";
	$ysflat		= "";
	$ysflong	= "";
	$dmrduration	= "";
	$dmrloss	= "";
	$dmrber		= "";

	foreach ($logLines as $logLine) {
		$duration	= "";
		$loss		= "";
		$ber		= "";
		$rssi		= "";
		//removing invalid lines
		if(strpos($logLine,"BS_Dwn_Act")) {
			continue;
		} else if(strpos($logLine,"invalid access")) {
			continue;
		} else if(strpos($logLine,"NXDN, received RF header from")) {
			continue;
		} else if(strpos($logLine,"TX state = ON")) {
			continue;
		} else if(strpos($logLine,"received RF header for wrong repeater")) {
			continue;
		} else if(strpos($logLine,"unable to decode the network CSBK")) {
			continue;
		} else if(strpos($logLine,"overflow in the DMR slot RF queue")) {
			continue;
		} else if(strpos($logLine,"non repeater RF header received")) {
			continue;
		} else if(strpos($logLine,"Embedded Talker Alias")) {
                        continue;
		} else if(strpos($logLine,"DMR Talker Alias")) {
			continue;
		} else if(strpos($logLine,"CSBK Preamble")) {
                        continue;
		} else if(strpos($logLine,"Preamble CSBK")) {
                        continue;
		}

		if(strpos($logLine,"TX state") || strpos($logLine,"GPS Position") || strpos($logLine, "end of") || strpos($logLine, "watchdog has expired") || strpos($logLine, "ended RF data") || strpos($logLine, "ended network") || strpos($logLine, "RF user has timed out") || strpos($logLine, "transmission lost") || strpos($logLine, "POCSAG")) {

		if (strpos($logLine,"TX state = OFF")){
			$dvsm=substr($logLine, 27, strpos($logLine,",") - 27);
			if ($dvsm == "DMR") {
				$wasPos = strpos($logLine,"was");
		$framesPos = strpos($logLine,"frames");
		if ($wasPos !== false && $framesPos !== false) {
			$duration = substr($logLine, $wasPos+4, $framesPos - $wasPos-5)*0.059;
		} else {
			$duration = "";
		}
				$duration=number_format($duration, 1, '.', '.'); }
			if ($dvsm == "YSF" || $dvsm == "NXDN" || $dvsm == "P25" || $dvsm == "D-Star") {
				$duration="---"; }
			$ber = "---";
			$loss = "---";
			} else {

			$lineTokens = explode(", ",$logLine);
			if (array_key_exists(2,$lineTokens)) {
				$duration = strtok($lineTokens[2], " ");
			}
			if (array_key_exists(3,$lineTokens)) {
				$loss = $lineTokens[3];
			}
			if (strpos($logLine,"RF user has timed out")) {
				$duration = "TOut";
				$ber = "??%";
			}

			// if RF-Packet with no BER reported (e.g. YSF Wires-X commands) then RSSI is in LOSS position
			if (startsWith($loss,"RSSI")) {
				$lineTokens[4] = $loss; //move RSSI to the position expected on code below
				$loss = 'BER: ??%';
				}

			// if RF-Packet, no LOSS would be reported, so BER is in LOSS position
			if (startsWith($loss,"BER")) {
				$ber = substr($loss, 5);
				$loss = "0%";
				if (array_key_exists(4,$lineTokens) && startsWith($lineTokens[4],"RSSI")) {
					$rssi = substr($lineTokens[4], 6);
					$rssi = substr($rssi, strrpos($rssi,'/')+1); //average only
					$relint = intval($rssi) + 93;
					$signal = round(($relint/6)+9, 0);
					if ($signal < 0) $signal = 0;
					if ($signal > 9) $signal = 9;
					if ($relint > 0) {
						$rssi = "S{$signal}+{$relint}dB";
					} else {
						$rssi = "S{$signal}";
					}
				}
			} else {
				$loss = strtok($loss, " ");
				if (array_key_exists(4,$lineTokens)) {
					$ber = substr($lineTokens[4], 5);
				}
			 }
			}
			if (strpos($logLine,"ended RF data") || strpos($logLine,"ended network") || strpos($logLine,"GPS Position") ) {
				switch (substr($logLine, 27, strpos($logLine,",") - 27)) {
					case "DMR Slot 1":
						$ts1duration = "DMR Data";
						break;
					case "DMR Slot 2":
						$ts2duration = "DMR Data";
						break;
					case "YSF":
						$ysfduration = "GPS";
						$ysflat=trim(substr($logLine,strpos($logLine,"lat=")+4,strpos($logLine,"long=") - strpos($logLine,"lat=")-4));
						$ysflong=trim(substr($logLine, strpos($logLine,"long=")+5));
						break;
				}
			} else {
				switch (substr($logLine, 27, strpos($logLine,",") - 27)) {
					case "D-Star":
						$dstarduration	= $duration;
						$dstarloss	= $loss;
						$dstarber	= $ber;
						break;
					case "DMR":
						$dmrduration	= $duration;
						$dmrloss	= $loss;
						$dmrber		= $ber;
						break;
					case "DMR Slot 1":
						$ts1duration	= $duration;
						$ts1loss	= $loss;
						$ts1ber		= $ber;
						break;
					case "DMR Slot 2":
						$ts2duration	= $duration;
						$ts2loss	= $loss;
						$ts2ber		= $ber;
						break;
					case "YSF":
						$ysfduration	= $duration;
						$ysfloss	= $loss;
						$ysfber		= $ber;
						break;
					case "P25":
						$p25duration	= $duration;
						$p25loss	= $loss;
						$p25ber		= $ber;
						break;
					case "NXDN":
						$nxdnduration	= $duration;
						$nxdnloss	= $loss;
						$nxdnber	= $ber;
						break;
					case "POCSAG":
						$pocsagduration	= "";
						break;
				}
			}
		}

		if (strpos($logLine,"Begin TX")) {
		$mode = substr($logLine, 27, strpos($logLine,",") - 27);
		$callsign = substr($logLine, strpos($logLine,"metadata=")+9);
		$callsign = trim($callsign);
		$target = "TG ".substr($logLine,strpos($logLine,"dst=")+4,strpos($logLine,"slot=") - strpos($logLine,"dst=")-4);
		$source = "LNet";
		$timestamp = substr($logLine, 3, 19);
		$id ="";
		}
		if (strpos($logLine,"from") and strpos($logLine,"GPS Position") == False){
		$mode = substr($logLine, 27, strpos($logLine,",") - 27);
		$timestamp = substr($logLine, 3, 19);
		$fromPos = strpos($logLine,"from");
		$toPos = strpos($logLine,"to");
		if ($fromPos !== false && $toPos !== false) {
			$callsign2 = substr($logLine, $fromPos + 5, $toPos - $fromPos - 6);
		} else {
			$callsign2 = "";
		}
		$callsign = $callsign2;
		if( $callsign == "0" || $callsign == "1234" || $callsign == "1234567") {$callsign="N0CALL";}
		if (strpos($callsign2,"/") > 0) {
			$callsign = substr($callsign2, 0, strpos($callsign2,"/"));
		}
		$callsign = trim($callsign);

		$id ="";
		if ($mode == "D-Star") {
			$id = substr($callsign2, strpos($callsign2,"/") + 1);
		}

		$toPos = strpos($logLine, "to");
		if ($toPos !== false) {
			$target = trim(substr($logLine, $toPos + 3));
			// Handle more verbose logging from MMDVM_Bridge
			if (strpos($target,",") !== false) { $target = explode(",", $target)[0]; }
		} else {
			$target = "";
		}

		$source = "Net";
		};
		switch ($mode) {
			case "D-Star":
				$duration	= $dstarduration;
				$loss		= $dstarloss;
				$ber		= $dstarber;
				$rssi		= $dstarrssi;
				break;
			case "DMR":
				$duration	= $dmrduration;
                		$loss		= (is_string($dmrloss) && strlen($dmrloss)) ? $dmrloss : "---";
                		$ber		= (is_string($dmrber) && strlen($dmrber)) ? $dmrber : "---";
				break;
			case "DMR Slot 1":
				$duration	= $ts1duration;
				$loss		= $ts1loss;
				$ber		= $ts1ber;
				$rssi		= $ts1rssi;
				break;
			case "DMR Slot 2":
				$duration	= $ts2duration;
				$loss		= $ts2loss;
				$ber		= $ts2ber;
				$rssi		= $ts2rssi;
				break;
			case "YSF":
                		$duration	= $ysfduration;
                		$loss		= $ysfloss;
                		$ber		= $ysfber;
				$rssi		= $ysfrssi;
                		$lat		= $ysflat;
				$long		= $ysflong;
				$target		= preg_replace('!\s+!', ' ', $target);
                		break;
			case "P25":
				if ($source == "Net" && $target == "TG 10") {$callsign = "PARROT";}
				if ($source == "Net" && $callsign == "10999") {$callsign = "MMDVM";}
                		$duration	= $p25duration;
                		$loss		= (is_string($p25loss) && strlen($p25loss)) ? $p25loss : "---";
                		$ber		= (is_string($p25ber) && strlen($p25ber)) ? $p25ber : "---";
				$rssi		= $p25rssi;
                		break;
			case "NXDN":
				if ($source == "Net" && $target == "TG 10") {$callsign = "PARROT";}
                		$duration	= $nxdnduration;
                		$loss		= (is_string($nxdnloss) && strlen($nxdnloss)) ? $nxdnloss : "---";
                		$ber		= (is_string($nxdnber) && strlen($nxdnber)) ? $nxdnber : "---";
				$rssi		= $nxdnrssi;
                		break;
			case "POCSAG":
				$callsign	= "DAPNET";
				$target		= "DAPNET User";
				$duration	= "0.0";
				$loss		= "0%";
                		$ber		= "0.0%";
				break;
		}

		// Callsign or ID should be less than 11 chars long, otherwise it could be errorneous
		// Ensure $callsign is a string before calling strlen()
		$callsign = is_string($callsign) ? $callsign : (string)$callsign;
		if ( strlen($callsign) < 11 ) {
			array_push($heardList, array($timestamp, $mode, $callsign, $id, $target, $source, $duration, $loss, $ber, $lat, $long));
			$duration = "";
			$loss ="";
			$ber = "";
			$lat ="";
			$long = "";
		}
	}
	return $heardList;
}

function getLastHeard(array $logLines): array {
	//returns last heard list from log
	$lastHeard = array();
	$heardCalls = array();
	$heardList = getHeardList($logLines);
	$counter = 0;
	foreach ($heardList as $listElem) {
		if ( ($listElem[1] == "D-Star") || ($listElem[1] == "YSF") || ($listElem[1] == "P25") || ($listElem[1] == "NXDN") || ($listElem[1] == "POCSAG") || (startsWith($listElem[1], "DMR")) ) {
			$callUuid = $listElem[2]."#".$listElem[1].$listElem[3].$listElem[5];
			if(!(array_search($callUuid, $heardCalls) > -1)) {
				array_push($heardCalls, $callUuid);
				array_push($lastHeard, $listElem);
				$counter++;
			}
		}
	}
	return $lastHeard;
}

function getActualMode(array $metaLastHeard, array $mmdvmconfigs): string {
    // returns mode of repeater actual working in
        $utc_tz =  new DateTimeZone('UTC');
        $local_tz = new DateTimeZone(date_default_timezone_get ());
        $listElem = $metaLastHeard[0];
        $timestamp = new DateTime($listElem[0], $utc_tz);
        $timestamp->setTimeZone($local_tz);
        $mode = $listElem[1];
    if (startsWith($mode, "DMR")) {
	$mode = "DMR";
    }

    $now =  new DateTime();
    $hangtime = "0";
    $timestamp->add(new DateInterval('PT' . $hangtime . 'S'));

    if ($listElem[6] != null) { //if terminated, hangtime counts after end of transmission
//	$timestamp->add(new DateInterval('PT' . ceil($listElem[6]) . 'S'));
    } else { //if not terminated, always return mode
	return $mode;
    }
    if ($now->format('U') > $timestamp->format('U')) {
	return "idle";
    } else {
	return $mode;
    }
}

function getDSTARLinks(): string {
	// returns link-states of all D-Star-modules
	if (filesize(LINKLOGPATH."/Links.log") == 0) {
		return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
	}
	if ($linkLog = fopen(LINKLOGPATH."/Links.log",'r')) {
		while ($linkLine = fgets($linkLog)) {
			$linkDate	= "&nbsp;";
			$protocol	= "&nbsp;";
			$linkType	= "&nbsp;";
			$linkSource	= "&nbsp;";
			$linkDest	= "&nbsp;";
			$linkDir	= "&nbsp;";
// Reflector-Link, sample:
// 2011-09-22 02:15:06: DExtra link - Type: Repeater Rptr: DB0LJ	B Refl: XRF023 A Dir: Outgoing
// 2012-04-03 08:40:07: DPlus link - Type: Dongle Rptr: DB0ERK B Refl: REF006 D Dir: Outgoing
// 2012-04-03 08:40:07: DCS link - Type: Repeater Rptr: DB0ERK C Refl: DCS001 C Dir: Outgoing
			if(preg_match_all('/^(.{19}).*(D[A-Za-z]*).*Type: ([A-Za-z]*).*Rptr: (.{8}).*Refl: (.{8}).*Dir: (.{8})/',$linkLine,$linx) > 0){
				$linkDate	= $linx[1][0];
				$protocol	= $linx[2][0];
				$linkType	= $linx[3][0];
				$linkSource	= $linx[4][0];
				$linkDest	= $linx[5][0];
				$linkDir	= $linx[6][0];
			}
// CCS-Link, sample:
// 2013-03-30 23:21:53: CCS link - Rptr: PE1AGO C Remote: PE1KZU	Dir: Incoming
			if(preg_match_all('/^(.{19}).*(CC[A-Za-z]*).*Rptr: (.{8}).*Remote: (.{8}).*Dir: (.{8})/',$linkLine,$linx) > 0){
				$linkDate	= $linx[1][0];
				$protocol	= $linx[2][0];
				$linkType	= $linx[2][0];
				$linkSource	= $linx[3][0];
				$linkDest	= $linx[4][0];
				$linkDir	= $linx[5][0];
			}
// Dongle-Link, sample: 
// 2011-09-24 07:26:59: DPlus link - Type: Dongle User: DC1PIA	Dir: Incoming
// 2012-03-14 21:32:18: DPlus link - Type: Dongle User: DC1PIA Dir: Incoming
			if(preg_match_all('/^(.{19}).*(D[A-Za-z]*).*Type: ([A-Za-z]*).*User: (.{6,8}).*Dir: (.*)$/',$linkLine,$linx) > 0){
				$linkDate	= $linx[1][0];
				$protocol	= $linx[2][0];
				$linkType	= $linx[3][0];
				$linkSource	= "&nbsp;";
				$linkDest	= $linx[4][0];
				$linkDir	= $linx[5][0];
			}
			$out = "Linked to <span style=\"color:#b5651d;font-weight:bold;\">" . $linkDest . "</span><br />\n(<span style=\"color:green;\"><b>" . $protocol . " " . $linkDir . "</b></span>)";
		}
	}
	fclose($linkLog);
	return $out;
}

function getActualLink(array $logLines, string $mode): string {
	// returns actual link state of specific mode
	//M: 2016-05-02 07:04:10.504 D-Star link status set to "Verlinkt zu DCS002 S"
	//M: 2016-04-03 16:16:18.638 DMR Slot 2, received network voice header from 4000 to 2625094
	//M: 2016-04-03 19:30:03.099 DMR Slot 2, received network voice header from 4020 to 2625094
	//M: 2017-09-03 08:10:42.862 DMR Slot 2, received network data header from M6JQD to TG 9, 5 blocks
	switch ($mode) {
    case "D-Star":
    	if (isProcessRunning(IRCDDBGATEWAY)) {
			return getDSTARLinks();
    	} else {
    		return "<span style=\"color:#b0b0b0;\"><b>No D-Star Network</b></span>";
    	}
        break;

	case "DMR Slot 1":
	case "DMR Slot 2":
	    //M: 2016-04-03 16:16:18.638 DMR Slot 2, received network voice header from 4000 to 2625094
	    //M: 2020-01-22 01:54:50.780 DMR Slot 2, received network voice header from 4000 to TG 9
	    //M: 2016-04-03 19:30:03.099 DMR Slot 2, received network voice header from 4020 to 2625094
	    //M: 2017-09-03 08:10:42.862 DMR Slot 2, received network data header from M6JQD to TG 9, 5 blocks
            foreach ($logLines as $logLine) {
        	if(strpos($logLine,"unable to decode the network CSBK")) {
		    continue;
		}
		else if(substr($logLine, 27, strpos($logLine,",") - 27) == $mode) {
		    $to = "";
		    $from = "";
		    if (strpos($logLine, "from") != FALSE) {
			$from = trim(get_string_between($logLine, "from", "to"));
		if ($from === false) { $from = ""; }
		    }
		    if (strpos($logLine,"to")) {
			$toPos = strpos($logLine,"to");
			if ($toPos !== false) {
				$to = trim(substr($logLine, $toPos + 3));
			} else {
				$to = "";
			}
		    }
		    if ($from !== "") {
			if ($from === "4000") {
			    return "No TG";
			}
		    }
		    if ($to !== "") {
			if (substr($to, 0, 3) !== 'TG ') {
			    continue;
			}
			if ($to === "TG 4000") {
			    return "No TG";
			}
			if (strpos($to, ',') !== false) {
			    $to = substr($to, 0, strpos($to, ','));
			}
			return $to;
		    }
		}
	    }
	    return "No TG";
            break;

    case "YSF":
	// 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
	// 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
	// M: 0000-00-00 00:00:00.000 Connect to 62829 has been requested by M1ABC
	// M: 0000-00-00 00:00:00.000 Automatic connection to 62829
	// New YSFGateway Format
	// M: 0000-00-00 00:00:00.000 Opening YSF network connection
	// M: 0000-00-00 00:00:00.000 Automatic (re-)connection to 16710 - "GB SOUTH WEST   "
	// M: 0000-00-00 00:00:00.000 Automatic (re-)connection to FCS00290
	// M: 0000-00-00 00:00:00.000 Linked to GB SOUTH WEST
	// M: 0000-00-00 00:00:00.000 Linked to FCS002-90
	// M: 0000-00-00 00:00:00.000 Disconnect via DTMF has been requested by M1ABC
	// M: 0000-00-00 00:00:00.000 Connect to 00003 - "YSF2NXDN        " has been requested by M1ABC
	// M: 0000-00-00 00:00:00.000 Link has failed, polls lost
         if ((isProcessRunning("YSFGateway")) && (isProcessRunning("MMDVM_Bridge"))||(isProcessRunning("MMDVM_Bridge"))||(isProcessRunning("YSFGateway"))) {
            $to = "";
            foreach($logLines as $logLine) {
               if ( (strpos(substr($logLine, 37),":")) && (strpos(substr($logLine, 37),"."))) {
                  $to = trim(substr($logLine, 37));
		  $address=trim(substr($to,0,strpos($to,":")));
		  $port=trim(substr($to,strpos($to,":")+1));
		  $link = $address.";".$port;
		if (file_exists("/var/lib/mmdvm/YSFHosts.txt")) { 
			// PHP replacement for shell pipeline - memory optimized
			$ysfstatus = "";
			$handle = fopen("/var/lib/mmdvm/YSFHosts.txt", 'r');
			if ($handle) {
				while (($line = fgets($handle)) !== false) {
					$line = trim($line);
					if (empty($line)) continue;
					
					if (strpos($line, $link) !== false) {
						$ysfstatus = $line;
						break; // Found the match, no need to continue
					}
				}
				fclose($handle);
			}
		}
		    if ($ysfstatus != "") {
		        $ysfname= explode(";",$ysfstatus);
		        $to = $ysfname[1];}
		    }
               if ( (!strpos(substr($logLine, 37),":")) && (strpos($logLine,"Linked to")) && (!strpos($logLine,"Linked to MMDVM")) && (isProcessRunning("YSFGateway"))) {
                  $to = trim(substr($logLine, 37, 16));
		  if (substr($to, 0, 3) === "FCS") { $to = str_replace(' ', '', str_replace('-', '', $to)); }
               }

               if (strpos($logLine,"Automatic (re-)connection to")) {
		  if (strpos($logLine,"Automatic (re-)connection to FCS")) {
			$to = substr($logLine, 56, 8);
		  }
		  else {
                  	$to = substr($logLine, 56, 5);
		  }
               }
               if (strpos($logLine,"Connect to")) {
                  $to = substr($logLine, 38, 5);
               }
               if (strpos($logLine,"Automatic connection to")) {
                  $to = substr($logLine, 51, 5);
               }
               if (strpos($logLine,"Disconnect via DTMF")) {
                  $to = "Not Linked";
               }
               if (strpos($logLine,"Opening YSF network connection")) {
                  $to = "Not Linked";
               }
	       if (strpos($logLine,"Link has failed")) {
                  $to = "Not Linked";
               }
               if (strpos($logLine,"DISCONNECT Reply")) {
                  $to = "Not Linked";
               }
               if ($to !== "") {
                  return $to;
               }
            }
            return "Not Linked";
         } else {
            return "No YSF Network";
         }
         break;

     case "NXDN":
        // 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
        // 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
        // 2000-01-01 00:00:00.000 Linked at startup to reflector 65000
        // 2000-01-01 00:00:00.000 Unlinked from reflector 10100 by M1ABC
        // 2000-01-01 00:00:00.000 Linked to reflector 10200 by M1ABC
        // 2000-01-01 00:00:00.000 No response from 10200, unlinking
        if (isProcessRunning("NXDNGateway")) {
            foreach($logLines as $logLine) {
               $to = "";
               if (strpos($logLine,"Linked to")) {
                  $to = preg_replace('/[^0-9]/', '', substr($logLine, 44, 5));
                  $to = preg_replace('/[^0-9]/', '', $to);
                  return "Linked to <span style=\"color:#b5651d;font-weight:bold;\">TG ".$to."</span>";
               }
               if (strpos($logLine,"Linked at start")) {
                  $to = preg_replace('/[^0-9]/', '', substr($logLine, 55, 5));
                  $to = preg_replace('/[^0-9]/', '', $to);
                  return "Linked to <span style=\"color:#b5651d;font-weight:bold;\">TG ".$to."</span>";
               }
	       if (strpos($logLine,"Starting NXDNGateway")) {
                  return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
               }
               if (strpos($logLine,"unlinking")) {
                  return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
               }
               if (strpos($logLine,"Unlinked from")) {
                  return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
               }
            }
            return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
        } else {
            return "<span style=\"color:#b0b0b0;\"><b>No NXDN Network</b></span>";
        }
        break;

    case "P25":
	// 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
	// 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
	// 2000-01-01 00:00:00.000 Linked at startup to reflector 10100
	// 2000-01-01 00:00:00.000 Unlinked from reflector 10100 by M1ABC
	// 2000-01-01 00:00:00.000 Linked to reflector 10200 by M1ABC
	// 2000-01-01 00:00:00.000 No response from 10200, unlinking
	if (isProcessRunning("P25Gateway")) {
	    foreach($logLines as $logLine) {
               $to = "";
               if (strpos($logLine,"Linked to")) {
		  $to = preg_replace('/[^0-9]/', '', substr($logLine, 44, 5));
		  $to = preg_replace('/[^0-9]/', '', $to);
		  return "Linked to <span style=\"color:#b5651d;font-weight:bold;\">TG ".$to."</span>";
               }
               if (strpos($logLine,"Linked at startup to")) {
		  $to = preg_replace('/[^0-9]/', '', substr($logLine, 55, 5));
		  $to = preg_replace('/[^0-9]/', '', $to);
		  return "Linked to <span style=\"color:#b5651d;font-weight:bold;\">TG ".$to."</span>";
               }
	       if (strpos($logLine,"Starting P25Gateway")) {
                  return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
               }
	       if (strpos($logLine,"unlinking")) {
                  return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
               }
               if (strpos($logLine,"Unlinked")) {
                  return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
               }
	    }
            return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
	} else {
            return "<span style=\"color:#b0b0b0;\"><b>No P25 Network</b></span>";
        }
	break;
	}
	return "<span style=\"color:#b0b0b0;\"><b>Service Not Started</b></span>";
}

function getActualReflector(array $logLines, string $mode): string {
	// 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
	// 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
	// M: 2016-05-02 07:04:10.504 D-Star link status set to "Verlinkt zu DCS002 S"
	// M: 2016-04-03 16:16:18.638 DMR Slot 2, received network voice header from 4000 to 2625094
	// M: 2016-04-03 19:30:03.099 DMR Slot 2, received network voice header from 4020 to 2625094
	foreach ($logLines as $logLine) {
		if (substr($logLine, 27, strpos($logLine,",") - 27) == $mode) {
			$from = substr($logLine, strpos($logLine,"from") + 5, strpos($logLine,"to") - strpos($logLine,"from") - 6);
			$from = is_string($from) ? $from : (string)$from;
			if (strlen($from) == 4 && startsWith($from,"4")) {
				if ($from == "4000") {
					return "No Ref";
				} else {
					return "Ref ".$from;
				}
			}
		}
	}
	return "No Ref";
}


//Some basic inits
$mmdvmconfigs = getMMDVMConfig();
if (!in_array($_SERVER["PHP_SELF"],array('/include/bm_links.php','/include/bm_manager.php'),true)) {
	$logLinesMMDVM = getMMDVMLog();
	$reverseLogLinesMMDVM = $logLinesMMDVM;
	array_multisort($reverseLogLinesMMDVM,SORT_DESC);
	$lastHeard = getLastHeard($reverseLogLinesMMDVM);

	// Only need these in:
	if (strpos($_SERVER["PHP_SELF"], 'status.php') !== false || strpos($_SERVER["PHP_SELF"], 'index.php') !== false) {
		//$YSFGatewayconfigs = getYSFGatewayConfig();
		$logLinesYSFGateway = getYSFGatewayLog();
		$reverseLogLinesYSFGateway = $logLinesYSFGateway;
		array_multisort($reverseLogLinesYSFGateway,SORT_DESC);
		//$P25Gatewayconfigs = getP25GatewayConfig();
		$logLinesP25Gateway = getP25GatewayLog();
		//$reverseLogLinesP25Gateway = array_reverse(getP25GatewayLog());
		//$NXDNGatewayconfigs = getNXDNGatewayConfig();
		$logLinesNXDNGateway = getNXDNGatewayLog();
		//$reverseLogLinesNXDNGateway = array_reverse(getNXDNGatewayLog());
	}
	// Only need these in index.php and lh.php
	if (strpos($_SERVER["PHP_SELF"], 'index.php') !== false || strpos($_SERVER["PHP_SELF"], 'lh.php') !== false) {
        if ( DISPLAYNAME == "YES"  && file_exists(DMRIDDATPATH."/DMRIds.dat") && ! empty(DMRIDDATPATH."/DMRIds.dat")) {
	     $dmrIDline = file_get_contents(DMRIDDATPATH."/DMRIds.dat");
	    }
	}
}

function getABInfo(string $filename): array {
	$json = file_get_contents($filename);
	$json_data = json_decode($json,true);
	return $json_data;
}

function cidr_match(string $ip, string $cidr): bool {
    $outcome = false;
    $pattern = '/^(([01]?\d?\d|2[0-4]\d|25[0-5])\.){3}([01]?\d?\d|2[0-4]\d|25[0-5])\/(\d{1}|[0-2]{1}\d{1}|3[0-2])$/';
    if (preg_match($pattern, $cidr)){
        list($subnet, $mask) = explode('/', $cidr);
        if (ip2long($ip) >> (32 - $mask) == ip2long($subnet) >> (32 - $mask)) {
            $outcome = true;
        }
    }
    return $outcome;
}

function getDMRGstat(string $dmrserver): ?string {
	// PHP replacement for shell pipeline - memory optimized
	function parseDMRGatewayStatus($logPath, $dmrserver) {
		if (!file_exists($logPath)) {
			return "";
		}
		
		// Check file size to avoid processing extremely large files
		$fileSize = filesize($logPath);
		if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
			return "";
		}
		
		$lastStatus = "";
		$handle = @fopen($logPath, 'r');
		
		if ($handle) {
			$lineCount = 0;
			$maxLines = 5000; // Limit total lines processed
			
			while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
				$lineCount++;
				$line = trim($line);
				if (empty($line)) continue;
				
				// Check for patterns: $dmrserver, Logged|$dmrserver, Closing DMR|$dmrserver, Opening DMR|$dmrserver, Connection
				if (preg_match('/' . preg_quote($dmrserver, '/') . ', Logged|' . preg_quote($dmrserver, '/') . ', Closing DMR|' . preg_quote($dmrserver, '/') . ', Opening DMR|' . preg_quote($dmrserver, '/') . ', Connection/', $line)) {
					$fields = preg_split('/\s+/', $line);
					if (isset($fields[4])) {
						$lastStatus = $fields[4];
					}
				}
			}
			fclose($handle);
		}
		
		return $lastStatus;
	}
	
	$logPath = "/var/log/mmdvm/DMRGateway-".gmdate("Y-m-d").".log";
	$dmrstatus = parseDMRGatewayStatus($logPath, $dmrserver);
	
	if (empty($dmrstatus)) {
		$logPath = "/var/log/mmdvm/DMRGateway-".gmdate("Y-m-d", time() - 86340).".log";
		$dmrstatus = parseDMRGatewayStatus($logPath, $dmrserver);
	}
	
	$dmrserver = str_replace('_', ' ', $dmrserver);
	$dmrserver = is_string($dmrserver) ? $dmrserver : (string)$dmrserver;
	if (strlen($dmrserver) > 19) { $dmrserver = substr($dmrserver, 0, 17) . '..'; }
	if (strpos($dmrstatus, 'Logged') !== false ) {
             return "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b5651d;font-weight: bold\">".$dmrserver."</span></td></tr>\n";
        } else if (strpos($dmrstatus, 'Opening') !== false || strpos($dmrstatus, 'Closing') !== false || strpos($dmrstatus, 'Connection') !== false) {
             return "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b0b0b0;font-weight: bold\">".$dmrserver."</span></td></tr>\n"; }
}


function Get_User_IP(): string|false {
    $IP = false;
    if (getenv('HTTP_CLIENT_IP'))
    {
        $IP = getenv('HTTP_CLIENT_IP');
    }
    else if(getenv('HTTP_X_FORWARDED_FOR'))
    {
        $IP = getenv('HTTP_X_FORWARDED_FOR');
    }
    else if(getenv('HTTP_X_FORWARDED'))
    {
        $IP = getenv('HTTP_X_FORWARDED');
    }
    else if(getenv('HTTP_FORWARDED_FOR'))
    {
        $IP = getenv('HTTP_FORWARDED_FOR');
    }
    else if(getenv('HTTP_FORWARDED'))
    {
        $IP = getenv('HTTP_FORWARDED');
    }
    else if(getenv('REMOTE_ADDR'))
    {
        $IP = getenv('REMOTE_ADDR');
    }

    //If HTTP_X_FORWARDED_FOR == server ip
    if((($IP) && ($IP == getenv('SERVER_ADDR')) && (getenv('REMOTE_ADDR')) || (!filter_var($IP, FILTER_VALIDATE_IP))))
    {
        $IP = getenv('REMOTE_ADDR');
    }

    if($IP)
    {
        if(!filter_var($IP, FILTER_VALIDATE_IP))
        {
            $IP = false;
        }
    }
    else
    {
        $IP = false;
    }
    return $IP;
}



?>
