<?php
/**
 * Calculate daily_usage and monthly_usage from readings
 * This script aggregates data from readings table
 */

// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Database connection
$host = 'localhost';
$user = 'iotdigi';
$pass = 'iotdigi11';
$db = 'iotdigi_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Calculating Daily and Monthly Usage ===\n\n";

// Step 1: Clear existing data
echo "1. Clearing existing aggregates...\n";
$conn->query("DELETE FROM daily_usage");
$conn->query("DELETE FROM monthly_usage");
echo "   ✓ Cleared\n\n";

// Step 2: Calculate daily_usage
echo "2. Calculating daily_usage...\n";

// Get all readings ordered by timestamp
$result = $conn->query("SELECT value, timestamp FROM readings ORDER BY timestamp ASC");

if (!$result) {
    die("Query failed: " . $conn->error);
}

$readings = [];
while ($row = $result->fetch_assoc()) {
    $readings[] = $row;
}

if (empty($readings)) {
    die("No readings found!\n");
}

// Group readings by day and calculate consumption
$dailyData = [];
foreach ($readings as $reading) {
    $date = date('Y-m-d', $reading['timestamp']);
    
    if (!isset($dailyData[$date])) {
        $dailyData[$date] = [
            'start_value' => $reading['value'],
            'end_value' => $reading['value'],
            'readings' => []
        ];
    }
    
    $dailyData[$date]['end_value'] = $reading['value'];
    $dailyData[$date]['readings'][] = $reading;
}

// Insert daily_usage
$dailyCount = 0;
foreach ($dailyData as $date => $data) {
    $consumption = $data['end_value'] - $data['start_value'];
    $consumption = round($consumption, 2);
    
    $stmt = $conn->prepare("INSERT INTO daily_usage (date, consumption, start_value, end_value) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sddd", $date, $consumption, $data['start_value'], $data['end_value']);
    $stmt->execute();
    $stmt->close();
    
    $dailyCount++;
}

echo "   ✓ Created " . $dailyCount . " daily records\n\n";

// Step 3: Calculate monthly_usage with tiered pricing
echo "3. Calculating monthly_usage with tiered pricing...\n";

// Get rate tiers
$tiers = [];
$tierResult = $conn->query("SELECT tier_level, from_m3, to_m3, rate FROM rate_tiers ORDER BY tier_level");
while ($row = $tierResult->fetch_assoc()) {
    $tiers[] = $row;
}

// Group by month
$monthlyData = [];
foreach ($dailyData as $date => $data) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    $yearMonth = $dateObj->format('Y-m');
    
    $consumption = $data['end_value'] - $data['start_value'];
    
    if (!isset($monthlyData[$yearMonth])) {
        $monthlyData[$yearMonth] = [
            'year' => (int)$dateObj->format('Y'),
            'month' => (int)$dateObj->format('m'),
            'consumption' => 0,
            'start_value' => $data['start_value'],
            'end_value' => $data['end_value']
        ];
    }
    
    $monthlyData[$yearMonth]['consumption'] += $consumption;
    $monthlyData[$yearMonth]['end_value'] = $data['end_value'];
}

// Insert monthly_usage with tiered pricing
$monthlyCount = 0;
foreach ($monthlyData as $yearMonth => $data) {
    $consumption = round($data['consumption'], 2);
    
    // Calculate tiered usage
    $tier1 = 0;
    $tier2 = 0;
    $tier3 = 0;
    $tier4 = 0;
    
    if ($consumption <= 10) {
        $tier1 = $consumption;
    } elseif ($consumption <= 20) {
        $tier1 = 10;
        $tier2 = $consumption - 10;
    } elseif ($consumption <= 30) {
        $tier1 = 10;
        $tier2 = 10;
        $tier3 = $consumption - 20;
    } else {
        $tier1 = 10;
        $tier2 = 10;
        $tier3 = 10;
        $tier4 = $consumption - 30;
    }
    
    // Calculate cost
    $cost = ($tier1 * 5973) + ($tier2 * 7052) + ($tier3 * 8669) + ($tier4 * 15929);
    $cost = round($cost, 2);
    
    $stmt = $conn->prepare("INSERT INTO monthly_usage (year, month, consumption, cost, tier_1_usage, tier_2_usage, tier_3_usage, tier_4_usage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidddddd", $data['year'], $data['month'], $consumption, $cost, $tier1, $tier2, $tier3, $tier4);
    $stmt->execute();
    $stmt->close();
    
    echo "   " . $yearMonth . ": " . number_format($consumption, 2) . " m³ = " . number_format($cost, 0) . " VND\n";
    $monthlyCount++;
}

echo "   ✓ Created " . $monthlyCount . " monthly records\n\n";

// Step 4: Summary
echo "=== Summary ===\n";
echo "Total readings: " . count($readings) . "\n";
echo "Date range: " . date('Y-m-d', $readings[0]['timestamp']) . " to " . date('Y-m-d', $readings[count($readings)-1]['timestamp']) . "\n";
echo "Starting value: " . $readings[0]['value'] . " m³\n";
echo "Ending value: " . $readings[count($readings)-1]['value'] . " m³\n";
echo "Total consumption: " . round($readings[count($readings)-1]['value'] - $readings[0]['value'], 2) . " m³\n\n";

// Show monthly breakdown
echo "=== Monthly Breakdown ===\n";
$monthResult = $conn->query("SELECT year, month, consumption, cost FROM monthly_usage ORDER BY year, month");
while ($row = $monthResult->fetch_assoc()) {
    printf("%04d-%02d: %6.2f m³ = %10.0f VND\n", $row['year'], $row['month'], $row['consumption'], $row['cost']);
}

$conn->close();
echo "\n✓ Done!\n";
?>
