<?php
/**
 * API: Cambiar sucursal activa
 * Mr Huevos POS - PHP Version
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

startSecureSession();

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();

// Solo administradores pueden cambiar de sucursal
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    $db = getDB();
    
    // Obtener parámetros
    $data = json_decode(file_get_contents('php://input'), true);
    $branchId = $data['branchId'] ?? null;
    
    if (!$branchId) {
        echo json_encode(['success' => false, 'error' => 'ID de sucursal requerido']);
        exit;
    }
    
    // Verificar que la sucursal existe y está activa
    $stmt = $db->prepare("SELECT id, name FROM branches WHERE id = ? AND active = 1");
    $stmt->execute([$branchId]);
    $branch = $stmt->fetch();
    
    if (!$branch) {
        echo json_encode(['success' => false, 'error' => 'Sucursal no encontrada o inactiva']);
        exit;
    }
    
    // Actualizar sucursal en sesión
    $_SESSION['user_branch_id'] = $branchId;
    $_SESSION['user_branch_name'] = $branch['name'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Sucursal cambiada correctamente',
        'branch' => [
            'id' => $branch['id'],
            'name' => $branch['name']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
