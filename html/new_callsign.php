<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Callsign</title>
</head>
<body>

<h2>Step 1 — Enter Callsign</h2>

<form action="generate_password.php" method="POST">
    <input type="text" name="callsign" id="callsign" placeholder="CALLSIGN" required>
    <button type="submit">Next</button>
</form>

<script>
// force uppercase while typing
document.querySelector('#callsign').addEventListener('input', function () {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});
</script>

</body>
</html>
