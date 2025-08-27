-- Ejemplo de archivo SQL para restaurar base de datos de pizzería
-- Este archivo puede ser cargado directamente en el sistema de backups

-- ========================================
-- BACKUP DE BASE DE DATOS - PIZZERÍA DEMO
-- ========================================
-- Fecha: 2025-08-24
-- Tipo: Solo base de datos
-- Descripción: Datos de ejemplo para pizzería

-- Configuración
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Limpiar tablas existentes (opcional)
-- DROP TABLE IF EXISTS `orders`;
-- DROP TABLE IF EXISTS `order_items`;
-- DROP TABLE IF EXISTS `products`;
-- DROP TABLE IF EXISTS `categories`;
-- DROP TABLE IF EXISTS `users`;

-- ========================================
-- USUARIOS DEL SISTEMA
-- ========================================
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@pizzaexpress.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW(), NOW()),
(2, 'Gerente', 'gerente@pizzaexpress.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', NOW(), NOW()),
(3, 'Empleado', 'empleado@pizzaexpress.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', NOW(), NOW());

-- ========================================
-- CATEGORÍAS DE PRODUCTOS
-- ========================================
INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Pizzas', 'Deliciosas pizzas artesanales', 1, 1, NOW(), NOW()),
(2, 'Bebidas', 'Bebidas frías y calientes', 1, 2, NOW(), NOW()),
(3, 'Acompañamientos', 'Complementos perfectos', 1, 3, NOW(), NOW()),
(4, 'Postres', 'Dulces tentaciones', 1, 4, NOW(), NOW());

-- ========================================
-- PRODUCTOS
-- ========================================
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `is_available`, `preparation_time`, `ingredients`, `created_at`, `updated_at`) VALUES
-- Pizzas
(1, 1, 'Pizza Margherita', 'Pizza clásica con tomate, mozzarella y albahaca fresca', 12.99, 'margherita.jpg', 1, 15, 'Masa, salsa de tomate, mozzarella, albahaca', NOW(), NOW()),
(2, 1, 'Pizza Pepperoni', 'Pizza con pepperoni y mozzarella', 14.99, 'pepperoni.jpg', 1, 15, 'Masa, salsa de tomate, mozzarella, pepperoni', NOW(), NOW()),
(3, 1, 'Pizza Hawaiana', 'Pizza con jamón y piña', 13.99, 'hawaiana.jpg', 1, 15, 'Masa, salsa de tomate, mozzarella, jamón, piña', NOW(), NOW()),
(4, 1, 'Pizza Cuatro Quesos', 'Pizza con cuatro tipos de queso', 16.99, 'cuatro-quesos.jpg', 1, 18, 'Masa, mozzarella, parmesano, gorgonzola, ricotta', NOW(), NOW()),
(5, 1, 'Pizza Vegetariana', 'Pizza con vegetales frescos', 15.99, 'vegetariana.jpg', 1, 20, 'Masa, salsa de tomate, mozzarella, pimientos, champiñones, cebolla', NOW(), NOW()),

-- Bebidas
(6, 2, 'Coca Cola', 'Refresco de cola 500ml', 2.99, 'coca-cola.jpg', 1, 0, NULL, NOW(), NOW()),
(7, 2, 'Agua Mineral', 'Agua mineral 500ml', 1.99, 'agua.jpg', 1, 0, NULL, NOW(), NOW()),
(8, 2, 'Jugo de Naranja', 'Jugo natural de naranja 300ml', 3.99, 'jugo-naranja.jpg', 1, 2, 'Naranja natural', NOW(), NOW()),
(9, 2, 'Cerveza Artesanal', 'Cerveza artesanal de la casa 330ml', 4.99, 'cerveza.jpg', 1, 0, NULL, NOW(), NOW()),

-- Acompañamientos
(10, 3, 'Pan de Ajo', 'Pan tostado con mantequilla de ajo', 4.99, 'pan-ajo.jpg', 1, 8, 'Pan, mantequilla, ajo, perejil', NOW(), NOW()),
(11, 3, 'Papas Fritas', 'Papas fritas crujientes', 3.99, 'papas-fritas.jpg', 1, 10, 'Papas, aceite, sal', NOW(), NOW()),
(12, 3, 'Ensalada César', 'Ensalada fresca con aderezo césar', 6.99, 'ensalada-cesar.jpg', 1, 5, 'Lechuga, crutones, parmesano, aderezo césar', NOW(), NOW()),

