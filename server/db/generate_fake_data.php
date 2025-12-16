<?php
/**
 * Generate fake water meter readings for testing
 * Period: 1/11/2025 to 14/12/2025
 * Frequency: Hourly
 * Daily consumption: Variable
 * Growth: Only between 6:00 to 23:59 (no growth 0:00-5:59)
 * Target Devices: 123, 456
 */

// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Starting date and end date
$startDate = new DateTime('2025-11-01 00:00:00');
$endDate = new DateTime('2025-12-14 13:00:00');

// Devices configuration
$devices = [
    '123' => ['current' => 275.60, 'start' => 275.60],
    '456' => ['current' => 120.50, 'start' => 120.50]
];

// Array to store all readings
$readings = [];

// Generate readings for each hour
$currentTime = clone $startDate;
while ($currentTime <= $endDate) {
    $hour = (int)$currentTime->format('H');
    $timestamp = $currentTime->getTimestamp();
    
    // Process each device
    foreach ($devices as $deviceId => &$deviceData) {
        // Only increase meter between 6:00 and 23:59
        if ($hour >= 6) {
            // Random consumption: 0.01, 0.02, or 0.03
            $consumption = rand(1, 3) / 100;
            $deviceData['current'] += $consumption;
        }
        
        // Ensure value doesn't exceed 2 decimal places
        $deviceData['current'] = round($deviceData['current'], 2);
        
        $readings[] = [
            'device_id' => $deviceId,
            'value' => $deviceData['current'],
            'timestamp' => $timestamp,
            'error_code' => 0,
            'error_message' => null
        ];
    }
    unset($deviceData); // Break reference
    
    // Move to next hour
    $currentTime->add(new DateInterval('PT1H'));
}

// Generate SQL INSERT statements
$sql = "-- Generated fake water meter readings\n";
$sql .= "-- Period: " . $startDate->format('d/m/Y') . " to " . $endDate->format('d/m/Y') . "\n";
$sql .= "-- Readings: " . count($readings) . " total\n";
foreach ($devices as $id => $data) {
    $sql .= "-- Device $id: Start " . $data['start'] . " m³ -> End " . $data['current'] . " m³ (Consumed: " . round($data['current'] - $data['start'], 2) . " m³)\n";
}
$sql .= "\n";

$sql .= "INSERT INTO readings (device_id, value, timestamp, error_code, error_message) VALUES\n";

for ($i = 0; $i < count($readings); $i++) {
    $r = $readings[$i];
    $errorMsg = $r['error_message'] ? "'" . addslashes($r['error_message']) . "'" : "NULL";
    $sql .= "('" . $r['device_id'] . "', " . $r['value'] . ", " . $r['timestamp'] . ", " . $r['error_code'] . ", " . $errorMsg . ")";
    
    if ($i < count($readings) - 1) {
        $sql .= ",\n";
    } else {
        $sql .= ";\n";
    }
}

// Output to file in current directory for easier access
$outputFile = __DIR__ . '/fake_data.sql';
file_put_contents($outputFile, $sql);

echo "✓ Fake data generated successfully!\n";
echo "Total readings: " . count($readings) . "\n";
echo "Period: " . $startDate->format('d/m/Y') . " to " . $endDate->format('d/m/Y H:i') . "\n";

foreach ($devices as $id => $data) {
    echo "Device $id: " . $data['start'] . " -> " . $data['current'] . " m³ (+" . round($data['current'] - $data['start'], 2) . ")\n";
}

echo "File: " . $outputFile . "\n";
?>
