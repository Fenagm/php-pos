<?php
/**
 * API: Obtener vehículos
 * Mr Huevos POS - PHP Version
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

startSecureSession();

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM vehicles ORDER BY name ASC");
    $stmt->execute();
    $vehicles = $stmt->fetchAll();

    // Castear id/capacity/active explícitamente: si PDO los devuelve como
    // string, editVehicle(${vehicle.id}) en logistics.php compara con ===
    // contra un número y nunca matchea el vehículo a editar.
    $vehicles = array_map(function($v) {
        $v['id'] = intval($v['id']);
        $v['capacity_cajones'] = isset($v['capacity_cajones']) ? intval($v['capacity_cajones']) : 0;
        $v['capacity_pallets'] = isset($v['capacity_pallets']) ? intval($v['capacity_pallets']) : 0;
        // Mantener compatibilidad con capacity si existe
        $v['capacity'] = isset($v['capacity']) ? intval($v['capacity']) : 0;
        $v['current_cajones'] = isset($v['current_cajones']) ? intval($v['current_cajones']) : 0;
        $v['current_pallets'] = isset($v['current_pallets']) ? intval($v['current_pallets']) : 0;
        $v['active'] = (bool)$v['active'];
        return $v;
    }, $vehicles);

    echo json_encode(['success' => true, 'vehicles' => $vehicles]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
