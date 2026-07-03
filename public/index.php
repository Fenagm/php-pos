<?php
/**
 * Punto de entrada principal
 * Mr Huevos POS - PHP Version
 * Redirige al login si no hay sesión activa
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

startSecureSession();

// Si está autenticado, ir al POS, sino al login
if (isAuthenticated()) {
    header('Location: pos.php');
} else {
    header('Location: login.php');
}
exit;
