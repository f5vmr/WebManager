<?php
// ============================
// Admin Users Configuration
// ============================
// Keys are callsigns (usernames), values are password hashes
// Generate a hash with password_hash('yourpassword', PASSWORD_DEFAULT)
$ADMIN_USERS = [
    "M0ABC" => '$2y$10$E6p1JdW5l9J5kQDlP/0kOuN9VhXxuhPj.k2zI.xYeVx/7xYhM9sI2',
    "G7XYZ" => '$2y$10$7pVx7/4GqTnZK0D1a3j9JeIUKnTkl2F1hY9wLJQdT8G9GgFHoHxOy'
];

// ============================
// Logging function
// ============================
function logAdminAction($username, $action) {
    $logFile = __DIR__ . '/admin.log';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $entry = sprintf("[%s] - %s - %s - %s\n", date('Y-m-d H:i:s'), $ip, $username, $action);
    file_put_contents($logFile, $entry, FILE_APPEND);
}
