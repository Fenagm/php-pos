<?php
/**
 * Gestión de Logística (Entregas)
 * Mr Huevos POS - PHP Version
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();
$user = getCurrentUser();

// Solo admin y manager pueden acceder
if (!hasRole(['admin', 'manager'])) {
    header('Location: pos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logística - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="min-h-screen">
    <!-- Header with gradient -->
    <header class="gradient-header shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white"><?php echo APP_NAME; ?></h1>
                            <span class="text-xs text-blue-100"><?php echo htmlspecialchars($user['branch_name'] ?? 'Principal'); ?></span>
                        </div>
                    </div>
                </div>
                <nav class="nav-container">
                    <a href="pos.php" class="nav-tab nav-tab-inactive">POS</a>
                    <a href="customers.php" class="nav-tab nav-tab-inactive">Clientes</a>
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                        <a href="inventory.php" class="nav-tab nav-tab-inactive">Inventario</a>
                        <a href="purchases.php" class="nav-tab nav-tab-inactive">Compras</a>
                        <a href="logistics.php" class="nav-tab nav-tab-active">Logística</a>
                        <a href="reports.php" class="nav-tab nav-tab-inactive">Reportes</a>
                        <a href="mayorista.php" class="nav-tab nav-tab-inactive">Mayorista</a>
                    <?php endif; ?>
                </nav>
                <div class="flex items-center gap-4">
                    <?php if ($user['role'] === 'admin'): ?>
                        <select id="branchSelector" class="input-field text-sm py-1 !bg-white/10 !text-white !border-white/20" onchange="switchBranch(this.value)">
                            <option value="">Cargando sucursales...</option>
                        </select>
                    <?php else: ?>
                        <span class="text-xs text-blue-100"><?php echo htmlspecialchars($user['branch_name'] ?? 'Principal'); ?></span>
                    <?php endif; ?>
                    <div class="hidden sm:flex items-center gap-2 text-white/90">
                        <div class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <button onclick="logout()" class="btn-secondary text-sm !bg-white/10 !text-white !border-white/20 hover:!bg-white/20">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Salir
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="space-y-6">
            <!-- Pending Deliveries -->
            <div class="card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Entregas Pendientes</h2>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Entrega</label>
                        <input type="date" id="filterDeliveryDate" class="input-field" onchange="loadDeliveries()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select id="filterStatus" class="input-field" onchange="loadDeliveries()">
                            <option value="">Todos</option>
                            <option value="pending">Pendiente</option>
                            <option value="in_transit">En Tránsito</option>
                            <option value="delivered">Entregado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Fecha Entrega</th>
                                <th>Cliente</th>
                                <th>Dirección</th>
                                <th>Teléfono</th>
                                <th>Bultos</th>
                                <th>Vehículo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="deliveriesTableBody">
                            <!-- Deliveries will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vehicles -->
            <div class="card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Vehículos</h2>
                    <button onclick="openVehicleModal()" class="btn-primary">
                        + Nuevo Vehículo
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Patente</th>
                                <th>Capacidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="vehiclesTableBody">
                            <!-- Vehicles will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Vehicle Modal -->
    <div id="vehicleModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-lg">
            <h3 class="text-xl font-bold mb-4" id="vehicleModalTitle">Nuevo Vehículo</h3>
            
            <form id="vehicleForm" onsubmit="saveVehicle(event)" class="space-y-4">
                <input type="hidden" id="vehicleId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" id="vehicleName" class="input-field" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Patente</label>
                        <input type="text" id="vehicleLicense" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Capacidad (bultos)</label>
                        <input type="number" id="vehicleCapacity" class="input-field" min="0">
                    </div>
                </div>
                
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="vehicleActive" checked>
                        <span class="text-sm text-gray-700">Activo</span>
                    </label>
                </div>

                <div class="flex gap-2 pt-4">
                    <button type="submit" class="btn-primary flex-1">Guardar</button>
                    <button type="button" onclick="closeVehicleModal()" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delivery Update Modal -->
    <div id="deliveryModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-lg">
            <h3 class="text-xl font-bold mb-4">Actualizar Entrega</h3>
            
            <form id="deliveryForm" onsubmit="updateDelivery(event)" class="space-y-4">
                <input type="hidden" id="deliveryId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                    <select id="deliveryStatus" class="input-field" required>
                        <option value="pending">Pendiente</option>
                        <option value="in_transit">En Tránsito</option>
                        <option value="delivered">Entregado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vehículo</label>
                    <select id="deliveryVehicle" class="input-field">
                        <option value="">Sin asignar</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                    <textarea id="deliveryNotes" class="input-field" rows="3"></textarea>
                </div>

                <div class="flex gap-2 pt-4">
                    <button type="submit" class="btn-primary flex-1">Actualizar</button>
                    <button type="button" onclick="closeDeliveryModal()" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let deliveries = [];
        let vehicles = [];
        let currentBranchId = <?php echo $user['branch_id'] ? $user['branch_id'] : 'null'; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('filterDeliveryDate').value = new Date().toISOString().split('T')[0];
            loadVehicles();
            loadDeliveries();
            <?php if ($user['role'] === 'admin'): ?>
                loadBranches();
            <?php endif; ?>
        });

        // Load Branches (Admin only)
        async function loadBranches() {
            try {
                const response = await fetch('api/get-branches.php');
                const branchesData = await response.json();
                
                if (branchesData.success) {
                    renderBranches(branchesData.branches);
                }
            } catch (error) {
                console.error('Error loading branches:', error);
            }
        }

        // Render Branches
        function renderBranches(branches) {
            const select = document.getElementById('branchSelector');
            if (!select) return;
            
            select.innerHTML = branches.map(b => `
                <option value="${b.id}" ${b.id == currentBranchId ? 'selected' : ''}>
                    ${escapeHtml(b.name)}
                </option>
            `).join('');
        }

        // Switch Branch
        async function switchBranch(branchId) {
            if (!branchId) return;
            
            try {
                const response = await fetch('api/switch-branch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ branchId: parseInt(branchId) })
                });
                const data = await response.json();
                
                if (data.success) {
                    currentBranchId = parseInt(branchId);
                    alert('Sucursal cambiada a: ' + data.branch.name);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        async function loadVehicles() {
            try {
                const response = await fetch('api/get-vehicles.php');
                const data = await response.json();
                
                if (data.success) {
                    vehicles = data.vehicles;
                    renderVehicles(vehicles);
                    
                    // Populate vehicle select in delivery modal
                    const select = document.getElementById('deliveryVehicle');
                    select.innerHTML = '<option value="">Sin asignar</option>' + 
                        vehicles.filter(v => v.active).map(v => `<option value="${v.id}">${escapeHtml(v.name)} (${v.license_plate || 'S/P'})</option>`).join('');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function loadDeliveries() {
            try {
                const deliveryDate = document.getElementById('filterDeliveryDate').value;
                const status = document.getElementById('filterStatus').value;
                
                const response = await fetch(`api/get-deliveries.php?date=${deliveryDate}&status=${status}`);
                const data = await response.json();
                
                if (data.success) {
                    deliveries = data.deliveries;
                    renderDeliveries(deliveries);
                } else {
                    alert('Error al cargar entregas');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        function renderDeliveries(deliveriesToRender) {
            const tbody = document.getElementById('deliveriesTableBody');
            
            if (deliveriesToRender.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-4">No se encontraron entregas</td></tr>';
                return;
            }

            const statusLabels = {
                'pending': 'badge-amber',
                'in_transit': 'badge-blue',
                'delivered': 'badge-green',
                'cancelled': 'badge-gray'
            };

            const statusNames = {
                'pending': 'Pendiente',
                'in_transit': 'En Tránsito',
                'delivered': 'Entregado',
                'cancelled': 'Cancelado'
            };

            tbody.innerHTML = deliveriesToRender.map(delivery => `
                <tr>
                    <td>${new Date(delivery.delivery_date).toLocaleDateString()}</td>
                    <td class="font-medium">${escapeHtml(delivery.customer_name)}</td>
                    <td>${escapeHtml(delivery.customer_address || '-')}</td>
                    <td>${escapeHtml(delivery.customer_phone || '-')}</td>
                    <td class="num">${delivery.total_bultos || '-'}</td>
                    <td>${escapeHtml(delivery.vehicle_name || 'Sin asignar')}</td>
                    <td>
                        <span class="badge ${statusLabels[delivery.status] || 'badge-gray'}">
                            ${statusNames[delivery.status] || delivery.status}
                        </span>
                    </td>
                    <td>
                        <button onclick="editDelivery(${delivery.id})" class="text-blue-600 hover:text-blue-800 text-sm">
                            Actualizar
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function renderVehicles(vehiclesToRender) {
            const tbody = document.getElementById('vehiclesTableBody');
            
            if (vehiclesToRender.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-500 py-4">No se encontraron vehículos</td></tr>';
                return;
            }

            tbody.innerHTML = vehiclesToRender.map(vehicle => `
                <tr>
                    <td class="font-medium">${escapeHtml(vehicle.name)}</td>
                    <td>${escapeHtml(vehicle.license_plate || '-')}</td>
                    <td class="num">${vehicle.capacity}</td>
                    <td>
                        <span class="badge ${vehicle.active ? 'badge-green' : 'badge-gray'}">
                            ${vehicle.active ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <button onclick="editVehicle(${vehicle.id})" class="text-blue-600 hover:text-blue-800 text-sm mr-2">
                            Editar
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function openVehicleModal() {
            document.getElementById('vehicleModalTitle').textContent = 'Nuevo Vehículo';
            document.getElementById('vehicleForm').reset();
            document.getElementById('vehicleId').value = '';
            document.getElementById('vehicleActive').checked = true;
            document.getElementById('vehicleModal').classList.remove('hidden');
        }

        function closeVehicleModal() {
            document.getElementById('vehicleModal').classList.add('hidden');
        }

        function editVehicle(id) {
            const vehicle = vehicles.find(v => v.id === id);
            if (!vehicle) return;

            document.getElementById('vehicleModalTitle').textContent = 'Editar Vehículo';
            document.getElementById('vehicleId').value = vehicle.id;
            document.getElementById('vehicleName').value = vehicle.name;
            document.getElementById('vehicleLicense').value = vehicle.license_plate || '';
            document.getElementById('vehicleCapacity').value = vehicle.capacity;
            document.getElementById('vehicleActive').checked = vehicle.active;
            document.getElementById('vehicleModal').classList.remove('hidden');
        }

        async function saveVehicle(event) {
            event.preventDefault();

            const vehicleData = {
                id: document.getElementById('vehicleId').value || null,
                name: document.getElementById('vehicleName').value.trim(),
                licensePlate: document.getElementById('vehicleLicense').value.trim(),
                capacity: parseInt(document.getElementById('vehicleCapacity').value) || 0,
                active: document.getElementById('vehicleActive').checked
            };

            try {
                const response = await fetch('api/save-vehicle.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(vehicleData)
                });

                const data = await response.json();

                if (data.success) {
                    alert('Vehículo guardado exitosamente');
                    closeVehicleModal();
                    loadVehicles();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        function editDelivery(id) {
            const delivery = deliveries.find(d => d.id === id);
            if (!delivery) return;

            document.getElementById('deliveryId').value = delivery.id;
            document.getElementById('deliveryStatus').value = delivery.status;
            document.getElementById('deliveryVehicle').value = delivery.vehicle_id || '';
            document.getElementById('deliveryNotes').value = delivery.notes || '';
            document.getElementById('deliveryModal').classList.remove('hidden');
        }

        function closeDeliveryModal() {
            document.getElementById('deliveryModal').classList.add('hidden');
        }

        async function updateDelivery(event) {
            event.preventDefault();

            const deliveryData = {
                id: document.getElementById('deliveryId').value,
                status: document.getElementById('deliveryStatus').value,
                vehicleId: document.getElementById('deliveryVehicle').value || null,
                notes: document.getElementById('deliveryNotes').value.trim()
            };

            try {
                const response = await fetch('api/update-delivery.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(deliveryData)
                });

                const data = await response.json();

                if (data.success) {
                    alert('Entrega actualizada exitosamente');
                    closeDeliveryModal();
                    loadDeliveries();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        async function logout() {
            if (!confirm('¿Cerrar sesión?')) return;

            try {
                await fetch('api/logout.php', { method: 'POST' });
            } catch (error) {
                console.error('Logout error:', error);
            }

            window.location.href = 'login.php';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
