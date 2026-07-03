<?php
/**
 * API: Crear venta
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

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    $db = getDB();
    $user = getCurrentUser();
    $data = json_decode(file_get_contents('php://input'), true);

    // La venta debe quedar atada a una sesión de caja abierta para poder
    // arquear caja al cierre (antes no se validaba esto en absoluto).
    $stmt = $db->prepare("SELECT id FROM cash_sessions WHERE user_id = ? AND branch_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1");
    $stmt->execute([$user['id'], $user['branch_id']]);
    $openSession = $stmt->fetch();

    if (!$openSession) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Debes abrir caja antes de registrar ventas']);
        exit;
    }
    $sessionId = $openSession['id'];

    $items = $data['items'] ?? [];
    $total = floatval($data['total'] ?? 0);
    $paymentMethod = $data['paymentMethod'] ?? 'cash';
    $customerId = $data['customerId'] ?? null;
    $customerName = $data['customerName'] ?? null;
    $customerAddress = $data['customerAddress'] ?? null;
    $customerPhone = $data['customerPhone'] ?? null;
    $isForDelivery = isset($data['isForDelivery']) ? (bool)$data['isForDelivery'] : false;
    $deliveryDate = $data['deliveryDate'] ?? null;
    $totalBultos = intval($data['totalBultos'] ?? 0);
    
    if (empty($items) || $total <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Datos de venta inválidos']);
        exit;
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Insertar venta
    $stmt = $db->prepare("
        INSERT INTO sales (user_id, branch_id, session_id, customer_id, customer_name, customer_address, customer_phone, 
                          total, payment_method, is_for_delivery, delivery_date, total_bultos)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $user['branch_id'],
        $sessionId,
        $customerId,
        $customerName,
        $customerAddress,
        $customerPhone,
        $total,
        $paymentMethod,
        $isForDelivery ? 1 : 0,
        $deliveryDate,
        $totalBultos
    ]);
    $saleId = $db->lastInsertId();
    
    // Insertar items de la venta y actualizar stock
    foreach ($items as $item) {
        $productId = $item['productId'] ?? null;
        $quantity = intval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        
        if (!$productId || $quantity <= 0) {
            continue;
        }
        
        // Insertar item
        $stmt = $db->prepare("
            INSERT INTO sale_items (sale_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$saleId, $productId, $quantity, $price]);
        
        // Actualizar stock si es un producto físico (no es monto libre)
        if ($productId > 0) {
            $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $productId]);
        }
    }
    
    // Si es cuenta corriente, registrar movimiento
    if ($paymentMethod === 'account' && $customerId) {
        // Obtener balance actual del cliente
        $stmt = $db->prepare("SELECT account_balance FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();
        $newBalance = floatval($customer['account_balance']) + $total;
        
        // Actualizar balance del cliente
        $stmt = $db->prepare("UPDATE customers SET account_balance = ?, total_purchases = total_purchases + ? WHERE id = ?");
        $stmt->execute([$newBalance, $total, $customerId]);
        
        // Registrar movimiento
        $stmt = $db->prepare("
            INSERT INTO account_movements (customer_id, user_id, type, amount, balance_after, description, payment_method)
            VALUES (?, ?, 'sale', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $customerId,
            $user['id'],
            $total,
            $newBalance,
            'Venta #' . $saleId,
            $paymentMethod
        ]);
    }
    
    // Confirmar transacción
    $db->commit();
    
    echo json_encode(['success' => true, 'saleId' => $saleId]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al crear venta: ' . $e->getMessage()]);
}
