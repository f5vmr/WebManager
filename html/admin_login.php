<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include config
require_once __DIR__ . '/../config/config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Force uppercase callsign
    $username = strtoupper(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!isset($ADMIN_USERS[$username])) {
        $error = "Invalid callsign";
    } else {
        // No password set yet → invite to set one
        if (empty($ADMIN_USERS[$username])) {
            if ($password === '') {
                $error = "Please enter a password to set";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                setAdminPassword($username, $hash);
                logAdminAction($username, 'Password set for first time');
                $error = "Password set! Please log in again.";
            }
        } else {
            // Normal login
            if (password_verify($password, $ADMIN_USERS[$username])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                logAdminAction($username, 'Successful login');
                header("Location: admin_panel.php");
                exit();
            } else {
                logAdminAction($username, 'Failed login attempt');
                $error = "Invalid credentials";
            }
        }
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
    <input type="text" name="username" placeholder="Callsign" required
           oninput="this.value = this.value.toUpperCase();">
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

    <?php if ($error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
</div>
</body>
</html>
