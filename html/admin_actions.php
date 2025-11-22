<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Not authorized']);
    exit();
}

define('SVX_CONF','/etc/svxlink/svxreflector.conf');
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

// --- Helpers ---
function backupConfig() {
    $timestamp = date('Ymd_His');
    $backup = SVX_CONF.'.bak.'.$timestamp;
    copy(SVX_CONF, $backup);
}

function readConfig() {
    $lines = file(SVX_CONF, FILE_IGNORE_NEW_LINES);
    $users = [];
    $pws = [];
    $active = [];
    $mode = '';
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '[USERS]') { $mode='users'; continue; }
        if ($trim === '[PASSWORDS]') { $mode='pass'; continue; }
        if (empty($trim) || str_starts_with($trim,'[')) { $mode=''; continue; }
        
        if($mode==='users'){
            $active[] = !str_starts_with($trim,'#');
            $users[] = ltrim($trim,'#');
        } elseif($mode==='pass'){
            if(str_contains($trim,'=')){
                [$pseudo,$pw] = explode('=',$trim,2);
                $pseudo = trim($pseudo);
                $pw = trim($pw);
                $pws[$pseudo] = $pw;
            }
        }
    }

    // Combine users with real password from pseudo-password map
    $result = [];
    foreach($users as $idx => $call){
        $pseudo = strtolower($call);
        $result[] = [
            'callsign' => $call,
            'password' => $pws[$pseudo] ?? '', // retrieve actual password
            'active' => $active[$idx]
        ];
    }
    return $result;
}


function writeConfig($users) {
    backupConfig();
    $lines = file(SVX_CONF, FILE_IGNORE_NEW_LINES);
    $out=[];
    $mode='';
    foreach($lines as $line){
        $trim = trim($line);
        if($trim==='[USERS]'){ $mode='users'; $out[]=$line; continue; }
        if($trim==='[PASSWORDS]'){ $mode='pass'; $out[]=$line; $mode='pass'; continue; }
        if(empty($trim) || str_starts_with($trim,'[')){ $out[]=$line; $mode=''; continue; }
        if($mode==='users'){
            $call = ltrim($line,'#');
            $found=false;
            foreach($users as $u){
                if($u['callsign']===$call){
                    $out[]=($u['active']?'':'#').$call;
                    $found=true;
                    break;
                }
            }
            if(!$found) $out[]=$line;
        } elseif($mode==='pass'){
            [$pseudo,$pw] = explode('=',$line);
            $pseudo=trim($pseudo);
            foreach($users as $u){
                if(strtolower($u['callsign'])===$pseudo){
                    $out[]=$pseudo.'='.$u['password'];
                    break;
                }
            }
        } else {
            $out[]=$line;
        }
    }
    file_put_contents(SVX_CONF, implode("\n",$out));
}

// --- Actions ---
$users = readConfig();

switch($action){
    case 'fetch':
        echo json_encode(['success'=>true,'users'=>$users]);
        break;
    case 'add':
        $callsign = strtoupper($data['callsign'] ?? '');
        // Check exists
        foreach($users as $u){ if($u['callsign']===$callsign){ echo json_encode(['success'=>false]); exit; } }
        // Generate unique 13 char password
        do { $pw=substr(bin2hex(random_bytes(8)),0,13); } while(in_array($pw,array_column($users,'password')));
        $users[]=['callsign'=>$callsign,'password'=>$pw,'active'=>true];
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
