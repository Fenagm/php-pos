-- Mr Huevos POS Database Schema for MySQL
-- Modern POS System with Multi-branch Support
-- Modules: Auth, Inventory, Sales, Customers, Accounts Receivable, Cash Register, Purchases, Logistics

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS mr_huevos_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mr_huevos_pos;

-- Create branches table
CREATE TABLE IF NOT EXISTS branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  address TEXT,
  phone VARCHAR(20),
  active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) DEFAULT 'user',
  branch_id INT,
  active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create customers table with credit fields
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  phone VARCHAR(20),
  address TEXT,
  ruc VARCHAR(20),
  account_balance DECIMAL(10,2) DEFAULT 0,
  credit_limit DECIMAL(10,2) DEFAULT 500,
  active TINYINT DEFAULT 1,
  total_purchases DECIMAL(10,2) DEFAULT 0,
  branch_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create products table with branch support
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  cost_price DECIMAL(10,2) DEFAULT 0,
  retail_price DECIMAL(10,2) NOT NULL,
  wholesale_price DECIMAL(10,2) NOT NULL,
  stock INT DEFAULT 0,
  active TINYINT DEFAULT 1,
  branch_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create cash_sessions table (debe existir antes que 'sales' por la FK session_id)
CREATE TABLE IF NOT EXISTS cash_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  branch_id INT,
  initial_cash DECIMAL(10,2) DEFAULT 0,
  final_cash DECIMAL(10,2),
  notes TEXT,
  difference DECIMAL(10,2),
  opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  closed_at TIMESTAMP NULL,
  status VARCHAR(20) DEFAULT 'open',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sales table
CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  branch_id INT,
  session_id INT,
  customer_id INT,
  customer_name VARCHAR(100),
  customer_address TEXT,
  customer_phone VARCHAR(20),
  total DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(30) DEFAULT 'cash',
  is_for_delivery TINYINT DEFAULT 0,
  delivery_date DATE,
  total_bultos INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
  FOREIGN KEY (session_id) REFERENCES cash_sessions(id) ON DELETE SET NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sale_items table
CREATE TABLE IF NOT EXISTS sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT,
  product_id INT,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create expenses table
CREATE TABLE IF NOT EXISTS expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT,
  branch_id INT,
  user_id INT,
  amount DECIMAL(10,2) NOT NULL,
  category VARCHAR(50),
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES cash_sessions(id) ON DELETE SET NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create account_movements table
CREATE TABLE IF NOT EXISTS account_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT,
  user_id INT,
  type VARCHAR(20) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  balance_after DECIMAL(10,2) NOT NULL,
  description TEXT,
  payment_method VARCHAR(30),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create purchases table
CREATE TABLE IF NOT EXISTS purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  product_id INT,
  product_name VARCHAR(100) NOT NULL,
  supplier VARCHAR(120) NOT NULL,
  supplier_ruc VARCHAR(20),
  quantity INT NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  user_id INT,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create spoilages table
CREATE TABLE IF NOT EXISTS spoilages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT,
  product_id INT,
  quantity INT NOT NULL,
  reason VARCHAR(50),
  notes TEXT,
  user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create stock_transfers table for inter-branch transfers
