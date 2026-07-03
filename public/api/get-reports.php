<?php
/**
 * API: Obtener reportes de ventas
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

    // Obtener parámetros de filtro
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-d');
    $paymentMethod = $_GET['paymentMethod'] ?? null;
    $consolidated = isset($_GET['consolidated']) && $_GET['consolidated'] === '1';

    // Construir consulta de ventas
    $sql = "
        SELECT s.*, u.username
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE DATE(s.created_at) BETWEEN ? AND ?
    ";
    $params = [$dateFrom, $dateTo];

    if ($paymentMethod) {
        $sql .= " AND s.payment_method = ?";
        $params[] = $paymentMethod;
    }

    // Solo filtrar por sucursal si NO es consolidado y el usuario tiene branch_id
    if (!$consolidated && $user['branch_id']) {
        $sql .= " AND (s.branch_id = ? OR s.branch_id IS NULL)";
        $params[] = $user['branch_id'];
    }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();

    // Obtener totales de items
    $itemsSql = "
        SELECT COALESCE(SUM(si.quantity), 0) as total_items
        FROM sale_items si
        INNER JOIN sales s ON si.sale_id = s.id
        WHERE DATE(s.created_at) BETWEEN ? AND ?
    ";
    $itemsParams = [$dateFrom, $dateTo];
    
    if ($paymentMethod) {
        $itemsSql .= " AND s.payment_method = ?";
        $itemsParams[] = $paymentMethod;
    }

    // Solo filtrar por sucursal si NO es consolidado y el usuario tiene branch_id
    if (!$consolidated && $user['branch_id']) {
        $itemsSql .= " AND (s.branch_id = ? OR s.branch_id IS NULL)";
        $itemsParams[] = $user['branch_id'];
    }

    $stmt = $db->prepare($itemsSql);
    $stmt->execute($itemsParams);
    $totalItems = $stmt->fetch()['total_items'];

    // Calcular resumen
    $totalSales = array_sum(array_column($sales, 'total'));
    $summary = [
        'totalSales' => $totalSales,
        'totalTransactions' => count($sales),
        'totalItems' => intval($totalItems)
    ];

    echo json_encode([
        'success' => true,
        'sales' => $sales,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
