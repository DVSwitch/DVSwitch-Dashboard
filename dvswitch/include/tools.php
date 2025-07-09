<?php
declare(strict_types=1);

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

function isProcessRunning(string $processName, bool $full = false, bool $refresh = false): bool {
  if ($full) {
    static $processes_full = array();
    if ($refresh) $processes_full = array();
    if (empty($processes_full))
      exec('ps -eo args', $processes_full);
  } else {
    static $processes = array();
    if ($refresh) $processes = array();
    if (empty($processes))
      exec('ps -eo comm', $processes);
  }
  foreach (($full ? $processes_full : $processes) as $processString) {
    if (strpos($processString, $processName) !== false)
      return true;
  }
  return false;
}

