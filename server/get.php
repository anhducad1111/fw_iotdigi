<?php
require_once 'auth_check.php';
header('Content-Type: application/json');

// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Database connection
require_once 'db/database_config.php';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$response = [
    'status' => 'success',
    'webhook_data' => null,
    'monthly_cost' => null,
    'today_usage' => null,
    'monthly_usage' => null
];

$targetDeviceId = getTargetDeviceId();

if (!$targetDeviceId) {
    // No device selected or accessible
    echo json_encode($response);
    $conn->close();
    exit;
}

$deviceId = $conn->real_escape_string($targetDeviceId);

// Get latest reading
$result = $conn->query("SELECT * FROM readings WHERE device_id = '$deviceId' ORDER BY timestamp DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $response['webhook_data'] = [
        'name' => 'main',
        'timestamp' => $row['timestamp'],
        'rawValue' => $row['value'],
        'value' => $row['value'],
        'preValue' => $row['value'],
        'rate' => '0.00', // Will be calculated from previous reading
        'changeAbsolute' => '0.00',
        'errorCode' => $row['error_code'],
        'error' => $row['error_message'] ?? 'no error'
    ];
    
    // Get previous reading to calculate rate
    $prevResult = $conn->query("SELECT value, timestamp FROM readings WHERE device_id = '$deviceId' AND timestamp < " . $row['timestamp'] . " ORDER BY timestamp DESC LIMIT 1");
    if ($prevResult && $prevRow = $prevResult->fetch_assoc()) {
        $changeAbsolute = $row['value'] - $prevRow['value'];
        $timeDiffHours = ($row['timestamp'] - $prevRow['timestamp']) / 3600; // Convert seconds to hours
        $rate = $timeDiffHours > 0 ? $changeAbsolute / $timeDiffHours : 0;
        
        $response['webhook_data']['preValue'] = $prevRow['value'];
        $response['webhook_data']['changeAbsolute'] = number_format($changeAbsolute, 2);
        $response['webhook_data']['rate'] = number_format($rate, 2);
    }
}

// Get this month's cost
$currentYear = date('Y');
$currentMonth = date('m');
$monthResult = $conn->query("SELECT consumption, cost FROM monthly_usage WHERE device_id = '$deviceId' AND year = " . $currentYear . " AND month = " . $currentMonth);
if ($monthResult && $monthRow = $monthResult->fetch_assoc()) {
    $response['monthly_cost'] = [
        'consumption' => $monthRow['consumption'],
        'cost' => $monthRow['cost']
    ];
}

// Get today's usage
$today = date('Y-m-d');
$todayResult = $conn->query("SELECT consumption FROM daily_usage WHERE device_id = '$deviceId' AND date = '" . $today . "'");
if ($todayResult && $todayRow = $todayResult->fetch_assoc()) {
    $response['today_usage'] = $todayRow['consumption'];
}

// Get this month's total
if ($monthResult && isset($monthRow)) {
    $response['monthly_usage'] = $monthRow['consumption'] ?? 0;
}

$conn->close();
echo json_encode($response);
?>
