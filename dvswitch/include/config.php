<?php
// Report all errors except E_NOTICE
// In production, consider using: error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
error_reporting(E_ALL & ~E_NOTICE);

// Load config from JSON with proper error handling
$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    die('Configuration file (config.json) missing.');
}

$configContent = @file_get_contents($configFile);
if ($configContent === false) {
    die('Unable to read configuration file.');
}

$config = json_decode($configContent, true);
$jsonError = json_last_error();

if ($jsonError !== JSON_ERROR_NONE) {
    $errorMsg = 'Configuration file contains invalid JSON.';
    switch ($jsonError) {
        case JSON_ERROR_DEPTH:
            $errorMsg .= ' Maximum stack depth exceeded.';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $errorMsg .= ' Underflow or the modes mismatch.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $errorMsg .= ' Unexpected control character found.';
            break;
        case JSON_ERROR_SYNTAX:
            $errorMsg .= ' Syntax error, malformed JSON.';
            break;
        case JSON_ERROR_UTF8:
            $errorMsg .= ' Malformed UTF-8 characters.';
            break;
        default:
            $errorMsg .= ' Unknown JSON error.';
    }
    die($errorMsg);
}

if ($config === null || !is_array($config)) {
    die('Configuration file (config.json) is invalid or empty.');
}

?>
