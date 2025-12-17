<?php
// server/chatbot/api.php
// Backend API cho Chatbot IoT Digi
// Trả về dữ liệu JSON đã xử lý sẵn để Model không phải tính toán.

header('Content-Type: application/json');
require_once '../db/database_config.php';

// Kết nối database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB Connection failed']);
    exit;
}

// Lấy tham số
$action = $_GET['action'] ?? '';
$apiKey = $_GET['api_key'] ?? ''; // Device ID
$date   = $_GET['date'] ?? date('Y-m-d');
$month  = $_GET['month'] ?? date('m');
$year   = $_GET['year'] ?? date('Y');

// Validate API Key
if (empty($apiKey)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing api_key']);
    exit;
}
$apiKey = $conn->real_escape_string($apiKey);

// Hàm helper để query
function queryDB($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) return null;
    return $result;
}

$response = ['status' => 'success', 'data' => null];

switch ($action) {
    case 'get_monthly_summary':
        // Lấy tổng quan tháng hiện tại (hoặc tháng được chỉ định)
        // Tra ve: Tieu thu, Tong tien, Chi tiet tung bac
        $sql = "SELECT * FROM monthly_usage 
                WHERE device_id = '$apiKey' AND month = '$month' AND year = '$year' 
                LIMIT 1";
        $res = queryDB($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $response['data'] = [
                'period' => "$month/$year",
                'total_consumption' => (float)$row['consumption'],
                'total_cost' => (float)$row['cost'],
                'breakdown' => [
                    'tier_1' => (float)$row['tier_1_usage'],
                    'tier_2' => (float)$row['tier_2_usage'],
                    'tier_3' => (float)$row['tier_3_usage'],
                    'tier_4' => (float)$row['tier_4_usage'],
                ],
                'payment_status' => $row['payment_status']
            ];
        } else {
             $response['data'] = [
                'period' => "$month/$year",
                'total_consumption' => 0,
                'total_cost' => 0,
                'message' => 'No data found for this month'
            ];
        }
        break;

    case 'get_daily_usage':
        // Lấy tiêu thụ của một ngày cu the
        $sql = "SELECT * FROM daily_usage 
                WHERE device_id = '$apiKey' AND date = '$date' 
                LIMIT 1";
        $res = queryDB($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $response['data'] = [
                'date' => $row['date'],
                'consumption' => (float)$row['consumption'],
                'start_value' => (float)$row['start_value'],
                'end_value' => (float)$row['end_value']
            ];
        } else {
            $response['data'] = [
                'date' => $date,
                'consumption' => 0,
                'message' => 'No usage data for this date'
            ];
        }
        break;

    case 'get_usage_chart':
        // Tra ve du lieu 7 ngay gan nhat de ve bieu do hoac phan tich xu huong
        $sql = "SELECT date, consumption FROM daily_usage 
                WHERE device_id = '$apiKey' 
                ORDER BY date DESC LIMIT 7";
        $res = queryDB($sql);
        $chartData = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $chartData[] = [
                    'date' => $row['date'],
                    'val' => (float)$row['consumption']
                ];
            }
        }
        // Reverse lai de tang dan theo thoi gian
        $response['data'] = array_reverse($chartData);
        break;

    case 'get_active_alerts':
        // Lay cac canh bao chua giai quyet
        $sql = "SELECT type, message, value_at_event, timestamp 
                FROM alerts 
                WHERE device_id = '$apiKey' AND resolved = 0 
                ORDER BY timestamp DESC";
        $res = queryDB($sql);
        $alerts = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $alerts[] = [
                    'type' => $row['type'],
                    'message' => $row['message'],
                    'time' => date('Y-m-d H:i:s', $row['timestamp']),
                    'val' => $row['value_at_event']
                ];
            }
        }
        $response['data'] = $alerts;
        break;

    case 'get_device_status':
        // Lay trang thai thiet bi
        $sql = "SELECT is_online, last_reading_timestamp FROM device_status 
                WHERE device_id = '$apiKey' LIMIT 1";
        $res = queryDB($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $lastSeen = $row['last_reading_timestamp'] ? date('Y-m-d H:i:s', $row['last_reading_timestamp']) : 'Never';
            $response['data'] = [
                'online' => (bool)$row['is_online'],
                'last_seen' => $lastSeen
            ];
        } else {
            $response['data'] = ['online' => false, 'message' => 'Device not found'];
        }
        break;

    default:
        $response['status'] = 'error';
        $response['message'] = 'Invalid action. Supported: get_monthly_summary, get_daily_usage, get_usage_chart, get_active_alerts, get_device_status';
        break;
}

$conn->close();
echo json_encode($response);
?>
