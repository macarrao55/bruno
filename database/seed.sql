USE mega_lanches_erp;

INSERT INTO roles (name, slug) VALUES
('ADMIN', 'admin'),
('GERENTE', 'gerente'),
('CAIXA', 'caixa'),
('COZINHA', 'cozinha'),
('ATENDENTE', 'atendente'),
('FINANCEIRO', 'financeiro');

INSERT INTO permissions (name, slug) VALUES
('Acesso total', '*'),
('Dashboard', 'dashboard.view'),
('PDV', 'pdv.use'),
('Pedidos', 'orders.manage'),
('Produtos', 'products.manage'),
('Clientes', 'customers.manage'),
('Caixa', 'cash.manage'),
('Financeiro', 'finance.manage'),
('Estoque', 'stock.manage'),
('Relatórios', 'reports.view'),
('Configurações', 'settings.manage');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE (r.slug='admin' AND p.slug='*')
   OR (r.slug='gerente' AND p.slug IN ('dashboard.view','orders.manage','products.manage','customers.manage','cash.manage','finance.manage','stock.manage','reports.view'))
   OR (r.slug='caixa' AND p.slug IN ('pdv.use','cash.manage','customers.manage'))
   OR (r.slug='cozinha' AND p.slug IN ('orders.manage'))
   OR (r.slug='atendente' AND p.slug IN ('orders.manage','customers.manage','products.manage'))
   OR (r.slug='financeiro' AND p.slug IN ('finance.manage','reports.view'));

INSERT INTO users (role_id, name, email, password, active) VALUES
((SELECT id FROM roles WHERE slug='admin'), 'Administrador', 'admin@megalanches.com', '$2y$12$F4uSNTmVZ2NhQR1TG4Dlee6eW3tBIXEVfSnfKpT4xBSF8JLqZi8Ma', 1);

INSERT INTO product_categories (name) VALUES
('Bebidas'), ('Lanches gourmet'), ('Lanches tradicionais'), ('Batatas'), ('Porções'), ('Açaí');

INSERT INTO addon_groups (name, min_select, max_select) VALUES ('Complementos',0,6), ('Açaí toppings',0,8);
INSERT INTO product_addons (group_id,name,price) VALUES
(1,'bacon',4.00),(1,'queijo extra',3.50),(1,'ovo',2.50),(1,'catupiry',3.50),(1,'cheddar',3.50),
(2,'granola',2.00),(2,'banana',2.50),(2,'morango',3.00),(2,'leite condensado',2.00),(2,'paçoca',2.00);

INSERT INTO products (category_id, code, name, description, sale_price, cost_price, margin_percent, active, stock_control, allow_addons) VALUES
((SELECT id FROM product_categories WHERE name='Lanches gourmet'),'LG001','Mega Smash Burger','Pão brioche, blend 150g, cheddar',28.90,12.50,131.2,1,1,1),
((SELECT id FROM product_categories WHERE name='Lanches tradicionais'),'LT001','X-Burger Tradicional','Pão, hambúrguer, queijo',18.90,8.20,130.4,1,1,1),
((SELECT id FROM product_categories WHERE name='Batatas'),'BT001','Batata Mega','Batata frita crocante',21.00,7.00,200.0,1,1,1),
((SELECT id FROM product_categories WHERE name='Bebidas'),'BB001','Refrigerante Lata','350ml gelado',6.00,3.20,87.5,1,1,0),
((SELECT id FROM product_categories WHERE name='Açaí'),'AC001','Açaí 500ml','Açaí cremoso 500ml',19.90,8.50,134.1,1,1,1);

INSERT INTO product_addon_links (product_id, addon_group_id)
SELECT p.id, 1 FROM products p WHERE p.allow_addons=1 AND p.category_id IN (SELECT id FROM product_categories WHERE name LIKE 'Lanches%');
INSERT INTO product_addon_links (product_id, addon_group_id)
SELECT p.id, 2 FROM products p WHERE p.category_id IN (SELECT id FROM product_categories WHERE name='Açaí');

