-- Seed Roles
INSERT OR IGNORE INTO roles (id, name, slug) VALUES (1, 'Administrator', 'admin');
INSERT OR IGNORE INTO roles (id, name, slug) VALUES (2, 'Customer', 'customer');

-- Seed Permissions
INSERT OR IGNORE INTO permissions (id, name, slug) VALUES (1, 'Manage Products', 'manage_products');
INSERT OR IGNORE INTO permissions (id, name, slug) VALUES (2, 'View Orders', 'view_orders');
INSERT OR IGNORE INTO permissions (id, name, slug) VALUES (3, 'Place Order', 'place_order');

-- Assign Permissions to Roles
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 2);
INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 3);

-- Seed Users
INSERT OR IGNORE INTO users (id, name, email, password) VALUES 
(1, 'Admin User', 'admin@shop.com', '$2y$10$p4s5P6w7Q8r9S0t1U2v3e.Jg2h3i4j5k6l7m8n9o0p1q2r3s4t5u6');
INSERT OR IGNORE INTO users (id, name, email, password) VALUES 
(2, 'Alex Johnson', 'customer@shop.com', '$2y$10$p4s5P6w7Q8r9S0t1U2v3e.Jg2h3i4j5k6l7m8n9o0p1q2r3s4t5u6');

-- Assign Roles to Users
INSERT OR IGNORE INTO user_roles (user_id, role_id) VALUES (1, 1);
INSERT OR IGNORE INTO user_roles (user_id, role_id) VALUES (2, 2);

-- Seed Categories
INSERT OR IGNORE INTO categories (id, name, slug, description) VALUES
(1, 'Laptops & Computers', 'laptops-computers', 'High-performance laptops, workstations, and desktop PC components.'),
(2, 'Audio & Sound', 'audio-sound', 'Wireless noise-canceling headphones, studio monitors, and earbuds.'),
(3, 'Wearables', 'wearables', 'Smartwatches, health trackers, and wearable accessories.'),
(4, 'Gaming Gear', 'gaming-gear', 'Mechanical keyboards, precision mice, and gaming headsets.');

-- Seed Products
INSERT OR IGNORE INTO products (id, category_id, name, slug, description, price, stock, image, featured) VALUES
(1, 1, 'Spartan ProBook M3 Max', 'spartan-probook-m3-max', 'Ultimate developer laptop featuring 16-core CPU, 40-core GPU, 64GB RAM, and 2TB SSD.', 2499.99, 15, 'probook.webp', 1),
(2, 1, 'Spartan Air Ultra Slim', 'spartan-air-ultra-slim', 'Ultra-lightweight 13-inch laptop with 24-hour battery life and Liquid Retina display.', 1199.00, 25, 'air_slim.webp', 1),
(3, 2, 'SonicShield Pro Noise Canceling', 'sonicshield-pro-noise-canceling', 'Premium wireless over-ear headphones with active noise cancellation and 40-hour battery life.', 349.50, 40, 'headphones.webp', 1),
(4, 2, 'PulseBuds Pro Wireless Earbuds', 'pulsebuds-pro-wireless-earbuds', 'True wireless earbuds with spatial audio, IPX7 water resistance, and wireless charging case.', 179.99, 60, 'earbuds.webp', 0),
(5, 3, 'Chronos Watch Series X', 'chronos-watch-series-x', 'Advanced smartwatch with ECG monitor, titanium case, GPS tracking, and OLED display.', 429.00, 20, 'smartwatch.webp', 1),
(6, 4, 'CyberStrike RGB Mechanical Keyboard', 'cyberstrike-rgb-mechanical-keyboard', 'Custom mechanical gaming keyboard with hot-swappable tactile switches and per-key RGB.', 149.99, 30, 'keyboard.webp', 1),
(7, 4, 'Vortex Precision Wireless Mouse', 'vortex-precision-wireless-mouse', 'Ultra-lightweight 58g gaming mouse with 26K DPI optical sensor and PTFE feet.', 89.99, 50, 'mouse.webp', 0),
(8, 4, 'AeroSound 7.1 Gaming Headset', 'aerosound-71-gaming-headset', 'Immersive 7.1 surround sound headset with detachable noise-canceling microphone.', 119.50, 35, 'headset.webp', 0);
