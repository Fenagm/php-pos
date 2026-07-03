<?php
/**
 * API: Transferir stock a sucursal
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

$user = getCurrentUser();
if (!hasRole(['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Solo administradores.']);
    exit;
}

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productId = $data['productId'] ?? null;
    $destBranchId = $data['branchId'] ?? null;
    $quantity = intval($data['quantity'] ?? 0);
    
    if (!$productId || !$destBranchId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Producto y sucursal requeridos']);
        exit;
    }
    
    if ($quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cantidad inválida']);
        exit;
    }
    
    // Verificar stock disponible en el producto de origen
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $sourceProduct = $stmt->fetch();
    
    if (!$sourceProduct) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
        exit;
    }
    
    if ($sourceProduct['stock'] < $quantity) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Stock insuficiente']);
        exit;
    }

    if ($sourceProduct['branch_id'] == $destBranchId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El producto ya pertenece a esa sucursal']);
        exit;
    }

    $db->beginTransaction();
    try {
        // Restar del origen (antes: esto no se hacía, se perdía el stock sobrante)
        $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$quantity, $productId]);

        // Buscar si ya existe el mismo producto (por nombre) en la sucursal destino,
        // para sumarle el stock en vez de crear un duplicado.
        $stmt = $db->prepare("SELECT id FROM products WHERE name = ? AND branch_id = ? LIMIT 1");
        $stmt->execute([$sourceProduct['name'], $destBranchId]);
        $destProduct = $stmt->fetch();

        if ($destProduct) {
            $stmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$quantity, $destProduct['id']]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO products (name, cost_price, retail_price, wholesale_price, stock, active, branch_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $sourceProduct['name'],
                $sourceProduct['cost_price'],
                $sourceProduct['retail_price'],
                $sourceProduct['wholesale_price'],
                $quantity,
                $sourceProduct['active'],
                $destBranchId,
            ]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
