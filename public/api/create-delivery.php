<?php
/**
 * API: Crear entrega (para ventas marcadas como "Para Envío")
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

try {
    $db = getDB();
    $user = getCurrentUser();
    $data = json_decode(file_get_contents('php://input'), true);

    $saleId = intval($data['saleId'] ?? 0);
    $customerId = intval($data['customerId'] ?? 0);
    $customerName = trim($data['customerName'] ?? '');
    $customerAddress = trim($data['customerAddress'] ?? '');
    $customerPhone = trim($data['customerPhone'] ?? '');
    $deliveryDate = $data['deliveryDate'] ?? date('Y-m-d');
    $totalBultos = intval($data['totalBultos'] ?? 0);
    $notes = trim($data['notes'] ?? '');

    if (!$saleId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de venta requerido']);
        exit;
    }

    // Verificar que la venta existe
    $stmt = $db->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Venta no encontrada']);
        exit;
    }

    // Si ya existe una entrega para esta venta, actualizarla
    $stmt = $db->prepare("SELECT * FROM deliveries WHERE sale_id = ?");
    $stmt->execute([$saleId]);
    $existingDelivery = $stmt->fetch();

    if ($existingDelivery) {
        // Actualizar entrega existente
        $stmt = $db->prepare("
            UPDATE deliveries 
            SET delivery_date = ?, customer_name = ?, customer_address = ?, customer_phone = ?, 
                total_bultos = ?, notes = ?, status = 'pending'
            WHERE sale_id = ?
        ");
        $stmt->execute([
            $deliveryDate, 
            $customerName ?: $sale['customer_name'], 
            $customerAddress ?: $sale['customer_address'],
            $customerPhone ?: $sale['customer_phone'],
            $totalBultos,
            $notes,
            $saleId
        ]);
        
        echo json_encode(['success' => true, 'deliveryId' => $existingDelivery['id'], 'updated' => true]);
    } else {
        // Crear nueva entrega
        $stmt = $db->prepare("
            INSERT INTO deliveries (sale_id, branch_id, customer_id, customer_name, customer_address, customer_phone, 
                                    delivery_date, total_bultos, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $saleId,
            $sale['branch_id'],
            $customerId ?: null,
            $customerName ?: $sale['customer_name'],
            $customerAddress ?: $sale['customer_address'],
            $customerPhone ?: $sale['customer_phone'],
            $deliveryDate,
            $totalBultos,
            $notes
        ]);
        
        // Marcar venta como para envío
        $stmt = $db->prepare("UPDATE sales SET is_for_delivery = 1, delivery_date = ? WHERE id = ?");
        $stmt->execute([$deliveryDate, $saleId]);
        
        echo json_encode(['success' => true, 'deliveryId' => $db->lastInsertId(), 'updated' => false]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al crear entrega: ' . $e->getMessage()]);
}
