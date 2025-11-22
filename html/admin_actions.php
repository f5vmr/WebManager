<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

// Config file
$confFile = '/etc/svxlink/svxreflector.conf';
$backupDir = __DIR__ . '/../backups'; // You may need to create this folder with write permissions

if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Read the config into memory
$confData = file($confFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$users = [];
$passwords = [];

// Parse [USERS] and [PASSWORDS]
$section = '';
foreach ($confData as $line) {
    $line = trim($line);
    if (preg_match('/^\[(.+)]$/', $line, $m)) {
        $section = strtoupper($m[1]);
        continue;
    }

    if ($section === 'USERS' && preg_match('/^#?\s*(\S+)/', $line, $m)) {
        $callsign = $m[1];
        $users[$callsign] = strpos($line, '#') === 0 ? 'INACTIVE' : 'ACTIVE';
    }

    if ($section === 'PASSWORDS' && preg_match('/^(\S+)\s*=\s*(\S+)/', $line, $m)) {
        $passwords[$m[1]] = $m[2];
    }
}

// Read input from fetch()
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'fetch':
        // Return table data
        $response = [];
        foreach ($users as $callsign => $status) {
            $response[] = [
                'callsign' => $callsign,
                'password' => $passwords[strtolower($callsign)] ?? '',
                'status' => $status
            ];
        }
        echo json_encode(['success' => true, 'users' => $response]);
        break;

    case 'toggle':
        $callsign = strtoupper(trim($input['callsign'] ?? ''));
        if (!isset($users[$callsign])) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        // Backup
        $timestamp = date('Ymd_His');
        copy($confFile, "$backupDir/svxreflector.conf.$timestamp.bak");

        // Toggle ACTIVE/INACTIVE
        foreach ($confData as &$line) {
            if (preg_match('/^#?\s*' . preg_quote($callsign, '/') . '$/', $line)) {
                if (strpos($line, '#') === 0) {
                    $line = substr($line, 1); // Remove #
                    $users[$callsign] = 'ACTIVE';
                } else {
                    $line = '#' . $line;
                    $users[$callsign] = 'INACTIVE';
                }
                break;
            }
        }
        unset($line);

        file_put_contents($confFile, implode("\n", $confData) . "\n");
        echo json_encode(['success' => true, 'status' => $users[$callsign]]);
        break;

    case 'add':
        $callsign = strtoupper(trim($input['callsign'] ?? ''));
        if (!$callsign) {
            echo json_encode(['success' => false, 'error' => 'No callsign provided']);
            exit;
        }
        if (isset($users[$callsign])) {
            echo json_encode(['success' => false, 'error' => 'Callsign already exists']);
            exit;
        }

        // Generate unique 13-character password
        do {
            $newPass = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 13);
        } while (in_array($newPass, $passwords));

        // Backup
        $timestamp = date('Ymd_His');
        copy($confFile, "$backupDir/svxreflector.conf.$timestamp.bak");

        // Add new user
        $confData[] = $callsign; // [USERS] section
        $confData[] = strtolower($callsign) . '=' . $newPass; // [PASSWORDS] section

        file_put_contents($confFile, implode("\n", $confData) . "\n");
        echo json_encode(['success' => true, 'callsign' => $callsign, 'password' => $newPass]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

