-- ========================================================
-- RESTAURANT MANAGEMENT SYSTEM - COMPREHENSIVE DATABASE SCHEMA
-- Next-Level Architecture for PHP 8+ / MySQL / MariaDB
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `feedback_reviews`;
DROP TABLE IF EXISTS `expense_records`;
DROP TABLE IF EXISTS `customer_addresses`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `dining_tables`;
DROP TABLE IF EXISTS `stock_transactions`;
DROP TABLE IF EXISTS `menu_ingredients`;
DROP TABLE IF EXISTS `ingredients`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `restaurant_settings`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- 1. USERS & RBAC TABLE
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone` VARCHAR(25) NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'manager', 'waiter', 'kitchen', 'cashier', 'customer') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `avatar` VARCHAR(255) DEFAULT 'default_avatar.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_role` (`role`),
  INDEX `idx_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. RESTAURANT SETTINGS
-- --------------------------------------------------------
CREATE TABLE `restaurant_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(80) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `setting_group` VARCHAR(50) DEFAULT 'general',
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. CATEGORIES
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `icon` VARCHAR(60) DEFAULT 'fa-utensils',
  `display_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. MENU ITEMS
-- --------------------------------------------------------
CREATE TABLE `menu_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `cost_price` DECIMAL(10,2) DEFAULT 0.00,
  `image` VARCHAR(255) DEFAULT 'default_food.png',
  `is_vegetarian` TINYINT(1) DEFAULT 0,
  `is_vegan` TINYINT(1) DEFAULT 0,
  `is_gluten_free` TINYINT(1) DEFAULT 0,
  `preparation_time` INT DEFAULT 15 COMMENT 'Minutes',
  `calories` INT NULL,
  `is_available` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_menu_category` (`category_id`),
  INDEX `idx_menu_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. INGREDIENTS (RAW INVENTORY)
-- --------------------------------------------------------
CREATE TABLE `ingredients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `unit` ENUM('g', 'kg', 'ml', 'l', 'pcs', 'slices', 'cans') DEFAULT 'pcs',
  `current_stock` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `reorder_level` DECIMAL(12,2) NOT NULL DEFAULT 10.00,
  `unit_cost` DECIMAL(10,2) DEFAULT 0.00,
  `last_restocked_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_stock_reorder` (`current_stock`, `reorder_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. MENU INGREDIENTS (RECIPE BILL OF MATERIALS)
