CREATE DATABASE leaf_market;
USE leaf_market;

-- Tabela de usuários (clientes)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de funcionários
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('gerente', 'estoquista', 'funcionario') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de categorias
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Tabela de produtos
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    category_id INT,
    image_url VARCHAR(255),
    status ENUM('disponivel', 'pendente', 'vendido') DEFAULT 'disponivel',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tabela de pedidos
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pendente', 'processando', 'enviado', 'entregue', 'cancelado') DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de itens do pedido
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Tabela do carrinho de compras
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT,
    product_id INT,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Inserir categorias
INSERT INTO categories (name, description) VALUES 
('Frutas', 'Frutas frescas e orgânicas'),
('Verduras', 'Verduras e folhas frescas'),
('Legumes', 'Legumes cultivados sem agrotóxicos'),
('Grãos', 'Grãos e cereais integrais'),
('Laticínios', 'Laticínios orgânicos e naturais');

-- Inserir produtos de exemplo
INSERT INTO products (name, description, price, quantity, category_id, image_url) VALUES 
('Maçã Orgânica', 'Maçãs cultivadas sem agrotóxicos, doces e suculentas.', 5.90, 100, 1, 'apple.jpg'),
('Banana Prata', 'Bananas orgânicas, ricas em potássio e vitaminas.', 4.50, 80, 1, 'banana.jpg'),
('Alface Crespa', 'Alface fresca e crocante, colhida diariamente.', 3.20, 50, 2, 'lettuce.jpg'),
('Brócolis', 'Brócolis fresco, rico em nutrientes e antioxidantes.', 7.80, 30, 3, 'broccoli.jpg'),
('Arroz Integral', 'Arroz integral orgânico, fonte de fibras e minerais.', 12.90, 40, 4, 'rice.jpg'),
('Leite Orgânico', 'Leite proveniente de vacas criadas livremente sem hormônios.', 8.50, 60, 5, 'milk.jpg'),
('Mamão Papaia', 'Mamão doce e maduro, excelente fonte de vitamina C.', 6.90, 45, 1, 'papaya.jpg'),
('Cenoura', 'Cenouras crocantes e doces, cultivadas organicamente.', 4.20, 70, 3, 'carrot.jpg'),
('Queijo Minas', 'Queijo minas frescal artesanal, produzido com leite orgânico.', 18.90, 25, 5, 'cheese.jpg'),
('Tomate', 'Tomates vermelhos e suculentos, perfeitos para saladas.', 5.50, 65, 3, 'tomato.jpg');

-- Inserir um funcionário admin padrão
INSERT INTO employees (name, email, password, role) VALUES 
('Jair', 'bolsonaro@leaf.com', '$2y$10$hbjwdkkTrsaoEqI9ZP462u3HF1LdBSN4/AXuxIExbLOfHb/ydLiIi', 'funcionario'),
('Luigi', 'luigi@leaf.com', '$2y$10$hClxokgzS91E7xRIeODT6uCHz2XRsuv4yOsULrJ8C4L9MMex.qlm6', 'estoquista'),
('Administrador', 'admin@leaf.com', '$2y$10$Ff7ntPGkje.gDpjh4KKJluNQHidLD/sQ/zMbNqqvakdcgsUHTFC7.', 'gerente');