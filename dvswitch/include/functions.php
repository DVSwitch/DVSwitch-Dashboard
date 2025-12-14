<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/**
 * Simple file-based cache implementation
 */
class SimpleCache {
	private static string $cacheDir = '/tmp/dvswitch_cache';
	private static int $defaultTTL = 5; // 5 seconds default
	
	/**
	 * Initializes cache directory
	 */
	private static function initCache(): void {
		if (!is_dir(self::$cacheDir)) {
			@mkdir(self::$cacheDir, 0755, true);
		}
	}
	
	/**
	 * Gets cached value
	 * @param string $key Cache key
	 * @param int $ttl Time to live in seconds
	 * @return mixed|null Cached value or null if expired/not found
	 */
	public static function get(string $key, int $ttl = 0): mixed {
		self::initCache();
		if ($ttl === 0) {
			$ttl = self::$defaultTTL;
		}
		
		$cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
		if (!file_exists($cacheFile)) {
			return null;
		}
		
		$cacheData = @file_get_contents($cacheFile);
		if ($cacheData === false) {
			return null;
		}
		
		$data = @unserialize($cacheData);
		if ($data === false || !is_array($data) || !isset($data['expires']) || !isset($data['value'])) {
			return null;
		}
		
		if (time() > $data['expires']) {
			@unlink($cacheFile);
			return null;
		}
		
		return $data['value'];
	}
	
	/**
	 * Sets cached value
	 * @param string $key Cache key
	 * @param mixed $value Value to cache
	 * @param int $ttl Time to live in seconds
	 * @return bool True on success
	 */
	public static function set(string $key, mixed $value, int $ttl = 0): bool {
		self::initCache();
		if ($ttl === 0) {
			$ttl = self::$defaultTTL;
		}
		
		$cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
		$data = [
			'value' => $value,
			'expires' => time() + $ttl,
			'created' => time()
		];
		
		return @file_put_contents($cacheFile, serialize($data)) !== false;
	}
	
	/**
	 * Clears cache for a specific key or all cache
	 * @param string|null $key Cache key or null to clear all
	 * @return bool True on success
	 */
	public static function clear(?string $key = null): bool {
		self::initCache();
		if ($key === null) {
			$files = glob(self::$cacheDir . '/*.cache');
			foreach ($files as $file) {
				@unlink($file);
			}
			return true;
		}
		
		$cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
		if (file_exists($cacheFile)) {
			return @unlink($cacheFile);
		}
		return true;
	}
}

/**
 * Common log parsing function to reduce code duplication
 * @param string $logPath Path to log file
 * @param array $includePatterns Array of regex patterns to include
 * @param array $excludePatterns Array of regex patterns to exclude
 * @param int $maxLines Maximum lines to process
 * @param int $maxFileSize Maximum file size in bytes (default 50MB)
 * @param callable|null $lineProcessor Optional function to process each line
 * @return array Filtered log lines
 */
function parseLogFile(string $logPath, array $includePatterns, array $excludePatterns = [], int $maxLines = 10000, int $maxFileSize = 52428800, ?callable $lineProcessor = null): array {
    // Validate path
    $validatedPath = validateFilePath($logPath);
    if ($validatedPath === false || !is_file($validatedPath)) {
        return array();
    }
    
    // Check file size to avoid processing extremely large files
    $fileSize = filesize($validatedPath);
    if ($fileSize === false || $fileSize > $maxFileSize) {
        return array();
    }
    
    $filteredLines = array();
    $handle = fopen($validatedPath, 'r');
    
    if ($handle === false) {
        return array();
    }
    
    try {
        $lineCount = 0;
        
        while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
            $lineCount++;
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Check if line matches any include pattern
            $matchesInclude = false;
            foreach ($includePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $matchesInclude = true;
                    break;
                }
            }
            
            if (!$matchesInclude) {
                continue;
            }
            
            // Check if line matches any exclude pattern
            $matchesExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $matchesExclude = true;
                    break;
                }
            }
            
            if ($matchesExclude) {
                continue;
            }
            
            // Apply line processor if provided
            if ($lineProcessor !== null) {
                $line = $lineProcessor($line);
            }
            
            $filteredLines[] = $line;
        }
    } finally {
        fclose($handle);
    }
    
    return $filteredLines;
}

// Parse functions moved to file level to prevent redeclaration errors
if (!function_exists('parseMMDVMLog')) {
    /**
     * Parses MMDVM log file
     * @param string $logPath Path to log file
     * @return array Filtered log lines
     */
    function parseMMDVMLog(string $logPath): array {
        $includePatterns = ['/Begin|state|frames|from|end|watchdog|lost/'];
        $excludePatterns = ['/CSBK|overflow|Downlink/'];
        $lineProcessor = function($line) {
            return str_replace('I:', 'M:', $line);
        };
        
        $filteredLines = parseLogFile($logPath, $includePatterns, $excludePatterns, 10000, 52428800, $lineProcessor);
        
        // Keep only last 100 lines to prevent memory buildup
        if (count($filteredLines) > 100) {
            $filteredLines = array_slice($filteredLines, -100);
        }
        
        return $filteredLines;
    }
}

if (!function_exists('parseYSFGatewayLog')) {
    /**
     * Parses YSF Gateway log file
     * @param string $logPath Path to log file
     * @return array Filtered log lines
     */
    function parseYSFGatewayLog(string $logPath): array {
        $includePatterns = ['/onnection to|onnect to|Link|isconnect|Opening YSF network/'];
        $excludePatterns = ['/Linked to Disconnect|Linked to MMDVM|Link successful to MMDVM|\*Link/'];
        
        $filteredLines = parseLogFile($logPath, $includePatterns, $excludePatterns, 5000, 52428800);
        
        // Keep only last line to prevent memory buildup
        if (count($filteredLines) > 1) {
            $filteredLines = array_slice($filteredLines, -1);
        }
        
        return $filteredLines;
    }
}

if (!function_exists('parseP25GatewayLog')) {
    /**
     * Parses P25 Gateway log file
     * @param string $logPath Path to log file
     * @return array Filtered log lines
     */
    function parseP25GatewayLog(string $logPath): array {
        $includePatterns = ['/Link|Starting|Unlink|unlinking/'];
        $lineProcessor = function($line) {
            // Extract fields 2 onwards (skip first field)
            $fields = preg_split('/\s+/', $line);
            if (count($fields) > 1) {
                return implode(' ', array_slice($fields, 1));
            }
            return $line;
        };
        
        $filteredLines = parseLogFile($logPath, $includePatterns, [], 5000, 52428800, $lineProcessor);
        
        // Keep only last line to prevent memory buildup
        if (count($filteredLines) > 1) {
            $filteredLines = array_slice($filteredLines, -1);
        }
        
        return $filteredLines;
    }
}

