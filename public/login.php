<?php
/**
 * Página de login
 * Mr Huevos POS - PHP Version
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

startSecureSession();

// Si ya está autenticado, redirigir al POS
if (isAuthenticated()) {
    header('Location: pos.php');
    exit;
}

$error = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Usuario y contraseña son requeridos';
    } else {
        $result = loginUser($username, $password);
        
        if ($result['success']) {
            header('Location: pos.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="card w-full max-w-md">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800"><?php echo APP_NAME; ?></h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Usuario</label>
                <input 
                    type="text" 
                    name="username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? 'admin'); ?>"
                    class="input-field"
                    placeholder="admin"
                    required
                    autofocus
                />
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña</label>
                <input 
                    type="password" 
                    name="password"
                    class="input-field"
                    placeholder="••••••••"
                    required
                />
            </div>

            <button 
                type="submit" 
                class="btn-primary w-full"
            >
                Ingresar
            </button>
        </form>

        <p class="mt-6 text-xs text-gray-500 text-center">
            Demo: admin/admin123 · encargado/encargado123 · vendedor/vendedor123
        </p>
    </div>
</body>
</html>
