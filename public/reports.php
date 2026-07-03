<?php
/**
 * Reportes y Ventas
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
    <title>Reportes - <?php echo APP_NAME; ?></title>
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
                    <a href="inventory.php" class="nav-tab nav-tab-inactive">Inventario</a>
                    <a href="purchases.php" class="nav-tab nav-tab-inactive">Compras</a>
                    <a href="logistics.php" class="nav-tab nav-tab-inactive">Logística</a>
                    <a href="reports.php" class="nav-tab nav-tab-active">Reportes</a>
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
        <div class="space-y-6">
            <!-- Filters -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">Filtros</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                        <input type="date" id="dateFrom" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                        <input type="date" id="dateTo" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago</label>
                        <select id="paymentMethod" class="input-field">
                            <option value="">Todos</option>
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                            <option value="account">Cuenta Corriente</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="loadReports()" class="btn-primary w-full">
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="card">
                    <p class="text-sm text-gray-600">Total Ventas</p>
                    <p class="text-2xl font-bold text-primary" id="totalSales">$0.00</p>
                </div>
                <div class="card">
                    <p class="text-sm text-gray-600">Cantidad Ventas</p>
                    <p class="text-2xl font-bold" id="totalTransactions">0</p>
                </div>
                <div class="card">
                    <p class="text-sm text-gray-600">Total Items</p>
                    <p class="text-2xl font-bold" id="totalItems">0</p>
                </div>
            </div>

            <!-- Sales Table -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">Ventas</h2>
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Método</th>
                                <th>Total</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                            <!-- Sales will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Set default dates (today)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const firstDayOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
            
            document.getElementById('dateFrom').value = firstDayOfMonth;
            document.getElementById('dateTo').value = today;
            
            loadReports();
        });

        async function loadReports() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const paymentMethod = document.getElementById('paymentMethod').value;

            const params = new URLSearchParams({
                dateFrom,
                dateTo,
                paymentMethod
            });

            try {
                const response = await fetch('api/get-reports.php?' + params);
                const data = await response.json();

                if (data.success) {
                    renderSales(data.sales);
                    updateSummary(data.summary);
                } else {
                    alert('Error al cargar reportes');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        function renderSales(sales) {
            const tbody = document.getElementById('salesTableBody');

            if (sales.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray-500 py-4">No se encontraron ventas</td></tr>';
                return;
            }

            tbody.innerHTML = sales.map(sale => `
                <tr>
                    <td class="font-mono">#${sale.id}</td>
                    <td>${formatDate(sale.created_at)}</td>
                    <td>${escapeHtml(sale.customer_name || 'Cliente General')}</td>
                    <td><span class="badge badge-gray">${translatePaymentMethod(sale.payment_method)}</span></td>
                    <td class="num font-semibold">$${parseFloat(sale.total).toFixed(2)}</td>
                    <td>${escapeHtml(sale.username || 'N/A')}</td>
                </tr>
            `).join('');
        }

        function updateSummary(summary) {
            document.getElementById('totalSales').textContent = `$${parseFloat(summary.totalSales || 0).toFixed(2)}`;
            document.getElementById('totalTransactions').textContent = summary.totalTransactions || 0;
            document.getElementById('totalItems').textContent = summary.totalItems || 0;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-PY') + ' ' + date.toLocaleTimeString('es-PY', { hour: '2-digit', minute: '2-digit' });
        }

        function translatePaymentMethod(method) {
            const translations = {
                'cash': 'Efectivo',
                'card': 'Tarjeta',
                'transfer': 'Transferencia',
                'account': 'Cta. Cte.'
            };
            return translations[method] || method;
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
