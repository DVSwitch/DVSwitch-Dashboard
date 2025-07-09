<?php
// Report all errors except E_NOTICE
error_reporting(E_ALL & ~E_NOTICE);

// Load config from JSON
$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
if ($config === null) {
    die('Configuration file (config.json) missing or invalid.');
}

?>
