<?php
// server/auth_check.php - Reusable auth logic

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

/**
 * Determine the Target Device ID for the query based on User Role.
 * 
 * Rules:
 * 1. Admin:
 *    - If $_SESSION['admin_view_device_id'] is set, use it.
 *    - If not set, return NULL (meaning "show nothing" or "select a device first").
 *      (Alternatively, could return 'all' but that complicates queries significantly).
 * 2. User:
 *    - Always reuse $_SESSION['api_key'] (which is their Device ID).
 */
function getTargetDeviceId() {
    $role = $_SESSION['role'] ?? 'user';

    if ($role === 'admin') {
        if (isset($_SESSION['admin_view_device_id']) && !empty($_SESSION['admin_view_device_id'])) {
            return $_SESSION['admin_view_device_id'];
        }
        return null; // No device selected
    } else {
        // Regular user
        return $_SESSION['api_key'] ?? null;
    }
}
?>
