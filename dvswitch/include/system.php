<?php
declare(strict_types=1);

include_once dirname(dirname(__FILE__)).'/include/tools.php';
include_once dirname(dirname(__FILE__)).'/include/config.php';
include_once dirname(dirname(__FILE__)).'/include/functions.php';

// Initialize MMDVM configs
if (!isset($mmdvmconfigs)) {
    $mmdvmconfigs = getMMDVMConfig();
}

// Ensure we have a valid config array
if (!is_array($mmdvmconfigs)) {
    $mmdvmconfigs = [];
}

// Safe file reading function for system files
function safeReadSystemFile(string $filePath): string|false {
    // Validate path - only allow specific system paths
    $allowedPaths = ['/proc/uptime', '/sys/class/thermal/thermal_zone0/temp'];
    $validatedPath = validateFilePath($filePath, $allowedPaths);
    if ($validatedPath === false || !is_file($validatedPath)) {
        return false;
    }
    return @file_get_contents($validatedPath);
}

// Get uptime with safe file reading
$rawuptime = safeReadSystemFile('/proc/uptime');
$uptime = "Unknown";
if ($rawuptime !== false) {
    $spacePos = strpos($rawuptime, " ");
    if ($spacePos !== false) {
        $uptime = format_uptime((float)substr($rawuptime, 0, $spacePos));
    }
}

// Get memory usage using a simpler approach
$free_output = shell_exec('free -m');
$free_mem = "Unknown";
if ($free_output !== null && $free_output !== false) {
    $lines = explode("\n", $free_output);
    if (isset($lines[1])) {
        $parts = preg_split('/\s+/', trim($lines[1]));
        if (isset($parts[1]) && isset($parts[2]) && $parts[1] > 0) {
            $free_mem = round(($parts[2] * 100) / $parts[1]) . "%";
        }
    }
}

// Get disk usage using a simpler approach
$df_output = shell_exec('df -h ' . escapeshellarg('/'));
$disk_used = "Unknown";
if ($df_output !== null && $df_output !== false) {
    $lines = explode("\n", $df_output);
    if (isset($lines[1])) {
        $parts = preg_split('/\s+/', trim($lines[1]));
        if (isset($parts[4])) {
            $disk_used = htmlspecialchars($parts[4], ENT_QUOTES, 'UTF-8');
        }
    }
}

$cpuLoad = sys_getloadavg() ?: [0, 0, 0];
$cpuTempHTML = "<td style=\"background: #white\">---</td>\n";

$tempFilePath = '/sys/class/thermal/thermal_zone0/temp';
if (file_exists($tempFilePath)) {
    $cpuTempCRaw = safeReadSystemFile($tempFilePath);
    if ($cpuTempCRaw !== false && $cpuTempCRaw !== "") {
        $cpuTempCRaw = trim($cpuTempCRaw);
        if (is_numeric($cpuTempCRaw)) {
            $cpuTempCRaw = (int)$cpuTempCRaw;
            if ($cpuTempCRaw > 1000) { 
                $cpuTempC = round($cpuTempCRaw / 1000); 
            } else { 
                $cpuTempC = round($cpuTempCRaw); 
            }
            $cpuTempF = round($cpuTempC * 9 / 5 + 32);
            $cpuTempC_escaped = htmlspecialchars((string)$cpuTempC, ENT_QUOTES, 'UTF-8');
            $cpuTempF_escaped = htmlspecialchars((string)$cpuTempF, ENT_QUOTES, 'UTF-8');
            
            if ($cpuTempC < 55) { 
                $cpuTempHTML = "<td style=\"background: #1d1\">{$cpuTempC_escaped}&deg;C / {$cpuTempF_escaped}&deg;F</td>\n"; 
            } elseif ($cpuTempC >= 55 && $cpuTempC < 70) { 
                $cpuTempHTML = "<td style=\"background: #fa0\">{$cpuTempC_escaped}&deg;C / {$cpuTempF_escaped}&deg;F</td>\n"; 
            } else { 
                $cpuTempHTML = "<td style=\"background: #f00\">{$cpuTempC_escaped}&deg;C / {$cpuTempF_escaped}&deg;F</td>\n"; 
            }
        }
    }
}
?>
<fieldset style="box-shadow:0 0 10px #999;background-color:#e8e8e8e8;width:855px;margin-top:8px;;margin-bottom:8px;margin-left:6px;margin-right:0px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
<table style="margin-top:2px;">
    <tr><th style="padding-top:4px;padding-bottom:4px;">Modes</th>
    <?php showMode("DMR", $mmdvmconfigs);?><?php showMode("System Fusion", $mmdvmconfigs);?>
    <?php showMode("NXDN", $mmdvmconfigs);?><?php showMode("P25", $mmdvmconfigs);?>
    <?php showMode("D-Star", $mmdvmconfigs);?>
    <th style="padding-top:4px;padding-bottom:4px;">Networks</th>
  <?php showMode("DMR Network", $mmdvmconfigs);?><?php showMode("System Fusion Network", $mmdvmconfigs);?>
  <?php showMode("NXDN Network", $mmdvmconfigs);?><?php showMode("P25 Network", $mmdvmconfigs);?>
  <?php showMode("D-Star Network", $mmdvmconfigs);?></tr>
