<?php
header('Content-Type: application/json');

// Device upload configuration
$DEVICE_IP = '192.168.1.100'; // Change this to your device IP
$CONFIG_FILE = __DIR__ . '/config.ini';
$UPLOAD_ENDPOINT = "http://{$DEVICE_IP}/upload/config/config.ini";

$response = [
    'status' => 'error',
    'message' => 'Unknown error'
];

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get the input data
    $input = json_decode(file_get_contents('php://input'), true);
    $maxRateValue = isset($input['maxRateValue']) ? floatval($input['maxRateValue']) : null;
    $interval = isset($input['interval']) ? intval($input['interval']) : null;
    
    if ($maxRateValue === null && $interval === null) {
        $response['message'] = 'Missing parameters';
        echo json_encode($response);
        exit;
    }
    
    // Check if config file exists
    $file_to_use = $CONFIG_FILE;
    
    if (!file_exists($file_to_use)) {
        $response['message'] = 'Config file not found: ' . $file_to_use;
        echo json_encode($response);
        exit;
    }
    
    // Check if file is readable
    if (!is_readable($file_to_use)) {
        $response['message'] = 'Config file is not readable. Check permissions.';
        echo json_encode($response);
        exit;
    }
    
    // Check if file is writable, if not try to make it writable
    if (!is_writable($file_to_use)) {
        if (!chmod($file_to_use, 0666)) {
            $response['message'] = 'Config file is not writable and cannot change permissions. Check file permissions (need 666 or 644).';
            echo json_encode($response);
            exit;
        }
    }
    
    // Read the config file
    $config = file_get_contents($file_to_use);
    if ($config === false) {
        $response['message'] = 'Failed to read config file';
        echo json_encode($response);
        exit;
    }
    
    // Update main.MaxRateValue if provided
    if ($maxRateValue !== null) {
        $config = preg_replace(
            '/^(main\.MaxRateValue\s*=\s*)[\d.]+/m',
            '${1}' . $maxRateValue,
            $config
        );
    }
    
    // Update Interval if provided
    if ($interval !== null) {
        $config = preg_replace(
            '/^(Interval\s*=\s*)\d+/m',
            '${1}' . $interval,
            $config
        );
    }
    
    // Write back to config file (use the same file we read from)
    $bytes_written = file_put_contents($file_to_use, $config);
    if ($bytes_written === false) {
        $response['message'] = 'Failed to write config file. Check file permissions and directory is writable.';
        echo json_encode($response);
        exit;
    }
    
    if ($bytes_written === 0) {
        $response['message'] = 'Config file write returned 0 bytes. File may not have been updated.';
        echo json_encode($response);
        exit;
    }
    
    // Upload to device using curl (use the same file we just wrote to)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $UPLOAD_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'data' => new CURLFile($file_to_use, 'text/plain', 'config.ini')
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $upload_result = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curl_error) {
        $response['status'] = 'partial';
        $response['message'] = "Config saved locally but upload failed: {$curl_error}";
        $response['http_code'] = $http_code;
    } else if ($http_code >= 200 && $http_code < 300) {
        $response['status'] = 'success';
        $response['message'] = 'Settings saved and uploaded successfully';
        $response['http_code'] = $http_code;
    } else {
        $response['status'] = 'partial';
        $response['message'] = "Config saved locally but device returned HTTP {$http_code}";
        $response['http_code'] = $http_code;
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Return current values
    $file_to_read = $CONFIG_FILE;
    
    if (!file_exists($file_to_read)) {
        $response['message'] = 'Config file not found';
        echo json_encode($response);
        exit;
    }
    
    $config = file_get_contents($file_to_read);
    $found_count = 0;
    
    // Extract main.MaxRateValue
    if (preg_match('/^main\.MaxRateValue\s*=\s*([\d.]+)/m', $config, $matches)) {
        $response['maxRateValue'] = floatval($matches[1]);
        $found_count++;
    }
    
    // Extract Interval
    if (preg_match('/^Interval\s*=\s*(\d+)/m', $config, $matches)) {
        $response['interval'] = intval($matches[1]);
        $found_count++;
    }
    
    if ($found_count > 0) {
        $response['status'] = 'success';
    } else {
        $response['message'] = 'Parameters not found in config file';
        echo json_encode($response);
        exit;
    }
}

echo json_encode($response);
?>