-- Postres
(13, 4, 'Tiramisu', 'Postre italiano tradicional', 5.99, 'tiramisu.jpg', 1, 0, 'Mascarpone, café, cacao, bizcocho', NOW(), NOW()),
(14, 4, 'Helado Artesanal', 'Helado de vainilla artesanal', 3.99, 'helado.jpg', 1, 0, 'Leche, crema, vainilla', NOW(), NOW());

-- ========================================
-- ÓRDENES DE EJEMPLO
-- ========================================
INSERT INTO `orders` (`id`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `order_type`, `status`, `total_amount`, `payment_method`, `payment_status`, `notes`, `ordered_at`, `estimated_delivery`, `created_at`, `updated_at`) VALUES
(1, 'Juan Pérez', '+1234567890', 'juan@example.com', 'Av. Principal 123', 'delivery', 'completed', 25.97, 'card', 'paid', 'Sin cebolla en la pizza', '2025-08-24 19:30:00', '2025-08-24 20:00:00', NOW(), NOW()),
(2, 'María García', '+0987654321', 'maria@example.com', NULL, 'pickup', 'preparing', 18.98, 'cash', 'pending', NULL, '2025-08-24 20:15:00', '2025-08-24 20:45:00', NOW(), NOW()),
(3, 'Carlos López', '+1122334455', 'carlos@example.com', 'Calle 2da 456', 'delivery', 'delivered', 31.96, 'card', 'paid', 'Llamar al llegar', '2025-08-24 18:45:00', '2025-08-24 19:30:00', NOW(), NOW());

-- ========================================
-- ITEMS DE LAS ÓRDENES
-- ========================================
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `special_instructions`, `created_at`, `updated_at`) VALUES
-- Orden 1
(1, 1, 1, 1, 12.99, 12.99, NULL, NOW(), NOW()),
(2, 1, 6, 2, 2.99, 5.98, NULL, NOW(), NOW()),
(3, 1, 10, 1, 4.99, 4.99, NULL, NOW(), NOW()),
(4, 1, 14, 1, 3.99, 3.99, NULL, NOW(), NOW()),

-- Orden 2
(5, 2, 2, 1, 14.99, 14.99, 'Extra queso', NOW(), NOW()),
(6, 2, 7, 1, 1.99, 1.99, NULL, NOW(), NOW()),
(7, 2, 11, 1, 3.99, 3.99, NULL, NOW(), NOW()),

-- Orden 3
(8, 3, 4, 1, 16.99, 16.99, NULL, NOW(), NOW()),
(9, 3, 3, 1, 13.99, 13.99, NULL, NOW(), NOW()),
(10, 3, 9, 1, 4.99, 4.99, NULL, NOW(), NOW());

-- ========================================
-- CONFIGURACIÓN DEL RESTAURANTE
-- ========================================
INSERT INTO `restaurant_settings` (`key`, `value`, `created_at`, `updated_at`) VALUES
('restaurant_name', 'Pizza Express', NOW(), NOW()),
('restaurant_phone', '+1234567890', NOW(), NOW()),
('restaurant_email', 'info@pizzaexpress.com', NOW(), NOW()),
('restaurant_address', 'Av. Principal 123, Ciudad', NOW(), NOW()),
('delivery_fee', '2.99', NOW(), NOW()),
('min_delivery_amount', '15.00', NOW(), NOW()),
('tax_rate', '0.18', NOW(), NOW()),
('currency', 'USD', NOW(), NOW()),
('is_open', '1', NOW(), NOW()),
('opening_hours', '{"monday":"11:00-23:00","tuesday":"11:00-23:00","wednesday":"11:00-23:00","thursday":"11:00-23:00","friday":"11:00-24:00","saturday":"11:00-24:00","sunday":"12:00-22:00"}', NOW(), NOW());

-- Restaurar configuración
SET FOREIGN_KEY_CHECKS=1;

-- ========================================
-- INFORMACIÓN DE BACKUP
-- ========================================
-- Este backup contiene:
-- ✅ 3 usuarios del sistema (admin, gerente, empleado)
-- ✅ 4 categorías de productos
-- ✅ 14 productos (pizzas, bebidas, acompañamientos, postres)  
-- ✅ 3 órdenes de ejemplo con sus items
-- ✅ Configuración básica del restaurante
-- 
-- Credenciales por defecto:
-- - admin@pizzaexpress.com / admin123
-- - gerente@pizzaexpress.com / gerente123
-- - empleado@pizzaexpress.com / empleado123
-- 
-- Fecha de creación: 2025-08-24
-- Tipo: Solo base de datos (SQL)
-- Compatible con: MySQL, MariaDB