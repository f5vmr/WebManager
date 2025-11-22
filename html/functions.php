<?php
// Automatically use system-wide svxreflector config
define('SVXREFLECTOR_CONF', '/etc/svxlink/svxreflector.conf');

// Verify the file exists and is readable
if (!file_exists(SVXREFLECTOR_CONF) || !is_readable(SVXREFLECTOR_CONF)) {
    error_log("Warning: svxreflector.conf not found or unreadable at " . SVXREFLECTOR_CONF);
}

// Function to parse users and passwords from the configuration file
function parseUsers($file = SVXREFLECTOR_CONF) {
    if (!file_exists($file)) {
        return ['users' => [], 'passwords' => []];
    }

    $users = [];
    $passwords = [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $section = '';

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        if ($line[0] === '[' && substr($line, -1) === ']') {
            $section = trim($line, '[]');
            continue;
        }

        if ($section === 'USERS' && strpos($line, '=') !== false) {
            [$callsign, $password_ref] = array_map('trim', explode('=', $line, 2));
            $users[$callsign] = $password_ref;
        } elseif ($section === 'PASSWORDS' && strpos($line, '=') !== false) {
            [$callsign, $password] = array_map('trim', explode('=', $line, 2));
            $passwords[$callsign] = $password;
        }
    }

    return ['users' => $users, 'passwords' => $passwords];
}
// Function to deactivate a user by adding #
function deactivate_user($file, $callsign) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updated = false;

    foreach ($lines as $key => $line) {
        if (strpos($line, "$callsign=") === 0) {
            $lines[$key] = "#$line";
            $updated = true;
            break;
        }
    }

    if ($updated) {
        file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
// Function to reactivate a user by removing #
function reactivate_user($file, $callsign) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updated = false;

    foreach ($lines as $key => $line) {
        if (strpos($line, "#$callsign=") === 0) {
            $lines[$key] = ltrim($line, "#");
            $updated = true;
            break;
        }
    }

    if ($updated) {
        file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}

// Add these new functions to your existing functions.php

function editUser($callsign, $newPassKey) {
    // Implementation for editing user pass key
    $configFile = SVXREFLECTOR_CONF;;
    $lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updated = false;
    
    foreach ($lines as $key => $line) {
        if (strpos($line, "$callsign=") === 0) {
            $lines[$key] = "$callsign=$newPassKey";
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        file_put_contents($configFile, implode(PHP_EOL, $lines) . PHP_EOL);
        return true;
    }
    return false;
}
function validate_DMRUser($callsign, $dmr_id) {
    $url = "https://radioid.net/api/dmr/user/?id=$dmr_id";
    $json = file_get_contents($url);
    $data = json_decode($json, true);
    $is_valid = ($data['count'] ?? 0) > 0;
    return ['valid' => $is_valid];
}

function validate_DMRRepeater($callsign, $dmr_id) {
    $url = "https://radioid.net/api/dmr/repeater/?id=$dmr_id";
    $json = file_get_contents($url);
    $data = json_decode($json, true);
    $is_valid = ($data['count'] ?? 0) > 0;
    return ['valid' => $is_valid];
}
//function deleteUser($callsign) {
//    $configFile = SVXREFLECTOR_CONF;
//    $lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
//    $updated = false;
//    
//    foreach ($lines as $key => $line) {
//        if (strpos($line, "$callsign=") === 0) {
//            unset($lines[$key]);
//            $updated = true;
//            break;
//        }
//    }
//    
//    if ($updated) {
//        file_put_contents($configFile, implode(PHP_EOL, $lines) . PHP_EOL);
//        return true;
//    }
//    return false;
//}

function fetchNewCopy() {
    // Implementation for fetching new copy of configuration
    $sourceFile = 'path/to/source/config';
    $destFile = 'path/to/destination/config';
    return copy($sourceFile, $destFile);
}

function pushLive() {
    // Implementation for pushing changes live
    $stagingFile = 'path/to/staging/config';
    $liveFile = 'path/to/live/config';
    return copy($stagingFile, $liveFile);
}
function editUserPassword($file, $callsign, $new_password_ref) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updated = false;

    foreach ($lines as $key => $line) {
        if (strpos($line, "$callsign=") === 0) {
            $lines[$key] = "$callsign=$new_password_ref";
            $updated = true;
            break;
        }
    }

    if ($updated) {
        file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}

function deleteUser($file, $callsign) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $filtered_lines = array_filter($lines, function($line) use ($callsign) {
        return strpos($line, "$callsign=") !== 0;
    });
    
    file_put_contents($file, implode(PHP_EOL, $filtered_lines) . PHP_EOL);
}

/**
 * Generate a random password using digits and upper/lowercase letters.
 *
 * @param int $length Length of the password to generate (default 13).
 * @return string The generated password.
 */
function generate_random_password($length = 13) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $maxIndex = strlen($chars) - 1;
    $password = '';

    // Use cryptographically secure random_int when available
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $maxIndex)];
    }

    return $password;
}
function echolink_lookup($node) {
    $url = "https://www.echolink.org/validation/node_lookup.jsp";
    $postData = http_build_query(['node' => $node]);

    // Use cURL to POST the data
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    curl_close($ch);

    // Bail if no response or an error page
    if (!$response || stripos($response, 'Callsign') === false) {
        return false;
    }

    // Parse out the two <td> fields (callsign and node number)
    if (preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $response, $matches)) {
        if (count($matches[1]) >= 2) {
            return [
                'callsign' => trim($matches[1][0]),
                'node'     => trim($matches[1][1])
            ];
        }
    }

    return false;
}