if (!function_exists('parseNXDNGatewayLog')) {
    /**
     * Parses NXDN Gateway log file
     * @param string $logPath Path to log file
     * @return array Filtered log lines
     */
    function parseNXDNGatewayLog(string $logPath): array {
        $includePatterns = ['/Link|Starting|Unlink|unlinking/'];
        $lineProcessor = function($line) {
            // Extract fields 2 onwards (skip first field)
            $fields = preg_split('/\s+/', $line);
            if (count($fields) > 1) {
                return implode(' ', array_slice($fields, 1));
            }
            return $line;
        };
        
        $filteredLines = parseLogFile($logPath, $includePatterns, [], 5000, 52428800, $lineProcessor);
        
        // Keep only last line to prevent memory buildup
        if (count($filteredLines) > 1) {
            $filteredLines = array_slice($filteredLines, -1);
        }
        
        return $filteredLines;
    }
}

if (!function_exists('parseDAPNETGatewayLog')) {
    /**
     * Parses DAPNET Gateway log file
     * @param string $logPath Path to log file
     * @return array Filtered log lines
     */
    function parseDAPNETGatewayLog(string $logPath): array {
        $includePatterns = ['/Sending message/'];
        $lineProcessor = function($line) {
            // Extract fields 2 onwards (skip first field)
            $fields = preg_split('/\s+/', $line);
            if (count($fields) > 1) {
                return implode(' ', array_slice($fields, 1));
            }
            return $line;
        };
        
        $filteredLines = parseLogFile($logPath, $includePatterns, [], 5000, 52428800, $lineProcessor);
        
        // Keep only last 20 lines to prevent memory buildup
        if (count($filteredLines) > 20) {
            $filteredLines = array_slice($filteredLines, -20);
        }
        
        // Return in reverse order (like tac)
        return array_reverse($filteredLines);
    }
}

if (!function_exists('parseDMRGatewayStatus')) {
    /**
     * Parses DMR Gateway status from log file
     * @param string $logPath Path to log file
     * @param string $dmrserver DMR server name to search for
     * @return string|null Matching log line or null
     */
    function parseDMRGatewayStatus(string $logPath, string $dmrserver): ?string {
        // Validate path
        $validatedPath = validateFilePath($logPath);
        if ($validatedPath === false || !is_file($validatedPath)) {
            return null;
        }
        
        // Check file size to avoid processing extremely large files
        $fileSize = filesize($validatedPath);
        if ($fileSize === false || $fileSize > 52428800) { // 50MB limit
            return null;
        }
        
        $handle = fopen($validatedPath, 'r');
        if ($handle === false) {
            return null;
        }
        
        try {
            $lineCount = 0;
            $maxLines = 1000; // Limit total lines processed
            
            while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
                $lineCount++;
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                // Look for lines containing the DMR server
                if (strpos($line, $dmrserver) !== false) {
                    return $line;
                }
            }
        } finally {
            fclose($handle);
        }
        
        return null;
    }
}

/**
 * Validates and sanitizes file paths to prevent directory traversal attacks
 * @param string $path The file path to validate
 * @param array $allowedBasePaths Array of allowed base paths (whitelist)
 * @return string|false Returns the real path if valid, false otherwise
 */
function validateFilePath(string $path, array $allowedBasePaths = []): string|false {
    // Resolve the real path to prevent directory traversal
    $realPath = realpath($path);
    if ($realPath === false) {
        return false;
    }
    
    // If whitelist is provided, check against it
    if (!empty($allowedBasePaths)) {
        foreach ($allowedBasePaths as $basePath) {
            $realBasePath = realpath($basePath);
            if ($realBasePath !== false && strpos($realPath, $realBasePath) === 0) {
                return $realPath;
            }
        }
        return false;
    }
    
    return $realPath;
}

/**
 * Safely reads a file with path validation
 * @param string $filePath The file path to read
 * @param array $allowedBasePaths Array of allowed base paths
 * @return string|false File contents or false on failure
 */
function safeFileRead(string $filePath, array $allowedBasePaths = []): string|false {
    $validatedPath = validateFilePath($filePath, $allowedBasePaths);
    if ($validatedPath === false) {
        return false;
    }
    
    // Additional check: ensure it's a file, not a directory
    if (!is_file($validatedPath)) {
        return false;
    }
    
    return @file_get_contents($validatedPath);
}

/**
 * Extracts a substring between two delimiters
 * @param string $string The string to search in
 * @param string $start Start delimiter
 * @param string $end End delimiter
 * @return string Extracted substring or empty string if not found
 */
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

/**
 * Gets MMDVM configuration from INI file with path validation and caching
 * @return array Configuration array
 */
function getMMDVMConfig(): array {
    global $config;
	$cacheKey = 'mmdvm_config_' . ($config['MMDVMINIPATH'] ?? '') . '_' . ($config['MMDVMINIFILENAME'] ?? '');
	
	// Try cache first (5 second TTL for config files)
	$cached = SimpleCache::get($cacheKey, 5);
	if ($cached !== null) {
		return $cached;
	}
	
	$conf = array();
	
	// Validate and construct path
	$basePath = $config['MMDVMINIPATH'] ?? '';
	$fileName = $config['MMDVMINIFILENAME'] ?? '';
	if (empty($basePath) || empty($fileName)) {
		return $conf;
	}
	
	$filePath = rtrim($basePath, '/') . '/' . $fileName;
	$validatedPath = validateFilePath($filePath);
	
	if ($validatedPath !== false && is_file($validatedPath)) {
		$configs = fopen($validatedPath, 'r');
		if ($configs !== false) {
			while (($configLine = fgets($configs)) !== false) {
				array_push($conf, trim($configLine, " \t\n\r\0\x0B"));
			}
			fclose($configs);
		}
	}
	
	// Cache the result
	SimpleCache::set($cacheKey, $conf, 5);
	return $conf;
}

/**
 * Gets YSF Gateway configuration from INI file with path validation
 * @return array Configuration array
 */
function getYSFGatewayConfig(): array {
    global $config;
	$conf = array();
	
	// Validate and construct path
	$basePath = $config['YSFGATEWAYINIPATH'] ?? '';
	$fileName = $config['YSFGATEWAYINIFILENAME'] ?? '';
	if (empty($basePath) || empty($fileName)) {
		return $conf;
	}
	
	$filePath = rtrim($basePath, '/') . '/' . $fileName;
	$validatedPath = validateFilePath($filePath);
	
	if ($validatedPath !== false && is_file($validatedPath)) {
		$configs = fopen($validatedPath, 'r');
		if ($configs !== false) {
			while (($configLine = fgets($configs)) !== false) {
				array_push($conf, trim($configLine, " \t\n\r\0\x0B"));
			}
			fclose($configs);
		}
	}
	return $conf;
}

