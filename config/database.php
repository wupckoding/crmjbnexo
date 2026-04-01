<?php
/**
 * CRM JBNEXO - Conexión a la base de datos
 * 
 * ⚠️ CONFIGURAR ANTES DE SUBIR AL HOSTING:
 *    Cambia DB_USER, DB_PASS (y DB_HOST si es necesario)
 *    con los datos del panel de tu hosting (Hostinger, cPanel, etc.)
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // ← Cambiar: usuario MySQL del hosting
define('DB_PASS', '');               // ← Cambiar: contraseña MySQL del hosting
define('DB_NAME', 'crmjbnexo');      // ← Cambiar si el hosting usa prefijo (ej: u123456789_crmjbnexo)

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
