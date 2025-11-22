<?php
session_start();

// Require the config using absolute path to avoid relative path issues
require_once __DIR__ . '/../config/config.php';

// Initialize error variable
$error = '';

// Handle POST login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        if (isset($ADMIN_USERS[$username]) && password_verify($password, $ADMIN_USERS[$username])) {
            // Prevent session fixation
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;

            // Log successful login
            logAdminAction($username, 'Successful login');

            // Redirect to admin panel
            header("Location: admin_panel.php");
            exit();
        } else {
            // Log failed login attempt
            logAdminAction($username, 'Failed login attempt');
            $error = "Invalid credentials";
        }
    } else {
        $error = "Username and password are required";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>Admin Login</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Callsign" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <?php if ($error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
</div>
</body>
</html>