/**
 * Gets P25 Gateway configuration from INI file with path validation
 * @return array Configuration array
 */
function getP25GatewayConfig(): array {
    global $config;
	$conf = array();
	
	// Validate and construct path
	$basePath = $config['P25GATEWAYINIPATH'] ?? '';
	$fileName = $config['P25GATEWAYINIFILENAME'] ?? '';
	if (empty($basePath) || empty($fileName)) {
		return $conf;
	}
	
	$filePath = rtrim($basePath, '/') . '/' . $fileName;
	$validatedPath = validateFilePath($filePath);
	
	if ($validatedPath !== false && is_file($validatedPath)) {
		$configs = fopen($validatedPath, 'r');
		if ($configs !== false) {
			while (($configLine = fgets($configs)) !== false) {
				array_push($conf, trim($configLine, " \t\n\r\0\x0B"));
			}
			fclose($configs);
		}
	}
	return $conf;
}

/**
 * Gets NXDN Gateway configuration from INI file with path validation
 * @return array Configuration array
 */
function getNXDNGatewayConfig(): array {
    global $config;
	$conf = array();
	
	// Validate and construct path
	$basePath = $config['NXDNGATEWAYINIPATH'] ?? '';
	$fileName = $config['NXDNGATEWAYINIFILENAME'] ?? '';
	if (empty($basePath) || empty($fileName)) {
		return $conf;
	}
	
	$filePath = rtrim($basePath, '/') . '/' . $fileName;
	$validatedPath = validateFilePath($filePath);
	
	if ($validatedPath !== false && is_file($validatedPath)) {
		$configs = fopen($validatedPath, 'r');
		if ($configs !== false) {
			while (($configLine = fgets($configs)) !== false) {
				array_push($conf, trim($configLine, " \t\n\r\0\x0B"));
			}
			fclose($configs);
		}
	}
	return $conf;
}

/**
 * Gets DAPNET Gateway configuration with path validation
 * @return array Configuration array
 */
function getDAPNETGatewayConfig(): array {
	// loads /etc/dapnetgateway into array for further use
	$conf = array();
	$filePath = '/etc/dapnetgateway';
	$validatedPath = validateFilePath($filePath);
	
	if ($validatedPath !== false && is_file($validatedPath)) {
		$configs = fopen($validatedPath, 'r');
		if ($configs !== false) {
			while (($config = fgets($configs)) !== false) {
				array_push($conf, trim($config, " \t\n\r\0\x0B"));
			}
			fclose($configs);
		}
	}
	return $conf;
}

/**
 * Retrieves the corresponding config-entry within a [section]
 * @param string $section Section name
 * @param string $key Configuration key
 * @param array $configs Configuration array
 * @return string|null Configuration value or null if not found
 */
function getConfigItem(string $section, string $key, array $configs): ?string {
	$sectionSearch = "[" . $section . "]";
	$sectionpos = array_search($sectionSearch, $configs);
	
	if ($sectionpos === false) {
		return null;
	}
	
	$sectionpos++;
	$len = count($configs);
	$keyPrefix = $key . "=";
	
	while ($sectionpos < $len) {
		if (!isset($configs[$sectionpos])) {
			break;
		}
		
		$line = $configs[$sectionpos];
		if (startsWith($line, $keyPrefix)) {
			return substr($line, strlen($keyPrefix));
		}
		
		if (startsWith($line, "[")) {
			return null;
		}
		
		$sectionpos++;
	}

	return null;
}

/**
 * Gets enabled/disabled state of a mode
 * @param string $mode Mode name
 * @param array $mmdvmconfigs MMDVM configuration array
 * @return string|null "1" if enabled, null if disabled or not found
 */
function getEnabled(string $mode, array $mmdvmconfigs): ?string {
	return getConfigItem($mode, "Enable", $mmdvmconfigs);
}

/**
 * Displays mode status (enabled/disabled) with appropriate styling
 * @param string $mode Mode name
 * @param array $mmdvmconfigs MMDVM configuration array
 * @return void
 */
function showMode(string $mode, array $mmdvmconfigs): void {
    global $config;
	if (getEnabled($mode, $mmdvmconfigs) == 1) {
		if ($mode == "D-Star Network") {
            if (isProcessRunning($config['IRCDDBGATEWAY'])) {
                echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">";
			} else {
                echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">";
			}
		}
		elseif ($mode == "System Fusion Network") {
			if ( (isProcessRunning("MMDVM_Bridge")) || (getConfigItem("System Fusion Network", "GatewayAddress", $mmdvmconfigs) == '127.0.0.1' && isProcessRunning("YSFGateway"))) {
                    echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">";
				} else {
                    echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">";
				}
			}
		elseif ($mode == "P25 Network") {
			if (isProcessRunning("P25Gateway")) {
                echo "<td style=\"background:#12AD2A; color:#030; width:10%;\">";
			} else {
                echo "<td style=\"background:#b00; color:#f9f9f9; width:10%;\">";
			}
		}
		elseif ($mode == "NXDN Network") {
			if (isProcessRunning("NXDNGateway")) {
                echo "<td style=\"background:#12AD2A; color:#030; width:10%;\">";
			} else {
                echo "<td style=\"background:#b00; color:#f9f9f9; width:10%;\">";
			}
		}
		elseif ($mode == "DMR Network") {
			if (getConfigItem("DMR Network", "Address", $mmdvmconfigs) == '127.0.0.1') {
				if (isProcessRunning("DMRGateway") || isProcessRunning("MMDVM_Bridge") ) {
                    echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">";
				} else {
                    echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">";
				}
			}
			else {
				if (isProcessRunning("MMDVM_Bridge")) {
                    echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">";
				} else {
                    echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">";
				}
			}
		}
		else {
			if ($mode == "D-Star" || $mode == "DMR" || $mode == "System Fusion" || $mode == "P25" || $mode == "NXDN" ) {
				if (isProcessRunning("MMDVM_Bridge")) {
                    echo "<td style=\"background:#12AD2A; color:#030; width:8%;\">";
				} else {
                    echo "<td style=\"background:#b00; color:#f9f9f9; width:8%;\">";
				}
			}
		}
	}
	else {
        echo "<td style=\"background:#606060; color:#b0b0b0; width:8%;\">";
    }
    $mode = str_replace("System Fusion", "YSF", $mode);
    $mode = str_replace("Network", "Net", $mode);
    if (strpos($mode, 'YSF2') > -1) { $mode = str_replace(" Net", "", $mode); }
    if (strpos($mode, 'DMR2') > -1) { $mode = str_replace(" Net", "", $mode); }
    echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8')."</td>\n";
}

/**
 * Gets MMDVM log lines with path validation and caching
 * @return array Log lines
 */
