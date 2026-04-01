<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>CRM JBNEXO - Diagnóstico</h2>";
echo "<p>PHP " . phpversion() . "</p>";

// Test DB
try {
    require_once 'config/database.php';
    echo "<p style='color:green'>✅ Conexión a base de datos OK</p>";
    
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tablas encontradas: " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $t) echo "<li>$t</li>";
    echo "</ul>";
    
    // Check configuracion_global
    $cfg = $pdo->query("SELECT * FROM configuracion_global")->fetchAll();
    echo "<p>Config global: " . count($cfg) . " filas</p>";
    
    // Check usuarios
    $users = $pdo->query("SELECT id, nombre, email, rol FROM usuarios")->fetchAll();
    echo "<p>Usuarios: " . count($users) . "</p>";
    foreach ($users as $u) echo "<p>&nbsp;&nbsp;- {$u['nombre']} ({$u['email']}) - {$u['rol']}</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test session
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session data: <pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre></p>";

// Test includes
echo "<h3>Test includes:</h3>";
$files = ['includes/helpers.php', 'includes/auth_check.php', 'config/lang/es.php'];
foreach ($files as $f) {
    echo "<p>" . (file_exists($f) ? "✅" : "❌") . " $f</p>";
}
