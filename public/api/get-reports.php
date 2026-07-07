<?php
/**
 * API: Obtener reportes de ventas y gastos
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
    $userRole = $user['role'] ?? 'seller';
    $userBranchId = $user['branch_id'] ?? null;
    
    // Obtener parámetros
    $dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : date('Y-m-01');
    $dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : date('Y-m-d');
    $paymentMethod = isset($_GET['paymentMethod']) ? $_GET['paymentMethod'] : '';
    $branchId = isset($_GET['branchId']) ? (int)$_GET['branchId'] : null;
    
    // Validar fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $dateFrom = date('Y-m-01');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateTo = date('Y-m-d');
    }
    
    // ============================================
    // 1. OBTENER VENTAS
    // ============================================
    $salesSql = "SELECT 
                    s.id,
                    s.total,
                    s.payment_method,
                    s.created_at,
                    s.customer_name,
                    u.username,
                    b.name as branch_name,
                    s.branch_id
                FROM sales s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN branches b ON s.branch_id = b.id
                WHERE s.created_at BETWEEN ? AND ?";
    
    $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
    
    // Filtrar por método de pago
    if (!empty($paymentMethod)) {
        $salesSql .= " AND s.payment_method = ?";
        $params[] = $paymentMethod;
    }
    
    // Filtrar por sucursal
    if ($branchId > 0) {
        $salesSql .= " AND s.branch_id = ?";
        $params[] = $branchId;
    } elseif ($userRole !== 'admin' && $userBranchId) {
        $salesSql .= " AND s.branch_id = ?";
        $params[] = $userBranchId;
    }
    
    $salesSql .= " ORDER BY s.created_at DESC";
    
    $stmt = $db->prepare($salesSql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // 2. OBTENER GASTOS (COMPRAS)
    // ============================================
    $expensesSql = "SELECT 
                        p.id,
                        p.product_name,
                        p.supplier,
                        p.quantity,
                        p.unit_price,
                        p.total_price,
                        p.created_at,
                        b.name as branch_name,
                        p.branch_id
                    FROM purchases p
                    LEFT JOIN branches b ON p.branch_id = b.id
                    WHERE p.created_at BETWEEN ? AND ?";
    
    $expensesParams = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
    
    // Filtrar por sucursal
    if ($branchId > 0) {
        $expensesSql .= " AND p.branch_id = ?";
        $expensesParams[] = $branchId;
    } elseif ($userRole !== 'admin' && $userBranchId) {
        $expensesSql .= " AND p.branch_id = ?";
        $expensesParams[] = $userBranchId;
    }
    
    $expensesSql .= " ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($expensesSql);
    $stmt->execute($expensesParams);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // 3. CALCULAR RESUMEN
    // ============================================
    $totalSales = array_sum(array_column($sales, 'total'));
    $totalExpenses = array_sum(array_column($expenses, 'total_price'));
    
    $summary = [
        'totalSales' => $totalSales,
        'totalExpenses' => $totalExpenses,
        'totalTransactions' => count($sales)
    ];
    
    echo json_encode([
        'success' => true,
        'sales' => $sales,
        'expenses' => $expenses,
        'summary' => $summary,
        'filters' => [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'paymentMethod' => $paymentMethod,
            'branchId' => $branchId
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("PDO Error en get-reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en get-reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
