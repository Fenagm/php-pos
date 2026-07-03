<?php
/**
 * API: Obtener sucursales activas
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

try {
    $db = getDB();
    
    // Obtener todas las sucursales activas
    $stmt = $db->query("SELECT id, name, address, phone FROM branches WHERE active = 1 ORDER BY name ASC");
    $branches = $stmt->fetchAll();
    
    $formattedBranches = array_map(function($b) {
        return [
            'id' => $b['id'],
            'name' => $b['name'],
            'address' => $b['address'],
            'phone' => $b['phone']
        ];
    }, $branches);
    
    echo json_encode(['success' => true, 'branches' => $formattedBranches]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
