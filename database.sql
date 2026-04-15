-- ============================================
--  Crave & Order - Full Database Setup
--  Import this in phpMyAdmin or MySQL CLI:
--  mysql -u root -p < database.sql
-- ============================================

CREATE DATABASE IF NOT EXISTS crave_order CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crave_order;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)        NOT NULL,
    email       VARCHAR(150)        NOT NULL UNIQUE,
    mobile      VARCHAR(15)         NOT NULL,
    password    VARCHAR(255)        NOT NULL,
    role        ENUM('user','admin') DEFAULT 'user',
    profile_image VARCHAR(255)      DEFAULT NULL,
    bio         TEXT                DEFAULT NULL,
    created_at  DATETIME            DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLE: products
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)        NOT NULL,
    price       DECIMAL(10,2)       NOT NULL,
    image       VARCHAR(255)        NOT NULL,
    category    VARCHAR(50)         NOT NULL,
    created_at  DATETIME            DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLE: orders
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT             NOT NULL,
    total_amount   DECIMAL(10,2)   NOT NULL,
    payment_method VARCHAR(50)     DEFAULT 'cod',
    status         ENUM('Placed','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Placed',
    created_at     DATETIME        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABLE: order_items
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT             NOT NULL,
    name        VARCHAR(150)    NOT NULL,
    price       DECIMAL(10,2)   NOT NULL,
    quantity    INT             NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABLE: cart  (server-side cart)
-- ============================================
CREATE TABLE IF NOT EXISTS cart (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT             NOT NULL,
    name        VARCHAR(150)    NOT NULL,
    price       DECIMAL(10,2)   NOT NULL,
    quantity    INT             NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABLE: contact_messages
-- ============================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)    NOT NULL,
    email       VARCHAR(150)    NOT NULL,
    message     TEXT            NOT NULL,
    created_at  DATETIME        DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- DEFAULT ADMIN USER
-- Password: admin123  (bcrypt)
-- ============================================
INSERT INTO users (name, email, mobile, password, role)
VALUES (
    'Admin',
    'admin@craveorder.com',
    '9327967988',
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',  -- password: admin123
    'admin'
) ON DUPLICATE KEY UPDATE id=id;

-- ============================================
-- SAMPLE PRODUCTS
-- ============================================
INSERT INTO products (name, price, image, category) VALUES
('Classic Brownie',        120, 'brownies/classic-brownie.jpg',                    'brownie'),
('Choco Chip Brownie',     140, 'brownies/choco-chip-brownie.jpg',                 'brownie'),
('Fudge Chocolate Brownie',150, 'brownies/fudge-chocolate-brownie.jpg',            'brownie'),
('Coffee Mocha Brownie',   130, 'brownies/coffee-mocha-brownie.jpg',               'brownie'),
('Peanut Butter Swirl',    145, 'brownies/peanut-butter-swirl-brownie.jpg',        'brownie'),
('Orange Zest Brownie',    135, 'brownies/orange-zest-chocolate-brownie.jpg',      'brownie'),
('Choco Strawberry Brownie',155,'brownies/chocolate-strawberry-brownie.jpg',       'brownie'),
('Black Forest Cake',      450, 'cakes/black-forest-cake.jpg',                     'cake'),
('Chocolate Truffle Cake', 480, 'cakes/chocolate-truffle-cake.jpg',                'cake'),
('Mango Cake',             420, 'cakes/mango-cake.jpg',                            'cake'),
('Oreo Chocolate Cake',    500, 'cakes/oreo-chocolate-cake.jpg',                   'cake'),
('Red Velvet Cake',        520, 'cakes/red-velvet-cake.jpg',                       'cake'),
('Strawberry Cake',        460, 'cakes/strawberry-cake.jpg',                       'cake'),
('Strawberry Donut',        80, 'donuts/strawberry-donut.jpg',                     'donut'),
('Caramel Donut',           75, 'donuts/caramel-donut.jpg',                        'donut'),
('Red Velvet Donut',        90, 'donuts/red-velvet-donut.jpg',                     'donut'),
('Matcha Donut',            85, 'donuts/macha-donut.jpg',                          'donut'),
('Alcapone Donut',         100, 'donuts/alcapone-donut.jpg',                       'donut'),
('Choco Caviour Donut',     95, 'donuts/choco-caviour-chocolate-donut.jpg',        'donut'),
('Hot Coffee',             120, 'coffee/Hot-Coffee.jpg',                           'coffee'),
('Irish Coffee',           160, 'coffee/Irish Coffee.jpg',                         'coffee'),
('Mint Mojito Iced Coffee', 150,'coffee/mint-mojito-iced-coffee.jpg',              'coffee'),
('Mocha Coffee',           140, 'coffee/mocha-coffee.jpg',                         'coffee'),
('Nutella Coffee',         170, 'coffee/nuttella-coffe.jpg',                       'coffee'),
('Raspberry White Coffee', 165, 'coffee/Raspberry-white-Coffee.jpg',               'coffee'),
('Almond Chocolate',       200, 'chocolates/Almond-chocolate.jpg',                 'chocolate'),
('Hazelnut Chocolate',     220, 'chocolates/hazelnut chocolate.jpg',               'chocolate'),
('Ruby Chocolate',         250, 'chocolates/Ruby Chocolate.jpg',                   'chocolate'),
('Strawberry Chocolate',   230, 'chocolates/strawberry chocolate.jpg',             'chocolate'),
('Truffle Chocolate',      210, 'chocolates/truffle chocolate.jpg',                'chocolate'),
('White Chocolate',        190, 'chocolates/White Chocolate .jpg',                 'chocolate'),
('Blueberry Ice Cream',    180, 'ice cream/Blueberry Ice Cream.jpg',               'ice-cream'),
('Butter Pecan Ice Cream', 170, 'ice cream/butter pecan ice cream.jpg',            'ice-cream'),
('Cookie & Cream Ice Cream',190,'ice cream/Coockie & Cream Ice CReam.jpg',         'ice-cream'),
('Mint Choco Ice Cream',   175, 'ice cream/Mint Chocolate Ice Cream.jpg',          'ice-cream'),
('Raspberry Ice Cream',    185, 'ice cream/Raspberry Ice Cream.jpg',               'ice-cream'),
('Red Velvet Ice Cream',   195, 'ice cream/red-velvet-ice-cream.jpg',              'ice-cream'),
('Garlic Bread',            90, 'breads/Garlic Bread.jpg',                         'bread'),
('Herb Focaccia Bread',    110, 'breads/Herb Focaccia Bread.jpg',                  'bread'),
('Milk Bread',              80, 'breads/Milk Bread.jpg',                           'bread'),
('Multigrain Bread',       120, 'breads/Multigrain Bread.jpg',                     'bread'),
('White Bread',             70, 'breads/White Bread.jpg',                          'bread'),
('Whole Wheat Bread',      100, 'breads/Whole Wheat Bread.jpg',                    'bread'),
('Black Forest Pastry',    160, 'pastries/Black Forest Pastry.jpg',                'pastry'),
('Butterscotch Pastry',    155, 'pastries/Butterscotch Pastry.jpg',                'pastry'),
('Chocolate Cream Pastry', 170, 'pastries/Chocolate Cream Pastry.jpg',             'pastry'),
('Chocolate Truffle Pastry',175,'pastries/Chocolate Truffle Pastry.jpg',           'pastry'),
('Mango Pastry',           145, 'pastries/Mango Pastry.jpg',                       'pastry'),
('Oreo Pastry',            165, 'pastries/Oreo Pastry.jpg',                        'pastry'),
('Pineapple Pastry',       150, 'pastries/Pineapple Pastry.jpg',                   'pastry'),
('Red Velvet Pastry',      160, 'pastries/Red Velvet Pastry.jpg',                  'pastry'),
('Strawberry Pastry',      155, 'pastries/Strawberry Pastry.jpg',                  'pastry'),
('Vanilla Cream Pastry',   140, 'pastries/Vanilla Cream Pastry.jpg',               'pastry');
