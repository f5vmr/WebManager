<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

include 'functions.php';

// === Local Configuration Paths ===
define('LIVE_CONF', '/etc/svxlink/svxreflector.conf');
define('WORKING_CONF', '/var/www/html/tmp/svxreflector.conf');
define('BACKUP_CONF', '/etc/svxlink/svxreflector.conf.bak');

// === Ensure working directory exists ===
if (!file_exists(dirname(WORKING_CONF))) {
    mkdir(dirname(WORKING_CONF), 0755, true);
}

// === Copy the live config locally for editing ===
if (file_exists(LIVE_CONF)) {
    copy(LIVE_CONF, WORKING_CONF);
    exec("chown www-data:www-data " . WORKING_CONF);
    exec("chmod 664 " . WORKING_CONF);
}

// === Handle AJAX requests ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // --- Toggle user activation ---
    if (isset($data['action']) && isset($data['callsign'])) {
        $lines = file(WORKING_CONF, FILE_IGNORE_NEW_LINES);
        foreach ($lines as &$line) {
            if (preg_match('/^\#?' . preg_quote($data['callsign'], '/') . '\s*=/', $line)) {
                if ($data['action'] === 'deactivate') {
                    if ($line[0] !== '#') $line = '#' . $line;
                } elseif ($data['action'] === 'reactivate') {
                    $line = ltrim($line, '#');
                }
            }
        }
        file_put_contents(WORKING_CONF, implode(PHP_EOL, $lines) . PHP_EOL);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // --- Apply changes and restart service ---
    if (isset($data['action']) && $data['action'] === 'apply_changes') {
        // Backup existing live config
        if (file_exists(LIVE_CONF)) {
            copy(LIVE_CONF, BACKUP_CONF);
        }

        // Copy working copy to live
        $copied = copy(WORKING_CONF, LIVE_CONF);

        if ($copied) {
            // Restart local svxreflector
            exec("systemctl restart svxreflector 2>&1", $output, $resultCode);
            $success = ($resultCode === 0);
        } else {
            $success = false;
        }

        // Return confirmation page
        header('Content-Type: text/html');
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Changes Applied - SVXReflector</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #1c1b29;
                    color: #d4d4ff;
                    text-align: center;
                    padding: 50px;
                }
                .confirmation-container {
                    margin: 50px auto;
                    background-color: #2a2738;
                    padding: 30px;
                    border-radius: 10px;
                    width: 400px;
                    box-shadow: 0 0 20px rgba(0,0,0,0.3);
                }
                .back-button {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 20px;
                    background: #007bff;
                    color: white;
                    border-radius: 5px;
                    text-decoration: none;
                }
                .back-button:hover { background: #006bf0; }
            </style>
        </head>
        <body>
            <div class="confirmation-container">
                <h2>' . ($success ? 'Changes Applied Successfully' : 'Error Applying Changes') . '</h2>
                <p>' . ($success ? 'SVXReflector restarted successfully.' : 'Manual restart may be required.') . '</p>
                <p>A backup has been saved as <code>' . BACKUP_CONF . '</code>.</p>
                <a href="index.php" class="back-button">Back to SVXLink View Panel</a>
            </div>
        </body>
        </html>';
        exit;
    }
}

// === Parse Users and Passwords for Display ===
$data = parseUsers(WORKING_CONF);
$users = $data['users'];
$passwords = $data['passwords'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SVXReflector User Management</title>
    <style>
        :root {
            --bg-color: #1c1b29;
            --text-color: #d4d4ff;
            --highlight-color: #4f86f7;
            --card-bg-color: #2a2738;
            --border-color: #393552;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
        }
        .frame-container {
            width: 600px;
            margin: 20px auto;
            background-color: var(--card-bg-color);
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #e0e0e0;
        }
        .user-entry {
            margin: 8px 0;
            padding: 12px;
            border-radius: 4px;
            color: white;
        }
        .user-entry:nth-child(odd) { background: rgb(168, 168, 220); }
        .user-entry:nth-child(even) { background: rgb(112, 112, 209); }
        .user-input {
            padding: 8px;
            margin-right: 10px;
            width: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: var(--card-bg-color);
            color: #fff;
        }
        .action-button {
            padding: 8px 15px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .action-button.deactivate { background: #ff0000; color: white; }
        .action-button.reactivate { background: #00ff00; color: black; }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-active { background: #1565c0; }
        .status-inactive { background: #b71c1c; }
        .control-buttons {
            text-align: center;
            margin: 15px 0;
        }
        .apply-button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .apply-button:hover { background: #006bf0; }
    </style>
</head>
<body>
    <div class="frame-container">
        <h2>SVXReflector User Management</h2>
        <div class="control-buttons">
            <button class="apply-button" onclick="applyChanges()">Apply Changes</button>
        </div>

        <div class="users-container">
        <?php foreach ($users as $callsign => $password_ref):
            $is_inactive = strpos($callsign, '#') === 0;
            $display_callsign = $is_inactive ? ltrim($callsign, '#') : $callsign;
            $password = isset($passwords[$password_ref]) ? $passwords[$password_ref] : '';
        ?>
            <div class="user-entry <?php echo $is_inactive ? 'inactive' : 'active'; ?>">
                <span class="status-indicator <?php echo $is_inactive ? 'status-inactive' : 'status-active'; ?>"></span>
                <input type="text" class="user-input" value="<?php echo htmlspecialchars($display_callsign); ?>" readonly>
                <input type="text" class="user-input" value="<?php echo htmlspecialchars($password); ?>" readonly>
                <button class="action-button <?php echo $is_inactive ? 'reactivate' : 'deactivate'; ?>"
                        onclick="toggleUserStatus('<?php echo htmlspecialchars($display_callsign); ?>', 
                                                  '<?php echo $is_inactive ? 'reactivate' : 'deactivate'; ?>')">
                    <?php echo $is_inactive ? 'Reactivate' : 'Deactivate'; ?>
                </button>
            </div>
        <?php endforeach; ?>
        </div>

        <div class="control-buttons">
            <button class="apply-button" onclick="applyChanges()">Apply Changes</button>
        </div>
    </div>

<script>
function toggleUserStatus(callsign, action) {
    fetch('admin_panel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, callsign: callsign })
    })
    .then(response => response.json())
    .then(data => { if (data.success) location.reload(); });
}

function applyChanges() {
    fetch('admin_panel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'apply_changes' })
    })
    .then(response => response.text())
    .then(html => { document.documentElement.innerHTML = html; });
}
</script>
</body>
</html>
