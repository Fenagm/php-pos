<?php
/**
 * API: Guardar producto (crear o actualizar)
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
    $costPrice = floatval($data['costPrice'] ?? 0);
    $retailPrice = floatval($data['retailPrice'] ?? 0);
    $wholesalePrice = floatval($data['wholesalePrice'] ?? 0);
    $stock = intval($data['stock'] ?? 0);
    $active = isset($data['active']) ? (bool)$data['active'] : true;
    $branchId = $data['branchId'] ?? $user['branch_id'];
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nombre requerido']);
        exit;
    }
    
    if ($id) {
        // Actualizar producto existente
        $stmt = $db->prepare("
            UPDATE products 
            SET name = ?, cost_price = ?, retail_price = ?, wholesale_price = ?, 
                stock = ?, active = ?, branch_id = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $costPrice, $retailPrice, $wholesalePrice, 
            $stock, $active ? 1 : 0, $branchId, $id
        ]);
        
        $productId = $id;
    } else {
        // Crear nuevo producto
        $stmt = $db->prepare("
            INSERT INTO products (name, cost_price, retail_price, wholesale_price, stock, active, branch_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $costPrice, $retailPrice, $wholesalePrice, $stock, $active ? 1 : 0, $branchId]);
        $productId = $db->lastInsertId();
    }
    
    // Obtener producto guardado
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    $formattedProduct = [
        'id' => $product['id'],
        'name' => $product['name'],
        'costPrice' => floatval($product['cost_price']),
        'retailPrice' => floatval($product['retail_price']),
        'wholesalePrice' => floatval($product['wholesale_price']),
        'stock' => intval($product['stock']),
        'active' => (bool)$product['active'],
        'branchId' => $product['branch_id'],
        'createdAt' => $product['created_at'],
    ];
    
    echo json_encode(['success' => true, 'product' => $formattedProduct]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
