# Mr Huevos POS - PHP Version

Sistema de Punto de Venta completo desarrollado en **PHP, JavaScript Vanilla, HTML y MySQL**.

## 🚀 Tecnologías

- **Backend**: PHP 7.4+ con PDO
- **Frontend**: HTML5, JavaScript Vanilla (ES6+)
- **Estilos**: TailwindCSS (vía CDN)
- **Base de Datos**: MySQL 5.7+ / MariaDB 10.3+
- **Arquitectura**: MVC simplificado con API RESTful

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o MariaDB 10.3+
- Servidor web (Apache/Nginx) o PHP built-in server
- Navegador moderno con soporte para ES6

## 🛠️ Instalación

### 1. Clonar o copiar el proyecto

```bash
cd php-pos
```

### 2. Configurar la base de datos

Importar el esquema de base de datos:

```bash
mysql -u root -p < database/schema.sql
```

O usar phpMyAdmin para importar `database/schema.sql`.

### 3. Configurar credenciales

Editar `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mr_huevos_pos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
// APP_URL se configura automáticamente según el dominio
```

**Nota para producción**: El sistema detecta automáticamente la URL base. Si necesitas forzar una URL específica (ej. con HTTPS), edita `includes/config.php` y actualiza:

```php
define('APP_URL', 'https://tudominio.com/php-pos/public');
```

### 4. Iniciar el servidor

**Opción A: PHP Built-in Server (Desarrollo)**

```bash
cd public
php -S localhost:8000
```

**Opción B: Apache**

Configurar el DocumentRoot a `/ruta/al/proyecto/php-pos/public`

**Opción C: Nginx**

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/php-pos/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🔐 Usuarios por defecto

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| admin | admin123 | Administrador |
| encargado | encargado123 | Gerente |
| vendedor | vendedor123 | Vendedor |

## 📁 Estructura del Proyecto

```
php-pos/
├── api/                    # Endpoints API REST (JSON)
│   ├── login.php          # Autenticación
│   ├── logout.php         # Cerrar sesión
│   ├── get-products.php   # Obtener productos
│   ├── save-product.php   # Guardar producto
│   ├── create-sale.php    # Crear venta
│   ├── get-customers.php  # Obtener clientes
│   ├── save-customer.php  # Guardar cliente
│   ├── toggle-customer-status.php  # Activar/Desactivar cliente
│   └── get-reports.php    # Reportes de ventas
├── public/                 # Páginas web accesibles
│   ├── login.php          # Login
│   ├── pos.php            # Punto de venta
│   ├── inventory.php      # Inventario
│   ├── customers.php      # Clientes
│   └── reports.php        # Reportes
├── includes/               # Código PHP compartido
│   ├── config.php         # Configuración
│   ├── database.php       # Conexión DB (Singleton PDO)
│   └── auth.php           # Funciones de autenticación
├── database/
│   └── schema.sql         # Esquema de base de datos
├── assets/
│   └── styles.css         # Estilos TailwindCSS compilados
└── README.md
```

## 🎯 Características

- ✅ **Autenticación segura** con sesiones PHP y contraseñas hash (bcrypt)
- ✅ **Roles de usuario** (admin, manager, seller) con permisos diferenciados
- ✅ **Punto de Venta (POS)** funcional con carrito de compras
- ✅ **Gestión de productos** e inventario en tiempo real
- ✅ **Clientes y cuentas corrientes** con límites de crédito
- ✅ **Múltiples métodos de pago** (efectivo, tarjeta, transferencia, cuenta corriente)
- ✅ **API RESTful** JSON para integración con frontend
- ✅ **Diseño responsive** con TailwindCSS
- ✅ **Reportes de ventas** con filtros por fecha y método de pago
- ✅ **Soporte multi-sucursal** (branches)

## 🔧 APIs Disponibles

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/login.php` | POST | Iniciar sesión |
| `/api/logout.php` | POST | Cerrar sesión |
| `/api/get-products.php` | GET | Listar productos |
| `/api/save-product.php` | POST | Crear/actualizar producto |
| `/api/create-sale.php` | POST | Registrar venta |
| `/api/get-customers.php` | GET | Listar clientes |
| `/api/save-customer.php` | POST | Crear/actualizar cliente |
| `/api/toggle-customer-status.php` | POST | Activar/desactivar cliente |
| `/api/get-reports.php` | GET | Obtener reportes de ventas |

## 🔒 Seguridad

- Sesiones seguras con cookies HTTP-only y SameSite=Strict
- Protección CSRF implementada
- Consultas preparadas (PDO) contra SQL injection
- Hash de contraseñas con bcrypt (password_hash/password_verify)
- Validación de roles por página y API endpoint
- Sanitización de datos de entrada y salida (htmlspecialchars)

## 📝 Notas de Producción

1. **Cambiar credenciales por defecto** inmediatamente después de instalar
2. **Configurar HTTPS** en producción
3. **Ajustar config.php**:
   - `display_errors = 0` en producción
   - Usar credenciales reales de base de datos
4. **Configurar firewall** para permitir solo tráfico necesario
5. **Realizar backups** periódicos de la base de datos

## 📄 Licencia

Proyecto de código abierto para uso educativo y comercial.
