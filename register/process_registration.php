<?php
session_start();
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../functions.php';
require __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$validated = "";
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/apache2/error.log');

// === Input Variables ===
$email       = $_POST['email'] ?? '';
$callsign    = strtoupper(trim($_POST['callsign'] ?? ''));
$repeater    = intval($_POST['repeater'] ?? 0);
$dmr_id      = trim($_POST['dmr_id'] ?? '');
$echolink_id = trim($_POST['echolink_id'] ?? '');

// === Validate Email ===
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    show_error_page("Invalid Email", "The email address you provided is invalid. Please check and try again.");
}

// === Check for missing IDs ===
if (empty($dmr_id) && empty($echolink_id)) {
    show_error_page("Registration Failed", "An ID number from either RadioId.net or EchoLink is required to validate your Callsign.<br><br>
    Please register with either of these, then run this page again.<br><br>In case of difficulty, email support@svxlink.uk.");
}

// === Perform Validation ===
if ($repeater === 0 && intval($dmr_id) > 0) {
    $result = validate_DMRUser($callsign, $dmr_id);
    $validated = $result['valid'] ?? false;
} elseif ($repeater === 1 && intval($dmr_id) > 0) {
    $result = validate_DMRRepeater($callsign, $dmr_id);
    $validated = $result['valid'] ?? false;
} elseif ($repeater === 0 && intval($echolink_id) > 0) {
    $validated = (echolink_lookup($echolink_id) !== false);
} elseif ($repeater === 1 && intval($echolink_id) > 0) {
    $validated = (echolink_lookup($echolink_id) !== false);
}

// === If validation failed ===
if (!$validated) {
    show_error_page("Registration Failed", "Registration failed for $callsign. Please check your input and try again.");
}

// === Define Config Paths ===
define('LIVE_CONF', '/etc/svxlink/svxreflector.conf');
$backupFile = '/etc/svxlink/svxreflector.conf.bak.' . date('YmdHis');
define('WORKING_CONF', '/var/www/html/tmp/svxreflector.conf');

// Ensure working directory
if (!file_exists(dirname(WORKING_CONF))) {
    mkdir(dirname(WORKING_CONF), 0755, true);
}

if (!file_exists(LIVE_CONF)) {
    show_error_page("System Error", "The SvxReflector configuration file could not be found on this system.");
}

// Backup and working copy
copy(LIVE_CONF, $backupFile);
copy(LIVE_CONF, WORKING_CONF);

// === Parse Config ===
$parsedData = parse_svx_file(WORKING_CONF);

// === Check for Existing Callsign ===
if (check_existing_callsign($parsedData, $callsign)) {
    show_error_page("Registration Failed", "Callsign $callsign is already registered in the SvxReflector.<br><br>If you need to update your registration, please email help@svxlink.uk.");
}

// === Generate Password and Add to Config ===
$password = generate_reflector_password();
$pwd_callsign = strtolower($callsign) . "pass";
$upload_callsign_line = $callsign . "=" . $pwd_callsign;
$upload_password_line =  $pwd_callsign . "=" . $password;

add_entry_to_section($parsedData, "USERS", $upload_callsign_line);
add_entry_to_section($parsedData, "PASSWORDS", $upload_password_line);

// === Write Back Config ===
try {
    write_svx_file(WORKING_CONF, $parsedData);
    copy(WORKING_CONF, LIVE_CONF);
    exec("systemctl restart svxreflector 2>&1", $out, $rc);
} catch (Exception $e) {
    show_error_page("System Error", "Unable to update configuration: " . htmlspecialchars($e->getMessage()));
}

// === Log Registration Attempt ===
$logLine = date('Y-m-d H:i:s') . " | " . $_SERVER['REMOTE_ADDR'] . " | $callsign | $email\n";
file_put_contents('/var/log/svxlink_registrations.log', $logLine, FILE_APPEND);

// === Display Success Page ===
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SVXLink User Registration</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>
<div class="container" style="text-align: justify;">
    <h1>SVXLink User Registration</h1>
    <div class="message-container alert alert-warning">
        <h2>Registration Succeeded</h2>
        <p>Callsign <strong>' . htmlspecialchars($callsign) . '</strong> has been successfully registered.</p>
        <p>Your password (AUTH_KEY) <strong>' . htmlspecialchars($password) . '</strong> will be emailed to you.</p>
        <p>Open your node dashboard and edit <code>svxlink.conf</code>.</p>
        <p>In the <strong>ReflectorLogic Section</strong>, locate the line <code>AUTH_KEY</code>.</p>
        <p>Copy the password from your email and place it in double quotes like <code>"' . htmlspecialchars($password) . '"</code>.</p>
        <p>Then click the <strong>Big Red Button</strong> to save your changes.</p>
        <p>If you have any further questions, please email help@svxlink.uk.</p>
    </div>
</div>
</body>
</html>';

// === Send Registration Email ===
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_port;

    $mail->setFrom($smtp_from_email, $smtp_from_name);
    $mail->addAddress($email, $callsign);

    $mail->isHTML(true);
    $mail->Subject = 'SVXLink Registration - Your AUTH_KEY';
    $mail->Body = "
        <h2>Welcome to SVXLink!</h2>
        <p>Your registration for callsign <strong>{$callsign}</strong> has been processed.</p>
        <p>Your AUTH_KEY is: <strong>{$password}</strong></p>
        <p>To configure your node:</p>
        <ol>
            <li>Edit <code>svxlink.conf</code></li>
            <li>Locate the <strong>ReflectorLogic Section</strong></li>
            <li>Find <code>AUTH_KEY</code></li>
            <li>Paste your AUTH_KEY in quotes: <code>\"{$password}\"</code></li>
        </ol>
        <p>You can now find your callsign listed in the local SvxReflector user list.</p>
        <p>Questions? Email <a href='mailto:help@svxlink.uk'>help@svxlink.uk</a></p>
    ";
    $mail->AltBody = "Your AUTH_KEY for $callsign is: $password";
    $mail->send();
} catch (Exception $e) {
    error_log("Email send failed for $callsign ($email): " . $mail->ErrorInfo);
}

/**
 * Utility function to show a styled error page and exit.
 */
function show_error_page($title, $message) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>SVXLink User Registration</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <h1>SVXLink User Registration</h1>
            <div class="message-container alert alert-warning">
                <h2>' . htmlspecialchars($title) . '</h2>
                <p>' . $message . '</p>
                <p><a href="register.php" style="color:#4f86f7;">Try Again</a></p>
            </div>
        </div>
    </body>
    </html>';
    exit;
}
?>
