// Load current settings on page load
document.addEventListener('DOMContentLoaded', async () => {
    await loadSettings();
    
    // Save button event listener
    document.getElementById('save-settings-btn').addEventListener('click', saveSettings);
});

async function loadSettings() {
    try {
        const response = await fetch('settings_api.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            if (data.maxRateValue !== undefined) {
                document.getElementById('max-rate-input').value = data.maxRateValue;
            }
            if (data.interval !== undefined) {
                document.getElementById('interval-input').value = data.interval;
            }
        } else {
            showMessage('Failed to load settings: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading settings:', error);
        showMessage('Error loading settings: ' + error.message, 'error');
    }
}

async function saveSettings() {
    const btn = document.getElementById('save-settings-btn');
    const maxRateValue = parseFloat(document.getElementById('max-rate-input').value);
    const interval = parseInt(document.getElementById('interval-input').value);
    
    // Validation
    if ((isNaN(maxRateValue) || maxRateValue < 0) && (isNaN(interval) || interval < 1)) {
        showMessage('Please enter valid values', 'error');
        return;
    }
    
    if (isNaN(maxRateValue) || maxRateValue < 0) {
        showMessage('Daily Consumption Goal must be a positive number', 'error');
        return;
    }
    
    if (isNaN(interval) || interval < 1) {
        showMessage('Interval must be a positive integer', 'error');
        return;
    }
    
    // Disable button during save
    btn.disabled = true;
    document.getElementById('save-btn-text').textContent = 'Saving...';
    
    try {
        const response = await fetch('settings_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                maxRateValue: maxRateValue,
                interval: interval
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showMessage('Settings saved and uploaded successfully!', 'success');
        } else if (data.status === 'partial') {
            showMessage(data.message + ' (HTTP ' + data.http_code + ')', 'warning');
        } else {
            showMessage('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showMessage('Error saving settings: ' + error.message, 'error');
    } finally {
        // Re-enable button
        btn.disabled = false;
        document.getElementById('save-btn-text').textContent = 'Save Changes';
    }
}

function showMessage(message, type = 'info') {
    const messageEl = document.getElementById('status-message');
    
    // Set styling based on type
    messageEl.classList.remove('hidden', 'bg-blue-50', 'dark:bg-blue-900/20', 'text-blue-800', 'dark:text-blue-300', 
                                'bg-green-50', 'dark:bg-green-900/20', 'text-green-800', 'dark:text-green-300',
                                'bg-yellow-50', 'dark:bg-yellow-900/20', 'text-yellow-800', 'dark:text-yellow-300',
                                'bg-red-50', 'dark:bg-red-900/20', 'text-red-800', 'dark:text-red-300');
    
    messageEl.textContent = message;
    
    switch(type) {
        case 'success':
            messageEl.classList.add('bg-green-50', 'dark:bg-green-900/20', 'text-green-800', 'dark:text-green-300');
            break;
        case 'warning':
            messageEl.classList.add('bg-yellow-50', 'dark:bg-yellow-900/20', 'text-yellow-800', 'dark:text-yellow-300');
            break;
        case 'error':
            messageEl.classList.add('bg-red-50', 'dark:bg-red-900/20', 'text-red-800', 'dark:text-red-300');
            break;
        default:
            messageEl.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'text-blue-800', 'dark:text-blue-300');
    }
}
