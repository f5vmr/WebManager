<?php
// ============================
// Admin Users Configuration
// ============================
// Only these callsigns are valid. Passwords start empty.
$ADMIN_USERS = [
    "G4NAB" => "",
    "M0YDG" => "",
    "M0DIT" => ""
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

// ============================
// Save password back to config.php
// ============================
function setAdminPassword($username, $passwordHash) {
    global $ADMIN_USERS;
    $ADMIN_USERS[$username] = $passwordHash;

    // Build the PHP config file content
    $configContent = "<?php\n\$ADMIN_USERS = [\n";
    foreach ($ADMIN_USERS as $user => $hash) {
        $configContent .= "    \"$user\" => \"$hash\",\n";
    }
    $configContent .= "];\n\n";
    $configContent .= file_get_contents(__FILE__, false, null, strpos(file_get_contents(__FILE__), "// ============================\n// Logging function"));
    
    file_put_contents(__FILE__, $configContent);
}