function getMMDVMLog(): array {
    global $config;
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
	// Cache key based on date and log path
	$cacheKey = 'mmdvm_log_' . ($config['LOGPATH'] ?? '') . '_' . ($config['MMDVMLOGPREFIX'] ?? '') . '_' . gmdate("Y-m-d");
	
	// Try cache first (2 second TTL for log files - they change frequently)
	$cached = SimpleCache::get($cacheKey, 2);
	if ($cached !== null) {
		return $cached;
	}
	
	// Open Logfile and copy loglines into LogLines-Array()
	$logLines = array();
	$logLines1 = array();
	$logLines2 = array();
	
	$logPath = $config['LOGPATH'] ?? '';
	$logPrefix = $config['MMDVMLOGPREFIX'] ?? '';
	
	if (empty($logPath) || empty($logPrefix)) {
		return array();
	}
	
	// Validate base log path
	$baseLogPath = validateFilePath($logPath, ['/var/log']);
	if ($baseLogPath === false) {
		return array();
	}
	
	$todayLog = $baseLogPath . "/" . $logPrefix . "-" . gmdate("Y-m-d") . ".log";
	$validatedTodayLog = validateFilePath($todayLog, [$baseLogPath]);
	if ($validatedTodayLog !== false) {
		$logLines1 = parseMMDVMLog($validatedTodayLog);
	}
	
	if (count($logLines1) < 100) {
		$yesterdayLog = $baseLogPath . "/" . $logPrefix . "-" . gmdate("Y-m-d", time() - 86340) . ".log";
		$validatedYesterdayLog = validateFilePath($yesterdayLog, [$baseLogPath]);
		if ($validatedYesterdayLog !== false) {
			$logLines2 = parseMMDVMLog($validatedYesterdayLog);
		}
	}
	
	$logLines = array_merge($logLines1, $logLines2);
	$logLines = array_slice($logLines, -100);
	
	// Cache the result
	SimpleCache::set($cacheKey, $logLines, 2);
	return $logLines;
}

/**
 * Gets YSF Gateway log lines with path validation
 * @return array Log lines
 */
function getYSFGatewayLog(): array {
    global $config;
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
	$logPath = $config['LOGPATH'] ?? '';
	$logPrefix = $config['YSFGATEWAYLOGPREFIX'] ?? '';
	
	if (empty($logPath) || empty($logPrefix)) {
		return array();
	}
	
	// Validate base log path
	$baseLogPath = validateFilePath($logPath, ['/var/log']);
	if ($baseLogPath === false) {
		return array();
	}
	
	$logLines = array();
	$todayLog = $baseLogPath . "/" . $logPrefix . "-" . gmdate("Y-m-d") . ".log";
	$validatedTodayLog = validateFilePath($todayLog, [$baseLogPath]);
	if ($validatedTodayLog !== false) {
		$logLines = parseYSFGatewayLog($validatedTodayLog);
		$logLines = array_filter($logLines);
	}
	
	if (count($logLines) == 0) {
		$yesterdayLog = $baseLogPath . "/" . $logPrefix . "-" . gmdate("Y-m-d", time() - 86340) . ".log";
		$validatedYesterdayLog = validateFilePath($yesterdayLog, [$baseLogPath]);
		if ($validatedYesterdayLog !== false) {
			$logLines = parseYSFGatewayLog($validatedYesterdayLog);
			$logLines = array_filter($logLines);
		}
	}
	
	return array_filter($logLines);
}

/**
 * Gets P25 Gateway log lines with path validation
 * @return array Log lines
 */
function getP25GatewayLog(): array {
    global $config;
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
	$logPath = $config['LOGPATH'] ?? '';
	$logPrefix = $config['P25GATEWAYLOGPREFIX'] ?? '';
	
	if (empty($logPath) || empty($logPrefix)) {
		return array();
	}
	
	// Validate base log path
	$baseLogPath = validateFilePath($logPath, ['/var/log']);
	if ($baseLogPath === false) {
		return array();
	}
	
	$logLines = array();
	$todayLog = $baseLogPath . "/" . $logPrefix . "-" . gmdate("Y-m-d") . ".log";
	$validatedTodayLog = validateFilePath($todayLog, [$baseLogPath]);
	if ($validatedTodayLog !== false) {
		$logLines = parseP25GatewayLog($validatedTodayLog);
		$logLines = array_filter($logLines);
	}
	
	if (count($logLines) == 0) {
		$yesterdayLog = $baseLogPath . "/" . $logPrefix . "-" . gmdate("Y-m-d", time() - 86340) . ".log";
		$validatedYesterdayLog = validateFilePath($yesterdayLog, [$baseLogPath]);
		if ($validatedYesterdayLog !== false) {
			$logLines = parseP25GatewayLog($validatedYesterdayLog);
			$logLines = array_filter($logLines);
		}
	}
	
	return array_filter($logLines);
}

/**
 * Gets NXDN Gateway log lines with path validation
 * @return array Log lines
 */
function getNXDNGatewayLog(): array {
    global $config;
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
	$logPath = $config['LOGPATH'] ?? '';
	$logPrefix = $config['NXDNGATEWAYLOGPREFIX'] ?? '';
	
	if (empty($logPath) || empty($logPrefix)) {
		return array();
	}
	
	// Validate base log path
	$baseLogPath = validateFilePath($logPath, ['/var/log']);
	if ($baseLogPath === false) {
		return array();
	}
	
	$logLines = array();
	$todayLog = $baseLogPath . "/" . $logPrefix . "-" . gmdate("Y-m-d") . ".log";
	$validatedTodayLog = validateFilePath($todayLog, [$baseLogPath]);
	if ($validatedTodayLog !== false) {
		$logLines = parseNXDNGatewayLog($validatedTodayLog);
		$logLines = array_filter($logLines);
	}
	
	if (count($logLines) == 0) {
		$yesterdayLog = $baseLogPath . "/" . $logPrefix . "-" . gmdate("Y-m-d", time() - 86340) . ".log";
		$validatedYesterdayLog = validateFilePath($yesterdayLog, [$baseLogPath]);
		if ($validatedYesterdayLog !== false) {
			$logLines = parseNXDNGatewayLog($validatedYesterdayLog);
			$logLines = array_filter($logLines);
		}
	}
	
	return array_filter($logLines);
}

/**
 * Gets DAPNET Gateway log lines with path validation
 * @return array Log lines
 */
