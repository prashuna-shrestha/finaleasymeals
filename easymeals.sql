-- Create the database and use it
CREATE DATABASE easymeals;
USE easymeals;

-- Branches table
CREATE TABLE branches (
    branch_id INT AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(255) NOT NULL UNIQUE
);

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    branch_id INT,
    role ENUM('user', 'seller', 'admin') NOT NULL DEFAULT 'user',
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id)
);

ALTER TABLE users  
ADD COLUMN full_name VARCHAR(255) NOT NULL AFTER user_id;

ALTER TABLE users
ADD COLUMN profile_picture VARCHAR(255);
select * from users;

ALTER TABLE users
MODIFY COLUMN role ENUM('user', 'seller', 'admin', 'superadmin') NOT NULL DEFAULT 'user';
  -- Add the approval_status field to the users table
ALTER TABLE users
ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER role; 
 -- Update approval_status for all sellers and admins to 'pending'
 
 
 
 -- admin details
 CREATE TABLE admin_details (
    admin_id INT PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    contact VARCHAR(15) NOT NULL,
    branch_id INT NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE
);

-- Rename the column from first_name to full_name
ALTER TABLE admin_details CHANGE COLUMN first_name full_name VARCHAR(255);
 select * from admin_details;
 
 
 
-- Categories table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    branch_id INT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id)
);
 CREATE TABLE offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    offer_name VARCHAR(255) NOT NULL,
    branch_id INT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id)
);
select * from offers;
-- Inventory table
CREATE TABLE inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    quantity VARCHAR(255) NOT NULL,
    branch_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id)
);
ALTER TABLE inventory ADD max_quantity INT NOT NULL DEFAULT 0;


-- Products table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    branch_id INT NOT NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id)
);
ALTER TABLE products 
ADD COLUMN total_ratings INT NOT NULL DEFAULT 0, 
ADD COLUMN average_rating DECIMAL(3,2) NOT NULL DEFAULT 0.0;

-- inserting data
UPDATE products
SET total_ratings = 5, average_rating = 3.8
WHERE product_id = 79;

UPDATE products
SET total_ratings = 8, average_rating = 4.2
WHERE product_id = 80;
UPDATE products
SET total_ratings = 5, average_rating = 3.8
WHERE product_id = 81;


select * from products;
delete from products where branch_id=1;

-- Queries to display the content of each table
SELECT * FROM branches;
SELECT * FROM users;
SELECT * FROM categories;
SELECT * FROM inventory;
SELECT * FROM products;

-- delete  from products where product_id=132;

CREATE TABLE subscriptions (
    subscription_id INT PRIMARY KEY AUTO_INCREMENT,
    plan_name VARCHAR(255),
    price DECIMAL(10, 2),
    meals_per_month INT,
    sweets INT DEFAULT 0,
    drinks INT DEFAULT 0,
    created_at TIMESTAMP
);
INSERT INTO subscriptions (plan_name, price, meals_per_month) 
VALUES ('Basic', 4000, 20);
INSERT INTO subscriptions (plan_name, price, meals_per_month) 
VALUES ('Premium', 5000, 29);
INSERT INTO subscriptions (plan_name, price, meals_per_month, sweets, drinks) 
VALUES ('Custom', 6000, 35, 2, 3);


CREATE TABLE user_subscriptions (
user_subscriptions_id int primary key auto_increment,
    user_id INT,
    subscription_id INT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive', 'expired'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id)
);
select * from user_subscriptions;
select * from subscriptions;


-- order
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  branch_id INT NOT NULL,
  order_time DATETIME NOT NULL,  -- Time when the order was placed by the user
  bill_number VARCHAR(255) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  total_order INT NOT NULL,
  loyalty_points DECIMAL(10,2) NOT NULL,
  remaining_after_loyalty DECIMAL(10,2) NOT NULL,
  subscription_credit_used DECIMAL(10,2) NOT NULL,
  final_total DECIMAL(10,2) NOT NULL,
  delivery_date_time DATETIME,  -- Time for delivery, can be NULL if not selected
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (branch_id) REFERENCES branches(branch_id)
);
alter table orders
  add column `items` JSON;
select * from orders;
-- for bar diagram
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,  -- References orders table
  product_id INT NOT NULL,  -- References products table
  quantity INT NOT NULL,
  price_per_unit DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ,
  FOREIGN KEY (product_id) REFERENCES products(product_id) 
);
select *from order_items;

CREATE TABLE ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    UNIQUE KEY unique_user_product (user_id, product_id)
);
select * from ratings;
insert into ratings


-- payment
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL, -- e.g., Khalti, Cash, etc.
    transaction_id VARCHAR(255), -- Unique transaction ID from payment gateway
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id)
);
SELECT * FROM payments;

