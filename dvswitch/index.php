<?php
declare(strict_types=1);

include_once 'include/config.php';
include_once 'include/tools.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="index, follow" />
    <meta name="language" content="English" />
    <meta charset="utf-8" />
    <meta name="generator" content="DVSwitch" />
    <meta name="Author" content="Andrew Taylor (MW0MWZ), Waldek (SP2ONG)" />
    <meta name="Description" content="Dashboard based on Pi-Star Dashboard, © Andy Taylor (MW0MWZ) and adapted to DVSwitch by SP2ONG" />
    <meta name="KeyWords" content="MMDVM_Bridge,Analog_Bridge,ircDDBGateway,D-Star,ircDDB,DMRGateway,DMR,YSFGateway,YSF,C4FM,NXDNGateway,NXDN,P25Gateway,P25,DVSwitch,DL5DI,DG9VH,MW0MWZ,SP2ONG" />
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="pragma" content="no-cache" />
<link rel="shortcut icon" href="images/favicon.ico" sizes="16x16 32x32" type="image/png">
    <title>DVSwitch Dashboard</title>
<?php include_once "include/browserdetect.php"; ?>
    <script src="scripts/jquery.min.js"></script>
    <script src="scripts/functions.js"></script>
    <script src="scripts/pcm-player.js"></script>
    <script type="text/javascript">
      // Modern AJAX setup - disable caching for dynamic content
      if (typeof $ !== 'undefined') {
        $.ajaxSetup({ cache: false });
      }
    </script>
    <link href="css/featherlight.css" rel="stylesheet" />
    <script src="scripts/featherlight.js"></script>
    <script>
      // Initialize modern lightbox with custom options
      document.addEventListener('DOMContentLoaded', () => {
        if (window.modernLightbox) {
          // Customize lightbox options if needed
          console.log('Modern lightbox initialized');
        }
      });
    </script>
    
    <!-- Dark Mode Styles -->
    <style>
      :root {
        /* Light theme variables */
        --bg-primary: #f8f8f8;
        --bg-secondary: #fafafa;
        --text-primary: #333;
        --text-secondary: #666;
        --border-color: #ddd;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --accent-color: #007bff;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --error-color: #dc3545;
        --header-bg: #fafafa;
        --button-bg: #007bff;
        --button-text: #fff;
        --button-hover: #0056b3;
      }
      
      [data-theme="dark"] {
        /* Dark theme variables */
        --bg-primary: #1a1a1a;
        --bg-secondary: #2d2d2d;
        --text-primary: #ffffff;
        --text-secondary: #b0b0b0;
        --border-color: #444;
        --shadow-color: rgba(0, 0, 0, 0.3);
        --accent-color: #4dabf7;
        --success-color: #51cf66;
        --warning-color: #ffd43b;
        --error-color: #ff6b6b;
        --header-bg: #2d2d2d;
        --button-bg: #4dabf7;
        --button-text: #1a1a1a;
        --button-hover: #339af0;
      }
      
      body {
        background-color: var(--bg-primary) !important;
        color: var(--text-primary);
        transition: background-color 0.3s ease, color 0.3s ease;
      }
      
      .container {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        box-shadow: 0 0 10px var(--shadow-color);
      }
      
      .header {
        background-color: var(--header-bg);
        border-bottom: 1px solid var(--border-color);
      }
      
      .header h2, .header h3, .header h4 {
        color: var(--text-primary);
      }
      
      .button {
        background-color: var(--button-bg);
        color: var(--button-text);
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
      }
      
      .button:hover {
        background-color: var(--button-hover);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px var(--shadow-color);
      }
      
      .nav {
        background-color: var(--bg-secondary);
        border-right: 1px solid var(--border-color);
      }
      
      .content {
        background-color: var(--bg-secondary);
      }
      
      .content2 {
        background-color: var(--bg-secondary);
        border-top: 1px solid var(--border-color);
      }
      
      table {
        background-color: var(--bg-secondary);
        color: var(--text-primary);
      }
      
      td {
        background-color: var(--bg-secondary);
        border-color: var(--border-color);
      }
      
      /* Dark mode toggle button */
      .theme-toggle {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: var(--button-bg);
        color: var(--button-text);
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px var(--shadow-color);
      }
      
      .theme-toggle:hover {
        background: var(--button-hover);
        transform: scale(1.1);
      }
      
      /* Status indicators */
      .status-online {
        color: var(--success-color);
      }
      
      .status-offline {
        color: var(--error-color);
      }
      
      .status-warning {
        color: var(--warning-color);
      }
      
      /* Responsive design */
      @media (max-width: 768px) {
        .theme-toggle {
          top: 10px;
          right: 10px;
          width: 40px;
          height: 40px;
          font-size: 16px;
        }
      }
      
      /* Additional dark mode styles for specific elements */
      [data-theme="dark"] {
        /* Basic text readability - only for essential elements */
        body {
          color: var(--text-primary) !important;
        }
        
        /* Links in dark mode */
        a {
          color: var(--accent-color) !important;
        }
        
        a:hover {
          color: var(--button-hover) !important;
        }
        
        /* Button styles for dark mode */
        .button, button {
          background-color: var(--button-bg) !important;
          color: var(--button-text) !important;
          border-color: var(--border-color) !important;
        }
        
        .button:hover, button:hover {
          background-color: var(--button-hover) !important;
        }
        
        /* Tooltip styles for dark mode */
        .tooltip .tooltiptext {
          background-color: var(--bg-secondary) !important;
          color: var(--text-primary) !important;
          border: 1px solid var(--border-color) !important;
        }
        
        /* Footer text only */
        .footer-text {
          color: var(--text-secondary) !important;
        }
        
        /* Only fix essential text that would be unreadable */
        .essential-text {
          color: var(--text-primary) !important;
        }
        
        /* Last Heard table fields that need white text in dark mode */
        .lh-time, .lh-duration, .lh-loss, .lh-ber {
          color: var(--text-primary) !important;
        }
        
        /* Ensure table headers are readable in dark mode */
        .lh-table th {
          color: var(--text-primary) !important;
        }
        
        /* System info table fields that need white text in dark mode */
        .sys-hostname, .sys-kernel, .sys-platform, .sys-disk, .sys-memory, .sys-cpu {
          color: var(--text-primary) !important;
        }
        
        /* System info table headers */
        .sys-table th {
          color: var(--text-primary) !important;
        }
      }
    </style>
