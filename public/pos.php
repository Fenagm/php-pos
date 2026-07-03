<?php
/**
 * Punto de Venta (POS) - Página Principal
 * Mr Huevos POS - PHP Version
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - <?php echo APP_NAME; ?></title>
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
                    <a href="pos.php" class="nav-tab nav-tab-active">POS</a>
                    <a href="customers.php" class="nav-tab nav-tab-inactive">Clientes</a>
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                        <a href="inventory.php" class="nav-tab nav-tab-inactive">Inventario</a>
                        <a href="purchases.php" class="nav-tab nav-tab-inactive">Compras</a>
                        <a href="logistics.php" class="nav-tab nav-tab-inactive">Logística</a>
                        <a href="reports.php" class="nav-tab nav-tab-inactive">Reportes</a>
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
        <!-- Cash Register Alert -->
        <?php if (!isset($_SESSION['cash_session_open']) || !$_SESSION['cash_session_open']): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-amber-800">Caja Cerrada</p>
                        <p class="text-sm text-amber-700">Debes abrir caja antes de realizar ventas</p>
                    </div>
                </div>
                <button onclick="openCashRegisterModal()" class="btn-primary">Abrir Caja</button>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Product List -->
            <div class="lg:col-span-2">
                <div class="card">
                    <!-- Accesos Rápidos: Huevo Blanco por calibre y presentación -->
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2">Accesos Rápidos - Huevo Blanco</h3>
                        <div id="quickAccessGrid" class="grid grid-cols-3 gap-2">
                            <!-- Se generan por JS: N1/N2/N3 x Cajón/Maple/Pallet -->
                        </div>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Productos</h2>
                        <input 
                            type="text" 
                            id="searchProduct" 
                            placeholder="Buscar producto..." 
                            class="input-field w-64"
                            oninput="filterProducts()"
                        />
                    </div>
                    <div id="productsList" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Products will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Cart -->
            <div class="lg:col-span-1">
                <div class="card sticky top-20">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Carrito</h2>
                    
                    <!-- Customer Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cliente (Opcional)</label>
                        <select id="customerSelect" class="input-field" onchange="updateCustomerInfo()">
                            <option value="">Cliente General</option>
                        </select>
                    </div>

                    <!-- Cart Items -->
                    <div id="cartItems" class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                        <p class="text-gray-500 text-sm text-center py-4">El carrito está vacío</p>
                    </div>

                    <!-- Totals -->
                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-mono" id="subtotal">$0.00</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold">
                            <span class="text-gray-800">Total:</span>
                            <span class="font-mono text-primary" id="total">$0.00</span>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago</label>
                        <select id="paymentMethod" class="input-field">
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                            <option value="account">Cuenta Corriente</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 space-y-2">
                        <button onclick="processSale()" class="btn-success w-full py-3 text-lg">
                            Procesar Venta
                        </button>
                        <button onclick="clearCart()" class="btn-secondary w-full">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Cash Register Modal -->
    <div id="cashRegisterModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-lg">
            <h3 class="text-xl font-bold mb-4">Gestión de Caja</h3>
            
            <!-- Open Cash Form -->
            <form id="openCashForm" onsubmit="openCashRegister(event)" class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Efectivo Inicial</label>
                    <input type="number" id="initialCash" class="input-field" step="0.01" value="0" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                    <textarea id="openNotes" class="input-field" rows="2"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1">Abrir Caja</button>
                    <button type="button" onclick="closeCashRegisterModal()" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
            
            <!-- Close Cash Form (shown when closing) -->
            <div id="closeCashSection" class="border-t pt-4">
                <h4 class="font-semibold mb-3">Cerrar Caja</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recuento Final de Efectivo</label>
                        <input type="number" id="finalCash" class="input-field" step="0.01" value="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas de Cierre</label>
                        <textarea id="closeNotes" class="input-field" rows="2"></textarea>
                    </div>
                    <button onclick="closeCashRegister()" class="btn-secondary w-full">Cerrar Caja</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let products = [];
        let cart = [];
        let customers = [];
        let currentBranchId = <?php echo $user['branch_id'] ? $user['branch_id'] : 'null'; ?>;
        const userRole = '<?php echo $user['role']; ?>';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            loadCustomers();
            checkCashRegister();
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
                    loadProducts();
                    checkCashRegister();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        // Load Products
        async function loadProducts() {
            try {
                const url = currentBranchId ? `api/get-products.php?branchId=${currentBranchId}` : 'api/get-products.php';
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    products = data.products;
                    renderProducts(products);
                    renderQuickAccess();
                } else {
                    showError('Error al cargar productos');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión');
            }
        }

        // Load Customers
        async function loadCustomers() {
            try {
                const response = await fetch('api/get-customers.php');
                const data = await response.json();
                
                if (data.success) {
                    customers = data.customers;
                    renderCustomers();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Render Products
        function renderProducts(productsToRender) {
            const container = document.getElementById('productsList');
            
            if (productsToRender.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center col-span-2">No se encontraron productos</p>';
                return;
            }

            container.innerHTML = productsToRender.map(product => `
                <div 
                    class="card-accent cursor-pointer hover:shadow-md transition-shadow"
                    onclick="addToCart(${product.id})"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-800">${escapeHtml(product.name)}</h3>
                            <p class="text-sm text-gray-600">Stock: ${product.stock}</p>
                        </div>
                        <div class="text-right">
                            <p class="price text-lg font-bold text-primary">$${product.retailPrice.toFixed(2)}</p>
                            ${product.wholesalePrice !== product.retailPrice ? 
                                `<p class="text-xs text-gray-500">May: $${product.wholesalePrice.toFixed(2)}</p>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Render Customers
        function renderCustomers() {
            const select = document.getElementById('customerSelect');
            const options = customers.map(c => `
                <option value="${c.id}" data-balance="${c.accountBalance}" data-limit="${c.creditLimit}">
                    ${escapeHtml(c.name)} ${c.accountBalance > 0 ? `(Debe: $${c.accountBalance.toFixed(2)})` : ''}
                </option>
            `).join('');
            select.innerHTML = '<option value="">Cliente General</option>' + options;
        }

        // Filter Products
        function filterProducts() {
            const search = document.getElementById('searchProduct').value.toLowerCase();
            const filtered = products.filter(p => p.name.toLowerCase().includes(search));
            renderProducts(filtered);
        }

        // Add to Cart
        function addToCart(productId) {
            const product = products.find(p => p.id === productId);
            if (!product || product.stock <= 0) {
                alert('Producto sin stock');
                return;
            }

            const existingItem = cart.find(item => item.productId === productId);
            
            if (existingItem) {
                if (existingItem.quantity < product.stock) {
                    existingItem.quantity++;
                } else {
                    alert('No hay más stock disponible');
                    return;
                }
            } else {
                cart.push({
                    productId: product.id,
                    name: product.name,
                    price: product.retailPrice,
                    quantity: 1,
                    stock: product.stock
                });
            }

            renderCart();
        }

        // Accesos Rápidos: Huevo Blanco N1/N2/N3 x Cajón/Maple/Pallet
        // Busca el producto por nombre exacto (case-insensitive) en el catálogo
        // ya cargado. Si no existe, avisa para crearlo en Inventario con ese
        // mismo nombre (así después el botón lo encuentra solo).
        const QUICK_ACCESS_ITEMS = [
            'Huevo Blanco N1 - Cajón', 'Huevo Blanco N1 - Maple', 'Huevo Blanco N1 - Pallet',
            'Huevo Blanco N2 - Cajón', 'Huevo Blanco N2 - Maple', 'Huevo Blanco N2 - Pallet',
            'Huevo Blanco N3 - Cajón', 'Huevo Blanco N3 - Maple', 'Huevo Blanco N3 - Pallet',
        ];

        function renderQuickAccess() {
            const container = document.getElementById('quickAccessGrid');
            if (!container) return;

            container.innerHTML = QUICK_ACCESS_ITEMS.map(name => {
                const product = products.find(p => p.name.toLowerCase() === name.toLowerCase());
                const disabled = !product || product.stock <= 0;
                return `
                    <button
                        onclick="quickAddByName('${name.replace(/'/g, "\\'")}')"
                        class="btn-secondary text-xs py-2 px-1 leading-tight text-center ${disabled ? 'opacity-50' : ''}"
                        title="${product ? 'Stock: ' + product.stock : 'No existe en Inventario, creálo con este nombre'}"
                    >
                        ${escapeHtml(name)}
                    </button>
                `;
            }).join('');
        }

        function quickAddByName(name) {
            const product = products.find(p => p.name.toLowerCase() === name.toLowerCase());
            if (!product) {
                alert(`No encontré "${name}" en Inventario. Creálo ahí primero con ese nombre exacto.`);
                return;
            }
            addToCart(product.id);
        }

        // Remove from Cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        // Update Quantity
        function updateQuantity(index, change) {
            const item = cart[index];
            const product = products.find(p => p.id === item.productId);
            
            const newQuantity = item.quantity + change;
            
            if (newQuantity <= 0) {
                removeFromCart(index);
                return;
            }
            
            if (newQuantity > product.stock) {
                alert('No hay más stock disponible');
                return;
            }
            
            item.quantity = newQuantity;
            renderCart();
        }

        // Render Cart
        function renderCart() {
            const container = document.getElementById('cartItems');
            
            if (cart.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">El carrito está vacío</p>';
                updateTotals();
                return;
            }

            container.innerHTML = cart.map((item, index) => `
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800">${escapeHtml(item.name)}</p>
                        <p class="text-xs text-gray-500">$${item.price.toFixed(2)} x ${item.quantity}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="updateQuantity(${index}, -1)" class="btn-secondary px-2 py-1 text-sm">-</button>
                        <span class="text-sm font-mono w-8 text-center">${item.quantity}</span>
                        <button onclick="updateQuantity(${index}, 1)" class="btn-secondary px-2 py-1 text-sm">+</button>
                    </div>
                    <div class="text-right ml-2">
                        <p class="text-sm font-mono">$${(item.price * item.quantity).toFixed(2)}</p>
                        <button onclick="removeFromCart(${index})" class="text-xs text-red-500 hover:text-red-700">Eliminar</button>
                    </div>
                </div>
            `).join('');

            updateTotals();
        }

        // Update Totals
        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('total').textContent = `$${subtotal.toFixed(2)}`;
        }

        // Update Customer Info
        function updateCustomerInfo() {
            const select = document.getElementById('customerSelect');
            const option = select.options[select.selectedIndex];
            const balance = parseFloat(option.dataset.balance || 0);
            const limit = parseFloat(option.dataset.limit || 0);
            
            if (balance >= limit && select.value) {
                alert('Este cliente ha alcanzado su límite de crédito');
                select.value = '';
            }
        }

        // Process Sale
        async function processSale() {
            if (cart.length === 0) {
                alert('El carrito está vacío');
                return;
            }

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const paymentMethod = document.getElementById('paymentMethod').value;
            const customerSelect = document.getElementById('customerSelect');
            const customerId = customerSelect.value ? parseInt(customerSelect.value) : null;

            if (paymentMethod === 'account' && !customerId) {
                alert('Debe seleccionar un cliente para venta a cuenta corriente');
                return;
            }

            // Calculate total bultos (assuming each product is 1 bulto per unit, can be customized)
            const totalBultos = cart.reduce((sum, item) => sum + item.quantity, 0);

            const saleData = {
                items: cart.map(item => ({
                    productId: item.productId,
                    quantity: item.quantity,
                    price: item.price
                })),
                total: total,
                paymentMethod: paymentMethod,
                customerId: customerId,
                isForDelivery: false,
                totalBultos: totalBultos
            };

            try {
                const response = await fetch('api/create-sale.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });

                const data = await response.json();

                if (data.success) {
                    const saleId = data.saleId;
                    
                    // Ask if this is for delivery
                    if (confirm('¿Esta venta es para envío?')) {
                        const selectedCustomer = customers.find(c => c.id === customerId);
                        const deliveryDate = prompt('Fecha de entrega (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
                        
                        if (deliveryDate) {
                            const deliveryData = {
                                saleId: saleId,
                                customerId: customerId || null,
                                customerName: selectedCustomer ? selectedCustomer.name : '',
                                customerAddress: selectedCustomer ? selectedCustomer.address : '',
                                customerPhone: selectedCustomer ? selectedCustomer.phone : '',
                                deliveryDate: deliveryDate,
                                totalBultos: totalBultos
                            };
                            
                            try {
                                const deliveryResponse = await fetch('api/create-delivery.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(deliveryData)
                                });
                                const deliveryData = await deliveryResponse.json();
                                
                                if (deliveryData.success) {
                                    alert('Venta realizada con éxito y entrega programada!');
                                } else {
                                    alert('Venta realizada pero hubo un error al programar la entrega: ' + deliveryData.error);
                                }
                            } catch (error) {
                                console.error('Delivery error:', error);
                                alert('Venta realizada pero hubo un error al programar la entrega');
                            }
                        } else {
                            alert('Venta realizada con éxito!');
                        }
                    } else {
                        alert('Venta realizada con éxito!');
                    }
                    
                    clearCart();
                    loadProducts(); // Reload to update stock
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión al procesar la venta');
            }
        }

        // Clear Cart
        function clearCart() {
            cart = [];
            renderCart();
            document.getElementById('paymentMethod').value = 'cash';
            document.getElementById('customerSelect').value = '';
        }

        // Logout
        async function logout() {
            if (!confirm('¿Cerrar sesión?')) return;

            try {
                await fetch('api/logout.php', { method: 'POST' });
            } catch (error) {
                console.error('Logout error:', error);
            }
            
            window.location.href = 'login.php';
        }

        // Utility: Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Utility: Show Error
        function showError(message) {
            alert(message);
        }

        // Cash Register Modal Functions
        let cashSessionId = null;

        function openCashRegisterModal() {
            document.getElementById('cashRegisterModal').classList.remove('hidden');
        }

        function closeCashRegisterModal() {
            document.getElementById('cashRegisterModal').classList.add('hidden');
        }

        async function checkCashRegister() {
            try {
                const response = await fetch('api/open-cash-register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'check' })
                });
                const data = await response.json();
                
                if (data.success && data.isOpen) {
                    cashSessionId = data.session.id;
                    // Hide cash register alert if open
                    const alert = document.querySelector('.bg-amber-50');
                    if (alert) alert.style.display = 'none';
                }
            } catch (error) {
                console.error('Error checking cash register:', error);
            }
        }

        async function openCashRegister(event) {
            event.preventDefault();
            
            const initialCash = parseFloat(document.getElementById('initialCash').value) || 0;
            const notes = document.getElementById('openNotes').value.trim();
            
            try {
                const response = await fetch('api/open-cash-register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'open', 
                        initialCash: initialCash, 
                        notes: notes 
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    cashSessionId = data.sessionId;
                    alert('Caja abierta exitosamente');
                    closeCashRegisterModal();
                    // Hide cash register alert
                    const alert = document.querySelector('.bg-amber-50');
                    if (alert) alert.style.display = 'none';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        async function closeCashRegister() {
            if (!cashSessionId) {
                alert('No hay sesión de caja abierta');
                return;
            }
            
            if (!confirm('¿Confirmar cierre de caja?')) return;
            
            const finalCash = parseFloat(document.getElementById('finalCash').value) || 0;
            const notes = document.getElementById('closeNotes').value.trim();
            
            try {
                const response = await fetch('api/open-cash-register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'close', 
                        sessionId: cashSessionId, 
                        finalCash: finalCash, 
                        notes: notes 
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    const diffText = data.difference >= 0 ? 'Sobrante' : 'Faltante';
                    alert(`Caja cerrada.\nEfectivo esperado: $${data.expectedCash.toFixed(2)} (inicial $${data.initialCash.toFixed(2)} + ventas efectivo $${data.cashSales.toFixed(2)})\n${diffText}: $${Math.abs(data.difference).toFixed(2)}`);
                    cashSessionId = null;
                    closeCashRegisterModal();
                    // Show cash register alert again
                    const alert = document.querySelector('.bg-amber-50');
                    if (alert) alert.style.display = 'flex';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }
    </script>
</body>
</html>
