<?php
/**
 * Script para actualizar contraseñas de usuarios
 * Ejecutar UNA SOLA VEZ y luego borrar este archivo
 * 
 * URL: https://mrhuevos.espacioalma.com.ar/php-pos/public/update_passwords.php
 */

// Incluir configuración
require_once __DIR__ . '/../includes/config.php';

// Verificar conexión
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h1>Actualización de Contraseñas</h1>";
    echo "<p>Conexión exitosa a la base de datos.</p>";
    
    // Usuarios por defecto
    $users = [
        ['username' => 'admin', 'password' => 'admin123', 'role' => 'admin', 'name' => 'Administrador'],
        ['username' => 'encargado', 'password' => 'encargado123', 'role' => 'manager', 'name' => 'Encargado'],
        ['username' => 'vendedor', 'password' => 'vendedor123', 'role' => 'seller', 'name' => 'Vendedor']
    ];
    
    echo "<h2>Actualizando usuarios:</h2>";
    echo "<ul>";
    
    foreach ($users as $user) {
        // Generar hash seguro
        $hash = password_hash($user['password'], PASSWORD_DEFAULT);
        
        // Verificar si el usuario existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$user['username']]);
        
        if ($stmt->fetch()) {
            // Actualizar usuario existente
            $update = $pdo->prepare("UPDATE users SET password_hash = ?, name = ?, role = ? WHERE username = ?");
            $update->execute([$hash, $user['name'], $user['role'], $user['username']]);
            echo "<li style='color: green;'>✓ Usuario '{$user['username']}' actualizado correctamente</li>";
        } else {
            // Crear nuevo usuario
            $insert = $pdo->prepare("INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)");
            $insert->execute([$user['username'], $hash, $user['name'], $user['role']]);
            echo "<li style='color: blue;'>+ Usuario '{$user['username']}' creado correctamente</li>";
        }
    }
    
    echo "</ul>";
    echo "<hr>";
    echo "<h3>✅ ¡Proceso completado!</h3>";
    echo "<p>Ahora puedes iniciar sesión con las siguientes credenciales:</p>";
    echo "<ul>";
    echo "<li><strong>admin</strong> / admin123</li>";
    echo "<li><strong>encargado</strong> / encargado123</li>";
    echo "<li><strong>vendedor</strong> / vendedor123</li>";
    echo "</ul>";
    echo "<p style='color: red; font-weight: bold;'>⚠️ IMPORTANTE: Borra este archivo (update_passwords.php) inmediatamente por seguridad.</p>";
    echo "<p><a href='login.php'>Ir al Login</a></p>";
    
} catch (PDOException $e) {
    echo "<h1>Error de Conexión</h1>";
    echo "<p style='color: red;'>No se pudo conectar a la base de datos.</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Verifica:</h3>";
    echo "<ol>";
    echo "<li>Que la base de datos exista</li>";
    echo "<li>Que las credenciales en <code>includes/config.php</code> sean correctas</li>";
    echo "<li>Que el usuario de BD tenga permisos</li>";
    echo "</ol>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Contraseñas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #2c3e50; }
        ul { line-height: 2; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
</body>
</html>
