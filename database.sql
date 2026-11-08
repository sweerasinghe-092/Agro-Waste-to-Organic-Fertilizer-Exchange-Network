-- Create database
CREATE DATABASE IF NOT EXISTS agro_exchange_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agro_exchange_db;

-- Districts table
CREATE TABLE IF NOT EXISTS districts (
  district_id INT AUTO_INCREMENT PRIMARY KEY,
  district_name VARCHAR(100) NOT NULL UNIQUE,
  province VARCHAR(100) NOT NULL
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  role ENUM('provider', 'producer', 'buyer', 'admin') NOT NULL DEFAULT 'buyer',
  district_id INT NULL,
  address TEXT,
  business_name VARCHAR(150),
  profile_image VARCHAR(255),
  status ENUM('pending', 'active', 'suspended') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (district_id) REFERENCES districts(district_id) ON DELETE SET NULL
);

-- Waste categories table
CREATE TABLE IF NOT EXISTS waste_categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT
);

-- Waste listings table
CREATE TABLE IF NOT EXISTS waste_listings (
  listing_id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  category_id INT NOT NULL,
  district_id INT NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT,
  quantity_available DECIMAL(10,2) NOT NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'kg',
  price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
  available_from DATE,
  available_until DATE,
  pickup_location VARCHAR(255),
  image VARCHAR(255),
  status ENUM('available', 'reserved', 'completed', 'removed') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (provider_id) REFERENCES users(user_id),
  FOREIGN KEY (category_id) REFERENCES waste_categories(category_id),
  FOREIGN KEY (district_id) REFERENCES districts(district_id) ON DELETE SET NULL
);

-- Exchange requests table
CREATE TABLE IF NOT EXISTS exchange_requests (
  request_id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  producer_id INT NOT NULL,
  quantity_requested DECIMAL(10,2) NOT NULL,
  message TEXT,
  pickup_date DATE,
  status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES waste_listings(listing_id),
  FOREIGN KEY (producer_id) REFERENCES users(user_id)
);

-- Fertilizer products table
CREATE TABLE IF NOT EXISTS fertilizer_products (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  producer_id INT NOT NULL,
  product_name VARCHAR(160) NOT NULL,
  fertilizer_type VARCHAR(80) NOT NULL,
  description TEXT,
  source_materials TEXT,
  usage_instructions TEXT,
  price DECIMAL(10,2) NOT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'kg',
  stock_quantity INT NOT NULL DEFAULT 0,
  district_id INT NULL,
  image VARCHAR(255),
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producer_id) REFERENCES users(user_id),
  FOREIGN KEY (district_id) REFERENCES districts(district_id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total_amount DECIMAL(10,2) NOT NULL,
  delivery_address TEXT NOT NULL,
  payment_method ENUM('cash_on_delivery', 'bank_transfer') NOT NULL,
  order_status ENUM('pending', 'confirmed', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (buyer_id) REFERENCES users(user_id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
  item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id),
  FOREIGN KEY (product_id) REFERENCES fertilizer_products(product_id)
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
  review_id INT AUTO_INCREMENT PRIMARY KEY,
  reviewer_id INT NOT NULL,
  reviewed_user_id INT NOT NULL,
  product_id INT NULL,
  rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reviewer_id) REFERENCES users(user_id),
  FOREIGN KEY (reviewed_user_id) REFERENCES users(user_id),
  FOREIGN KEY (product_id) REFERENCES fertilizer_products(product_id) ON DELETE SET NULL
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  listing_id INT NULL,
  order_id INT NULL,
  message_text TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(user_id),
  FOREIGN KEY (receiver_id) REFERENCES users(user_id),
  FOREIGN KEY (listing_id) REFERENCES waste_listings(listing_id) ON DELETE SET NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(60) NOT NULL,
  content TEXT NOT NULL,
  link VARCHAR(255),
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Learning articles table
CREATE TABLE IF NOT EXISTS learning_articles (
  article_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  content TEXT NOT NULL,
  topic VARCHAR(100),
  author_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Add districts
INSERT IGNORE INTO districts (district_name, province) VALUES
('Colombo', 'Western'), ('Gampaha', 'Western'), ('Kalutara', 'Western'),
('Kandy', 'Central'), ('Matale', 'Central'), ('Nuwara Eliya', 'Central'),
('Galle', 'Southern'), ('Matara', 'Southern'), ('Hambantota', 'Southern'),
('Jaffna', 'Northern'), ('Vavuniya', 'Northern'), ('Batticaloa', 'Eastern'),
('Ampara', 'Eastern'), ('Trincomalee', 'Eastern'), ('Kurunegala', 'North Western'),
('Puttalam', 'North Western'), ('Anuradhapura', 'North Central'), ('Polonnaruwa', 'North Central'),
('Badulla', 'Uva'), ('Monaragala', 'Uva'), ('Ratnapura', 'Sabaragamuwa'), ('Kegalle', 'Sabaragamuwa');

-- Add waste categories
INSERT IGNORE INTO waste_categories (category_name, description) VALUES
('Crop Residue', 'Rice straw, husks and other crop leftovers'),
('Animal Manure', 'Cattle, poultry and other animal manure'),
('Kitchen and Market Waste', 'Fruit and vegetable scraps'),
('Plantation Waste', 'Tea waste, coconut husks and coir dust'),
('Food Processing Waste', 'Organic leftovers from food processing');

-- Demo admin account
-- Login: admin@agro.com / Admin@123
INSERT INTO users (full_name, email, password_hash, phone, role, district_id, address, status)
VALUES (
  'System Administrator',
  'admin@agro.com',
  '$2y$12$SZwr6z.FuAFdSOasW2iI2OJUPwbNZN4.rEmRLzdHG9Md8PwzojYp6',
  '0000000000',
  'admin',
  1,
  'Colombo',
  'active'
)
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Update for existing database (safe to run again)
ALTER TABLE users
  MODIFY status ENUM('pending', 'active', 'suspended') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(60) NOT NULL,
  content TEXT NOT NULL,
  link VARCHAR(255),
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE CASCADE
);