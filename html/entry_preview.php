<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_SESSION['new_callsign'], $_SESSION['new_pseudo'], $_SESSION['new_password'])) {
    die("Missing data. <a href='new_callsign.php'>Start again</a>");
}

$callsign = $_SESSION['new_callsign'];
$pseudo   = $_SESSION['new_pseudo'];
$password = $_SESSION['new_password'];

$user_line = "{$callsign} = {$pseudo}";
$pass_line = "{$pseudo} = {$password}";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Preview Entry</title>
</head>
<body>

<h2>Step 3 — Confirm New Entry</h2>

<p>The following lines will be added to <code>svxreflector.conf</code>:</p>

<h3>[USERS] section:</h3>
<pre><?= htmlspecialchars($user_line) ?></pre>

<h3>[PASSWORDS] section:</h3>
<pre><?= htmlspecialchars($pass_line) ?></pre>

<form action="commit_entry.php" method="POST">
    <button type="submit">Commit to Configuration</button>
</form>

<p style="margin-top:20px;">
    <a href="new_callsign.php">Cancel</a>
</p>

</body>
</html>
