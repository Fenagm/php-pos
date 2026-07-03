<?php
/**
 * API: Guardar cliente (crear o actualizar)
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
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');
    $creditLimit = floatval($data['creditLimit'] ?? 500);
    $active = isset($data['active']) ? (bool)$data['active'] : true;

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nombre requerido']);
        exit;
    }

    if ($id) {
        // Actualizar cliente existente
        $stmt = $db->prepare("
            UPDATE customers
            SET name = ?, phone = ?, email = ?, address = ?, credit_limit = ?, active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $phone, $email, $address, $creditLimit, $active ? 1 : 0, $id]);

        $customerId = $id;
    } else {
        // Crear nuevo cliente
        $stmt = $db->prepare("
            INSERT INTO customers (name, phone, email, address, credit_limit, active)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $phone, $email, $address, $creditLimit, $active ? 1 : 0]);
        $customerId = $db->lastInsertId();
    }

    // Obtener cliente guardado
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    $formattedCustomer = [
        'id' => $customer['id'],
        'name' => $customer['name'],
        'phone' => $customer['phone'],
        'email' => $customer['email'],
        'address' => $customer['address'],
        'accountBalance' => floatval($customer['account_balance']),
        'creditLimit' => floatval($customer['credit_limit']),
        'active' => (bool)$customer['active'],
        'totalPurchases' => floatval($customer['total_purchases']),
        'createdAt' => $customer['created_at'],
    ];

    echo json_encode(['success' => true, 'customer' => $formattedCustomer]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
