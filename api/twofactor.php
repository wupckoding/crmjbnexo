<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) $_SESSION['user_id'] = $_SESSION['usuario_id'];
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

$uid = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// TOTP helpers
function generateSecret($len = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $len; $i++) $secret .= $chars[random_int(0, 31)];
    return $secret;
}

function base32Decode($b32) {
    $lut = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
    $b32 = strtoupper(rtrim($b32, '='));
    $bin = '';
    foreach (str_split($b32) as $c) {
        if (!isset($lut[$c])) return false;
        $bin .= str_pad(decbin($lut[$c]), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i + 7 < strlen($bin); $i += 8) $out .= chr(bindec(substr($bin, $i, 8)));
    return $out;
}

function getTOTP($secret, $timeSlice = null) {
    if ($timeSlice === null) $timeSlice = floor(time() / 30);
    $key = base32Decode($secret);
    $msg = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $msg, $key, true);
    $offset = ord($hash[19]) & 0xf;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset+1]) & 0xff) << 16) |
        ((ord($hash[$offset+2]) & 0xff) << 8) |
        (ord($hash[$offset+3]) & 0xff)
    ) % 1000000;
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

function verifyTOTP($secret, $code, $window = 1) {
    $ts = floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(getTOTP($secret, $ts + $i), $code)) return true;
    }
    return false;
}

switch ($action) {
    case 'setup':
        // Generate new secret
        $secret = generateSecret();
        $user = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id = :id");
        $user->execute(['id' => $uid]);
        $user = $user->fetch();
        
        // Store temp secret (not activated yet)
        $pdo->prepare("UPDATE usuarios SET totp_secret = :s WHERE id = :id")
            ->execute(['s' => $secret, 'id' => $uid]);
        
        $issuer = 'NexoCRM';
        $label = rawurlencode($issuer) . ':' . rawurlencode($user['email']);
        $otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
        
        echo json_encode(['ok' => true, 'secret' => $secret, 'otpauth' => $otpauth]);
        break;

    case 'enable':
        $code = trim($_POST['code'] ?? '');
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            echo json_encode(['error' => 'Código inválido']);
            exit;
        }
        $user = $pdo->prepare("SELECT totp_secret FROM usuarios WHERE id = :id");
        $user->execute(['id' => $uid]);
        $secret = $user->fetchColumn();
        
        if (!$secret || !verifyTOTP($secret, $code)) {
            echo json_encode(['error' => 'Código incorrecto. Intenta de nuevo.']);
            exit;
        }
        
        $pdo->prepare("UPDATE usuarios SET totp_activo = 1 WHERE id = :id")
            ->execute(['id' => $uid]);
        echo json_encode(['ok' => true]);
        break;

    case 'disable':
        $code = trim($_POST['code'] ?? '');
        $user = $pdo->prepare("SELECT totp_secret FROM usuarios WHERE id = :id");
        $user->execute(['id' => $uid]);
        $secret = $user->fetchColumn();
        
        if (!$secret || !verifyTOTP($secret, $code)) {
            echo json_encode(['error' => 'Código incorrecto']);
            exit;
        }
        
        $pdo->prepare("UPDATE usuarios SET totp_secret = NULL, totp_activo = 0 WHERE id = :id")
            ->execute(['id' => $uid]);
        echo json_encode(['ok' => true]);
        break;

    case 'verify':
        // Called during login
        $code = trim($_POST['code'] ?? '');
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) { echo json_encode(['error' => 'Faltan datos']); exit; }
        
        $user = $pdo->prepare("SELECT totp_secret FROM usuarios WHERE id = :id AND totp_activo = 1");
        $user->execute(['id' => $userId]);
        $secret = $user->fetchColumn();
        
        if (!$secret || !verifyTOTP($secret, $code)) {
            echo json_encode(['error' => 'Código 2FA incorrecto']);
            exit;
        }
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
