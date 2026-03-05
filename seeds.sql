INSERT INTO users (name, username, password_hash, role, active) VALUES
('Administrador','admin','{{ADMIN_HASH}}','Admin',1),
('Caixa 1','caixa','{{ADMIN_HASH}}','Caixa',1),
('Cozinha 1','cozinha','{{ADMIN_HASH}}','Cozinha',1),
('Entregador App','entregador','{{ADMIN_HASH}}','Entregador',1);

INSERT INTO categories (name) VALUES ('Hambúrgueres'),('Bebidas'),('Porções');

INSERT INTO products (name, category_id, price, cost, active, prep_time, image_url) VALUES
('X-Burguer Mega',1,24.90,10.50,1,15,''),
('X-Salada',1,26.90,11.20,1,16,''),
('Batata Frita M',3,18.00,6.50,1,10,''),
('Refrigerante Lata',2,7.00,3.00,1,1,''),
('Suco Natural',2,9.50,4.20,1,3,'');

INSERT INTO clients (name, phone, address, notes, credit_limit) VALUES
('Carlos Souza','27999990001','Rua A, 100','Prefere sem cebola',150),
('Marina Oliveira','27999990002','Rua B, 200','Condomínio Sol',120),
('João Lima','27999990003','Av. Central, 300','Pagamento em dinheiro',90);

INSERT INTO riders (name, phone, active) VALUES
('Rafael Moto','27988880001',1),
('Bruno Entrega','27988880002',1),
('Diego Rota','27988880003',1);

INSERT INTO neighborhoods (name, delivery_fee, avg_time) VALUES
('Centro',5.00,25),('Vila Nova',7.00,35),('São José',8.50,40);

INSERT INTO ingredients (name, unit, stock_current, stock_min, avg_cost) VALUES
('Pão de Hambúrguer','un',200,50,1.20),
('Hambúrguer 120g','un',180,40,4.50),
('Queijo','g',8000,2000,0.03),
('Batata Congelada','kg',30,8,15.00),
('Refrigerante Lata','un',120,30,3.00);

INSERT INTO product_recipes (product_id, ingredient_id, qty) VALUES
(1,1,1),(1,2,1),(1,3,30),
(2,1,1),(2,2,1),(2,3,30),
(3,4,0.2),
(4,5,1);

INSERT INTO settings (key, value) VALUES
('store_name','Mega Lanches'),
('city','Montanha-ES'),
('whatsapp','(27) 99999-0000'),
('theme_primary','#c1121f'),
('theme_secondary','#111111'),
('theme_light','#ffffff'),
('printer_type','sem impressora por enquanto');