</head>
<body>
    <!-- Dark Mode Toggle Button -->
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
        <span id="themeIcon">🌙</span>
    </button>

<center>
<fieldset style="box-shadow:0 0 10px var(--shadow-color); background-color:var(--bg-secondary); color:var(--text-primary); width:0px;margin-top:15px;margin-left:0px;margin-right:5px;font-size:13px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
<div class="container"> 
<div class="header">
<center>
<h2>DVSwitch Dashboard</h2>
</center>
</div>
<div class="content"><center>
<div style="margin-top:8px;">
<?php
if ( RXMONITOR == "YES" ) {
echo '<button class="button link" onclick="playAudioToggle(8080, this)"><b>&nbsp;&nbsp;&nbsp;<img src=images/speaker.png alt="" style="vertical-align:middle">&nbsp;&nbsp;RX Monitor&nbsp;&nbsp;&nbsp;</b></button>';}
?>
</div></center>
</div>
<?php
function getMMDVMConfigFileContent() {
		// loads ini fule into array for further use
		$conf = array();
		if ($configs = @fopen('/opt/MMDVM_Bridge/MMDVM_Bridge.ini', 'r')) {
			while ($config = fgets($configs)) {
				array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
			}
			fclose($configs);
		}
		return $conf;
	}

$mmdvmconfigfile = getMMDVMConfigFileContent();
    echo '<table style="border:none; border-collapse:collapse; cellspacing:0; cellpadding:0; background-color:var(--bg-secondary);"><tr style="border:none;background-color:var(--bg-secondary);">';
    echo '<td width="200px" valign="top" class="hide" style="border:none;background-color:var(--bg-secondary);">';
    echo '<div class="nav">'."\n";
    echo '<script type="text/javascript">'."\n";
    echo '// Auto-reload functions are now handled in functions.js'."\n";
    echo '</script>'."\n";
    echo '<div id="modeInfo">'."\n";
    include 'include/status.php';			// Mode and Networks Info
    echo '</div>'."\n";
    echo '</div>'."\n";
    echo '</td>'."\n";

    echo '<td valign="top" style="border:none; height: 480px; background-color:var(--bg-secondary);">';
    echo '<div class="content">'."\n";
    echo '<script type="text/javascript">'."\n";
    echo '// Auto-reload functions are now handled in functions.js'."\n";
    echo '</script>'."\n";
    echo '<center><div id="lastHerd">'."\n";
    include 'include/lh.php';
    echo '</div></center>'."\n";
    echo "<br />\n";
    echo '<center><div id="localTxs">'."\n";
    include 'include/localtx.php';
    echo '</div></center>'."\n";
    echo '</td>';