function getDAPNETGatewayLog(): array {
	// Check memory usage to prevent exhaustion
	if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB limit
		return array();
	}
	
	$baseLogPath = validateFilePath('/var/log/mmdvm', ['/var/log']);
	if ($baseLogPath === false) {
		return array();
	}
	
	$logLines = array();
	$todayLog = $baseLogPath . "/DAPNETGateway-" . gmdate("Y-m-d") . ".log";
	$validatedTodayLog = validateFilePath($todayLog, [$baseLogPath]);
	if ($validatedTodayLog !== false) {
		$logLines = parseDAPNETGatewayLog($validatedTodayLog);
		$logLines = array_filter($logLines);
	}
	
	if (count($logLines) == 0) {
		$yesterdayLog = $baseLogPath . "/DAPNETGateway-" . gmdate("Y-m-d", time() - 86340) . ".log";
		$validatedYesterdayLog = validateFilePath($yesterdayLog, [$baseLogPath]);
		if ($validatedYesterdayLog !== false) {
			$yesterdayLines = parseDAPNETGatewayLog($validatedYesterdayLog);
			$yesterdayLines = array_filter($yesterdayLines);
			$logLines = array_merge($logLines, $yesterdayLines);
		}
	}
	
	$logLines = array_slice($logLines, -20);
	return array_filter($logLines);
}

/**
 * Checks if a log line should be filtered out as invalid
 * @param string $logLine The log line to check
 * @return bool True if line should be skipped
 */
function isInvalidLogLine(string $logLine): bool {
	$invalidPatterns = [
		"BS_Dwn_Act",
		"invalid access",
		"NXDN, received RF header from",
		"TX state = ON",
		"received RF header for wrong repeater",
		"unable to decode the network CSBK",
		"overflow in the DMR slot RF queue",
		"non repeater RF header received",
		"Embedded Talker Alias",
		"DMR Talker Alias",
		"CSBK Preamble",
		"Preamble CSBK"
	];
	
	foreach ($invalidPatterns as $pattern) {
		if (strpos($logLine, $pattern) !== false) {
			return true;
		}
	}
	
	return false;
}

/**
 * Parses RSSI value and formats it
 * @param string $rssiRaw Raw RSSI string
 * @return string Formatted RSSI string
 */
function parseRSSI(string $rssiRaw): string {
	$rssi = substr($rssiRaw, 6);
	$rssi = substr($rssi, strrpos($rssi, '/') + 1); // average only
	$relint = intval($rssi) + 93;
	$signal = round(($relint / 6) + 9, 0);
	if ($signal < 0) $signal = 0;
	if ($signal > 9) $signal = 9;
	if ($relint > 0) {
		return "S{$signal}+{$relint}dB";
	} else {
		return "S{$signal}";
	}
}

/**
 * Parses duration, loss, BER, and RSSI from a log line
 * @param string $logLine The log line to parse
 * @param array $lineTokens Tokenized log line
 * @return array Array with keys: duration, loss, ber, rssi
 */
function parseDurationLossBER(string $logLine, array $lineTokens): array {
	$result = ['duration' => '', 'loss' => '', 'ber' => '', 'rssi' => ''];
	
	if (strpos($logLine, "RF user has timed out") !== false) {
		$result['duration'] = "TOut";
		$result['ber'] = "??%";
		return $result;
	}
	
	if (isset($lineTokens[2])) {
		$result['duration'] = strtok($lineTokens[2], " ");
	}
	
	if (isset($lineTokens[3])) {
		$result['loss'] = $lineTokens[3];
	}
	
	// If RF-Packet with no BER reported (e.g. YSF Wires-X commands) then RSSI is in LOSS position
	if (startsWith($result['loss'], "RSSI")) {
		$lineTokens[4] = $result['loss']; // move RSSI to the position expected on code below
		$result['loss'] = 'BER: ??%';
	}
	
	// If RF-Packet, no LOSS would be reported, so BER is in LOSS position
	if (startsWith($result['loss'], "BER")) {
		$result['ber'] = substr($result['loss'], 5);
		$result['loss'] = "0%";
		if (isset($lineTokens[4]) && startsWith($lineTokens[4], "RSSI")) {
			$result['rssi'] = parseRSSI($lineTokens[4]);
		}
	} else {
		$result['loss'] = strtok($result['loss'], " ");
		if (isset($lineTokens[4])) {
			$result['ber'] = substr($lineTokens[4], 5);
		}
	}
	
	return $result;
}

/**
 * Parses TX state = OFF line and extracts duration for DMR
 * @param string $logLine The log line to parse
 * @return string Duration string
 */
function parseTXStateOFF(string $logLine): string {
	$dvsm = substr($logLine, 27, strpos($logLine, ",") - 27);
	if ($dvsm == "DMR") {
		$wasPos = strpos($logLine, "was");
		$framesPos = strpos($logLine, "frames");
		if ($wasPos !== false && $framesPos !== false) {
			$duration = substr($logLine, $wasPos + 4, $framesPos - $wasPos - 5) * 0.059;
			return number_format($duration, 1, '.', '.');
		}
	}
	if ($dvsm == "YSF" || $dvsm == "NXDN" || $dvsm == "P25" || $dvsm == "D-Star") {
		return "---";
	}
	return "";
}

/**
 * Normalizes callsign value
 * @param string $callsign Raw callsign
 * @return string Normalized callsign
 */
function normalizeCallsign(string $callsign): string {
	if ($callsign == "0" || $callsign == "1234" || $callsign == "1234567") {
		return "N0CALL";
	}
	return trim($callsign);
}

/**
 * Gets mode-specific data (duration, loss, BER, RSSI, lat, long) based on mode
 * @param string $mode The mode (D-Star, DMR, YSF, etc.)
 * @param array $modeData Array containing mode-specific data variables
 * @return array Array with duration, loss, ber, rssi, lat, long
 */