CREATE TABLE IF NOT EXISTS stock_transfers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  from_branch_id INT,
  to_branch_id INT NOT NULL,
  quantity INT NOT NULL,
  user_id INT NOT NULL,
  status VARCHAR(20) DEFAULT 'pending',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (from_branch_id) REFERENCES branches(id) ON DELETE SET NULL,
  FOREIGN KEY (to_branch_id) REFERENCES branches(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create vehicles table for logistics
CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  license_plate VARCHAR(20),
  capacity INT NOT NULL DEFAULT 0,
  active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create deliveries table for logistics
CREATE TABLE IF NOT EXISTS deliveries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT,
  branch_id INT,
  customer_id INT,
  customer_name VARCHAR(100) NOT NULL,
  customer_address TEXT,
  customer_phone VARCHAR(20),
  delivery_date DATE NOT NULL,
  vehicle_id INT,
  route_order INT,
  status VARCHAR(20) DEFAULT 'pending',
  total_bultos INT DEFAULT 0,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for performance
CREATE INDEX idx_products_branch ON products(branch_id);
CREATE INDEX idx_products_active ON products(active);
CREATE INDEX idx_customers_branch ON customers(branch_id);
CREATE INDEX idx_sales_branch_created_at ON sales(branch_id, created_at);
CREATE INDEX idx_sales_payment_method ON sales(payment_method);
CREATE INDEX idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX idx_purchases_branch_created_at ON purchases(branch_id, created_at);
CREATE INDEX idx_purchases_product ON purchases(product_id);
CREATE INDEX idx_deliveries_date ON deliveries(delivery_date);
CREATE INDEX idx_deliveries_status ON deliveries(status);
CREATE INDEX idx_deliveries_vehicle ON deliveries(vehicle_id);
CREATE INDEX idx_account_movements_customer ON account_movements(customer_id);
CREATE INDEX idx_expenses_session ON expenses(session_id);
CREATE INDEX idx_stock_transfers_product ON stock_transfers(product_id);
CREATE INDEX idx_stock_transfers_status ON stock_transfers(status);

-- Insert default branches
INSERT INTO branches (name, address, phone) VALUES
('Centenario', 'Av. Principal 123', '555-0001'),
('Caaguazú', 'Calle Secundaria 456', '555-0002');

-- Insert default users (passwords hashed with bcrypt)
-- admin: admin123
-- encargado: encargado123
-- vendedor: vendedor123
INSERT INTO users (username, password_hash, role, branch_id, active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1),
('encargado', '$2y$10$4N8xVxY5k6PqGz8hN9kL2m.sU0vW2xY4zA.rQZ9vXJXLqKjQvJz8', 'manager', 1, 1),
('vendedor', '$2y$10$seller.hash.placeholder.for.vendedor123.password', 'seller', 2, 1);

-- Insert sample products
INSERT INTO products (name, cost_price, retail_price, wholesale_price, stock, branch_id) VALUES
('Huevo Blanco (30u)', 3.50, 4.50, 4.00, 100, 1),
('Huevo Rojo (30u)', 3.80, 4.80, 4.30, 80, 1),
('Huevo Orgánico (15u)', 2.20, 3.20, 2.80, 50, 1),
('Huevo de Codorniz (24u)', 1.80, 2.50, 2.20, 60, 1),
('Huevo Azul (30u)', 4.00, 5.00, 4.50, 40, 1),
('Cartón Vacío', 0.30, 0.50, 0.40, 200, 1);

-- Insert sample customers with RUC and branch assignment
INSERT INTO customers (name, email, phone, address, ruc, account_balance, credit_limit, branch_id) VALUES
('Juan Pérez', 'juan@email.com', '555-1001', 'Calle 1 #123', '8000001-1', 0, 500, 1),
('María García', 'maria@email.com', '555-1002', 'Calle 2 #456', '8000002-2', 150.00, 600, 1),
('Carlos López', 'carlos@email.com', '555-1003', 'Calle 3 #789', '8000003-3', 0, 400, 2),
('Distribuidora del Este', 'contacto@disteste.com', '555-2001', 'Ruta 7 Km 10', '8001000-5', 500.00, 2000, 1);

-- Insert sample vehicles
INSERT INTO vehicles (name, license_plate, capacity) VALUES
('Camión A - Grande', 'ABC-123', 500),
('Camión B - Mediano', 'DEF-456', 300),
('Furgoneta C - Pequeña', 'GHI-789', 100);

-- Insert sample deliveries
INSERT INTO deliveries (sale_id, customer_id, customer_name, customer_address, customer_phone, delivery_date, vehicle_id, route_order, status, total_bultos, notes) VALUES
(1, 1, 'Juan Pérez', 'Calle 1 #123', '555-1001', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 1, 1, 'pending', 5, 'Entregar por la mañana'),
(2, 2, 'María García', 'Calle 2 #456', '555-1002', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 2, 2, 'in_transit', 3, 'Llamar antes de entregar');

-- Insert sample purchases
INSERT INTO purchases (branch_id, product_id, product_name, supplier, supplier_ruc, quantity, total_price, unit_price, user_id, notes) VALUES
(1, 1, 'Huevo Blanco (30u)', 'Avícola Central', '8005000-1', 50, 175.00, 3.50, 1, 'Compra semanal'),
(1, 2, 'Huevo Rojo (30u)', 'Granja del Sur', '8005002-3', 40, 152.00, 3.80, 1, 'Stock nuevo'),
(2, 3, 'Huevo Orgánico (15u)', 'Campo Verde SRL', '8005003-4', 30, 66.00, 2.20, 3, 'Productos orgánicos certificados');

-- Insert sample stock transfers
INSERT INTO stock_transfers (product_id, from_branch_id, to_branch_id, quantity, user_id, status, notes) VALUES
(1, 1, 2, 20, 1, 'completed', 'Transferencia de stock inicial'),
(2, 1, 2, 15, 1, 'pending', 'Pendiente de aprobación');

