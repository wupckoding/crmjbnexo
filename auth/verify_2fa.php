<?php
session_start();
if (!isset($_SESSION['2fa_pending_user'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$userId = $_SESSION['2fa_pending_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    
    // TOTP verification
    $secret = $pdo->prepare("SELECT totp_secret FROM usuarios WHERE id = :id AND totp_activo = 1");
    $secret->execute(['id' => $userId]);
    $secret = $secret->fetchColumn();
    
    if ($secret) {
        // Base32 decode + TOTP verify (same as api/twofactor.php)
        $lut = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $b32 = strtoupper(rtrim($secret, '='));
        $bin = '';
        foreach (str_split($b32) as $c) {
            if (isset($lut[$c])) $bin .= str_pad(decbin($lut[$c]), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        for ($i = 0; $i + 7 < strlen($bin); $i += 8) $key .= chr(bindec(substr($bin, $i, 8)));
        
        $valid = false;
        $ts = floor(time() / 30);
        for ($w = -1; $w <= 1; $w++) {
            $msg = pack('N*', 0) . pack('N*', $ts + $w);
            $hash = hash_hmac('sha1', $msg, $key, true);
            $offset = ord($hash[19]) & 0xf;
            $otp = (((ord($hash[$offset]) & 0x7f) << 24) | ((ord($hash[$offset+1]) & 0xff) << 16) | ((ord($hash[$offset+2]) & 0xff) << 8) | (ord($hash[$offset+3]) & 0xff)) % 1000000;
            if (hash_equals(str_pad($otp, 6, '0', STR_PAD_LEFT), $code)) { $valid = true; break; }
        }
        
        if ($valid) {
            // Complete login
            $user = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = :id");
            $user->execute(['id' => $userId]);
            $user = $user->fetch();
            
            $_SESSION['login_attempts'] = 0;
            session_regenerate_id(true);
            
            $_SESSION['usuario_id']     = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_email']  = $user['email'];
            $_SESSION['usuario_rol']    = $user['rol'];
            
            // Remember me
            if ($_SESSION['2fa_pending_remember'] ?? false) {
                $token = bin2hex(random_bytes(32));
                $hash  = password_hash($token, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE usuarios SET token_recuperacion = :token WHERE id = :id')
                    ->execute(['token' => $hash, 'id' => $user['id']]);
                setcookie('remember_token', $user['id'] . ':' . $token, [
                    'expires'  => time() + (30 * 24 * 60 * 60),
                    'path'     => '/',
                    'httponly'  => true,
                    'samesite' => 'Strict',
                ]);
            }
            
            unset($_SESSION['2fa_pending_user'], $_SESSION['2fa_pending_remember']);
            
            $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id')->execute(['id' => $user['id']]);
            
            header('Location: ../dashboard.php');
            exit;
        } else {
            $error = 'Código incorrecto. Intenta de nuevo.';
        }
    } else {
        $error = 'Error de seguridad.';
        unset($_SESSION['2fa_pending_user']);
        header('Location: ../index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA | CRM JBNEXO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { nexo: { 400:'#a78bfa', 500:'#8b5cf6', 600:'#7c3aed' } } } },
            darkMode: 'class'
        }
    </script>
    <style>body { background: #09090b; color: #fff; }</style>
</head>
<body class="dark min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-full border-2 border-nexo-500/60 flex items-center justify-center bg-nexo-600/10 mb-4">
                <span class="text-2xl">🔐</span>
            </div>
            <h1 class="text-xl font-bold">Verificación 2FA</h1>
            <p class="text-sm text-white/40 mt-1">Ingresa el código de tu app de autenticación</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-3 mb-4 text-center">
            <p class="text-sm text-red-400"><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <input type="text" name="code" maxlength="6" pattern="[0-9]*" inputmode="numeric" autofocus required
                    placeholder="000000"
                    class="w-full px-4 py-3 text-center text-2xl font-mono tracking-[0.5em] rounded-xl bg-white/5 border border-white/10 outline-none focus:border-nexo-500/50 transition-colors">
            </div>
            <button type="submit" class="w-full py-3 rounded-xl bg-nexo-600 hover:bg-nexo-700 text-white font-semibold text-sm transition-colors">
                Verificar
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="../index.php" class="text-xs text-white/30 hover:text-white/50 transition-colors">← Volver al login</a>
        </div>
    </div>
</body>
</html>
