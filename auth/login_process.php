<?php
session_start();

// Rate limiting simples
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}

if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['first_attempt_time']) < 300) {
    $_SESSION['login_error'] = 'Demasiados intentos. Espera 5 minutos.';
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Verificar CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['login_error'] = 'Token de seguridad inválido. Intenta de nuevo.';
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Por favor completa todos los campos.';
    header('Location: ../index.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, nombre, email, password, rol, activo, totp_activo FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['login_attempts']++;
        $_SESSION['login_error'] = 'E-mail o contraseña incorrectos.';
        header('Location: ../index.php');
        exit;
    }

    if (!$user['activo']) {
        $_SESSION['login_error'] = 'Tu cuenta está desactivada. Contacta al administrador.';
        header('Location: ../index.php');
        exit;
    }

    // Check if 2FA is enabled
    if ($user['totp_activo']) {
        $_SESSION['2fa_pending_user'] = $user['id'];
        $_SESSION['2fa_pending_remember'] = isset($_POST['remember']) && $_POST['remember'] === 'on';
        header('Location: ../auth/verify_2fa.php');
        exit;
    }

    // Login exitoso - resetear intentos
    $_SESSION['login_attempts'] = 0;
    
    // Regenerar session ID para prevenir session fixation
    session_regenerate_id(true);
    
    // Guardar datos en sesión
    $_SESSION['usuario_id']     = $user['id'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    $_SESSION['usuario_email']  = $user['email'];
    $_SESSION['usuario_rol']    = $user['rol'];

    // Actualizar último acceso
    $update = $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id');
    $update->execute(['id' => $user['id']]);

    // Remember me - cookie segura
    if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
        $token = bin2hex(random_bytes(32));
        $hash  = password_hash($token, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare('UPDATE usuarios SET token_recuperacion = :token WHERE id = :id');
        $stmt->execute(['token' => $hash, 'id' => $user['id']]);
        
        setcookie('remember_token', $user['id'] . ':' . $token, [
            'expires'  => time() + (30 * 24 * 60 * 60),
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Strict',
        ]);
    }

    header('Location: ../dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    $_SESSION['login_error'] = 'Error del servidor. Intenta más tarde.';
    header('Location: ../index.php');
    exit;
}
