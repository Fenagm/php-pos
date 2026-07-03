<?php
/**
 * API: Activar/Desactivar cliente
 * Mr Huevos POS - PHP Version
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

startSecureSession();

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación y permisos
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
if (!hasRole(['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = $data['id'] ?? null;
    $active = isset($data['active']) ? (bool)$data['active'] : true;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de cliente requerido']);
        exit;
    }

    // Actualizar estado del cliente
    $stmt = $db->prepare("UPDATE customers SET active = ? WHERE id = ?");
    $stmt->execute([$active ? 1 : 0, $id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
