<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success'=>false,'message'=>'Not authorized']);
    exit();
}

include_once 'functions.php'; // Contains generate_random_password()

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
        if ($trim === '[USERS]') { $mode='users'; continue; }
        if ($trim === '[PASSWORDS]') { $mode='passwords'; continue; }
        if (empty($trim) || str_starts_with($trim,'[')) { $mode=''; continue; }

        if ($mode==='users') {
            $active[] = !str_starts_with($trim,'#');
            $users[] = ltrim($trim,'#');
        }

        if ($mode==='passwords' && str_contains($trim,'=')) {
            [$pseudo, $realpw] = explode('=', $trim, 2);
            $pseudoMap[trim($pseudo)] = trim($realpw);
        }
    }

    $result=[];
    foreach ($users as $idx => $line) {
        if (str_contains($line,'=')) {
            [$call,$pseudo]=explode('=',$line,2);
            $call=trim($call);
            $pseudo=trim($pseudo);
        } else {
            $call=trim($line);
            $pseudo=strtolower($call);
        }
        $realpw=$pseudoMap[$pseudo]??'';
        $result[]= ['callsign'=>$call,'password'=>$realpw,'active'=>$active[$idx]??false,'pseudo'=>$pseudo];
    }
    return $result;
}

function writeConfig($users) {
    backupConfig();
    $lines=file(SVX_CONF,FILE_IGNORE_NEW_LINES);
    $out=[]; $mode='';
    foreach($lines as $line){
        $trim=trim($line);
        if($trim==='[USERS]'){$mode='users'; $out[]=$line; continue;}
        if($trim==='[PASSWORDS]'){$mode='pass'; $out[]=$line; continue;}
        if(empty($trim) || str_starts_with($trim,'[')){$out[]=$line; $mode=''; continue;}

        if($mode==='users'){
            $call=ltrim($line,'#');
            foreach($users as $u){
                if($u['callsign']===$call){
                    $out[]=($u['active']?'':'#').$call.' = '.$u['pseudo'];
                    break;
                }
            }
        } elseif($mode==='pass'){
            [$pseudo,$pw]=explode('=',$line);
            $pseudo=trim($pseudo);
            foreach($users as $u){
                if($u['pseudo']===$pseudo){
                    $out[]=$pseudo.'='.$u['password'];
                    break;
                }
            }
        } else {$out[]=$line;}
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
        $callsign = strtoupper(trim($data['callsign'] ?? ''));
        if (!$callsign || !preg_match('/^[A-Z0-9]+$/',$callsign)) {
            echo json_encode(['success'=>false,'message'=>'Invalid callsign']); exit;
        }

        foreach($users as $u){ if($u['callsign']===$callsign){ 
            echo json_encode(['success'=>false,'message'=>'Callsign exists']); exit; 
        }}

        do {
            $pseudo = strtolower($callsign);
            $realpw = generate_random_password(13);
            $exists=false;
            foreach($users as $u){ if($u['password']===$realpw){ $exists=true; break; } }
        } while($exists);

        $users[]=['callsign'=>$callsign,'pseudo'=>$pseudo,'password'=>$realpw,'active'=>true];
        echo json_encode(['success'=>true,'password'=>$realpw]);
        break;

    case 'activate':
    case 'deactivate':
        $callsign=$data['callsign']??'';
        foreach($users as &$u){
            if($u['callsign']===$callsign){ $u['active']=$action==='activate'; break; }
        }
        echo json_encode(['success'=>true]);
        break;

    case 'commit':
        writeConfig($users);
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
        break;
    case 'generate':
    $callsign = strtoupper(trim($data['callsign'] ?? ''));
    if (!$callsign || !preg_match('/^[A-Z0-9]+$/', $callsign)) {
        echo json_encode(['success'=>false,'message'=>'Invalid callsign']);
        exit();
    }

    $password = generate_random_password(13);
    echo json_encode(['success'=>true,'password'=>$password]);
    break;

}