function getModeSpecificData(string $mode, array $modeData): array {
	$result = ['duration' => '', 'loss' => '', 'ber' => '', 'rssi' => '', 'lat' => '', 'long' => ''];
	
	switch ($mode) {
		case "D-Star":
			$result['duration'] = $modeData['dstarduration'] ?? '';
			$result['loss'] = $modeData['dstarloss'] ?? '';
			$result['ber'] = $modeData['dstarber'] ?? '';
			$result['rssi'] = $modeData['dstarrssi'] ?? '';
			break;
		case "DMR":
			$result['duration'] = $modeData['dmrduration'] ?? '';
			$result['loss'] = (is_string($modeData['dmrloss'] ?? '') && strlen($modeData['dmrloss'])) ? $modeData['dmrloss'] : "---";
			$result['ber'] = (is_string($modeData['dmrber'] ?? '') && strlen($modeData['dmrber'])) ? $modeData['dmrber'] : "---";
			break;
		case "DMR Slot 1":
			$result['duration'] = $modeData['ts1duration'] ?? '';
			$result['loss'] = $modeData['ts1loss'] ?? '';
			$result['ber'] = $modeData['ts1ber'] ?? '';
			$result['rssi'] = $modeData['ts1rssi'] ?? '';
			break;
		case "DMR Slot 2":
			$result['duration'] = $modeData['ts2duration'] ?? '';
			$result['loss'] = $modeData['ts2loss'] ?? '';
			$result['ber'] = $modeData['ts2ber'] ?? '';
			$result['rssi'] = $modeData['ts2rssi'] ?? '';
			break;
		case "YSF":
			$result['duration'] = $modeData['ysfduration'] ?? '';
			$result['loss'] = $modeData['ysfloss'] ?? '';
			$result['ber'] = $modeData['ysfber'] ?? '';
			$result['rssi'] = $modeData['ysfrssi'] ?? '';
			$result['lat'] = $modeData['ysflat'] ?? '';
			$result['long'] = $modeData['ysflong'] ?? '';
			break;
		case "P25":
			$result['duration'] = $modeData['p25duration'] ?? '';
			$result['loss'] = (is_string($modeData['p25loss'] ?? '') && strlen($modeData['p25loss'])) ? $modeData['p25loss'] : "---";
			$result['ber'] = (is_string($modeData['p25ber'] ?? '') && strlen($modeData['p25ber'])) ? $modeData['p25ber'] : "---";
			$result['rssi'] = $modeData['p25rssi'] ?? '';
			break;
		case "NXDN":
			$result['duration'] = $modeData['nxdnduration'] ?? '';
			$result['loss'] = (is_string($modeData['nxdnloss'] ?? '') && strlen($modeData['nxdnloss'])) ? $modeData['nxdnloss'] : "---";
			$result['ber'] = (is_string($modeData['nxdnber'] ?? '') && strlen($modeData['nxdnber'])) ? $modeData['nxdnber'] : "---";
			$result['rssi'] = $modeData['nxdnrssi'] ?? '';
			break;
		case "POCSAG":
			$result['duration'] = "0.0";
			$result['loss'] = "0%";
			$result['ber'] = "0.0%";
			break;
	}
	
	return $result;
}

/**
 * Gets heard list from log lines - refactored version
 * @param array $logLines Array of log lines
 * @return array Array of heard entries
 */
function getHeardList(array $logLines): array {
	$heardList = array();
	
	// Mode-specific data storage
	$modeData = [
		'ts1duration' => '', 'ts1loss' => '', 'ts1ber' => '', 'ts1rssi' => '',
		'ts2duration' => '', 'ts2loss' => '', 'ts2ber' => '', 'ts2rssi' => '',
		'dstarduration' => '', 'dstarloss' => '', 'dstarber' => '', 'dstarrssi' => '',
		'ysfduration' => '', 'ysfloss' => '', 'ysfber' => '', 'ysfrssi' => '',
		'p25duration' => '', 'p25loss' => '', 'p25ber' => '', 'p25rssi' => '',
		'nxdnduration' => '', 'nxdnloss' => '', 'nxdnber' => '', 'nxdnrssi' => '',
		'ysflat' => '', 'ysflong' => ''
	];
	
	// Current entry data
	$currentEntry = [
		'mode' => '', 'callsign' => '', 'id' => '', 'target' => '',
		'source' => '', 'timestamp' => '', 'duration' => '', 'loss' => '',
		'ber' => '', 'lat' => '', 'long' => ''
	];

	foreach ($logLines as $logLine) {
		// Skip invalid lines
		if (isInvalidLogLine($logLine)) {
			continue;
		}
		
		// Handle TX state, GPS, end of transmission, etc.
		if (strpos($logLine, "TX state") !== false || strpos($logLine, "GPS Position") !== false || 
			strpos($logLine, "end of") !== false || strpos($logLine, "watchdog has expired") !== false || 
			strpos($logLine, "ended RF data") !== false || strpos($logLine, "ended network") !== false || 
			strpos($logLine, "RF user has timed out") !== false || strpos($logLine, "transmission lost") !== false || 
			strpos($logLine, "POCSAG") !== false) {
			
			if (strpos($logLine, "TX state = OFF") !== false) {
				$dvsm = substr($logLine, 27, strpos($logLine, ",") - 27);
				$duration = parseTXStateOFF($logLine);
				$ber = "---";
				$loss = "---";
			} else {
				$lineTokens = explode(", ", $logLine);
				$parsed = parseDurationLossBER($logLine, $lineTokens);
				$duration = $parsed['duration'];
				$loss = $parsed['loss'];
				$ber = $parsed['ber'];
				$rssi = $parsed['rssi'];
			}
			
			// Handle ended RF data, network, or GPS Position
			if (strpos($logLine, "ended RF data") !== false || strpos($logLine, "ended network") !== false || strpos($logLine, "GPS Position") !== false) {
				$modeFromLine = substr($logLine, 27, strpos($logLine, ",") - 27);
				switch ($modeFromLine) {
					case "DMR Slot 1":
						$modeData['ts1duration'] = "DMR Data";
						break;
					case "DMR Slot 2":
						$modeData['ts2duration'] = "DMR Data";
						break;
					case "YSF":
						$modeData['ysfduration'] = "GPS";
						$modeData['ysflat'] = trim(substr($logLine, strpos($logLine, "lat=") + 4, strpos($logLine, "long=") - strpos($logLine, "lat=") - 4));
						$modeData['ysflong'] = trim(substr($logLine, strpos($logLine, "long=") + 5));
						break;
				}
			} else {
				// Store mode-specific data
				$modeFromLine = substr($logLine, 27, strpos($logLine, ",") - 27);
				switch ($modeFromLine) {
					case "D-Star":
						$modeData['dstarduration'] = $duration;
						$modeData['dstarloss'] = $loss;
						$modeData['dstarber'] = $ber;
						break;
					case "DMR":
						$modeData['dmrduration'] = $duration;
						$modeData['dmrloss'] = $loss;
						$modeData['dmrber'] = $ber;
						break;
					case "DMR Slot 1":
						$modeData['ts1duration'] = $duration;
						$modeData['ts1loss'] = $loss;
						$modeData['ts1ber'] = $ber;
						$modeData['ts1rssi'] = $rssi ?? '';
						break;
					case "DMR Slot 2":
						$modeData['ts2duration'] = $duration;
						$modeData['ts2loss'] = $loss;
						$modeData['ts2ber'] = $ber;
						$modeData['ts2rssi'] = $rssi ?? '';
						break;
					case "YSF":
						$modeData['ysfduration'] = $duration;
						$modeData['ysfloss'] = $loss;
						$modeData['ysfber'] = $ber;
						$modeData['ysfrssi'] = $rssi ?? '';
						break;
					case "P25":
						$modeData['p25duration'] = $duration;
						$modeData['p25loss'] = $loss;
						$modeData['p25ber'] = $ber;
						$modeData['p25rssi'] = $rssi ?? '';
						break;
					case "NXDN":
						$modeData['nxdnduration'] = $duration;
						$modeData['nxdnloss'] = $loss;
						$modeData['nxdnber'] = $ber;
						$modeData['nxdnrssi'] = $rssi ?? '';
						break;
				}
			}
		}
		
		// Handle Begin TX
		if (strpos($logLine, "Begin TX") !== false) {
			$currentEntry['mode'] = substr($logLine, 27, strpos($logLine, ",") - 27);
			$currentEntry['callsign'] = trim(substr($logLine, strpos($logLine, "metadata=") + 9));
			$dstPos = strpos($logLine, "dst=");
			$slotPos = strpos($logLine, "slot=");
			if ($dstPos !== false && $slotPos !== false) {
				$currentEntry['target'] = "TG " . substr($logLine, $dstPos + 4, $slotPos - $dstPos - 4);
			} else {
				$currentEntry['target'] = "";
			}
			$currentEntry['source'] = "LNet";
			$currentEntry['timestamp'] = substr($logLine, 3, 19);
			$currentEntry['id'] = "";
		}
		
		// Handle "from" lines
		if (strpos($logLine, "from") !== false && strpos($logLine, "GPS Position") === false) {
			$currentEntry['mode'] = substr($logLine, 27, strpos($logLine, ",") - 27);
			$currentEntry['timestamp'] = substr($logLine, 3, 19);
			$fromPos = strpos($logLine, "from");
			$toPos = strpos($logLine, "to");
			$callsign2 = "";
			if ($fromPos !== false && $toPos !== false) {
				$callsign2 = substr($logLine, $fromPos + 5, $toPos - $fromPos - 6);
			}
			$currentEntry['callsign'] = normalizeCallsign($callsign2);
			
			if (strpos($callsign2, "/") > 0) {
				$currentEntry['callsign'] = substr($callsign2, 0, strpos($callsign2, "/"));
			}
			$currentEntry['callsign'] = trim($currentEntry['callsign']);
			
			$currentEntry['id'] = "";
			if ($currentEntry['mode'] == "D-Star") {
				$slashPos = strpos($callsign2, "/");
				if ($slashPos !== false) {
					$currentEntry['id'] = substr($callsign2, $slashPos + 1);
				}
			}
			
			$toPos = strpos($logLine, "to");
			if ($toPos !== false) {
				$currentEntry['target'] = trim(substr($logLine, $toPos + 3));
				// Handle more verbose logging from MMDVM_Bridge
				if (strpos($currentEntry['target'], ",") !== false) {
					$currentEntry['target'] = explode(",", $currentEntry['target'])[0];
				}
			} else {
				$currentEntry['target'] = "";
			}
			
			$currentEntry['source'] = "Net";
			
			// Get mode-specific data
			$modeSpecific = getModeSpecificData($currentEntry['mode'], $modeData);
			$currentEntry['duration'] = $modeSpecific['duration'];
			$currentEntry['loss'] = $modeSpecific['loss'];
			$currentEntry['ber'] = $modeSpecific['ber'];
			$currentEntry['lat'] = $modeSpecific['lat'];
			$currentEntry['long'] = $modeSpecific['long'];
			
			// Handle special cases
			if ($currentEntry['mode'] == "P25") {
				if ($currentEntry['source'] == "Net" && $currentEntry['target'] == "TG 10") {
					$currentEntry['callsign'] = "PARROT";
				}
				if ($currentEntry['source'] == "Net" && $currentEntry['callsign'] == "10999") {
					$currentEntry['callsign'] = "MMDVM";
				}
			} elseif ($currentEntry['mode'] == "NXDN") {
				if ($currentEntry['source'] == "Net" && $currentEntry['target'] == "TG 10") {
					$currentEntry['callsign'] = "PARROT";
				}
			} elseif ($currentEntry['mode'] == "POCSAG") {
				$currentEntry['callsign'] = "DAPNET";
				$currentEntry['target'] = "DAPNET User";
				$currentEntry['duration'] = "0.0";
				$currentEntry['loss'] = "0%";
				$currentEntry['ber'] = "0.0%";
			} elseif ($currentEntry['mode'] == "YSF") {
				$currentEntry['target'] = preg_replace('!\s+!', ' ', $currentEntry['target']);
			}
			
			// Validate and add to heard list
			$callsign = is_string($currentEntry['callsign']) ? $currentEntry['callsign'] : (string)$currentEntry['callsign'];
			if (strlen($callsign) < 11) {
				array_push($heardList, array(
					$currentEntry['timestamp'],
					$currentEntry['mode'],
					$currentEntry['callsign'],
					$currentEntry['id'],
					$currentEntry['target'],
					$currentEntry['source'],
					$currentEntry['duration'],
					$currentEntry['loss'],
					$currentEntry['ber'],
					$currentEntry['lat'],
					$currentEntry['long']
				));
			}
		}
	}
	
	return $heardList;
}