</table>

</fieldset>
<span class="section-header" style="font-weight: bold;font-size:13px;">Hardware Info</span>
<fieldset style="box-shadow:0 0 10px #999;background-color:#e8e8e8e8; width:855px;margin-top:8px;margin-left:6px;margin-right:0px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
<table style="margin-top:2px;" class="sys-table">
  <tr>
    <th class="sys-hostname">Hostname<br/><span style="font-weight: bold;color:#effd5f;font-size:10px;">IP: <?php $ip_addrs = preg_split('/\s+/', trim(exec('hostname -I'))); echo implode('<br />', array_map(function($ip) { return htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); }, $ip_addrs)); ?></span></th>
    <th class="sys-kernel"><b>Kernel<br/>release</b></th>
    <th class="sys-platform" colspan="2">Platform <br><span style="font-weight: bold;color:#effd5f;font-size:12px;">Uptime: <?php echo htmlspecialchars($uptime, ENT_QUOTES, 'UTF-8'); ?></span></th>
    <th class="sys-disk"><span><b>Disk<br> used</b></span></th>
    <th class="sys-memory"><span><b>Memory<br> used</b></span></th>
    <th class="sys-cpu"><span><b>CPU Load</b></span></th>
<?php if (file_exists('/sys/class/thermal/thermal_zone0/temp')) {
    echo "<th><span><b>CPU Temp</b></span></th>"; }
?>
  </tr>
  <tr height="24px">
    <td class="sys-hostname"><?php echo htmlspecialchars(php_uname('n'), ENT_QUOTES, 'UTF-8');?></td>
    <td class="sys-kernel"><?php echo htmlspecialchars(php_uname('r'), ENT_QUOTES, 'UTF-8');?></td>
    <td class="sys-platform" colspan="2"><?php 
        $platformScript = '/usr/local/sbin/platformDetect.sh';
        $platformOutput = '';
        if (file_exists($platformScript) && is_executable($platformScript)) {
            $platformOutput = exec(escapeshellarg($platformScript));
        }
        echo htmlspecialchars($platformOutput ?: 'Unknown', ENT_QUOTES, 'UTF-8');
    ?></td>
    <td class="sys-disk"><?php echo htmlspecialchars($disk_used, ENT_QUOTES, 'UTF-8');?></td>
    <td class="sys-memory"><?php echo htmlspecialchars($free_mem, ENT_QUOTES, 'UTF-8');?></td>
    <td class="sys-cpu"><?php echo htmlspecialchars((string)round($cpuLoad[0],1), ENT_QUOTES, 'UTF-8');?> / <?php echo htmlspecialchars((string)round($cpuLoad[1],1), ENT_QUOTES, 'UTF-8');?> / <?php echo htmlspecialchars((string)round($cpuLoad[2],1), ENT_QUOTES, 'UTF-8');?></td>
   <?php if (file_exists('/sys/class/thermal/thermal_zone0/temp')) { echo $cpuTempHTML; } ?>
  </tr>
</table>
</fieldset>
<br>

<?php
// System utility functions
function getSystemUptime(): string {
    $rawuptime = safeReadSystemFile('/proc/uptime');
    if ($rawuptime === false) {
        return "Unknown";
    }
    $spacePos = strpos($rawuptime, " ");
    if ($spacePos === false) {
        return "Unknown";
    }
    return format_uptime((float)substr($rawuptime, 0, $spacePos));
}

function getMemoryUsage(): string {
    $free_output = shell_exec('free -m');
    if ($free_output) {
        $lines = explode("\n", $free_output);
        if (isset($lines[1])) {
            $parts = preg_split('/\s+/', trim($lines[1]));
            if (isset($parts[1]) && isset($parts[2]) && $parts[1] > 0) {
                return round(($parts[2] * 100) / $parts[1]) . "%";
            }
        }
    }
    return "Unknown";
}

function getDiskUsage(): string {
    $df_output = shell_exec('df -h /');
    if ($df_output) {
        $lines = explode("\n", $df_output);
        if (isset($lines[1])) {
            $parts = preg_split('/\s+/', trim($lines[1]));
            if (isset($parts[4])) {
                return $parts[4];
            }
        }
    }
    return "Unknown";
}

function getCpuTemp(): string {
    $tempFilePath = '/sys/class/thermal/thermal_zone0/temp';
    if (file_exists($tempFilePath)) {
        $cpuTempCRaw = safeReadSystemFile($tempFilePath);
        if ($cpuTempCRaw !== false && $cpuTempCRaw !== "") {
            $cpuTempCRaw = trim($cpuTempCRaw);
            if (is_numeric($cpuTempCRaw)) {
                $cpuTempCRaw = (int)$cpuTempCRaw;
                if ($cpuTempCRaw > 1000) { 
                    $cpuTempC = round($cpuTempCRaw / 1000); 
                } else { 
                    $cpuTempC = round($cpuTempCRaw); 
                }
                return $cpuTempC . "°C";
            }
        }
    }
    return "---";
}

function getCpuUsage(): string {
    $cpuLoad = sys_getloadavg();
    return round($cpuLoad[0], 1) . " / " . round($cpuLoad[1], 1) . " / " . round($cpuLoad[2], 1);
}
?>
