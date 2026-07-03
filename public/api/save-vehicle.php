<?php
/**
 * API: Guardar vehículo
 * Mr Huevos POS - PHP Version
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

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
    $name = trim($data['name'] ?? '');
    $licensePlate = trim($data['licensePlate'] ?? '');
    $capacity = intval($data['capacity'] ?? 0);
    $active = isset($data['active']) ? (bool)$data['active'] : true;
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nombre requerido']);
        exit;
    }
    
    if ($id) {
        $stmt = $db->prepare("
            UPDATE vehicles 
            SET name = ?, license_plate = ?, capacity = ?, active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $licensePlate, $capacity, $active ? 1 : 0, $id]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO vehicles (name, license_plate, capacity, active)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $licensePlate, $capacity, $active ? 1 : 0]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
