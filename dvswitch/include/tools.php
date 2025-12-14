<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function format_time(int $seconds): string {
	$secs = intval($seconds % 60);
	$mins = intval($seconds / 60 % 60);
	$hours = intval($seconds / 3600 % 24);
	$days = intval($seconds / 86400);
	$uptimeString = "";

	if ($days > 0) {
		$uptimeString .= $days;
		$uptimeString .= (($days == 1) ? " day" : " days");
	}
	if ($hours > 0) {
		$uptimeString .= (($days > 0) ? ", " : "") . $hours;
		$uptimeString .= (($hours == 1) ? " hr" : " hrs");
	}
	if ($mins > 0) {
		$uptimeString .= (($days > 0 || $hours > 0) ? ", " : "") . $mins;
		$uptimeString .= (($mins == 1) ? " min" : " mins");
	}
	if ($secs > 0) {
		$uptimeString .= (($days > 0 || $hours > 0 || $mins > 0) ? ", " : "") . $secs;
		$uptimeString .= (($secs == 1) ? " s" : " s");
	}
	return $uptimeString;
}

function format_uptime(float $float_secs): string {
    $seconds = (int)$float_secs;
    $secs = $seconds % 60;
    $mins = ((int)($seconds / 60)) % 60;
    $hours = ((int)($seconds / 3600)) % 24;
    $days = intval($seconds / 86400);
    $uptimeString = "";

    if ($days > 0) {
    $uptimeString .= $days;
    $uptimeString .= (($days == 1) ? " day" : " days");
    }
    if ($hours > 0) {
    $uptimeString .= (($days > 0) ? ", " : "") . $hours;
    $uptimeString .= (($hours == 1) ? " hr" : " hrs");
    }
    if ($mins > 0) {
    $uptimeString .= (($days > 0 || $hours > 0) ? ", " : "") . $mins;
    $uptimeString .= (($mins == 1) ? " min" : " mins");
    }
    return $uptimeString;
}

function startsWith(string $haystack, string $needle): bool {
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

/**
 * Checks if a process is running with caching
 * @param string $processName Process name to check
 * @param bool $full Use full process list (args) instead of just command names
 * @param bool $refresh Force refresh of cached process list
 * @return bool True if process is running
 */
function isProcessRunning(string $processName, bool $full = false, bool $refresh = false): bool {
  // Cache process list for 1 second to avoid excessive ps calls
  $cacheKey = 'process_list_' . ($full ? 'full' : 'comm');
  
  if ($refresh) {
    SimpleCache::clear($cacheKey);
  }
  
  $cached = SimpleCache::get($cacheKey, 1);
  if ($cached !== null && is_array($cached)) {
    $processes = $cached;
  } else {
    $processes = array();
    if ($full) {
      exec('ps -eo args', $processes);
    } else {
      exec('ps -eo comm', $processes);
    }
    SimpleCache::set($cacheKey, $processes, 1);
  }
  
  foreach ($processes as $processString) {
    if (strpos($processString, $processName) !== false) {
      return true;
    }
  }
  return false;
}

