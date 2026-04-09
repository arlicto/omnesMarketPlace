-- create database
CREATE DATABASE IF NOT EXISTS omnes_marketplace;
USE omnes_marketplace;

-- users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(120) NOT NULL,
  role ENUM('buyer','seller','admin') DEFAULT 'buyer'
);

-- products table
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255),
  images TEXT,
  video VARCHAR(255),
  is_sold TINYINT(1) DEFAULT 0,
  category VARCHAR(100),
  sale_type ENUM('buy_now','auction','negotiation') DEFAULT 'buy_now'
);

-- for old databases, add new media columns if missing
ALTER TABLE products ADD COLUMN IF NOT EXISTS images TEXT;
ALTER TABLE products ADD COLUMN IF NOT EXISTS video VARCHAR(255);
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_sold TINYINT(1) DEFAULT 0;

-- cart table
CREATE TABLE IF NOT EXISTS cart (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL
);

-- orders table (one product can have one final order)
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL UNIQUE,
  final_price DECIMAL(10,2),
  negotiation_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- negotiations table
CREATE TABLE IF NOT EXISTS negotiations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  offer_price FLOAT NOT NULL,
  status ENUM('pending','accepted','rejected','countered') DEFAULT 'pending',
  round INT NOT NULL DEFAULT 1
);

-- negotiation chat/messages table
CREATE TABLE IF NOT EXISTS negotiation_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  negotiation_id INT NOT NULL,
  sender_role VARCHAR(20) NOT NULL,
  message TEXT,
  offer_price FLOAT,
  action VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- sample users
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin User', 'admin@omnes.com', '123456', 'admin'),
('Buyer User', 'buyer@omnes.com', '123456', 'buyer'),
('Seller User', 'seller@omnes.com', '123456', 'seller');

-- sample products
INSERT IGNORE INTO products (name, description, price, image, images, video, category, sale_type) VALUES
('Blue T-Shirt', 'Simple blue cotton t-shirt', 15.99, 'https://via.placeholder.com/300x200?text=Blue+T-Shirt', 'https://via.placeholder.com/300x200?text=Blue+T-Shirt,https://via.placeholder.com/300x200?text=Blue+T-Shirt+Side', '', 'Fashion', 'buy_now'),
('Running Shoes', 'Comfortable running shoes', 45.50, 'https://via.placeholder.com/300x200?text=Running+Shoes', 'https://via.placeholder.com/300x200?text=Running+Shoes,https://via.placeholder.com/300x200?text=Running+Shoes+Top', '', 'Footwear', 'buy_now'),
('Backpack', 'School and travel backpack', 22.00, 'https://via.placeholder.com/300x200?text=Backpack', 'https://via.placeholder.com/300x200?text=Backpack,https://via.placeholder.com/300x200?text=Backpack+Back', '', 'Bags', 'negotiation');
