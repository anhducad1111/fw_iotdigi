<?php
header('Content-Type: application/json');

// Set timezone to Vietnam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Database connection
$db_host = 'localhost';
$db_user = 'iotdigi';
$db_pass = 'iotdigi11';
$db_name = 'iotdigi_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}


$WEBHOOK_MAGIC_HEADER = 0x44494749; // "DIGI"
$ERROR_CODES = [
    0 => "no error",
    1 => "Neg. Rate",
    2 => "Rate too high",
    255 => "unknown error"
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // 1. API Key Check
        $api_key = $_SERVER['HTTP_APIKEY'] ?? '';
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Invalid API key"]);
            exit;
        }
        
        $user_row = $result->fetch_assoc();
        $device_id = $user_row['api_key']; // Use API Key as Device ID
        $stmt->close();

        // 2. Content Type Check
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        if ($content_type != 'application/octet-stream') {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid Content-Type"]);
            exit;
        }

        // 3. Get Data
        $body = file_get_contents('php://input');
        if (!$body || strlen($body) < 8) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Empty or too small body"]);
            exit;
        }

        // 4. Parse Data
        $parsed = parse_binary_packet($body);

        // 5. Save to database
        if (!empty($parsed['items'])) {
            $item = $parsed['items'][0];
            $value = floatval($item['value']);
            $timestamp = intval($item['timestamp']);
            $error_code = intval($item['errorCode']);
            $error_message = $item['error'];
            
            $stmt = $conn->prepare("INSERT INTO readings (device_id, value, timestamp, error_code, error_message) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdiss", $device_id, $value, $timestamp, $error_code, $error_message);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert reading: " . $stmt->error);
            }
            $stmt->close();
            
            // Update daily_usage for this day
            update_daily_usage($conn, $timestamp, $device_id);
            
            // Update monthly_usage for this month
            update_monthly_usage($conn, $timestamp, $device_id);
        }

        // 6. Log
        error_log("Webhook received and saved to database: " . json_encode($parsed));

        echo json_encode(["status" => "success", "data" => $parsed]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}

function parse_binary_packet($data) {
    if (strlen($data) < 8) {
        throw new Exception("Packet too small");
    }

    // Read magic header (big-endian uint32)
    $magic = unpack('N', substr($data, 0, 4))[1];
    if ($magic != $GLOBALS['WEBHOOK_MAGIC_HEADER']) {
        throw new Exception("Invalid magic header");
    }

    // Read data length (big-endian uint16)
    $data_length = unpack('n', substr($data, 4, 2))[1];
    $expected_total = 4 + 2 + $data_length + 2;
    if (strlen($data) != $expected_total) {
        throw new Exception("Packet length mismatch");
    }

    // Extract data section
    $data_section = substr($data, 6, $data_length);

    // Read CRC (big-endian uint16)
    $received_crc = unpack('n', substr($data, 6 + $data_length, 2))[1];
    $calculated_crc = calculate_crc16(substr($data, 0, 6 + $data_length));

    // Skip CRC check for testing
    // if ($received_crc != $calculated_crc) {
    //     throw new Exception("CRC16 mismatch");
    // }

    // Parse data section
    $offset = 0;
    $num_items = ord($data_section[$offset++]);

    $items = [];
    for ($i = 0; $i < $num_items; $i++) {
        $item = [];

        // Read strings
        // NOTE: Name field removed from binary protocol
        // $item['name'] = read_string($data_section, $offset);
        
        $unpacked = unpack('J', substr($data_section, $offset, 8));
        $item['timestamp'] = $unpacked[1];
        
        $offset += 8;
        $item['rawValue'] = read_string($data_section, $offset);
        $item['value'] = read_string($data_section, $offset);
        $item['preValue'] = read_string($data_section, $offset);
        $item['rate'] = read_string($data_section, $offset);
        $item['changeAbsolute'] = read_string($data_section, $offset);
        $item['errorCode'] = ord($data_section[$offset++]);
        $item['error'] = $GLOBALS['ERROR_CODES'][$item['errorCode']] ?? "unknown error";

        $items[] = $item;
    }

    return [
        'receivedAt' => date('Y-m-d H:i:s'),
        'magic' => sprintf('0x%08X', $magic),
        'dataLength' => $data_length,
        'numItems' => $num_items,
        'checksum' => sprintf('0x%04X', $received_crc),
        'packetSize' => strlen($data),
        'items' => $items
    ];
}

function read_string(&$data, &$offset) {
    $length = ord($data[$offset++]);
    $string = substr($data, $offset, $length);
    $offset += $length;
    return $string;
}

function calculate_crc16($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $byte = ord($data[$i]);
        $crc ^= $byte << 8;
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc = $crc << 1;
            }
            $crc &= 0xFFFF;
        }
    }
    return $crc;
}

function update_daily_usage($conn, $timestamp, $device_id) {
    $date = date('Y-m-d', $timestamp);
    
    // Get all readings for this day and this device
    $stmt = $conn->prepare("SELECT value FROM readings WHERE device_id = ? AND DATE(FROM_UNIXTIME(timestamp)) = ? ORDER BY timestamp ASC");
    $stmt->bind_param("ss", $device_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows < 1) {
        return;
    }
    
    $readings = [];
    while ($row = $result->fetch_assoc()) {
        $readings[] = $row['value'];
    }
    
    // Calculate consumption for the day
    if (count($readings) > 0) {
        $start_value = $readings[0];
        $end_value = $readings[count($readings) - 1];
        $consumption = round($end_value - $start_value, 2);
        
        // Insert or update daily_usage
        $stmt = $conn->prepare("INSERT INTO daily_usage (device_id, date, consumption, start_value, end_value) 
                                VALUES (?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE 
                                consumption = VALUES(consumption), 
                                start_value = VALUES(start_value), 
                                end_value = VALUES(end_value)");
        $stmt->bind_param("ssddd", $device_id, $date, $consumption, $start_value, $end_value);
        $stmt->execute();
        $stmt->close();
    }
}

function update_monthly_usage($conn, $timestamp, $device_id) {
    $year = date('Y', $timestamp);
    $month = date('m', $timestamp);
    
    // Get all readings for this month and device
    $stmt = $conn->prepare("SELECT value FROM readings WHERE device_id = ? AND YEAR(FROM_UNIXTIME(timestamp)) = ? AND MONTH(FROM_UNIXTIME(timestamp)) = ? ORDER BY timestamp ASC");
    $stmt->bind_param("sii", $device_id, $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows < 1) {
        return;
    }
    
    $readings = [];
    while ($row = $result->fetch_assoc()) {
        $readings[] = $row['value'];
    }
    
    // Calculate consumption for the month
    if (count($readings) > 0) {
        $start_value = $readings[0];
        $end_value = $readings[count($readings) - 1];
        $consumption = round($end_value - $start_value, 2);
        
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
        
        // Insert or update monthly_usage
        $stmt = $conn->prepare("INSERT INTO monthly_usage (device_id, year, month, consumption, cost, tier_1_usage, tier_2_usage, tier_3_usage, tier_4_usage) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE 
                                consumption = VALUES(consumption), 
                                cost = VALUES(cost), 
                                tier_1_usage = VALUES(tier_1_usage),
                                tier_2_usage = VALUES(tier_2_usage),
                                tier_3_usage = VALUES(tier_3_usage),
                                tier_4_usage = VALUES(tier_4_usage)");
        $stmt->bind_param("siidddddd", $device_id, $year, $month, $consumption, $cost, $tier1, $tier2, $tier3, $tier4);
        $stmt->execute();
        $stmt->close();
    }
}

?>