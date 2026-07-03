<?php
/**
 * Gestión de Compras
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
    <title>Compras - <?php echo APP_NAME; ?></title>
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
                    <a href="purchases.php" class="nav-tab nav-tab-active">Compras</a>
                    <a href="logistics.php" class="nav-tab nav-tab-inactive">Logística</a>
                    <a href="reports.php" class="nav-tab nav-tab-inactive">Reportes</a>
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
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Compras</h2>
                <button onclick="openPurchaseModal()" class="btn-primary">
                    + Nueva Compra
                </button>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
                    <input type="date" id="filterDateFrom" class="input-field" onchange="loadPurchases()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
                    <input type="date" id="filterDateTo" class="input-field" onchange="loadPurchases()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Proveedor</label>
                    <input type="text" id="filterSupplier" class="input-field" placeholder="Buscar proveedor..." oninput="loadPurchases()">
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Proveedor</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                            <th>Sucursal</th>
                        </tr>
                    </thead>
                    <tbody id="purchasesTableBody">
                        <!-- Purchases will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Purchase Modal -->
    <div id="purchaseModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-lg">
            <h3 class="text-xl font-bold mb-4">Nueva Compra</h3>
            
            <form id="purchaseForm" onsubmit="savePurchase(event)" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Producto *</label>
                    <select id="purchaseProduct" class="input-field" required onchange="updateProductInfo()">
                        <option value="">Seleccionar producto...</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Proveedor *</label>
                    <input type="text" id="purchaseSupplier" class="input-field" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad *</label>
                        <input type="number" id="purchaseQuantity" class="input-field" required min="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unitario *</label>
                        <input type="number" id="purchaseUnitPrice" class="input-field" step="0.01" required min="0">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total</label>
                    <input type="text" id="purchaseTotal" class="input-field" readonly>
                </div>

                <div class="flex gap-2 pt-4">
                    <button type="submit" class="btn-primary flex-1">Guardar</button>
                    <button type="button" onclick="closePurchaseModal()" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let purchases = [];
        let products = [];

        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates (current month)
            const firstDay = new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-01';
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('filterDateFrom').value = firstDay;
            document.getElementById('filterDateTo').value = today;
            
            loadProducts();
            loadPurchases();
        });

        async function loadProducts() {
            try {
                const response = await fetch('api/get-products.php');
                const data = await response.json();
                
                if (data.success) {
                    products = data.products;
                    const select = document.getElementById('purchaseProduct');
                    select.innerHTML = '<option value="">Seleccionar producto...</option>' + 
                        products.map(p => `<option value="${p.id}" data-cost="${p.costPrice}">${escapeHtml(p.name)}</option>`).join('');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function updateProductInfo() {
            const select = document.getElementById('purchaseProduct');
            const option = select.options[select.selectedIndex];
            const costPrice = option.dataset.cost || 0;
            document.getElementById('purchaseUnitPrice').value = costPrice;
            calculateTotal();
        }

        document.getElementById('purchaseQuantity').addEventListener('input', calculateTotal);
        document.getElementById('purchaseUnitPrice').addEventListener('input', calculateTotal);

        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('purchaseQuantity').value) || 0;
            const unitPrice = parseFloat(document.getElementById('purchaseUnitPrice').value) || 0;
            document.getElementById('purchaseTotal').value = '$' + (quantity * unitPrice).toFixed(2);
        }

        async function loadPurchases() {
            try {
                const dateFrom = document.getElementById('filterDateFrom').value;
                const dateTo = document.getElementById('filterDateTo').value;
                const supplier = document.getElementById('filterSupplier').value;
                
                const response = await fetch(`api/get-purchases.php?dateFrom=${dateFrom}&dateTo=${dateTo}&supplier=${encodeURIComponent(supplier)}`);
                const data = await response.json();
                
                if (data.success) {
                    purchases = data.purchases;
                    renderPurchases(purchases);
                } else {
                    alert('Error al cargar compras');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        function renderPurchases(purchasesToRender) {
            const tbody = document.getElementById('purchasesTableBody');
            
            if (purchasesToRender.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-500 py-4">No se encontraron compras</td></tr>';
                return;
            }

            tbody.innerHTML = purchasesToRender.map(purchase => `
                <tr>
                    <td>${new Date(purchase.created_at).toLocaleDateString()}</td>
                    <td class="font-medium">${escapeHtml(purchase.product_name)}</td>
                    <td>${escapeHtml(purchase.supplier)}</td>
                    <td class="num">${purchase.quantity}</td>
                    <td class="num">$${parseFloat(purchase.unit_price).toFixed(2)}</td>
                    <td class="num font-semibold">$${parseFloat(purchase.total_price).toFixed(2)}</td>
                    <td>${escapeHtml(purchase.branch_name || 'N/A')}</td>
                </tr>
            `).join('');
        }

        function openPurchaseModal() {
            document.getElementById('purchaseForm').reset();
            document.getElementById('purchaseModal').classList.remove('hidden');
        }

        function closePurchaseModal() {
            document.getElementById('purchaseModal').classList.add('hidden');
        }

        async function savePurchase(event) {
            event.preventDefault();

            const purchaseData = {
                productId: document.getElementById('purchaseProduct').value,
                productName: document.getElementById('purchaseProduct').options[document.getElementById('purchaseProduct').selectedIndex]?.text || '',
                supplier: document.getElementById('purchaseSupplier').value.trim(),
                quantity: parseInt(document.getElementById('purchaseQuantity').value),
                unitPrice: parseFloat(document.getElementById('purchaseUnitPrice').value)
            };

            if (!purchaseData.productId) {
                alert('Seleccione un producto');
                return;
            }

            if (!purchaseData.supplier) {
                alert('El proveedor es requerido');
                return;
            }

            try {
                const response = await fetch('api/save-purchase.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(purchaseData)
                });

                const data = await response.json();

                if (data.success) {
                    alert('Compra registrada exitosamente');
                    closePurchaseModal();
                    loadPurchases();
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
