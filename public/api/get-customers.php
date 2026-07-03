<?php
/**
 * API: Obtener clientes
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
    
    $sql = "SELECT * FROM customers ORDER BY name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    // Formatear respuesta
    // 'id' debe ser número real: editCustomer/toggleCustomerStatus en
    // customers.php comparan con === contra un id numérico.
    $formattedCustomers = array_map(function($c) {
        return [
            'id' => intval($c['id']),
            'name' => $c['name'],
            'email' => $c['email'],
            'phone' => $c['phone'],
            'address' => $c['address'],
            'accountBalance' => floatval($c['account_balance']),
            'creditLimit' => floatval($c['credit_limit']),
            'active' => (bool)$c['active'],
            'totalPurchases' => floatval($c['total_purchases']),
            'createdAt' => $c['created_at'],
        ];
    }, $customers);
    
    echo json_encode(['success' => true, 'customers' => $formattedCustomers]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