?>
</tr></table>
<?php
    echo '<div class="content2">'."\n";
    echo '<script type="text/javascript">'."\n";
    echo '// Auto-reload functions are now handled in functions.js'."\n";
    echo '</script>'."\n";
    echo '<div id="sysInfo">'."\n";
    include 'include/system.php';		// Basic System Info
    echo '</div>'."\n";
    echo '</div>'."\n";
?>
<div class="content">
<center><span style="font: 7pt arial, sans-serif;">DVSwitch Dashboard Version 20250706 <?php $cdate=date("Y"); if ($cdate > "2020") {$cdate="2020-".date("Y");} echo $cdate; ?>
	<br>Dashboard based on Pi-Star Dashboard, © Andy Taylor (MW0MWZ) and adapted to DVSwitch by SP2ONG</span></center>
<!-- DVSwitch Dashboard: version 20250101 -->
	</div>
</div>
</fieldset>

<!-- Dark Mode JavaScript -->
<script>
// Dark mode functionality
class DarkMode {
  constructor() {
    this.themeToggle = document.getElementById('themeToggle');
    this.themeIcon = document.getElementById('themeIcon');
    this.currentTheme = localStorage.getItem('theme') || 'light';
    
    this.init();
  }
  
  init() {
    // Set initial theme
    this.setTheme(this.currentTheme);
    
    // Add event listener
    this.themeToggle.addEventListener('click', () => {
      this.toggleTheme();
    });
    
    // Listen for system theme preference changes
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('theme')) {
          this.setTheme(e.matches ? 'dark' : 'light');
        }
      });
    }
  }
  
  setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    this.currentTheme = theme;
    localStorage.setItem('theme', theme);
    
    // Update icon
    this.themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
    this.themeIcon.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    
    // Update button title
    this.themeToggle.setAttribute('title', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
  }
  
  toggleTheme() {
    const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
    this.setTheme(newTheme);
    
    // Add animation effect
    this.themeToggle.style.transform = 'rotate(360deg)';
    setTimeout(() => {
      this.themeToggle.style.transform = '';
    }, 300);
  }
  
  // Get current theme
  getCurrentTheme() {
    return this.currentTheme;
  }
  
  // Check if dark mode is active
  isDarkMode() {
    return this.currentTheme === 'dark';
  }
}

// Initialize dark mode when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.darkMode = new DarkMode();
  
  // Make it globally accessible
  window.toggleDarkMode = () => window.darkMode.toggleTheme();
  window.getCurrentTheme = () => window.darkMode.getCurrentTheme();
  window.isDarkMode = () => window.darkMode.isDarkMode();
});
</script>

</body>
</html>