INSERT INTO customers (name, phone_main, birth_date, notes, total_spent, orders_count) VALUES
('João Silva', '11988887777', '1992-03-10', 'Cliente frequente', 260.00, 9),
('Maria Souza', '11977776666', '1989-09-21', 'Prefere sem cebola', 420.00, 14),
('Carlos Lima', '11966665555', '1996-11-02', 'Delivery bairro Centro', 130.00, 5);

INSERT INTO customer_addresses (customer_id, street, number, neighborhood, city, reference_point, is_default) VALUES
(1,'Rua A','100','Centro','São Paulo','Próximo à praça',1),
(2,'Rua B','45','Jardins','São Paulo','Ao lado da farmácia',1),
(3,'Rua C','200','Vila Nova','São Paulo','Portão azul',1);

INSERT INTO stock_items (name, category, unit, current_stock, min_stock, average_cost) VALUES
('Pão brioche', 'Padaria', 'un', 120, 30, 1.20),
('Blend bovino 150g', 'Carnes', 'un', 80, 20, 4.50),
('Queijo cheddar', 'Laticínios', 'kg', 7, 2, 28.00),
('Batata palito', 'Congelados', 'kg', 30, 8, 12.00),
('Base açaí', 'Açaí', 'kg', 25, 5, 16.00);

INSERT INTO product_recipes (product_id, stock_item_id, quantity_used) VALUES
((SELECT id FROM products WHERE code='LG001'), (SELECT id FROM stock_items WHERE name='Pão brioche'), 1),
((SELECT id FROM products WHERE code='LG001'), (SELECT id FROM stock_items WHERE name='Blend bovino 150g'), 1),
((SELECT id FROM products WHERE code='LG001'), (SELECT id FROM stock_items WHERE name='Queijo cheddar'), 0.040),
((SELECT id FROM products WHERE code='BT001'), (SELECT id FROM stock_items WHERE name='Batata palito'), 0.250),
((SELECT id FROM products WHERE code='AC001'), (SELECT id FROM stock_items WHERE name='Base açaí'), 0.450);

INSERT INTO delivery_zones (neighborhood, delivery_fee, estimated_minutes) VALUES
('Centro',5.00,30),('Jardins',7.00,40),('Vila Nova',6.50,35),('Industrial',9.00,50);

INSERT INTO coupons (code, discount_type, discount_value, min_order_value, starts_at, ends_at, usage_limit, active)
VALUES ('MEGA10','percentual',10,30,NOW(),DATE_ADD(NOW(), INTERVAL 90 DAY),1000,1);

INSERT INTO marketing_campaigns (name, campaign_type, message_template, status, start_date, end_date, created_by)
VALUES ('Aniversariantes do mês','aniversario','Feliz aniversário! Cupom especial pra você 🎉','ativa',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 30 DAY),1);

INSERT INTO system_settings (setting_key, setting_value) VALUES
('company_name','MEGA LANCHES ERP'),('company_cnpj','00.000.000/0001-00'),('company_phone','(11) 99999-9999'),
('company_whatsapp','(11) 99999-9999'),('company_instagram','@megalanches'),('print_width_mm','80'),
('loyalty_points_per_real','1'),('loyalty_points_expiration_days','365'),('default_order_statuses','novo,em preparo,pronto,saiu para entrega,entregue,cancelado'),
('pdv_shortcuts','F2 finalizar,F4 buscar,F8 desconto'),('multiunit_enabled','0');

INSERT INTO cash_registers (operator_id, opened_at, opening_amount, opening_notes, status)
VALUES (1, NOW(), 150.00, 'Abertura inicial', 'aberto');

INSERT INTO audit_logs (user_id, action, entity, entity_id, ip_address)
VALUES (1,'login','users',1,'127.0.0.1');
