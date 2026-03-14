CREATE DATABASE IF NOT EXISTS bruno_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bruno_sistema;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nivel ENUM('administrador','gerente','caixa','vendedor','entregador') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  telefone VARCHAR(30),
  rua VARCHAR(120),
  numero VARCHAR(20),
  bairro VARCHAR(100),
  referencia VARCHAR(120),
  cpf VARCHAR(20),
  tipo_cliente ENUM('comum','revendedor') DEFAULT 'comum',
  bloqueado TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  categoria VARCHAR(80),
  preco_venda DECIMAL(10,2) DEFAULT 0,
  preco_revenda DECIMAL(10,2) DEFAULT 0,
  custo DECIMAL(10,2) DEFAULT 0,
  estoque_minimo INT DEFAULT 0,
  estoque_atual INT DEFAULT 0,
  codigo_barras VARCHAR(60),
  codigo_interno VARCHAR(60),
  comissao DECIMAL(5,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fornecedor VARCHAR(120) NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL,
  custo_unitario DECIMAL(10,2) NOT NULL,
  frete DECIMAL(10,2) DEFAULT 0,
  desconto DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

CREATE TABLE vendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NULL,
  usuario_id INT NOT NULL,
  forma_pagamento VARCHAR(20) NOT NULL,
  recebimento_status ENUM('recebido','na_entrega') DEFAULT 'recebido',
  subtotal DECIMAL(10,2) DEFAULT 0,
  desconto_total DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) DEFAULT 0,
  lucro DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE itens_venda (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venda_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  desconto DECIMAL(10,2) DEFAULT 0,
  custo_unitario DECIMAL(10,2) DEFAULT 0,
  validade_galao DATE NULL,
  FOREIGN KEY (venda_id) REFERENCES vendas(id),
  FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

CREATE TABLE contas_receber (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT,
  valor DECIMAL(10,2) NOT NULL,
  vencimento DATE NOT NULL,
  status ENUM('aberto','pago','atrasado') DEFAULT 'aberto',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE contas_pagar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fornecedor VARCHAR(120) NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  vencimento DATE NOT NULL,
  status ENUM('aberto','pago','atrasado') DEFAULT 'aberto',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE despesas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria VARCHAR(120) NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  data DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE entregas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido VARCHAR(60) NOT NULL,
  cliente_id INT,
  entregador_id INT,
  status ENUM('preparando','em rota','entregue','cancelado','atrasado') DEFAULT 'preparando',
  valor_pedido DECIMAL(10,2) DEFAULT 0,
  comissao DECIMAL(10,2) DEFAULT 0,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  started_at DATETIME NULL,
  delivered_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  FOREIGN KEY (entregador_id) REFERENCES usuarios(id)
);

CREATE TABLE movimentos_vasilhames (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT,
  tipo ENUM('galao','botijao') NOT NULL,
  quantidade INT NOT NULL,
  movimento ENUM('emprestado','devolvido') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

INSERT INTO usuarios (nome, email, password_hash, nivel) VALUES
('Administrador', 'admin@sistema.local', '$2y$12$zUD8KOeEdwH.MPKGe7aArO.1Px0l3Aw99LrehWCx3W7zsFPtnYcGO', 'administrador'),
('Gerente', 'gerente@sistema.local', '$2y$12$zUD8KOeEdwH.MPKGe7aArO.1Px0l3Aw99LrehWCx3W7zsFPtnYcGO', 'gerente'),
('Caixa', 'caixa@sistema.local', '$2y$12$zUD8KOeEdwH.MPKGe7aArO.1Px0l3Aw99LrehWCx3W7zsFPtnYcGO', 'caixa'),
('Vendedor', 'vendedor@sistema.local', '$2y$12$zUD8KOeEdwH.MPKGe7aArO.1Px0l3Aw99LrehWCx3W7zsFPtnYcGO', 'vendedor'),
('Entregador', 'entregador@sistema.local', '$2y$12$zUD8KOeEdwH.MPKGe7aArO.1Px0l3Aw99LrehWCx3W7zsFPtnYcGO', 'entregador');
