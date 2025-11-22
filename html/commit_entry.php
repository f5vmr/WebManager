<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // For standalone dev mode, you can comment this line:
    die("Not authorized");
}

if (!isset($_SESSION['new_callsign'], $_SESSION['new_pseudo'], $_SESSION['new_password'])) {
    die("Missing data. <a href='new_callsign.php'>Start again</a>");
}

define('SVX_CONF', '/etc/svxlink/svxreflector.conf');

$callsign = $_SESSION['new_callsign'];
$pseudo   = $_SESSION['new_pseudo'];
$password = $_SESSION['new_password'];

$user_line = "{$callsign} = {$pseudo}";
$pass_line = "{$pseudo} = {$password}";

// --- Load existing config ---
$original = @file(SVX_CONF, FILE_IGNORE_NEW_LINES);
if (!$original) {
    die("Cannot read config file: " . SVX_CONF);
}

$out = [];
$mode = '';

foreach ($original as $line) {
    $trim = trim($line);

    // Insert under [USERS]
    if ($trim === '[USERS]') {
        $mode = 'users';
        $out[] = $line;
        $out[] = $user_line;
        continue;
    }

    // Insert under [PASSWORDS]
    if ($trim === '[PASSWORDS]') {
        $mode = 'pass';
        $out[] = $line;
        $out[] = $pass_line;
        continue;
    }

    $out[] = $line;
}

$new_config_preview = implode("\n", $out);

// Clear temporary session data
unset($_SESSION['new_callsign'], $_SESSION['new_pseudo'], $_SESSION['new_password']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Preview Only (No Write)</title>
    <style>
        pre {
            background:#f4f4f4;
            padding:12px;
            border-radius:6px;
            border:1px solid #ccc;
            overflow-x:auto;
        }
    </style>
</head>
<body>

<h2>Step 4 — Preview Only</h2>

<p><strong>This is a preview of the FULL config file with the new lines inserted.</strong><br>
<b>No changes have been written.</b></p>

<h3>New lines to be added:</h3>

<p>[USERS]</p>
<pre><?= htmlspecialchars($user_line) ?></pre>

<p>[PASSWORDS]</p>
<pre><?= htmlspecialchars($pass_line) ?></pre>

<h3>Full file preview:</h3>
<pre><?= htmlspecialchars($new_config_preview) ?></pre>

<p><strong>Nothing has been changed on disk.</strong></p>

<p><a href="new_callsign.php">Add another callsign</a></p>

</body>
</html>
