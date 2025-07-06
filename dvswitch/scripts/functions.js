// Modern ES6+ JavaScript functions for DVSwitch Dashboard

/**
 * Toggle field visibility and disabled state
 * @param {HTMLElement} hideObj - Element to hide
 * @param {HTMLElement} showObj - Element to show
 */
const toggleField = (hideObj, showObj) => {
  try {
    if (!hideObj || !showObj) {
      console.warn('toggleField: Invalid elements provided');
      return;
    }
    
    hideObj.disabled = true;
    hideObj.style.display = 'none';
    showObj.disabled = false;
    showObj.style.display = 'inline';
    showObj.focus();
  } catch (error) {
    console.error('Error in toggleField:', error);
  }
};

/**
 * Check if password fields match and update UI accordingly
 * Used for confirming matching password entries
 */
const checkPass = () => {
  try {
    const pass1 = document.getElementById('pass1');
    const pass2 = document.getElementById('pass2');
    const submitBtn = document.getElementById('submitpwd');
    
    if (!pass1 || !pass2 || !submitBtn) {
      console.warn('checkPass: Required elements not found');
      return;
    }
    
    const goodColor = "#66cc66";
    const badColor = "#ff6666";
    
    const passwordsMatch = pass1.value !== '' && pass1.value === pass2.value;
    
    pass2.style.backgroundColor = passwordsMatch ? goodColor : badColor;
    
    if (passwordsMatch) {
      submitBtn.removeAttribute("disabled");
    } else {
      submitBtn.setAttribute("disabled", "disabled");
    }
  } catch (error) {
    console.error('Error in checkPass:', error);
  }
};

/**
 * Play audio toggle functionality - handled by pcm-player.min.js
 * This function is defined in the PCM player library
 */

/**
 * Reload mode information via AJAX
 */
const reloadModeInfo = () => {
  try {
    const modeInfo = document.getElementById('modeInfo');
    if (modeInfo) {
      fetch('include/status.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.text();
        })
        .then(html => {
          modeInfo.innerHTML = html;
        })
        .catch(error => {
          console.error('Error reloading mode info:', error);
        })
        .finally(() => {
          setTimeout(reloadModeInfo, 1000);
        });
    }
  } catch (error) {
    console.error('Error in reloadModeInfo:', error);
  }
};

/**
 * Reload local transmissions via AJAX
 */
const reloadLocalTx = () => {
  try {
    const localTxs = document.getElementById('localTxs');
    if (localTxs) {
      fetch('include/localtx.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.text();
        })
        .then(html => {
          localTxs.innerHTML = html;
        })
        .catch(error => {
          console.error('Error reloading local transmissions:', error);
        })
        .finally(() => {
          setTimeout(reloadLocalTx, 1500);
        });
    }
  } catch (error) {
    console.error('Error in reloadLocalTx:', error);
  }
};

/**
 * Reload last heard information via AJAX
 */
const reloadLastHeard = () => {
  try {
    const lastHeard = document.getElementById('lastHerd');
    if (lastHeard) {
      fetch('include/lh.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.text();
        })
        .then(html => {
          lastHeard.innerHTML = html;
        })
        .catch(error => {
          console.error('Error reloading last heard:', error);
        })
        .finally(() => {
          setTimeout(reloadLastHeard, 1500);
        });
    }
  } catch (error) {
    console.error('Error in reloadLastHeard:', error);
  }
};

/**
 * Reload system information via AJAX
 */
const reloadSysInfo = () => {
  try {
    const sysInfo = document.getElementById('sysInfo');
    if (sysInfo) {
      fetch('include/system.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.text();
        })
        .then(html => {
          sysInfo.innerHTML = html;
        })
        .catch(error => {
          console.error('Error reloading system info:', error);
        })
        .finally(() => {
          setTimeout(reloadSysInfo, 15000);
        });
    }
  } catch (error) {
    console.error('Error in reloadSysInfo:', error);
  }
};

// Initialize auto-reload functions when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  try {
    // Start auto-reload functions
    setTimeout(reloadModeInfo, 1000);
    setTimeout(reloadLocalTx, 1500);
    setTimeout(reloadLastHeard, 1500);
    setTimeout(reloadSysInfo, 15000);
    
    // Trigger window resize for responsive design
    window.dispatchEvent(new Event('resize'));
  } catch (error) {
    console.error('Error initializing dashboard:', error);
  }
});
