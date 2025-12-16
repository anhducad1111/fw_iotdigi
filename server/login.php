<?php
session_start();
require_once 'db/database_config.php'; // Ensure database connection logic is separated

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Connect to DB
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            $error = "Database connection error.";
        } else {
            $stmt = $conn->prepare("SELECT id, username, password, role, api_key FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Verify password (using password_verify for BCrypt)
                // Note: For 'admin' and 'default_user' placeholder hashes were inserted.
                // In a real scenario, use password_hash('password', PASSWORD_DEFAULT) to generate hashes.
                // For this demo, assuming password matches hash logic or fallback for plain text if verify fails (NOT RECOMMENDED for prod but useful for debug if hash mismatch)
                if (password_verify($password, $user['password'])) {
                    // Password correct
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['api_key'] = $user['api_key']; // Contains Device ID for Users
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "Invalid username.";
            }
            $stmt->close();
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IoT Digi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background: white;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h3 {
            color: #0d6efd;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h3>IoT Digi</h3>
            <p class="text-muted">Sign in to your account</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
        </form>
    </div>

</body>
</html>
