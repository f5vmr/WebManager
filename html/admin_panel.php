<?php
session_start();
require_once '../config/config.php';

// Check login
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit();
}

$confFile = '/etc/svxlink/svxreflector.conf';

// Function to parse conf file
function parseConf($file) {
    $users = [];
    $passwords = [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $section = '';
    foreach ($lines as $idx => $line) {
        $trim = trim($line);
        if ($trim === '' || $trim[0] === ';') continue; // skip empty or comments
        if (preg_match('/^\[(.*)\]$/', $trim, $m)) {
            $section = strtoupper($m[1]);
            continue;
        }

        if ($section === 'USERS') {
            $comment = ($trim[0] === '#');
            $cleanLine = ltrim($trim, '#');
            $users[$cleanLine] = ['line'=>$idx, 'active'=>!$comment];
        }
        elseif ($section === 'PASSWORDS') {
            [$call, $pass] = explode('=', $trim, 2);
            $passwords[trim($call)] = trim($pass);
        }
    }
    return [$users, $passwords, $lines];
}

list($users, $passwords, $lines) = parseConf($confFile);

function getStatus($active) {
    return $active ? 'ACTIVE' : 'INACTIVE';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel - SvxLink Users</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="admin-container">
    <h1>Admin Panel - SvxLink Users</h1>

    <!-- Add New User Form -->
    <div class="user-form">
        <h2>Add New Callsign</h2>
        <form id="add-user-form" method="POST" action="admin_actions.php">
            <input type="text" name="callsign" placeholder="CALLSIGN (uppercase)" required>
            <button type="submit" name="action" value="add">Add User</button>
        </form>
        <p>New password will be generated automatically.</p>
    </div>

    <!-- Users Table -->
    <table class="users-list">
        <thead>
            <tr>
                <th>CALLSIGN</th>
                <th>Password</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $call => $info): ?>
            <tr class="<?= $info['active'] ? 'active' : 'inactive' ?>">
                <td><?= htmlspecialchars($call) ?></td>
                <td><?= htmlspecialchars($passwords[strtolower($call)] ?? '') ?></td>
                <td><?= getStatus($info['active']) ?></td>
                <td class="action-buttons">
                    <form method="POST" action="admin_actions.php" style="display:inline;">
                        <input type="hidden" name="callsign" value="<?= htmlspecialchars($call) ?>">
                        <button type="submit" name="action" value="toggle">
                            <?= $info['active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
