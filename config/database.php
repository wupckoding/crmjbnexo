<?php
/**
 * CRM JBNEXO - Conexión a la base de datos
 * Auto-detecta si está en local (XAMPP) o en hosting
 */

$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'])
        || php_sapi_name() === 'cli';

if ($isLocal) {
    // XAMPP local
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'crmjbnexo');
} else {
    // Hostinger hosting
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u862354873_crm');
    define('DB_PASS', 'Brulugahenz100');
    define('DB_NAME', 'u862354873_crm');
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Error de conexión a la base de datos.']));
}
