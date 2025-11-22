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
    $pseudoMap = [];
    $active = [];
    $mode = '';

    foreach ($lines as $line) {
        $trim = trim($line);

        if ($trim === '[USERS]') { $mode = 'users'; continue; }
        if ($trim === '[PASSWORDS]') { $mode = 'passwords'; continue; }
        if (empty($trim) || str_starts_with($trim,'[')) { $mode = ''; continue; }

        if ($mode === 'users') {
            $active[] = !str_starts_with($trim,'#');
            $users[] = ltrim($trim,'#'); // contains the callsign or pseudopassword line
        }

        if ($mode === 'passwords') {
            if (str_contains($trim,'=')) {
                [$pseudo, $realpw] = explode('=', $trim, 2);
                $pseudoMap[trim($pseudo)] = trim($realpw);
            }
        }
    }

    $result = [];
    foreach ($users as $idx => $line) {
        // Extract callsign and pseudo-password from [USERS]
        // Assumes format: CALLSIGN = pseudopassword
        if (str_contains($line, '=')) {
            [$call, $pseudo] = explode('=', $line, 2);
            $call = trim($call);
            $pseudo = trim($pseudo);
            $realpw = $pseudoMap[$pseudo] ?? '';
        } else {
            // No '=' in [USERS], assume call is the line, pseudo is same as call lowercase
            $call = trim($line);
            $pseudo = strtolower($call);
            $realpw = $pseudoMap[$pseudo] ?? '';
        }

        $result[] = [
            'callsign' => $call,
            'password' => $realpw,
            'active' => $active[$idx] ?? false
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
