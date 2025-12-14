<?php
// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Database connection
$host = 'localhost';
$user = 'iotdigi';
$pass = 'iotdigi11';
$db = 'iotdigi_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'data' => []
];

$type = $_GET['type'] ?? 'month'; // day, month, year
$date = $_GET['date'] ?? date('Y-m-d'); // For day view
$month = $_GET['month'] ?? date('m'); // For month view
$year = $_GET['year'] ?? date('Y'); // For year view

if ($type === 'day') {
    // Get all readings for a specific day
    $result = $conn->query("
        SELECT 
            id,
            timestamp,
            value,
            error_code,
            error_message
        FROM readings
        WHERE DATE(CONVERT_TZ(FROM_UNIXTIME(timestamp), '+00:00', '+07:00')) = '$date'
        ORDER BY timestamp DESC
    ");
    
    $readings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $timestamp = intval($row['timestamp']);
            $datetime = date('h:i A, d M', $timestamp);
            
            $readings[] = [
                'id' => $row['id'],
                'value' => floatval($row['value']),
                'timestamp' => $timestamp,
                'datetime' => $datetime,
                'error_code' => intval($row['error_code']),
                'error_message' => $row['error_message']
            ];
        }
    }
    $response['data'] = $readings;
    
} elseif ($type === 'month') {
    // Get daily summaries for a specific month
    $result = $conn->query("
        SELECT 
            date,
            consumption,
            start_value,
            end_value
        FROM daily_usage
        WHERE YEAR(date) = $year AND MONTH(date) = $month
        ORDER BY date ASC
    ");
    
    $daily = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $datetime = date('d M, Y', strtotime($row['date']));
            $day = intval(date('d', strtotime($row['date'])));
            
            $daily[] = [
                'date' => $row['date'],
                'day' => $day,
                'consumption' => floatval($row['consumption']),
                'datetime' => $datetime,
                'start_value' => floatval($row['start_value']),
                'end_value' => floatval($row['end_value'])
            ];
        }
    }
    $response['data'] = $daily;
    
} elseif ($type === 'year') {
    // Get monthly summaries for a specific year
    $result = $conn->query("
        SELECT 
            year,
            month,
            consumption,
            cost
        FROM monthly_usage
        WHERE year = $year
        ORDER BY month ASC
    ");
    
    $monthly = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $month_name = date('F', mktime(0, 0, 0, intval($row['month']), 1));
            
            $monthly[] = [
                'year' => intval($row['year']),
                'month' => intval($row['month']),
                'month_name' => $month_name,
                'consumption' => floatval($row['consumption']),
                'cost' => floatval($row['cost'])
            ];
        }
    }
    $response['data'] = $monthly;
}

$conn->close();
echo json_encode($response);