-- --------------------------------------------------------
CREATE TABLE `menu_ingredients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `menu_item_id` INT NOT NULL,
  `ingredient_id` INT NOT NULL,
  `quantity_required` DECIMAL(10,2) NOT NULL COMMENT 'Unit matches ingredient.unit',
  UNIQUE KEY `unique_item_ingredient` (`menu_item_id`, `ingredient_id`),
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. STOCK TRANSACTIONS
-- --------------------------------------------------------
CREATE TABLE `stock_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ingredient_id` INT NOT NULL,
  `transaction_type` ENUM('purchase', 'consumption', 'wastage', 'adjustment') NOT NULL,
  `quantity` DECIMAL(12,2) NOT NULL COMMENT 'Positive for stock increase, negative for decrease',
  `reference_order_id` INT NULL,
  `notes` VARCHAR(255) NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_stock_type` (`transaction_type`),
  INDEX `idx_stock_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. DINING TABLES
-- --------------------------------------------------------
CREATE TABLE `dining_tables` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `table_number` VARCHAR(20) NOT NULL UNIQUE,
  `seating_capacity` INT NOT NULL DEFAULT 4,
  `floor_location` ENUM('Ground Floor Main', 'Outdoor Terrace', 'VIP Lounge', 'Bar & High Top') DEFAULT 'Ground Floor Main',
  `status` ENUM('available', 'occupied', 'reserved', 'maintenance') DEFAULT 'available',
  `qr_code` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_table_status` (`status`),
  INDEX `idx_table_capacity` (`seating_capacity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. RESERVATIONS (WITH SMART TABLE ALLOCATION)
-- --------------------------------------------------------
CREATE TABLE `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_code` VARCHAR(30) UNIQUE,
  `customer_id` INT NULL,
  `customer_name` VARCHAR(120) NOT NULL,
  `customer_email` VARCHAR(120) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `table_id` INT NULL,
  `reservation_date` DATE NOT NULL,
  `reservation_time` TIME NOT NULL,
  `guest_count` INT NOT NULL DEFAULT 2,
  `special_requests` TEXT NULL,
  `status` ENUM('pending', 'confirmed', 'seated', 'completed', 'cancelled') DEFAULT 'confirmed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`table_id`) REFERENCES `dining_tables`(`id`) ON DELETE SET NULL,
  INDEX `idx_res_datetime` (`reservation_date`, `reservation_time`),
  INDEX `idx_res_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. ORDERS
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT NULL,
  `table_id` INT NULL,
  `waiter_id` INT NULL,
  `order_type` ENUM('dine_in', 'takeaway', 'delivery') DEFAULT 'dine_in',
  `status` ENUM('pending', 'accepted', 'preparing', 'ready', 'served', 'completed', 'cancelled') DEFAULT 'pending',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` VARCHAR(50) NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`table_id`) REFERENCES `dining_tables`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`waiter_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_order_status` (`status`),
  INDEX `idx_order_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. ORDER ITEMS
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `menu_item_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `special_instructions` VARCHAR(255) NULL,
  `item_status` ENUM('pending', 'preparing', 'ready', 'served') DEFAULT 'pending',
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE,
  INDEX `idx_item_status` (`item_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. ORDER STATUS HISTORY
-- --------------------------------------------------------
CREATE TABLE `order_status_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `notes` VARCHAR(255) NULL,
  `changed_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 13. PAYMENTS & BILLING
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(40) UNIQUE,
  `order_id` INT NOT NULL,
  `payment_method` ENUM('cash', 'credit_card', 'debit_card', 'upi', 'online', 'stripe') DEFAULT 'cash',
  `amount` DECIMAL(10,2) NOT NULL,
  `tax` DECIMAL(10,2) DEFAULT 0.00,
  `discount` DECIMAL(10,2) DEFAULT 0.00,
  `tip` DECIMAL(10,2) DEFAULT 0.00,
  `total_paid` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
  `transaction_reference` VARCHAR(100) NULL,
  `processed_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_payment_status` (`payment_status`),
  INDEX `idx_payment_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 14. COUPONS
-- --------------------------------------------------------
CREATE TABLE `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_order_amount` DECIMAL(10,2) DEFAULT 0.00,
  `max_discount` DECIMAL(10,2) DEFAULT 0.00,
  `valid_from` DATE NULL,
  `valid_to` DATE NULL,
  `usage_limit` INT DEFAULT 100,
  `times_used` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 15. NOTIFICATIONS
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL COMMENT 'NULL for broadcast to managers/admin',
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('order', 'low_stock', 'reservation', 'system', 'payment') DEFAULT 'system',
  `is_read` TINYINT(1) DEFAULT 0,
  `link` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 16. AUDIT LOGS (ENTERPRISE ACTION TRACKING)
-- --------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(60) NOT NULL,
  `entity_id` INT NULL,
  `old_value` JSON NULL,
  `new_value` JSON NULL,
  `ip_address` VARCHAR(50) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_audit_user` (`user_id`),
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 17. CUSTOMER ADDRESSES
-- --------------------------------------------------------
CREATE TABLE `customer_addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `address_line1` VARCHAR(255) NOT NULL,
  `address_line2` VARCHAR(255) NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 18. EXPENSE RECORDS
-- --------------------------------------------------------
CREATE TABLE `expense_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(80) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `notes` TEXT NULL,
  `recorded_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 19. FEEDBACK & REVIEWS
-- --------------------------------------------------------
CREATE TABLE `feedback_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NULL,
  `customer_id` INT NULL,
  `customer_name` VARCHAR(120) NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NULL,
  `is_public` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================================
-- SEED DATA - REALISTIC, RICH RESTAURANT DATASET
-- ========================================================

-- Settings
INSERT INTO `restaurant_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('restaurant_name', 'Gourmet Bistro & Grill', 'general', 'Official trading name'),
('restaurant_email', 'contact@gourmetbistro.com', 'general', 'Contact email'),
('restaurant_phone', '+1 (555) 234-5678', 'general', 'Customer care phone'),
('restaurant_address', '742 Evergreen Terrace, Downtown Food District, NY', 'general', 'Physical address'),
('currency_symbol', '$', 'financial', 'Display currency symbol'),
('currency_code', 'USD', 'financial', 'ISO Currency code'),
('tax_rate_percent', '8.50', 'financial', 'Standard sales tax percent'),
('service_charge_percent', '5.00', 'financial', 'Default service charge percent'),
('auto_inventory_deduction', '1', 'system', 'Automatically deduct recipe ingredients upon order creation'),
('kitchen_auto_refresh_seconds', '5', 'system', 'KDS live poll interval in seconds'),
('opening_time', '11:00:00', 'operations', 'Opening hour'),
('closing_time', '23:00:00', 'operations', 'Closing hour');

-- Users (All with known passwords verified via password_verify)
-- Passwords:
-- admin@restaurant.com: Admin@123
-- manager@restaurant.com: Manager@123
-- waiter@restaurant.com: Waiter@123
-- kitchen@restaurant.com: Kitchen@123
-- cashier@restaurant.com: Cashier@123
-- customer@restaurant.com: Customer@123
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `status`) VALUES
(1, 'Alexandre Moreau (Admin)', 'admin@restaurant.com', '+1 555-0101', '$2y$10$gViyILbfTh5mgyULc0eyneDThFkce.eJYu9mLjaJlLChkGF0t3Ftq', 'admin', 'active'),
(2, 'Sophia Martinez (Manager)', 'manager@restaurant.com', '+1 555-0102', '$2y$10$.DSsOa5ZtRcDcA6fW1aXJOCYj/oVH3b4jlxkS9uCe4O168QdIleN2', 'manager', 'active'),
(3, 'Marco Rossi (Head Waiter)', 'waiter@restaurant.com', '+1 555-0103', '$2y$10$dP/7nzl/v0caEc.sut677OA79HU4zASfiojyyUgCTCHAnMjspgKom', 'waiter', 'active'),
(4, 'Chef Gordon Vance (Kitchen)', 'kitchen@restaurant.com', '+1 555-0104', '$2y$10$hrDbXYb3.EurN5WABj4KCO2T877fBX9crP.ffqgaC.ygPKrNAgLku', 'kitchen', 'active'),
(5, 'Elena Rostova (Cashier)', 'cashier@restaurant.com', '+1 555-0105', '$2y$10$hqqIx7lp48OebxX8Qapj7OwapL9MNMQi0DwuxXRsrSOeztVgiyipK', 'cashier', 'active'),
(6, 'David Sterling (Customer)', 'customer@restaurant.com', '+1 555-0106', '$2y$10$AsIOH8EZT1UL89yLT6dMieMcd1//u7VHfLD/dS4fglEWoM8gdkXqG', 'customer', 'active');

-- Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `display_order`, `is_active`) VALUES
(1, 'Starters & Bites', 'starters', 'Crispy appetizers, tapas and small plates to begin your journey', 'fa-seedling', 1, 1),
(2, 'Gourmet Burgers', 'burgers', 'Brioche buns, prime cuts, house-crafted sauces and crispy fries', 'fa-burger', 2, 1),
(3, 'Wood-Fired Pizza', 'pizza', 'Stone-baked artisan dough with certified San Marzano tomatoes', 'fa-pizza-slice', 3, 1),
(4, 'Main Courses & Steaks', 'mains', 'Hand-cut aged steaks, ocean fresh seafood and poultry', 'fa-drumstick-bite', 4, 1),
(5, 'Artisan Pastas', 'pasta', 'Handmade egg pastas and slow-simmered regional sauces', 'fa-bowl-rice', 5, 1),
(6, 'Desserts & Sweets', 'desserts', 'Decadent hand-crafted pastries, gelato and cakes', 'fa-ice-cream', 6, 1),
(7, 'Signature Drinks', 'beverages', 'Craft mocktails, cold brews, artisanal sodas and hot drinks', 'fa-martini-glass-citrus', 7, 1);

-- Raw Ingredients (Stock Inventory)
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `reorder_level`, `unit_cost`) VALUES
(1, 'Prime Ground Beef', 'g', 15400.00, 3000.00, 0.02),
(2, 'Artisan Brioche Buns', 'pcs', 120.00, 30.00, 0.65),
(3, 'Aged Cheddar Cheese', 'slices', 180.00, 40.00, 0.35),
(4, 'Fresh Truffle Aioli', 'ml', 3200.00, 500.00, 0.04),
(5, 'Artisan Pizza Dough Ball', 'pcs', 65.00, 20.00, 1.10),
(6, 'San Marzano Tomato Sauce', 'ml', 12000.00, 2000.00, 0.01),
(7, 'Fior di Latte Mozzarella', 'g', 8500.00, 1500.00, 0.02),
(8, 'Fresh Basil Leaves', 'g', 450.00, 100.00, 0.05),
(9, 'Spicy Pepperoni Slices', 'g', 4200.00, 800.00, 0.03),
(10, 'Fresh Atlantic Salmon Fillet', 'g', 6800.00, 1200.00, 0.04),
(11, 'Carnaroli Risotto Rice', 'g', 9000.00, 2000.00, 0.01),
(12, 'Porcini & Wild Mushrooms', 'g', 3100.00, 600.00, 0.03),
(13, 'Parmigiano-Reggiano', 'g', 5500.00, 1000.00, 0.03),
(14, 'Italian Mascarpone Cream', 'g', 3800.00, 800.00, 0.02),
(15, 'Savoiardi Ladyfingers', 'pcs', 150.00, 40.00, 0.20),
(16, 'Espresso Roast Beans', 'g', 4200.00, 1000.00, 0.03),
(17, 'Fresh Calamari Tubes', 'g', 4500.00, 1000.00, 0.03),
(18, 'Organic Japanese Matcha', 'g', 180.00, 250.00, 0.15), -- Low stock alert example!
(19, 'Whole Farm Milk', 'ml', 4500.00, 5000.00, 0.002), -- Low stock alert example!
(20, 'Passion Fruit Puree', 'ml', 2400.00, 500.00, 0.03);

-- Menu Items
INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `cost_price`, `image`, `is_vegetarian`, `is_vegan`, `is_gluten_free`, `preparation_time`, `calories`, `is_available`) VALUES
(1, 1, 'Crispy Salt & Pepper Calamari', 'Tender calamari dusted in seasoned flour, flash-fried, served with garlic saffron aioli and lemon wedges.', 14.50, 4.20, 'calamari.jpg', 0, 0, 0, 10, 420, 1),
(2, 1, 'Bruschetta al Pomodoro', 'Grilled artisanal sourdough rubbed with garlic, topped with heirloom tomatoes, basil, and aged balsamic glaze.', 11.00, 2.50, 'bruschetta.jpg', 1, 1, 0, 8, 280, 1),
(3, 2, 'Black Truffle Gourmet Burger', 'Prime 200g beef patty, melted aged cheddar, caramelized onion relish, crispy bacon, and truffle garlic aioli in brioche.', 19.50, 5.80, 'truffle_burger.jpg', 0, 0, 0, 15, 880, 1),
(4, 2, 'Classic Double Cheeseburger', 'Two 100g smashed prime beef patties, double cheddar, house pickles, crisp lettuce and secret burger sauce.', 16.00, 4.50, 'cheeseburger.jpg', 0, 0, 0, 12, 790, 1),
(5, 3, 'Margherita D.O.P', 'San Marzano tomato sauce, fresh Fior di Latte mozzarella, fragrant basil leaves, and extra virgin olive oil.', 15.00, 3.20, 'margherita.jpg', 1, 0, 0, 12, 650, 1),
(6, 3, 'Diavola Spicy Pepperoni', 'Crushed tomato sauce, mozzarella, spicy artisan salami pepperoni, chili flakes, and hot blossom honey drizzle.', 18.50, 4.80, 'pepperoni.jpg', 0, 0, 0, 14, 780, 1),
(7, 4, 'Pan-Seared Atlantic Salmon', 'Crispy skin salmon fillet on a bed of citrus asparagus, pea puree, and lemon dill butter sauce.', 26.50, 8.50, 'salmon.jpg', 0, 0, 1, 18, 590, 1),
(8, 4, 'Prime Angus Ribeye Steak (300g)', '28-day dry aged ribeye steak char-grilled to order, served with rosemary roasted potatoes and peppercorn jus.', 34.00, 12.00, 'ribeye.jpg', 0, 0, 1, 20, 920, 1),
(9, 5, 'Wild Forest Mushroom Risotto', 'Slow-cooked Carnaroli rice, sautéed porcini mushrooms, white truffle oil, and 24-month Parmigiano-Reggiano.', 21.00, 5.00, 'risotto.jpg', 1, 0, 1, 16, 520, 1),
(10, 6, 'Tiramisu Classico Tradizionale', 'Coffee-soaked Savoiardi ladyfingers layered with velvety mascarpone zabaglione and dusted with dark cocoa.', 9.50, 2.30, 'tiramisu.jpg', 1, 0, 0, 5, 410, 1),
(11, 7, 'Tropical Passion Fruit Spritz', 'Sparkling mocktail with passion fruit puree, mint, lime juice, and soda water garnished with dehydrated citrus.', 7.00, 1.20, 'passion_spritz.jpg', 1, 1, 1, 4, 140, 1),
(12, 7, 'Iced Ceremonial Matcha Latte', 'First-harvest Uji matcha whisked with warm water, sweetened with organic agave and layered over cold milk.', 6.50, 1.60, 'matcha.jpg', 1, 0, 1, 4, 180, 1);

-- Menu Ingredients (Recipe BOM for Automatic Inventory Calculation)
-- Item 3: Black Truffle Burger
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(3, 1, 200.00), -- 200g Ground Beef
(3, 2, 1.00),   -- 1 Brioche Bun
(3, 3, 2.00),   -- 2 slices Cheddar
(3, 4, 25.00);  -- 25ml Truffle Aioli

-- Item 4: Classic Double Cheeseburger
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(4, 1, 200.00), -- 200g Ground Beef
(4, 2, 1.00),   -- 1 Brioche Bun
(4, 3, 2.00);   -- 2 slices Cheddar

-- Item 5: Margherita Pizza
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(5, 5, 1.00),   -- 1 Pizza Dough Ball
(5, 6, 80.00),  -- 80ml Tomato Sauce
(5, 7, 120.00), -- 120g Mozzarella
(5, 8, 10.00);  -- 10g Basil

-- Item 6: Diavola Pepperoni Pizza
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(6, 5, 1.00),   -- 1 Pizza Dough Ball
(6, 6, 80.00),  -- 80ml Tomato Sauce
(6, 7, 120.00), -- 120g Mozzarella
(6, 9, 80.00);  -- 80g Pepperoni

-- Item 7: Atlantic Salmon
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(7, 10, 220.00); -- 220g Salmon

-- Item 9: Wild Mushroom Risotto
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(9, 11, 100.00), -- 100g Rice
(9, 12, 80.00),  -- 80g Porcini
(9, 13, 30.00);  -- 30g Parmesan

-- Item 10: Tiramisu
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(10, 14, 90.00), -- 90g Mascarpone
(10, 15, 3.00),  -- 3 ladyfingers
(10, 16, 20.00); -- 20g Espresso beans

-- Item 11: Passion Fruit Spritz
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(11, 20, 45.00); -- 45ml Puree

-- Item 12: Iced Matcha Latte
INSERT INTO `menu_ingredients` (`menu_item_id`, `ingredient_id`, `quantity_required`) VALUES
(12, 18, 5.00),   -- 5g Matcha
(12, 19, 200.00); -- 200ml Milk

-- Dining Tables
INSERT INTO `dining_tables` (`id`, `table_number`, `seating_capacity`, `floor_location`, `status`, `qr_code`) VALUES
(1, 'T-01', 2, 'Ground Floor Main', 'available', 'QR-T01'),
(2, 'T-02', 2, 'Ground Floor Main', 'occupied', 'QR-T02'),
(3, 'T-03', 4, 'Ground Floor Main', 'available', 'QR-T03'),
(4, 'T-04', 4, 'Ground Floor Main', 'occupied', 'QR-T04'),
(5, 'T-05', 6, 'Ground Floor Main', 'available', 'QR-T05'),
(6, 'T-06', 4, 'Outdoor Terrace', 'reserved', 'QR-T06'),
(7, 'T-07', 4, 'Outdoor Terrace', 'available', 'QR-T07'),
(8, 'T-08', 6, 'Outdoor Terrace', 'available', 'QR-T08'),
(9, 'VIP-1', 8, 'VIP Lounge', 'available', 'QR-VIP1'),
(10, 'VIP-2', 10, 'VIP Lounge', 'occupied', 'QR-VIP2'),
(11, 'BAR-1', 2, 'Bar & High Top', 'available', 'QR-BAR1'),
(12, 'BAR-2', 2, 'Bar & High Top', 'available', 'QR-BAR2');

-- Coupons
INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount`, `valid_from`, `valid_to`, `usage_limit`, `times_used`, `is_active`) VALUES
('WELCOME10', 'percentage', 10.00, 20.00, 15.00, '2026-01-01', '2027-12-31', 500, 14, 1),
('FESTIVE20', 'percentage', 20.00, 50.00, 25.00, '2026-01-01', '2027-12-31', 200, 9, 1),
('FLAT5OFF', 'fixed', 5.00, 30.00, 5.00, '2026-01-01', '2027-12-31', 100, 5, 1);

