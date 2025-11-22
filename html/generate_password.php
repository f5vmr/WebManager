<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

include_once "../config/functions.php"; // contains generate_random_password()

// Validate input
$callsign = strtoupper(trim($_POST['callsign'] ?? ''));

if (!$callsign || !preg_match('/^[A-Z0-9]+$/', $callsign)) {
    die("Invalid callsign. <a href='new_callsign.php'>Try again</a>");
}

$password = generate_random_password(13);
$pseudo = strtolower($callsign);

// Store for next step
$_SESSION['new_callsign'] = $callsign;
$_SESSION['new_pseudo']   = $pseudo;
$_SESSION['new_password'] = $password;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Password</title>
</head>
<body>

<h2>Step 2 — Password Generated</h2>

<p><strong>Callsign:</strong> <?= htmlspecialchars($callsign) ?></p>
<p><strong>Generated Password:</strong> <?= htmlspecialchars($password) ?></p>

<form action="add_entry_preview.php" method="POST">
    <button type="submit">Proceed</button>
</form>

<form action="generate_password.php" method="POST" style="margin-top:10px;">
    <input type="hidden" name="callsign" value="<?= htmlspecialchars($callsign) ?>">
    <button type="submit">Generate New Password</button>
</form>

<p style="margin-top:20px;">
    <a href="new_callsign.php">Cancel</a>
</p>

</body>
</html>
