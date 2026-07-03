<?php
/**
 * API: Guardar compra
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
    
    $productId = $data['productId'] ?? null;
    $productName = trim($data['productName'] ?? '');
    $supplier = trim($data['supplier'] ?? '');
    $quantity = intval($data['quantity'] ?? 0);
    $unitPrice = floatval($data['unitPrice'] ?? 0);
    $branchId = $data['branchId'] ?? $user['branch_id'];
    
    if (empty($supplier)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Proveedor requerido']);
        exit;
    }
    
    if ($quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cantidad inválida']);
        exit;
    }
    
    if ($unitPrice < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Precio inválido']);
        exit;
    }
    
    $totalPrice = $quantity * $unitPrice;
    
    // Registrar compra
    $stmt = $db->prepare("
        INSERT INTO purchases (branch_id, product_id, product_name, supplier, quantity, total_price, unit_price)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$branchId, $productId, $productName, $supplier, $quantity, $totalPrice, $unitPrice]);
    
    // Actualizar stock del producto
    if ($productId) {
        $stmt = $db->prepare("
            UPDATE products 
            SET stock = stock + ?, cost_price = ?
            WHERE id = ?
        ");
        $stmt->execute([$quantity, $unitPrice, $productId]);
    }
    
    // Obtener compra guardada
    $purchaseId = $db->lastInsertId();
    $stmt = $db->prepare("
        SELECT p.*, pr.name as product_name, b.name as branch_name
        FROM purchases p
        LEFT JOIN products pr ON p.product_id = pr.id
        LEFT JOIN branches b ON p.branch_id = b.id
        WHERE p.id = ?
    ");
    $stmt->execute([$purchaseId]);
    $purchase = $stmt->fetch();
    
    echo json_encode(['success' => true, 'purchase' => $purchase]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
