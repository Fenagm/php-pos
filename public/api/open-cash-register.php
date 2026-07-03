<?php
/**
 * API: Abrir/Cerrar Caja
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

    $action = $data['action'] ?? '';
    
    if ($action === 'open') {
        // Verificar si ya hay una sesión abierta
        $stmt = $db->prepare("SELECT * FROM cash_sessions WHERE user_id = ? AND branch_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1");
        $stmt->execute([$user['id'], $user['branch_id']]);
        $existingSession = $stmt->fetch();
        
        if ($existingSession) {
            echo json_encode(['success' => false, 'error' => 'Ya hay una sesión de caja abierta']);
            exit;
        }
        
        $initialCash = floatval($data['initialCash'] ?? 0);
        $notes = trim($data['notes'] ?? '');
        
        // Insertar nueva sesión de caja
        $stmt = $db->prepare("
            INSERT INTO cash_sessions (user_id, branch_id, initial_cash, notes, status)
            VALUES (?, ?, ?, ?, 'open')
        ");
        $stmt->execute([$user['id'], $user['branch_id'], $initialCash, $notes]);
        
        // Guardar en sesión que la caja está abierta
        $_SESSION['cash_session_open'] = true;
        $_SESSION['cash_session_id'] = $db->lastInsertId();
        
        echo json_encode(['success' => true, 'sessionId' => $db->lastInsertId()]);
        
    } elseif ($action === 'close') {
        $sessionId = intval($data['sessionId'] ?? 0);
        $finalCash = floatval($data['finalCash'] ?? 0);
        $notes = trim($data['notes'] ?? '');
        
        // Obtener sesión actual
        $stmt = $db->prepare("SELECT * FROM cash_sessions WHERE id = ? AND user_id = ? AND status = 'open'");
        $stmt->execute([$sessionId, $user['id']]);
        $session = $stmt->fetch();
        
        if (!$session) {
            echo json_encode(['success' => false, 'error' => 'Sesión de caja no encontrada o ya cerrada']);
            exit;
        }

        // Efectivo esperado = lo que había al abrir + lo vendido en efectivo durante la sesión.
        // Antes esto no se calculaba: la "diferencia" solo comparaba el monto final contra
        // el inicial, ignorando por completo las ventas realizadas.
        $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) as total_cash_sales FROM sales WHERE session_id = ? AND payment_method = 'cash'");
        $stmt->execute([$sessionId]);
        $cashSales = floatval($stmt->fetch()['total_cash_sales']);

        $expectedCash = floatval($session['initial_cash']) + $cashSales;
        $difference = $finalCash - $expectedCash;

        // COALESCE evita que un 'notes' en NULL borre todo el campo al concatenar
        // (CONCAT devuelve NULL si cualquiera de sus argumentos es NULL).
        $stmt = $db->prepare("
            UPDATE cash_sessions 
            SET final_cash = ?, difference = ?, notes = CONCAT(COALESCE(notes, ''), ?, '\n'), status = 'closed', closed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$finalCash, $difference, $notes, $sessionId]);
        
        // Limpiar sesión
        unset($_SESSION['cash_session_open']);
        unset($_SESSION['cash_session_id']);
        
        echo json_encode([
            'success' => true,
            'difference' => $difference,
            'expectedCash' => $expectedCash,
            'cashSales' => $cashSales,
            'initialCash' => floatval($session['initial_cash']),
        ]);
        
    } elseif ($action === 'check') {
        // Verificar si hay sesión abierta
        $stmt = $db->prepare("SELECT * FROM cash_sessions WHERE user_id = ? AND branch_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1");
        $stmt->execute([$user['id'], $user['branch_id']]);
        $session = $stmt->fetch();
        
        if ($session) {
            $_SESSION['cash_session_open'] = true;
            $_SESSION['cash_session_id'] = $session['id'];
            echo json_encode(['success' => true, 'isOpen' => true, 'session' => $session]);
        } else {
            unset($_SESSION['cash_session_open']);
            echo json_encode(['success' => true, 'isOpen' => false]);
        }
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