-- Initial Orders (Real-time and historic demo orders)
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `table_id`, `waiter_id`, `order_type`, `status`, `subtotal`, `tax_amount`, `discount_amount`, `final_amount`, `coupon_code`, `notes`, `created_by`, `created_at`) VALUES
(1, 'ORD-2026-1001', 6, 2, 3, 'dine_in', 'preparing', 34.50, 2.93, 0.00, 37.43, NULL, 'Extra crisp on burger bun', 3, DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
(2, 'ORD-2026-1002', 6, 4, 3, 'dine_in', 'ready', 53.00, 4.51, 5.30, 52.21, 'WELCOME10', 'Table 4 requested chili oil on the side', 3, DATE_SUB(NOW(), INTERVAL 40 MINUTE)),
(3, 'ORD-2026-1003', NULL, 10, 3, 'dine_in', 'accepted', 102.50, 8.71, 0.00, 111.21, NULL, 'VIP party celebration', 3, DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
(4, 'ORD-2026-1004', NULL, NULL, 5, 'takeaway', 'completed', 25.50, 2.17, 0.00, 27.67, NULL, 'Pickup at counter', 5, DATE_SUB(NOW(), INTERVAL 90 MINUTE)),
(5, 'ORD-2026-1005', 6, 1, 3, 'dine_in', 'completed', 45.00, 3.83, 0.00, 48.83, NULL, 'Lunch service', 3, DATE_SUB(NOW(), INTERVAL 180 MINUTE));

-- Order Items
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `subtotal`, `special_instructions`, `item_status`) VALUES
-- Order 1: 1 Truffle Burger + 1 Margherita
(1, 3, 1, 19.50, 19.50, 'Medium-rare', 'preparing'),
(1, 5, 1, 15.00, 15.00, 'Extra basil', 'preparing'),
-- Order 2: 1 Salmon + 1 Risotto + 1 Spritz
(2, 7, 1, 26.50, 26.50, 'Crispy skin', 'ready'),
(2, 9, 1, 21.00, 21.00, 'Extra parmesan', 'ready'),
(2, 11, 1, 7.00, 7.00, 'Less ice', 'ready'),
-- Order 3: 2 Ribeye Steaks + 1 Pepperoni + 2 Tiramisu
(3, 8, 2, 34.00, 68.00, 'Medium done', 'pending'),
(3, 6, 1, 18.50, 18.50, 'Extra spicy', 'pending'),
(3, 10, 2, 9.50, 19.00, 'Serve with coffee', 'pending'),
-- Order 4: Takeaway Truffle burger + Calamari
(4, 3, 1, 19.50, 19.50, 'Takeaway pack', 'served'),
(4, 11, 1, 7.00, 7.00, 'No straw', 'served'),
-- Order 5: Completed order
(5, 4, 2, 16.00, 32.00, 'Well done', 'served'),
(5, 12, 2, 6.50, 13.00, 'Oat milk preference', 'served');

-- Order Status History
INSERT INTO `order_status_history` (`order_id`, `status`, `notes`, `changed_by`, `created_at`) VALUES
(1, 'pending', 'Order placed by Waiter Marco', 3, DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
(1, 'accepted', 'Kitchen accepted ticket', 4, DATE_SUB(NOW(), INTERVAL 22 MINUTE)),
(1, 'preparing', 'Chef started grilling', 4, DATE_SUB(NOW(), INTERVAL 18 MINUTE)),
(2, 'pending', 'Order placed by Waiter Marco', 3, DATE_SUB(NOW(), INTERVAL 40 MINUTE)),
(2, 'accepted', 'Kitchen accepted ticket', 4, DATE_SUB(NOW(), INTERVAL 36 MINUTE)),
(2, 'preparing', 'Under active cooking', 4, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 'ready', 'Plated and ready for pickup', 4, DATE_SUB(NOW(), INTERVAL 8 MINUTE)),
(3, 'pending', 'VIP order submitted', 3, DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
(3, 'accepted', 'Chef queued VIP ticket', 4, DATE_SUB(NOW(), INTERVAL 9 MINUTE)),
(4, 'completed', 'Takeaway fulfilled and paid', 5, DATE_SUB(NOW(), INTERVAL 75 MINUTE)),
(5, 'completed', 'Table paid and vacated', 5, DATE_SUB(NOW(), INTERVAL 150 MINUTE));

-- Payments
INSERT INTO `payments` (`invoice_number`, `order_id`, `payment_method`, `amount`, `tax`, `discount`, `tip`, `total_paid`, `payment_status`, `transaction_reference`, `processed_by`, `created_at`) VALUES
('INV-2026-0001', 4, 'credit_card', 25.50, 2.17, 0.00, 3.00, 30.67, 'completed', 'TXN-CC-89412', 5, DATE_SUB(NOW(), INTERVAL 75 MINUTE)),
('INV-2026-0002', 5, 'cash', 45.00, 3.83, 0.00, 5.00, 53.83, 'completed', 'TXN-CASH-1002', 5, DATE_SUB(NOW(), INTERVAL 150 MINUTE));

-- Reservations (With Smart Table Allocation)
INSERT INTO `reservations` (`reservation_code`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `table_id`, `reservation_date`, `reservation_time`, `guest_count`, `special_requests`, `status`, `created_at`) VALUES
('RES-88210', 6, 'David Sterling', 'customer@restaurant.com', '+1 555-0106', 6, CURDATE(), '19:30:00', 4, 'Window or terrace table preferred for anniversary dinner', 'confirmed', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('RES-88211', NULL, 'Dr. Emily Watson', 'emily.w@example.com', '+1 555-0188', 9, CURDATE(), '20:00:00', 8, 'Quiet VIP section for business group', 'confirmed', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
('RES-88212', NULL, 'Liam Hemsworth', 'liam.h@example.com', '+1 555-0199', 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', 3, 'High chair needed for toddler', 'pending', DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Low Stock Notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`) VALUES
(NULL, 'Low Stock Alert: Organic Matcha', 'Organic Japanese Matcha stock has dropped to 180g (Reorder point: 250g). Please place a supplier order.', 'low_stock', 0, 'inventory.php', NOW()),
(NULL, 'Low Stock Alert: Whole Farm Milk', 'Whole Farm Milk stock is at 4,500ml (Reorder point: 5,000ml).', 'low_stock', 0, 'inventory.php', NOW()),
(NULL, 'New VIP Reservation', 'Reservation RES-88211 confirmed for Dr. Emily Watson (8 guests, VIP-1).', 'reservation', 1, 'reservations.php', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(NULL, 'New Order Placed', 'Order ORD-2026-1003 placed for VIP Table 2 by Marco Rossi.', 'order', 0, 'orders.php', DATE_SUB(NOW(), INTERVAL 12 MINUTE));

-- Audit Logs
INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'UPDATE_MENU_PRICE', 'menu_items', 3, '{"price": 18.50}', '{"price": 19.50}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 'CREATE_COUPON', 'coupons', 1, NULL, '{"code": "WELCOME10", "discount": "10%"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'STOCK_ADJUSTMENT', 'ingredients', 1, '{"current_stock": 10000}', '{"current_stock": 15400}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'ORDER_CREATED', 'orders', 1, NULL, '{"order_number": "ORD-2026-1001", "table_id": 2}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
(4, 'KITCHEN_STATUS_UPDATE', 'orders', 1, '{"status": "accepted"}', '{"status": "preparing"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(NOW(), INTERVAL 18 MINUTE));

-- Feedback Reviews
INSERT INTO `feedback_reviews` (`order_id`, `customer_id`, `customer_name`, `rating`, `comment`, `is_public`) VALUES
(4, NULL, 'Jessica Taylor', 5, 'The Truffle Burger was cooked to absolute perfection! Packaging kept it warm and fresh.', 1),
(5, 6, 'David Sterling', 5, 'Outstanding service from Marco and the iced matcha latte was genuinely authentic.', 1);
