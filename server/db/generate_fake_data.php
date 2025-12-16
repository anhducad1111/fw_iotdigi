<?php
/**
 * Generate fake water meter readings + Aggregates (Daily/Monthly)
 * Period: 1/11/2025 to 14/12/2025
 * Frequency: Hourly
 * Devices: 123, 456
 */

date_default_timezone_set('Asia/Ho_Chi_Minh');

$startDate = new DateTime('2025-11-01 00:00:00');
$endDate = new DateTime('2025-12-14 13:00:00');

$devices = [
    '123' => ['current' => 275.60, 'start_initial' => 275.60],
    '456' => ['current' => 120.50, 'start_initial' => 120.50]
];

// Data structures for aggregation
// $dailyData['device_id']['Y-m-d'] = ['start' => float, 'end' => float]
// $monthlyData['device_id']['Y-m'] = ['start' => float, 'end' => float]
$dailyData = [];
$monthlyData = [];

$readings = [];
$currentTime = clone $startDate;

while ($currentTime <= $endDate) {
    $hour = (int)$currentTime->format('H');
    $dateStr = $currentTime->format('Y-m-d');
    $monthStr = $currentTime->format('Y-m');
    $timestamp = $currentTime->getTimestamp();
    
    foreach ($devices as $deviceId => &$deviceData) {
        // Increment meter
        if ($hour >= 6) {
            $consumption = rand(1, 4) / 100; // 0.01 - 0.04
            $deviceData['current'] += $consumption;
        }
        $deviceData['current'] = round($deviceData['current'], 2);
        
        $readings[] = [
            'device_id' => $deviceId,
            'value' => $deviceData['current'],
            'timestamp' => $timestamp,
            'error_code' => 0,
            'error_message' => null
        ];

        // Track aggregates
        // Daily: Min value seen is start, Max value seen is end
        if (!isset($dailyData[$deviceId][$dateStr])) {
            $dailyData[$deviceId][$dateStr] = ['start' => $deviceData['current'], 'end' => $deviceData['current']];
        } else {
            $dailyData[$deviceId][$dateStr]['end'] = $deviceData['current'];
        }

        // Monthly
        if (!isset($monthlyData[$deviceId][$monthStr])) {
            $monthlyData[$deviceId][$monthStr] = ['start' => $deviceData['current'], 'end' => $deviceData['current']];
        } else {
            $monthlyData[$deviceId][$monthStr]['end'] = $deviceData['current'];
        }
    }
    unset($deviceData);
    $currentTime->add(new DateInterval('PT1H'));
}

// Helper to calc cost
function calculateCost($consumption) {
    $tier1 = 0; $tier2 = 0; $tier3 = 0; $tier4 = 0;
    
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
        $tier1 = 10; $tier2 = 10; $tier3 = 10;
        $tier4 = $consumption - 30;
    }
    
    $cost = ($tier1 * 5973) + ($tier2 * 7052) + ($tier3 * 8669) + ($tier4 * 15929);
    return [
        'cost' => round($cost, 2),
        't1' => $tier1, 't2' => $tier2, 't3' => $tier3, 't4' => $tier4
    ];
}

// Generate SQL
$sql = "-- Generated fake data + aggregates\n";
$sql .= "TRUNCATE TABLE readings;\n";
$sql .= "TRUNCATE TABLE daily_usage;\n";
$sql .= "TRUNCATE TABLE monthly_usage;\n\n";

// 1. Readings
$sql .= "-- Readings\n";
$sql .= "INSERT INTO readings (device_id, value, timestamp, error_code, error_message) VALUES\n";
$batchSize = 2000;
$total = count($readings);
for ($i = 0; $i < $total; $i++) {
    $r = $readings[$i];
    $sql .= "('" . $r['device_id'] . "', " . $r['value'] . ", " . $r['timestamp'] . ", 0, NULL)";
    if (($i + 1) % $batchSize == 0 || $i == $total - 1) {
        $sql .= ";\n";
        if ($i < $total - 1) $sql .= "INSERT INTO readings (device_id, value, timestamp, error_code, error_message) VALUES\n";
    } else {
        $sql .= ",\n";
    }
}

// 2. Daily Usage
$sql .= "\n-- Daily Usage\n";
$sql .= "INSERT INTO daily_usage (device_id, date, consumption, start_value, end_value) VALUES\n";
$dailyEntries = [];
foreach ($dailyData as $deviceId => $dates) {
    foreach ($dates as $date => $vals) {
        $cons = round($vals['end'] - $vals['start'], 2);
        // Correct logic: start of day implies finding value at 00:00 vs end at 23:00.
        // My simple tracking: 'start' is the first value seen that day, 'end' is the last.
        // Approx is fine for fake data.
        $dailyEntries[] = "('$deviceId', '$date', $cons, {$vals['start']}, {$vals['end']})";
    }
}
$sql .= implode(",\n", $dailyEntries) . ";\n";

// 3. Monthly Usage
$sql .= "\n-- Monthly Usage\n";
$sql .= "INSERT INTO monthly_usage (device_id, year, month, consumption, cost, tier_1_usage, tier_2_usage, tier_3_usage, tier_4_usage) VALUES\n";
$monthlyEntries = [];
foreach ($monthlyData as $deviceId => $months) {
    foreach ($months as $ym => $vals) {
        list($y, $m) = explode('-', $ym);
        $cons = round($vals['end'] - $vals['start'], 2);
        $costData = calculateCost($cons);
        
        $monthlyEntries[] = "('$deviceId', $y, $m, $cons, {$costData['cost']}, {$costData['t1']}, {$costData['t2']}, {$costData['t3']}, {$costData['t4']})";
    }
}
$sql .= implode(",\n", $monthlyEntries) . ";\n";


$outputFile = __DIR__ . '/fake_data.sql';
file_put_contents($outputFile, $sql);
echo "✓ Generated fake_data.sql with READINGS, DAILY & MONTHLY usage.\n";
echo "File: $outputFile\n";
?>
