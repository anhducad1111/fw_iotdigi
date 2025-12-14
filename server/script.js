// Update sensor data from database
function updateSensorData() {
    fetch('get.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update webhook data from latest reading
                if (data.webhook_data) {
                    const item = data.webhook_data;
                    
                    // Update all webhook fields with null checks
                    const elements = {
                        'webhook-name': item.name,
                        'webhook-timestamp': new Date(item.timestamp * 1000).toLocaleString('vi-VN'),
                        'webhook-rawValue': item.rawValue,
                        'webhook-value': item.value,
                        'webhook-preValue': item.preValue,
                        'webhook-rate': item.rate + ' m³/h',
                        'webhook-changeAbsolute': item.changeAbsolute + ' m³',
                        'webhook-errorCode': item.errorCode,
                        'webhook-error': item.error,
                        'current-value-display': item.rate + ' m³/h',
                        'total-volume-display': item.value + ' m³'
                    };
                    
                    for (let id in elements) {
                        const el = document.getElementById(id);
                        if (el) el.textContent = elements[id];
                    }
                }
                
                // Update monthly cost from database
                if (data.monthly_cost) {
                    const monthlyCostEl = document.getElementById('monthly-cost-display');
                    if (monthlyCostEl) {
                        const cost = data.monthly_cost.cost || 0;
                        monthlyCostEl.textContent = Math.round(cost).toLocaleString('vi-VN') + ' ₫';
                    }
                }

                // Print debug info
                console.log("Data from database:", data);
            }
        })
        .catch(console.error);
}

// Load saved threshold and start updates
document.addEventListener('DOMContentLoaded', function() {
    updateSensorData();
    setInterval(updateSensorData, 5000);
});