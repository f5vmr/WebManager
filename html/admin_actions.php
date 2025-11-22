<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Not authorized']);
    exit();
}

include_once 'functions.php'; // where generate_random_password() lives

define('SVX_CONF','/etc/svxlink/svxreflector.conf');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

// --- Helpers (same as before) ---
function backupConfig() { /* ... */ }
function readConfig() { /* ... */ }
function writeConfig($users) { /* ... */ }

$users = readConfig();

switch($action){
    case 'fetch':
        echo json_encode(['success'=>true,'users'=>$users]);
        break;

    case 'add_generated':
        $callsign = strtoupper(trim($data['callsign'] ?? ''));
        $password = $data['password'] ?? '';
        if (!$callsign || !$password) { echo json_encode(['success'=>false]); exit; }

        foreach($users as $u){
            if($u['callsign']===$callsign){
                echo json_encode(['success'=>false,'message'=>'Callsign exists']); exit;
            }
        }

        $users[] = [
            'callsign'=>$callsign,
            'pseudo'=>strtolower($callsign),
            'password'=>$password,
            'active'=>true
        ];

        echo json_encode(['success'=>true]);
        break;

    case 'activate':
    case 'deactivate':
        $callsign=$data['callsign'] ?? '';
        foreach($users as &$u){
            if($u['callsign']===$callsign){
                $u['active']=$action==='activate';
                break;
            }
        }
        echo json_encode(['success'=>true]);
        break;

    case 'commit':
        writeConfig($users);
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['success'=>false]);
        break;
}

