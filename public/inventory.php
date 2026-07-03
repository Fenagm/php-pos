<?php
/**
 * Gestión de Inventario
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

// Obtener todas las sucursales (solo para admin)
$branches = [];
if ($user['role'] === 'admin') {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM branches WHERE active = 1 ORDER BY name");
        $branches = $stmt->fetchAll();
    } catch (Exception $e) {
        // Error al cargar sucursales
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - <?php echo APP_NAME; ?></title>
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
                        <a href="inventory.php" class="nav-tab nav-tab-active">Inventario</a>
                        <a href="purchases.php" class="nav-tab nav-tab-inactive">Compras</a>
                        <a href="logistics.php" class="nav-tab nav-tab-inactive">Logística</a>
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
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page header card -->
        <div class="card-header mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Gestión de Productos
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Administra el inventario y asigna stock por sucursal</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <?php if ($user['role'] === 'admin' && count($branches) > 0): ?>
                        <div class="relative">
                            <select id="branchFilter" class="input-field pr-10" onchange="loadProducts()">
                                <option value="">Todas las sucursales</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <button onclick="openProductModal()" class="btn-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Producto
                    </button>
                </div>
            </div>
        </div>

        <!-- Search and filters -->
        <div class="card mb-6">
            <div class="flex flex-col sm:flex-row gap-4 items-center">
                <div class="relative flex-1 w-full">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        type="text" 
                        id="searchProduct" 
                        placeholder="Buscar producto por nombre..." 
                        class="input-field pl-12"
                        oninput="filterProducts()"
                    />
                </div>
                <div class="flex gap-2">
                    <button class="btn-secondary" onclick="loadProducts()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table card -->
        <div class="card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Costo</th>
                            <th>Venta</th>
                            <th>Mayor</th>
                            <th>Stock</th>
                            <th>Sucursal</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <!-- Products will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Product Modal -->
    <div id="productModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-lg modal-content">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2" id="modalTitle">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Producto
                </h3>
                <button onclick="closeProductModal()" class="icon-btn icon-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="productForm" onsubmit="saveProduct(event)" class="space-y-5">
                <input type="hidden" id="productId">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre del Producto *</label>
                    <input type="text" id="productName" class="input-field" required placeholder="Ej: Huevos orgánicos">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Precio Costo</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="number" id="productCostPrice" class="input-field pl-8" step="0.01" value="0">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Precio Venta *</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="number" id="productRetailPrice" class="input-field pl-8" step="0.01" required placeholder="0.00">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Precio Mayorista</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                        <input type="number" id="productWholesalePrice" class="input-field pl-8" step="0.01" placeholder="0.00">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Stock Inicial</label>
                        <input type="number" id="productStock" class="input-field" value="0" min="0">
                    </div>
                    <?php if ($user['role'] === 'admin' && count($branches) > 0): ?>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sucursal</label>
                        <select id="productBranch" class="input-field">
                            <option value="">Sin asignar (global)</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" id="productActive" checked class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-semibold text-gray-700">Producto Activo</span>
                            <p class="text-xs text-gray-500">El producto estará disponible para la venta</p>
                        </div>
                    </label>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="btn-primary flex-1">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Producto
                    </button>
                    <button type="button" onclick="closeProductModal()" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Transfer Modal -->
    <div id="stockTransferModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-lg modal-content">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Asignar Stock a Sucursal
                </h3>
                <button onclick="closeStockTransferModal()" class="icon-btn icon-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="stockTransferForm" onsubmit="transferStock(event)" class="space-y-5">
                <input type="hidden" id="transferProductId">
                <input type="hidden" id="transferProductName">
                
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <label class="block text-sm font-semibold text-blue-800 mb-1">Producto</label>
                    <p class="text-lg font-bold text-blue-900" id="transferProductNameDisplay"></p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Stock Actual</label>
                        <p class="text-2xl font-bold text-gray-800 mono" id="transferCurrentStock">0</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                        <label class="block text-sm font-semibold text-green-800 mb-1">A Asignar</label>
                        <input type="number" id="transferQuantity" class="input-field !bg-white !border-green-300 focus:!border-green-500" min="1" required placeholder="0">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sucursal Destino *</label>
                    <select id="transferBranch" class="input-field" required>
                        <option value="">Seleccionar sucursal...</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="btn-success flex-1">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Confirmar Asignación
                    </button>
                    <button type="button" onclick="closeStockTransferModal()" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let products = [];
        let currentBranchId = <?php echo $user['branch_id'] ? $user['branch_id'] : 'null'; ?>;
        const userRole = '<?php echo $user['role']; ?>';

        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            <?php if ($user['role'] === 'admin'): ?>
                loadBranches();
            <?php endif; ?>
        });

        // Load Branches (Admin only)
        async function loadBranches() {
            try {
                const response = await fetch('api/get-branches.php');
                const data = await response.json();
                
                if (data.success) {
                    renderBranches(data.branches);
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

        async function loadProducts() {
            try {
                const branchId = document.getElementById('branchFilter')?.value || '';
                const url = branchId ? `api/get-products.php?branchId=${branchId}` : 'api/get-products.php';
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    products = data.products;
                    renderProducts(products);
                } else {
                    alert('Error al cargar productos');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        function renderProducts(productsToRender) {
            const tbody = document.getElementById('productsTableBody');
            
            if (productsToRender.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-12">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 font-medium">No se encontraron productos</p>
                                <p class="text-sm text-gray-400 mt-1">Crea un nuevo producto para comenzar</p>
                            </div>
                        </td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = productsToRender.map(product => `
                <tr class="group">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <span class="font-semibold text-gray-800">${escapeHtml(product.name)}</span>
                        </div>
                    </td>
                    <td class="num text-gray-600">$${product.costPrice.toFixed(2)}</td>
                    <td class="num font-bold text-green-600">$${product.retailPrice.toFixed(2)}</td>
                    <td class="num text-gray-600">$${product.wholesalePrice.toFixed(2)}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="num font-bold ${product.stock < 10 ? 'text-red-600' : product.stock < 20 ? 'text-amber-600' : 'text-gray-800'}">
                                ${product.stock}
                            </span>
                            ${product.stock < 10 ? '<span class="status-dot status-dot-active bg-red-500"></span>' : ''}
                        </div>
                    </td>
                    <td>
                        <span class="badge ${product.branch_name ? 'badge-purple' : 'badge-gray'}">
                            ${escapeHtml(product.branch_name || 'Global')}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${product.active ? 'badge-green' : 'badge-gray'}">
                            <span class="status-dot ${product.active ? 'status-dot-active' : 'status-dot-inactive'}"></span>
                            ${product.active ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick="editProduct(${product.id})" class="icon-btn icon-btn-primary" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            ${userRole === 'admin' ? `
                                <button onclick="openStockTransferModal(${product.id}, '${escapeHtml(product.name)}', ${product.stock})" class="icon-btn icon-btn-success" title="Asignar Stock">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function filterProducts() {
            const search = document.getElementById('searchProduct').value.toLowerCase();
            const filtered = products.filter(p => p.name.toLowerCase().includes(search));
            renderProducts(filtered);
        }

        function openProductModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Producto';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productActive').checked = true;
            document.getElementById('productModal').classList.remove('hidden');
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        function editProduct(id) {
            const product = products.find(p => p.id === id);
            if (!product) return;

            document.getElementById('modalTitle').textContent = 'Editar Producto';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productCostPrice').value = product.costPrice;
            document.getElementById('productRetailPrice').value = product.retailPrice;
            document.getElementById('productWholesalePrice').value = product.wholesalePrice;
            document.getElementById('productStock').value = product.stock;
            document.getElementById('productActive').checked = product.active;
            <?php if ($user['role'] === 'admin'): ?>
            document.getElementById('productBranch').value = product.branchId || '';
            <?php endif; ?>
            document.getElementById('productModal').classList.remove('hidden');
        }

        async function saveProduct(event) {
            event.preventDefault();

            const productData = {
                id: document.getElementById('productId').value || null,
                name: document.getElementById('productName').value.trim(),
                costPrice: parseFloat(document.getElementById('productCostPrice').value),
                retailPrice: parseFloat(document.getElementById('productRetailPrice').value),
                wholesalePrice: parseFloat(document.getElementById('productWholesalePrice').value),
                stock: parseInt(document.getElementById('productStock').value),
                active: document.getElementById('productActive').checked
                <?php if ($user['role'] === 'admin'): ?>
                , branchId: document.getElementById('productBranch').value || null
                <?php endif; ?>
            };

            if (!productData.name) {
                alert('El nombre es requerido');
                return;
            }

            if (!productData.retailPrice) {
                alert('El precio de venta es requerido');
                return;
            }

            try {
                const response = await fetch('api/save-product.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(productData)
                });

                const data = await response.json();

                if (data.success) {
                    alert('Producto guardado exitosamente');
                    closeProductModal();
                    loadProducts();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        // Stock Transfer functions
        function openStockTransferModal(productId, productName, currentStock) {
            document.getElementById('transferProductId').value = productId;
            document.getElementById('transferProductNameDisplay').textContent = productName;
            document.getElementById('transferCurrentStock').textContent = currentStock;
            document.getElementById('transferQuantity').value = '';
            document.getElementById('transferBranch').value = '';
            document.getElementById('stockTransferModal').classList.remove('hidden');
        }

        function closeStockTransferModal() {
            document.getElementById('stockTransferModal').classList.add('hidden');
        }

        async function transferStock(event) {
            event.preventDefault();

            const transferData = {
                productId: document.getElementById('transferProductId').value,
                branchId: document.getElementById('transferBranch').value,
                quantity: parseInt(document.getElementById('transferQuantity').value)
            };

            if (!transferData.branchId) {
                alert('Seleccione una sucursal');
                return;
            }

            if (!transferData.quantity || transferData.quantity <= 0) {
                alert('Cantidad inválida');
                return;
            }

            try {
                const response = await fetch('api/transfer-stock.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(transferData)
                });

                const data = await response.json();

                if (data.success) {
                    alert('Stock asignado exitosamente');
                    closeStockTransferModal();
                    loadProducts();
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
