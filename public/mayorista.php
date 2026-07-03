<?php
/**
 * Ventas Mayoristas
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
    <title>Mayorista - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="min-h-screen">
    <!-- Header -->
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
                    <a href="reports.php" class="nav-tab nav-tab-inactive">Reportes</a>
                    <a href="mayorista.php" class="nav-tab nav-tab-active">Mayorista</a>
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
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Product List -->
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Productos Mayoristas</h2>
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
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Carrito Mayorista</h2>
                    
                    <!-- Customer Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cliente *</label>
                        <select id="customerSelect" class="input-field" required>
                            <option value="">Seleccionar cliente...</option>
                        </select>
                    </div>

                    <!-- Price Input (Admin sets price) -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unitario (Negociado) *</label>
                        <input type="number" id="wholesalePrice" class="input-field" step="0.01" placeholder="0.00">
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
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="paymentMethod" value="cash" checked class="w-4 h-4 text-blue-600">
                                <span>💰 Efectivo</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="paymentMethod" value="card" class="w-4 h-4 text-blue-600">
                                <span>💳 Tarjeta</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="paymentMethod" value="transfer" class="w-4 h-4 text-blue-600">
                                <span>🏦 Transferencia</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="paymentMethod" value="account" class="w-4 h-4 text-blue-600">
                                <span>📋 Cuenta Corriente</span>
                            </label>
                        </div>
                    </div>

                    <!-- Delivery Option -->
                    <div class="mt-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="isForDelivery" class="w-4 h-4 text-blue-600">
                            <span class="text-sm text-gray-700">Requiere envío</span>
                        </label>
                    </div>

                    <!-- Delivery Details (hidden by default) -->
                    <div id="deliveryDetails" class="mt-4 hidden space-y-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Entrega</label>
                            <input type="date" id="deliveryDate" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                            <input type="text" id="deliveryAddress" class="input-field" placeholder="Dirección de entrega">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bultos</label>
                            <input type="number" id="deliveryBultos" class="input-field" min="1" value="1">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 space-y-2">
                        <button onclick="processWholesaleSale()" class="btn-success w-full py-3 text-lg">
                            Procesar Venta Mayorista
                        </button>
                        <button onclick="clearCart()" class="btn-secondary w-full">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="successModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-md text-center">
            <div class="text-6xl mb-4">✅</div>
            <h3 class="text-2xl font-bold text-green-600 mb-2">¡Venta Mayorista Exitosa!</h3>
            <p class="text-gray-600 mb-6">La venta se ha procesado correctamente.</p>
            <button onclick="closeSuccessModal()" class="btn-primary w-full">Aceptar</button>
        </div>
    </div>

    <div id="errorModal" class="modal-backdrop hidden">
        <div class="card w-full max-w-md text-center">
            <div class="text-6xl mb-4">❌</div>
            <h3 class="text-2xl font-bold text-red-600 mb-2">Error</h3>
            <p id="errorMessage" class="text-gray-600 mb-6">Ha ocurrido un error.</p>
            <button onclick="closeErrorModal()" class="btn-primary w-full">Aceptar</button>
        </div>
    </div>

    <script>
        let products = [];
        let cart = [];
        let customers = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            loadCustomers();
            
            document.getElementById('deliveryDate').value = new Date().toISOString().split('T')[0];
            
            // Toggle delivery details
            document.getElementById('isForDelivery').addEventListener('change', function() {
                document.getElementById('deliveryDetails').classList.toggle('hidden', !this.checked);
            });
        });

        async function loadProducts() {
            try {
                const response = await fetch('api/get-products.php');
                const data = await response.json();
                
                if (data.success) {
                    products = data.products;
                    renderProducts(products);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function loadCustomers() {
            try {
                const response = await fetch('api/get-customers.php');
                const data = await response.json();
                
                if (data.success) {
                    customers = data.customers;
                    const select = document.getElementById('customerSelect');
                    select.innerHTML = '<option value="">Seleccionar cliente...</option>' + 
                        customers.filter(c => c.active).map(c => `
                            <option value="${c.id}">${escapeHtml(c.name)}</option>
                        `).join('');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

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
                            <p class="text-sm text-gray-500">Precio sugerido:</p>
                            <p class="price text-lg font-bold text-primary">$${product.wholesalePrice.toFixed(2)}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function filterProducts() {
            const search = document.getElementById('searchProduct').value.toLowerCase();
            const filtered = products.filter(p => p.name.toLowerCase().includes(search));
            renderProducts(filtered);
        }

        function addToCart(productId) {
            const product = products.find(p => p.id === productId);
            if (!product || product.stock <= 0) {
                showErrorModal('Producto sin stock');
                return;
            }

            const existingItem = cart.find(item => item.productId === productId);
            
            if (existingItem) {
                if (existingItem.quantity < product.stock) {
                    existingItem.quantity++;
                } else {
                    showErrorModal('No hay más stock disponible');
                    return;
                }
            } else {
                cart.push({
                    productId: product.id,
                    name: product.name,
                    price: parseFloat(document.getElementById('wholesalePrice').value) || product.wholesalePrice,
                    quantity: 1,
                    stock: product.stock
                });
            }

            renderCart();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        function updateQuantity(index, change) {
            const item = cart[index];
            const product = products.find(p => p.id === item.productId);
            
            const newQuantity = item.quantity + change;
            
            if (newQuantity <= 0) {
                removeFromCart(index);
                return;
            }
            
            if (newQuantity > product.stock) {
                showErrorModal('No hay más stock disponible');
                return;
            }
            
            item.quantity = newQuantity;
            renderCart();
        }

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

        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('total').textContent = `$${subtotal.toFixed(2)}`;
        }

        // Update cart prices when wholesale price changes
        document.getElementById('wholesalePrice').addEventListener('input', function() {
            const price = parseFloat(this.value) || 0;
            cart.forEach(item => item.price = price);
            renderCart();
        });

        async function processWholesaleSale() {
            if (cart.length === 0) {
                showErrorModal('El carrito está vacío');
                return;
            }

            const customerId = document.getElementById('customerSelect').value;
            if (!customerId) {
                showErrorModal('Debe seleccionar un cliente');
                return;
            }

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
            const isForDelivery = document.getElementById('isForDelivery').checked;

            if (paymentMethod === 'account' && !customerId) {
                showErrorModal('Debe seleccionar un cliente para venta a cuenta corriente');
                return;
            }

            const saleData = {
                items: cart.map(item => ({
                    productId: item.productId,
                    quantity: item.quantity,
                    price: item.price
                })),
                total: total,
                paymentMethod: paymentMethod,
                customerId: parseInt(customerId),
                isForDelivery: isForDelivery,
                totalBultos: isForDelivery ? parseInt(document.getElementById('deliveryBultos').value) || 0 : 0,
                saleType: 'wholesale',
                deliveryDate: isForDelivery ? document.getElementById('deliveryDate').value : null,
                deliveryAddress: isForDelivery ? document.getElementById('deliveryAddress').value : null
            };

            try {
                const response = await fetch('api/create-sale.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });

                const data = await response.json();

                if (data.success) {
                    showSuccessModal('¡Venta mayorista realizada con éxito!');
                    
                    // If delivery, create delivery record
                    if (isForDelivery) {
                        const deliveryData = {
                            saleId: data.saleId,
                            customerId: customerId,
                            customerName: customers.find(c => c.id == customerId)?.name || '',
                            customerAddress: document.getElementById('deliveryAddress').value,
                            customerPhone: customers.find(c => c.id == customerId)?.phone || '',
                            deliveryDate: document.getElementById('deliveryDate').value,
                            totalBultos: parseInt(document.getElementById('deliveryBultos').value) || 0
                        };
                        
                        try {
                            const deliveryResponse = await fetch('api/create-delivery.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(deliveryData)
                            });
                        } catch (error) {
                            console.error('Delivery error:', error);
                        }
                    }
                    
                    clearCart();
                    loadProducts();
                } else {
                    showErrorModal('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorModal('Error de conexión al procesar la venta');
            }
        }

        function clearCart() {
            cart = [];
            renderCart();
            document.getElementById('wholesalePrice').value = '';
            document.getElementById('customerSelect').value = '';
            document.getElementById('isForDelivery').checked = false;
            document.getElementById('deliveryDetails').classList.add('hidden');
        }

        // Modal functions
        function showSuccessModal(message) {
            document.getElementById('successModal').classList.remove('hidden');
            document.querySelector('#successModal p').textContent = message;
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        function showErrorModal(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorModal').classList.remove('hidden');
        }

        function closeErrorModal() {
            document.getElementById('errorModal').classList.add('hidden');
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
