<?php
/**
 * API: Obtener compras
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
    $user = getCurrentUser();
    
    // Obtener parámetros
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-d');
    $supplier = $_GET['supplier'] ?? '';
    
    // Construir consulta
    $sql = "
        SELECT p.*, pr.name as product_name, b.name as branch_name
        FROM purchases p
        LEFT JOIN products pr ON p.product_id = pr.id
        LEFT JOIN branches b ON p.branch_id = b.id
        WHERE DATE(p.created_at) BETWEEN ? AND ?
    ";
    $params = [$dateFrom, $dateTo];
    
    if ($supplier) {
        $sql .= " AND p.supplier LIKE ?";
        $params[] = "%$supplier%";
    }
    
    if ($user['branch_id']) {
        $sql .= " AND (p.branch_id = ? OR p.branch_id IS NULL)";
        $params[] = $user['branch_id'];
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $purchases = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'purchases' => $purchases]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
