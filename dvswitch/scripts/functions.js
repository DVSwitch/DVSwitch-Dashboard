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
 * Reload mode information via AJAX with error backoff
 */
const reloadModeInfo = (() => {
  let errorCount = 0;
  const maxErrors = 5;
  const baseDelay = 1000;
  const maxDelay = 30000;
  
  return () => {
    try {
      const modeInfo = document.getElementById('modeInfo');
      if (!modeInfo) {
        return;
      }
      
      fetch('include/status.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          errorCount = 0; // Reset error count on success
          return response.text();
        })
        .then(html => {
          modeInfo.innerHTML = html;
        })
        .catch(error => {
          console.error('Error reloading mode info:', error);
          errorCount++;
          if (errorCount >= maxErrors) {
            console.warn('Too many errors, stopping auto-reload for mode info');
            return; // Stop reloading after too many errors
          }
        })
        .finally(() => {
          if (errorCount < maxErrors) {
            // Exponential backoff: delay increases with error count
            const delay = Math.min(baseDelay * Math.pow(2, errorCount), maxDelay);
            setTimeout(reloadModeInfo, delay);
          }
        });
    } catch (error) {
      console.error('Error in reloadModeInfo:', error);
      errorCount++;
      if (errorCount < maxErrors) {
        const delay = Math.min(baseDelay * Math.pow(2, errorCount), maxDelay);
        setTimeout(reloadModeInfo, delay);
      }
    }
  };
})();

/**
 * Reload local transmissions via AJAX with error backoff
 */
const reloadLocalTx = (() => {
  let errorCount = 0;
  const maxErrors = 5;
  const baseDelay = 1500;
  const maxDelay = 30000;
  
  return () => {
    try {
      const localTxs = document.getElementById('localTxs');
      if (!localTxs) {
        return;
      }
      
      fetch('include/localtx.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          errorCount = 0;
          return response.text();
        })
        .then(html => {
          localTxs.innerHTML = html;
        })
        .catch(error => {
          console.error('Error reloading local transmissions:', error);
          errorCount++;
          if (errorCount >= maxErrors) {
            console.warn('Too many errors, stopping auto-reload for local transmissions');
            return;
          }
        })
        .finally(() => {
          if (errorCount < maxErrors) {
            const delay = Math.min(baseDelay * Math.pow(2, errorCount), maxDelay);
            setTimeout(reloadLocalTx, delay);
          }
        });
    } catch (error) {
      console.error('Error in reloadLocalTx:', error);
      errorCount++;
      if (errorCount < maxErrors) {
        const delay = Math.min(baseDelay * Math.pow(2, errorCount), maxDelay);
        setTimeout(reloadLocalTx, delay);
      }
    }
  };
})();

/**
 * Reload last heard information via AJAX with error backoff
 */
const reloadLastHeard = (() => {
  let errorCount = 0;
  const maxErrors = 5;
  const baseDelay = 1500;
  const maxDelay = 30000;
  
  return () => {
    try {
      const lastHeard = document.getElementById('lastHerd');
      if (!lastHeard) {
        return;
      }
      
      fetch('include/lh.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          errorCount = 0;
          return response.text();
        })
        .then(html => {
          lastHeard.innerHTML = html;
        })
        .catch(error => {
          console.error('Error reloading last heard:', error);
          errorCount++;
          if (errorCount >= maxErrors) {
            console.warn('Too many errors, stopping auto-reload for last heard');
            return;
          }
        })
        .finally(() => {
          if (errorCount < maxErrors) {
            const delay = Math.min(baseDelay * Math.pow(2, errorCount), maxDelay);
            setTimeout(reloadLastHeard, delay);
          }
        });
    } catch (error) {
      console.error('Error in reloadLastHeard:', error);
      errorCount++;
      if (errorCount < maxErrors) {
        const delay = Math.min(baseDelay * Math.pow(2, errorCount), maxDelay);
        setTimeout(reloadLastHeard, delay);
      }
    }
  };
})();

/**
 * Reload system information via AJAX with error backoff
 */
const reloadSysInfo = (() => {
  let errorCount = 0;
  const maxErrors = 3;
  const baseDelay = 15000;
  const maxDelay = 60000;
  
  return () => {
    try {
      const sysInfo = document.getElementById('sysInfo');
      if (!sysInfo) {
        return;
      }
      
      fetch('include/system.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          errorCount = 0;
          return response.text();
        })
        .then(html => {
          sysInfo.innerHTML = html;
        })
        .catch(error => {
          console.error('Error reloading system info:', error);
          errorCount++;
          if (errorCount >= maxErrors) {
            console.warn('Too many errors, stopping auto-reload for system info');
            return;
          }
        })
        .finally(() => {
          if (errorCount < maxErrors) {
            const delay = Math.min(baseDelay * Math.pow(2, errorCount), maxDelay);
            setTimeout(reloadSysInfo, delay);
          }
        });
    } catch (error) {
      console.error('Error in reloadSysInfo:', error);
      errorCount++;
      if (errorCount < maxErrors) {
        const delay = Math.min(baseDelay * Math.pow(2, errorCount), maxDelay);
        setTimeout(reloadSysInfo, delay);
      }
    }
  };
})();

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
