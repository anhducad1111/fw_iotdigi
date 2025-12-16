<?php
// Script to ensure users exist and update their passwords/api_keys
// Run this file once to setup the demo users.

require_once 'database_config.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Users to create/update
$users = [
    'admin' => [
        'password' => 'admin123',
        'role' => 'admin',
        'api_key' => NULL,
        'full_name' => 'System Administrator'
    ],
    'user1' => [
        'password' => 'user123',
        'role' => 'user',
        'api_key' => '123',
        'full_name' => 'User One (Device 123)'
    ],
    'user2' => [
        'password' => 'user234',
        'role' => 'user',
        'api_key' => '456',
        'full_name' => 'User Two (Device 456)'
    ]
];

echo "Setting up users...\n\n";

foreach ($users as $username => $data) {
    // Generate BCrypt hash
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $role = $data['role'];
    $apiKey = $data['api_key']; // Can be NULL
    $fullName = $data['full_name'];
    
    // Prepare Statement for UPSERT (Insert or Update)
    // We use username as the unique key to check collision
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, api_key, full_name) 
                            VALUES (?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            password = VALUES(password), 
                            role = VALUES(role), 
                            api_key = VALUES(api_key),
                            full_name = VALUES(full_name)");
                            
    $stmt->bind_param("sssss", $username, $hash, $role, $apiKey, $fullName);
    
    if ($stmt->execute()) {
        echo "[SUCCESS] Processed user '$username'.\n";
        echo "          Password: " . $data['password'] . "\n";
        echo "          API Key:  " . ($apiKey ? $apiKey : "NULL") . "\n";
    } else {
        echo "[ERROR]   Failed to process user '$username': " . $stmt->error . "\n";
    }
    echo "---------------------------------------------------\n";
    $stmt->close();
}


$conn->close();
echo "\nDone.";
?>
