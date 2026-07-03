<?php
/**
 * Gestión de Clientes
 * Mr Huevos POS - PHP Version
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();
$user = getCurrentUser();

// Only admin and manager can access
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
    <title>Clientes - <?php echo APP_NAME; ?></title>
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
                    <a href="customers.php" class="nav-tab nav-tab-active">Clientes</a>
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                        <a href="inventory.php" class="nav-tab nav-tab-inactive">Inventario</a>
                        <a href="purchases.php" class="nav-tab nav-tab-inactive">Compras</a>
                        <a href="logistics.php" class="nav-tab nav-tab-inactive">Logística</a>
                        <a href="reports.php" class="nav-tab nav-tab-inactive">Reportes</a>
                    <?php endif; ?>
                </nav>
                <div class="flex items-center gap-4">
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
        <div class="card">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Clientes</h2>
                <button onclick="openCustomerModal()" class="btn-primary">
                    + Nuevo Cliente
                </button>
            </div>

            <!-- Search -->
            <div class="mb-4">
                <input 
                    type="text" 
                    id="searchCustomer" 
                    placeholder="Buscar cliente..." 
                    class="input-field w-full max-w-md"
                    oninput="filterCustomers()"
                />
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Balance</th>
                            <th>Límite Crédito</th>
                            <th>Total Compras</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <!-- Customers will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Customer Modal -->
    <div id="customerModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-lg">
            <h3 class="text-xl font-bold mb-4" id="modalTitle">Nuevo Cliente</h3>
            
            <form id="customerForm" onsubmit="saveCustomer(event)" class="space-y-4">
                <input type="hidden" id="customerId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" id="customerName" class="input-field" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="text" id="customerPhone" class="input-field">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="customerEmail" class="input-field">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <textarea id="customerAddress" class="input-field" rows="2"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Límite de Crédito</label>
                    <input type="number" id="customerCreditLimit" class="input-field" value="500" step="0.01">
                </div>
                
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="customerActive" checked>
                        <span class="text-sm text-gray-700">Activo</span>
                    </label>
                </div>

                <div class="flex gap-2 pt-4">
                    <button type="submit" class="btn-primary flex-1">Guardar</button>
                    <button type="button" onclick="closeCustomerModal()" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let customers = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadCustomers();
        });

        async function loadCustomers() {
            try {
                const response = await fetch('api/get-customers.php');
                const data = await response.json();
                
                if (data.success) {
                    customers = data.customers;
                    renderCustomers(customers);
                } else {
                    alert('Error al cargar clientes');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        function renderCustomers(customersToRender) {
            const tbody = document.getElementById('customersTableBody');
            
            if (customersToRender.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-4">No se encontraron clientes</td></tr>';
                return;
            }

            tbody.innerHTML = customersToRender.map(customer => `
                <tr>
                    <td class="font-medium">${escapeHtml(customer.name)}</td>
                    <td>${escapeHtml(customer.phone || '')}</td>
                    <td>${escapeHtml(customer.email || '')}</td>
                    <td class="num ${customer.accountBalance > 0 ? 'text-red-600' : ''}">$${customer.accountBalance.toFixed(2)}</td>
                    <td class="num">$${customer.creditLimit.toFixed(2)}</td>
                    <td class="num">$${customer.totalPurchases.toFixed(2)}</td>
                    <td>
                        <span class="badge ${customer.active ? 'badge-green' : 'badge-gray'}">
                            ${customer.active ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <button onclick="editCustomer(${customer.id})" class="text-blue-600 hover:text-blue-800 text-sm mr-2">
                            Editar
                        </button>
                        <button onclick="toggleCustomerStatus(${customer.id})" class="text-sm ${customer.active ? 'text-red-600' : 'text-green-600'} hover:underline">
                            ${customer.active ? 'Desactivar' : 'Activar'}
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function filterCustomers() {
            const search = document.getElementById('searchCustomer').value.toLowerCase();
            const filtered = customers.filter(c => c.name.toLowerCase().includes(search));
            renderCustomers(filtered);
        }

        function openCustomerModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Cliente';
            document.getElementById('customerForm').reset();
            document.getElementById('customerId').value = '';
            document.getElementById('customerCreditLimit').value = '500';
            document.getElementById('customerActive').checked = true;
            document.getElementById('customerModal').classList.remove('hidden');
        }

        function closeCustomerModal() {
            document.getElementById('customerModal').classList.add('hidden');
        }

        function editCustomer(id) {
            const customer = customers.find(c => c.id === id);
            if (!customer) return;

            document.getElementById('modalTitle').textContent = 'Editar Cliente';
            document.getElementById('customerId').value = customer.id;
            document.getElementById('customerName').value = customer.name;
            document.getElementById('customerPhone').value = customer.phone || '';
            document.getElementById('customerEmail').value = customer.email || '';
            document.getElementById('customerAddress').value = customer.address || '';
            document.getElementById('customerCreditLimit').value = customer.creditLimit;
            document.getElementById('customerActive').checked = customer.active;
            document.getElementById('customerModal').classList.remove('hidden');
        }

        async function saveCustomer(event) {
            event.preventDefault();

            const customerData = {
                id: document.getElementById('customerId').value || null,
                name: document.getElementById('customerName').value.trim(),
                phone: document.getElementById('customerPhone').value.trim(),
                email: document.getElementById('customerEmail').value.trim(),
                address: document.getElementById('customerAddress').value.trim(),
                creditLimit: parseFloat(document.getElementById('customerCreditLimit').value),
                active: document.getElementById('customerActive').checked
            };

            if (!customerData.name) {
                alert('El nombre es requerido');
                return;
            }

            try {
                const response = await fetch('api/save-customer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(customerData)
                });

                const data = await response.json();

                if (data.success) {
                    alert('Cliente guardado exitosamente');
                    closeCustomerModal();
                    loadCustomers();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        async function toggleCustomerStatus(id) {
            const customer = customers.find(c => c.id === id);
            if (!customer) return;

            if (!confirm(`¿${customer.active ? 'Desactivar' : 'Activar'} este cliente?`)) return;

            customer.active = !customer.active;

            try {
                const response = await fetch('api/toggle-customer-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, active: customer.active })
                });

                const data = await response.json();

                if (data.success) {
                    loadCustomers();
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
