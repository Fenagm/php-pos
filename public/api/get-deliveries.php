<?php
/**
 * API: Obtener entregas
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
    $user = getCurrentUser();
    
    $date = $_GET['date'] ?? date('Y-m-d');
    $status = $_GET['status'] ?? '';
    
    // Validar parámetro de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }
    
    $sql = "
        SELECT d.*, v.name as vehicle_name, b.name as branch_name
        FROM deliveries d
        LEFT JOIN branches b ON d.branch_id = b.id
        LEFT JOIN vehicles v ON d.vehicle_id = v.id
        WHERE d.delivery_date = ?
    ";
    $params = [$date];
    
    // Solo filtrar por status si se proporcionó un valor válido
    $validStatuses = ['pending', 'in_transit', 'delivered', 'cancelled'];
    if (!empty($status) && in_array($status, $validStatuses)) {
        $sql .= " AND d.status = ?";
        $params[] = $status;
    }
    
    if ($user['branch_id']) {
        $sql .= " AND (d.branch_id = ? OR d.branch_id IS NULL)";
        $params[] = $user['branch_id'];
    }
    
    $sql .= " ORDER BY d.route_order ASC, d.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll();

    // Mismo motivo que en get-vehicles.php: editDelivery(${delivery.id})
    // necesita que 'id' (y vehicle_id) sean números reales, no strings.
    $deliveries = array_map(function($d) {
        $d['id'] = intval($d['id']);
        $d['vehicle_id'] = $d['vehicle_id'] !== null ? intval($d['vehicle_id']) : null;
        $d['branch_id'] = $d['branch_id'] !== null ? intval($d['branch_id']) : null;
        $d['total_bultos'] = intval($d['total_bultos']);
        return $d;
    }, $deliveries);

    echo json_encode(['success' => true, 'deliveries' => $deliveries]);
    
} catch (Exception $e) {
    error_log("Error en get-deliveries.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
