<?php
// server/chatbot/chat_interface.php
// Bridge between Web Frontend and Python Chatbot Core

session_start();
set_time_limit(0); // Prevent PHP timeout
header('Content-Type: application/json');

// Check Auth
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get API Key (Device ID) from Session
$apiKey = $_SESSION['api_key'] ?? '';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_SESSION['admin_view_device_id'])) {
    $apiKey = $_SESSION['admin_view_device_id'];
}

if (empty($apiKey)) {
    echo json_encode(['status' => 'error', 'message' => 'No Device ID associated']);
    exit;
}

// Get Message
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Empty message']);
    exit;
}

// Sanitize for shell command (basic)
// Note: escapeshellarg is crucial for security
$safeKey = escapeshellarg($apiKey);
$safeMessage = escapeshellarg($message);

// Path to Python Script
// Assuming python is in PATH. If not, hardcode path e.g. "C:\\Python39\\python.exe"
// Path to Python Script
// Assuming python is in PATH. If not, hardcode path e.g. "C:\\Python39\\python.exe"
$pythonCmd = "python3"; 

// Check for Virtual Environment (common paths)
// 1. ../env/bin/python3 (If env is in server/)
// 2. ../../env/bin/python3 (If env is in project root)
$venvs = [
    __DIR__ . '/../env/bin/python3',
    __DIR__ . '/../../env/bin/python3'
];

foreach ($venvs as $venv) {
    if (file_exists($venv) && is_executable($venv)) {
        $pythonCmd = $venv;
        break;
    }
}

$scriptPath = __DIR__ . "/chat_core.py";

// Execute
$cmd = "$pythonCmd \"$scriptPath\" $safeKey $safeMessage";

// We need to set the working directory to where the script is, so it finds config.py and kb/
$cwd = __DIR__;
$descriptorspec = [
   0 => ["pipe", "r"],  // stdin
   1 => ["pipe", "w"],  // stdout
   2 => ["pipe", "w"]   // stderr
];

$process = proc_open($cmd, $descriptorspec, $pipes, $cwd);

if (is_resource($process)) {
    fclose($pipes[0]); // Close stdin

    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $return_value = proc_close($process);

    if ($return_value === 0) {
        $lines = explode("\n", trim($output));
        $debugLog = [];
        $finalResponse = "";

        foreach ($lines as $line) {
            if (strpos($line, "DEBUG:") === 0) {
                $debugLog[] = $line;
            } else {
                $finalResponse .= $line . "\n";
            }
        }

        echo json_encode([
            'status' => 'success', 
            'response' => trim($finalResponse),
            'debug_log' => $debugLog
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chatbot Process Error', 'debug' => $error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to launch chatbot process']);
}
?>
