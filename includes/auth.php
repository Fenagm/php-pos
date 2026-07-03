<?php
/**
 * Funciones de autenticación y sesión
 * Mr Huevos POS - PHP Version
 */

require_once __DIR__ . '/database.php';

/**
 * Iniciar sesión de forma segura
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // OJO: isset($_SERVER['HTTPS']) no alcanza: varios hostings (proxy, cPanel)
        // dejan HTTPS = 'off' cuando el sitio es HTTP plano, y con isset() eso
        // igual da true. Si la cookie queda "secure" en un sitio sin HTTPS real,
        // el navegador nunca la envía y la sesión (login, caja abierta, etc.) se pierde.
        $isHttps = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => $isHttps,
            'use_strict_mode' => true,
            'cookie_samesite' => 'Strict',
        ]);
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Obtener usuario actual
 */
function getCurrentUser() {
    startSecureSession();
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['user_role'],
        'branch_id' => $_SESSION['user_branch_id'],
        'branch_name' => $_SESSION['user_branch_name'] ?? null,
    ];
}

/**
 * Iniciar sesión de usuario
 */
function loginUser($username, $password) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.password_hash, u.role, u.branch_id, b.name as branch_name
        FROM users u
        LEFT JOIN branches b ON u.branch_id = b.id
        WHERE u.username = ? AND u.active != 0
    ");
    
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => 'Usuario no encontrado'];
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Contraseña incorrecta'];
    }
    
    // Iniciar sesión
    startSecureSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_branch_id'] = $user['branch_id'];
    $_SESSION['user_branch_name'] = $user['branch_name'];
    $_SESSION['login_time'] = time();
    
    return [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'branch_id' => $user['branch_id'],
            'branch_name' => $user['branch_name'],
        ]
    ];
}

/**
 * Cerrar sesión
 */
function logoutUser() {
    startSecureSession();
    session_destroy();
}

/**
 * Verificar permisos por rol
 */
function hasRole($allowedRoles) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    return in_array($user['role'], $allowedRoles);
}

/**
 * Redirigir si no está autenticado
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirigir si no tiene el rol requerido
 */
function requireRole($roles) {
    requireAuth();
    if (!hasRole($roles)) {
        header('Location: pos.php?error=access_denied');
        exit;
    }
}

/**
 * Generar token CSRF
 */
function generateCsrfToken() {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCsrfToken($token) {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
