<?php
/**
 * Generate fake water meter readings for testing
 * Period: 1/11/2025 to 14/12/2025
 * Frequency: 12 times per day (hourly)
 * Daily consumption: ~0.50 m³
 * Growth: Only between 6:00 to 23:59 (no growth 0:00-5:59)
 */

// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Starting date and end date
$startDate = new DateTime('2025-11-01 00:00:00');
$endDate = new DateTime('2025-12-14 13:00:00');

// Consumption per reading (either 0.02 or 0.03, alternating)
// With 12 readings during active hours (6:00-23:59)
$consumptionPerReading = 0.02; // Can be 0.02 or 0.03

// Starting meter value
$currentValue = 275.60;
$startingValue = $currentValue;

// Array to store all readings
$readings = [];

// Generate readings for each hour
$currentTime = clone $startDate;
while ($currentTime <= $endDate) {
    $hour = (int)$currentTime->format('H');
    $timestamp = $currentTime->getTimestamp();
    
    // Only increase meter between 6:00 and 23:59
    if ($hour >= 6) {
        $currentValue += $consumptionPerReading;
    }
    
    // Ensure value doesn't exceed 2 decimal places
    $currentValue = round($currentValue, 2);
    
    $readings[] = [
        'value' => $currentValue,
        'timestamp' => $timestamp,
        'error_code' => 0,
        'error_message' => null
    ];
    
    // Move to next hour
    $currentTime->add(new DateInterval('PT1H'));
}

// Generate SQL INSERT statements
$sql = "-- Generated fake water meter readings\n";
$sql .= "-- Period: 1/11/2025 to 14/12/2025\n";
$sql .= "-- Readings: " . count($readings) . " total\n";
$sql .= "-- Starting value: " . $startingValue . " m³\n";
$sql .= "-- Final value: " . $currentValue . " m³\n";
$sql .= "-- Total consumption: " . round($currentValue - $startingValue, 2) . " m³\n\n";

$sql .= "INSERT INTO readings (value, timestamp, error_code, error_message) VALUES\n";

for ($i = 0; $i < count($readings); $i++) {
    $r = $readings[$i];
    $errorMsg = $r['error_message'] ? "'" . addslashes($r['error_message']) . "'" : "NULL";
    $sql .= "(" . $r['value'] . ", " . $r['timestamp'] . ", " . $r['error_code'] . ", " . $errorMsg . ")";
    
    if ($i < count($readings) - 1) {
        $sql .= ",\n";
    } else {
        $sql .= ";\n";
    }
}

// Output to file
file_put_contents('/var/www/html/iotdigi/fake_data.sql', $sql);

echo "✓ Fake data generated successfully!\n";
echo "Total readings: " . count($readings) . "\n";
echo "Period: 1/11/2025 to 14/12/2025\n";
echo "Starting value: " . $startingValue . " m³\n";
echo "Final value: " . $currentValue . " m³\n";
echo "Total consumption: " . round($currentValue - $startingValue, 2) . " m³\n";
echo "File: /var/www/html/iotdigi/fake_data.sql\n";

// Output sample readings
echo "\nSample readings (first 24):\n";
for ($i = 0; $i < min(24, count($readings)); $i++) {
    $r = $readings[$i];
    $date = new DateTime();
    $date->setTimestamp($r['timestamp']);
    echo $date->format('Y-m-d H:i:s') . " => " . $r['value'] . " m³\n";
}
?>
