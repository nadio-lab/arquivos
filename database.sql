-- ============================================
-- FARMÁCIA PRO - Schema da Base de Dados
-- ============================================

CREATE DATABASE IF NOT EXISTS farmacia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE farmacia_db;

-- Utilizadores / Admin
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin','farmaceutico','atendente','financeiro') DEFAULT 'atendente',
    ativo TINYINT(1) DEFAULT 1,
    foto VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categorias de produtos
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fornecedores
CREATE TABLE fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cnpj VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100),
    endereco TEXT,
    contato VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Produtos / Medicamentos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    categoria_id INT,
    fornecedor_id INT,
    preco_custo DECIMAL(10,2) DEFAULT 0.00,
    preco_venda DECIMAL(10,2) NOT NULL,
    estoque_atual INT DEFAULT 0,
    estoque_minimo INT DEFAULT 10,
    validade DATE,
    lote VARCHAR(50),
    requer_receita TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
);

-- Clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(20),
    email VARCHAR(100),
    endereco TEXT,
    data_nascimento DATE,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vendas
CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INT,
    usuario_id INT NOT NULL,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    desconto DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('dinheiro','cartao_credito','cartao_debito','pix','fiado') DEFAULT 'dinheiro',
    status ENUM('pendente','concluida','cancelada') DEFAULT 'concluida',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Itens da Venda
CREATE TABLE venda_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    desconto DECIMAL(10,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- Compras / Entradas de Estoque
CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) UNIQUE NOT NULL,
    fornecedor_id INT,
    usuario_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pendente','recebida','cancelada') DEFAULT 'pendente',
    data_entrega DATE,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Itens da Compra
CREATE TABLE compra_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compra_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (compra_id) REFERENCES compras(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- Contas a Pagar
CREATE TABLE contas_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(200) NOT NULL,
    fornecedor_id INT,
    valor DECIMAL(10,2) NOT NULL,
    vencimento DATE NOT NULL,
    pago_em DATE,
    status ENUM('pendente','pago','vencido','cancelado') DEFAULT 'pendente',
    categoria VARCHAR(100),
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
);

-- Contas a Receber
CREATE TABLE contas_receber (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(200) NOT NULL,
    cliente_id INT,
    venda_id INT,
    valor DECIMAL(10,2) NOT NULL,
    vencimento DATE NOT NULL,
    recebido_em DATE,
    status ENUM('pendente','recebido','vencido','cancelado') DEFAULT 'pendente',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (venda_id) REFERENCES vendas(id)
);

-- Caixa
CREATE TABLE caixa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('entrada','saida') NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento VARCHAR(50),
    venda_id INT,
    usuario_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venda_id) REFERENCES vendas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Dados Iniciais
INSERT INTO usuarios (nome, email, senha, perfil) VALUES
('Administrador', 'admin@farmacia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- senha: password

INSERT INTO categorias (nome, descricao) VALUES
('Analgésicos', 'Medicamentos para dor'),
('Antibióticos', 'Medicamentos antibacterianos'),
('Anti-inflamatórios', 'Redução de inflamação'),
('Vitaminas e Suplementos', 'Suplementação nutricional'),
('Produtos de Higiene', 'Higiene pessoal'),
('Dermocosméticos', 'Cuidados com a pele'),
('Genéricos', 'Medicamentos genéricos'),
('Manipulados', 'Medicamentos manipulados');

INSERT INTO fornecedores (nome, cnpj, telefone, email, contato) VALUES
('Distribuidora Farma Plus', '12.345.678/0001-90', '+244 923 456 789', 'compras@farmaplus.co.ao', 'Manuel Costa'),
('Labortório Saúde Total', '98.765.432/0001-10', '+244 912 345 678', 'vendas@saudetotal.co.ao', 'Ana Ribeiro');

INSERT INTO produtos (codigo, nome, categoria_id, fornecedor_id, preco_custo, preco_venda, estoque_atual, estoque_minimo, requer_receita) VALUES
('MED001', 'Paracetamol 500mg', 1, 1, 150.00, 300.00, 150, 20, 0),
('MED002', 'Ibuprofeno 400mg', 3, 1, 200.00, 450.00, 80, 15, 0),
('MED003', 'Amoxicilina 500mg', 2, 2, 350.00, 700.00, 60, 10, 1),
('VIT001', 'Vitamina C 1000mg', 4, 1, 180.00, 380.00, 200, 30, 0),
('HIG001', 'Sabonete Antibacteriano', 5, 1, 120.00, 250.00, 100, 20, 0);