/**
 * Gets last heard list from log lines, removing duplicates
 * @param array $logLines Array of log lines
 * @return array Array of unique last heard entries
 */
function getLastHeard(array $logLines): array {
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

/**
 * Gets the actual current mode of the repeater
 * @param array $metaLastHeard Array of last heard entries
 * @param array $mmdvmconfigs MMDVM configuration array
 * @return string Current mode or "idle" if no active transmission
 */
function getActualMode(array $metaLastHeard, array $mmdvmconfigs): string {
    $utc_tz = new DateTimeZone('UTC');
    $local_tz = new DateTimeZone(date_default_timezone_get());
    $listElem = $metaLastHeard[0];
    $timestamp = new DateTime($listElem[0], $utc_tz);
    $timestamp->setTimeZone($local_tz);
    $mode = $listElem[1];
    if (startsWith($mode, "DMR")) {
	$mode = "DMR";
    }

    $now = new DateTime();
    $hangtime = "0";
    $timestamp->add(new DateInterval('PT' . $hangtime . 'S'));

    if ($listElem[6] != null) { // if terminated, hangtime counts after end of transmission
	// Hangtime calculation can be added here if needed
    } else { // if not terminated, always return mode
	return $mode;
    }
    if ($now->format('U') > $timestamp->format('U')) {
	return "idle";
    } else {
	return $mode;
    }
}

/**
 * Gets D-Star link states from log file
 * @return string HTML formatted link status
 */
function getDSTARLinks(): string {
	global $config;
	$linkLogPath = $config['LINKLOGPATH'] ?? '';
	if (empty($linkLogPath)) {
		return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
	}
	
	$linkFile = $linkLogPath . "/Links.log";
	$validatedPath = validateFilePath($linkFile, [$linkLogPath]);
	if ($validatedPath === false || filesize($validatedPath) == 0) {
		return "<span style=\"color:#b0b0b0;\"><b>Not Linked</b></span>";
	}
	
	if ($linkLog = fopen($validatedPath, 'r')) {
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
			// Sanitize dynamic content to prevent XSS
			$out = "Linked to <span style=\"color:#b5651d;font-weight:bold;\">" . htmlspecialchars($linkDest, ENT_QUOTES, 'UTF-8') . "</span><br />\n(<span style=\"color:green;\"><b>" . htmlspecialchars($protocol, ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($linkDir, ENT_QUOTES, 'UTF-8') . "</b></span>)";
		}
	}
	fclose($linkLog);
	return $out;
}

/**
 * Gets the actual link state for a specific mode
 * @param array $logLines Array of log lines
 * @param string $mode Mode name (D-Star, DMR Slot 1, DMR Slot 2, YSF, NXDN, P25)
 * @return string HTML formatted link status
 */
function getActualLink(array $logLines, string $mode): string {
	global $config;
	//M: 2016-05-02 07:04:10.504 D-Star link status set to "Verlinkt zu DCS002 S"
	//M: 2016-04-03 16:16:18.638 DMR Slot 2, received network voice header from 4000 to 2625094
	//M: 2016-04-03 19:30:03.099 DMR Slot 2, received network voice header from 4020 to 2625094
	//M: 2017-09-03 08:10:42.862 DMR Slot 2, received network data header from M6JQD to TG 9, 5 blocks
	switch ($mode) {
    case "D-Star":
    	if (isProcessRunning($config['IRCDDBGATEWAY'])) {
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
                  return "Linked to <span style=\"color:#b5651d;font-weight:bold;\">TG ".htmlspecialchars($to, ENT_QUOTES, 'UTF-8')."</span>";
               }
               if (strpos($logLine,"Linked at start")) {
                  $to = preg_replace('/[^0-9]/', '', substr($logLine, 55, 5));
                  $to = preg_replace('/[^0-9]/', '', $to);
                  return "Linked to <span style=\"color:#b5651d;font-weight:bold;\">TG ".htmlspecialchars($to, ENT_QUOTES, 'UTF-8')."</span>";
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
		  return "Linked to <span style=\"color:#b5651d;font-weight:bold;\">TG ".htmlspecialchars($to, ENT_QUOTES, 'UTF-8')."</span>";
               }
               if (strpos($logLine,"Linked at startup to")) {
		  $to = preg_replace('/[^0-9]/', '', substr($logLine, 55, 5));
		  $to = preg_replace('/[^0-9]/', '', $to);
		  return "Linked to <span style=\"color:#b5651d;font-weight:bold;\">TG ".htmlspecialchars($to, ENT_QUOTES, 'UTF-8')."</span>";
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

/**
 * Gets the actual reflector for a specific mode
 * @param array $logLines Array of log lines
 * @param string $mode Mode name
 * @return string Reflector identifier or "No Ref"
 */
function getActualReflector(array $logLines, string $mode): string {
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
        if ($config['DISPLAYNAME'] == "YES"  && file_exists($config['DMRIDDATPATH']."/DMRIds.dat") && ! empty($config['DMRIDDATPATH']."/DMRIds.dat")) {
	     $dmrIDline = file_get_contents($config['DMRIDDATPATH']."/DMRIds.dat");
	    }
	}
}

/**
 * Gets ABInfo from JSON file with validation and caching
 * @param string $filename Path to JSON file (should already be validated)
 * @return array|null Decoded JSON data or null on error
 */
function getABInfo(string $filename): ?array {
	// Cache key based on filename and file modification time for freshness
	$cacheKey = 'abinfo_' . md5($filename);
	$fileMTime = @filemtime($filename);
	if ($fileMTime !== false) {
		$cacheKey .= '_' . $fileMTime;
	}
	
	// Try cache first (1 second TTL - ABInfo changes frequently)
	$cached = SimpleCache::get($cacheKey, 1);
	if ($cached !== null && is_array($cached)) {
		return $cached;
	}
	
	// File should already be validated, but double-check it exists
	if (!is_file($filename)) {
		return null;
	}
	
	$json = @file_get_contents($filename);
	if ($json === false) {
		return null;
	}
	
	$json_data = json_decode($json, true);
	$jsonError = json_last_error();
	
	if ($jsonError !== JSON_ERROR_NONE || !is_array($json_data)) {
		return null;
	}
	
	// Cache the result
	SimpleCache::set($cacheKey, $json_data, 1);
	return $json_data;
}

/**
 * Checks if an IP address matches a CIDR range
 * @param string $ip IP address to check
 * @param string $cidr CIDR notation (e.g., "192.168.1.0/24")
 * @return bool True if IP matches CIDR range
 */
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

/**
 * Gets DMR Gateway status with path validation
 * @param string $dmrserver DMR server name
 * @return string|null HTML table row or null
 */
function getDMRGstat(string $dmrserver): ?string {
	// Validate base log path
	$baseLogPath = validateFilePath('/var/log/mmdvm', ['/var/log']);
	if ($baseLogPath === false) {
		return null;
	}
	
	// PHP replacement for shell pipeline - memory optimized
	$todayLog = $baseLogPath . "/DMRGateway-" . gmdate("Y-m-d") . ".log";
	$validatedTodayLog = validateFilePath($todayLog, [$baseLogPath]);
	$dmrstatus = null;
	
	if ($validatedTodayLog !== false) {
		$dmrstatus = parseDMRGatewayStatus($validatedTodayLog, $dmrserver);
	}
	
	if (empty($dmrstatus)) {
		$yesterdayLog = $baseLogPath . "/DMRGateway-" . gmdate("Y-m-d", time() - 86340) . ".log";
		$validatedYesterdayLog = validateFilePath($yesterdayLog, [$baseLogPath]);
		if ($validatedYesterdayLog !== false) {
			$dmrstatus = parseDMRGatewayStatus($validatedYesterdayLog, $dmrserver);
		}
	}
	
	$dmrserver = str_replace('_', ' ', $dmrserver);
	$dmrserver = is_string($dmrserver) ? $dmrserver : (string)$dmrserver;
	if (strlen($dmrserver) > 19) { 
		$dmrserver = substr($dmrserver, 0, 17) . '..'; 
	}
	
	if ($dmrstatus !== null && strpos($dmrstatus, 'Logged') !== false) {
		return "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b5651d;font-weight: bold\">".htmlspecialchars($dmrserver, ENT_QUOTES, 'UTF-8')."</span></td></tr>\n";
	} else if ($dmrstatus !== null && (strpos($dmrstatus, 'Opening') !== false || strpos($dmrstatus, 'Closing') !== false || strpos($dmrstatus, 'Connection') !== false)) {
		return "<tr><td  style=\"background: #ffffed;\" colspan=\"2\"><span style=\"color:#b0b0b0;font-weight: bold\">".htmlspecialchars($dmrserver, ENT_QUOTES, 'UTF-8')."</span></td></tr>\n";
	}
	
	return null;
}


/**
 * Gets the user's IP address, handling proxy headers
 * @return string|false IP address or false on failure
 */
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

    // If HTTP_X_FORWARDED_FOR == server ip or invalid IP
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
