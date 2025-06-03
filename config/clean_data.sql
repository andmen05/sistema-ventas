-- Desactivar restricciones de clave foránea temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar tablas transaccionales
TRUNCATE TABLE sale_details;
TRUNCATE TABLE sales;
TRUNCATE TABLE purchase_details;
TRUNCATE TABLE purchases;
TRUNCATE TABLE products;
TRUNCATE TABLE categories;
TRUNCATE TABLE customers;
TRUNCATE TABLE suppliers;
TRUNCATE TABLE inventory_movements;
TRUNCATE TABLE cash_register;
TRUNCATE TABLE payments;

-- Limpiar usuarios excepto admin
DELETE FROM users 
WHERE username != 'admin';

-- Limpiar roles excepto admin, manager y cashier
DELETE FROM roles 
WHERE name NOT IN ('admin', 'manager', 'cashier');

-- Reactivar restricciones de clave foránea
SET FOREIGN_KEY_CHECKS = 1;
