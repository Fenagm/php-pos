<?php
/**
 * API para gestión de unidades y capacidad de vehículos
 * Mr Huevos POS - PHP Version
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();
header('Content-Type: application/json');

try {
    $db = getDB();
    $user = getCurrentUser();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($action === 'update_capacity') {
        // Actualizar capacidad de un vehículo
        $vehicleId = (int)($_POST['vehicleId'] ?? 0);
        $capacityCajones = (int)($_POST['capacityCajones'] ?? 0);
        $capacityPallets = (int)($_POST['capacityPallets'] ?? 0);
        
        if ($vehicleId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de vehículo inválido']);
            exit;
        }
        
        $stmt = $db->prepare("
            UPDATE vehicles 
            SET capacity_cajones = ?, 
                capacity_pallets = ?,
                current_cajones = 0,
                current_pallets = 0
            WHERE id = ?
        ");
        $stmt->execute([$capacityCajones, $capacityPallets, $vehicleId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Capacidad actualizada correctamente',
            'data' => [
                'capacity_cajones' => $capacityCajones,
                'capacity_pallets' => $capacityPallets
            ]
        ]);
        exit;
    }
    
    if ($action === 'get_load') {
        // Obtener carga actual de un vehículo
        $vehicleId = (int)($_GET['vehicleId'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');
        
        if ($vehicleId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de vehículo inválido']);
            exit;
        }
        
        $stmt = $db->prepare("
            SELECT 
                v.id,
                v.name,
                v.capacity_cajones,
                v.capacity_pallets,
                v.current_cajones,
                v.current_pallets,
                COALESCE(SUM(d.total_cajones), 0) as assigned_cajones,
                COALESCE(SUM(d.total_pallets), 0) as assigned_pallets,
                COUNT(d.id) as total_deliveries
            FROM vehicles v
            LEFT JOIN deliveries d ON d.vehicle_id = v.id 
                AND d.delivery_date = ? 
                AND d.status IN ('pending', 'in_transit')
            WHERE v.id = ?
            GROUP BY v.id
        ");
        $stmt->execute([$date, $vehicleId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $availableCajones = $result['capacity_cajones'] - $result['assigned_cajones'];
            $availablePallets = $result['capacity_pallets'] - $result['assigned_pallets'];
            
            // Calcular porcentaje de ocupación (1 pallet = 40 cajones)
            $totalCapacity = $result['capacity_cajones'] + ($result['capacity_pallets'] * 40);
            $totalUsed = $result['assigned_cajones'] + ($result['assigned_pallets'] * 40);
            $loadPercentage = $totalCapacity > 0 ? round(($totalUsed / $totalCapacity) * 100, 1) : 0;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'vehicle' => $result,
                    'capacity' => [
                        'cajones' => $result['capacity_cajones'],
                        'pallets' => $result['capacity_pallets'],
                        'total_equivalente' => $totalCapacity
                    ],
                    'current_load' => [
                        'cajones' => $result['assigned_cajones'],
                        'pallets' => $result['assigned_pallets'],
                        'total_equivalente' => $totalUsed
                    ],
                    'available' => [
                        'cajones' => $availableCajones,
                        'pallets' => $availablePallets,
                        'total_equivalente' => $totalCapacity - $totalUsed
                    ],
                    'load_percentage' => $loadPercentage,
                    'total_deliveries' => $result['total_deliveries']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Vehículo no encontrado']);
        }
        exit;
    }
    
    if ($action === 'validate_assignment') {
        // Validar si se pueden asignar entregas a un vehículo
        $vehicleId = (int)($_POST['vehicleId'] ?? 0);
        $cajones = (int)($_POST['cajones'] ?? 0);
        $pallets = (int)($_POST['pallets'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if ($vehicleId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de vehículo inválido']);
            exit;
        }
        
        // Obtener carga actual
        $stmt = $db->prepare("
            SELECT 
                capacity_cajones,
                capacity_pallets,
                current_cajones,
                current_pallets,
                COALESCE(SUM(d.total_cajones), 0) as assigned_cajones,
                COALESCE(SUM(d.total_pallets), 0) as assigned_pallets
            FROM vehicles v
            LEFT JOIN deliveries d ON d.vehicle_id = v.id 
                AND d.delivery_date = ? 
                AND d.status IN ('pending', 'in_transit')
            WHERE v.id = ?
            GROUP BY v.id
        ");
        $stmt->execute([$date, $vehicleId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) {
            echo json_encode(['success' => false, 'error' => 'Vehículo no encontrado']);
            exit;
        }
        
        $newCajones = $current['assigned_cajones'] + $cajones;
        $newPallets = $current['assigned_pallets'] + $pallets;
        
        $valid = true;
        $errors = [];
        
        if ($newCajones > $current['capacity_cajones']) {
            $valid = false;
            $errors[] = "Excede capacidad de cajones. Disponible: {$current['capacity_cajones']}";
        }
        
        if ($newPallets > $current['capacity_pallets']) {
            $valid = false;
            $errors[] = "Excede capacidad de pallets. Disponible: {$current['capacity_pallets']}";
        }
        
        echo json_encode([
            'success' => $valid,
            'valid' => $valid,
            'errors' => $errors,
            'data' => [
                'current' => [
                    'cajones' => $current['assigned_cajones'],
                    'pallets' => $current['assigned_pallets']
                ],
                'new' => [
                    'cajones' => $newCajones,
                    'pallets' => $newPallets
                ],
                'available' => [
                    'cajones' => $current['capacity_cajones'] - $current['assigned_cajones'],
                    'pallets' => $current['capacity_pallets'] - $current['assigned_pallets']
                ]
            ]
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    
} catch (Exception $e) {
    error_log("Error en vehicle-capacity.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
