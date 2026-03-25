USE mega_lanches_erp;

INSERT INTO roles (name) VALUES ('ADMIN'),('GERENTE'),('CAIXA'),('COZINHA'),('FINANCEIRO');
INSERT INTO users (role_id,name,email,password,active) VALUES (1,'Administrador','admin@megalanches.com','$2y$12$COJaWGbvv5RZwPfOj65Ye.49B0j3p0R6rF2Y9ceDMMfNbwEr2SExm',1);

INSERT INTO system_settings (setting_key, setting_value) VALUES
('company_name','MEGA LANCHES ERP'),
('company_phone','(11)99999-9999'),
('company_instagram','@megalanches'),
('points_per_real','1'),
('crediario_due_days','30'),
('pix_entra_no_caixa','1');

INSERT INTO categories (name) VALUES
('Bebidas'),('Lanches gourmet'),('Lanches tradicionais'),('Batatas'),('Porções'),('Açaí');

INSERT INTO products (category_id,name,description,price,cost,controls_stock,allows_addons,active) VALUES
(2,'Mega Smash','Pão brioche, carne e cheddar',29.90,12.00,1,1,1),
(3,'X-Burger','Tradicional',18.90,8.00,1,1,1),
(4,'Batata P','Porção individual',14.90,5.50,1,1,1),
(1,'Refrigerante Lata','350ml',6.00,3.00,1,0,1),
(6,'Açaí 500ml','Com complementos',21.90,9.00,1,1,1);

INSERT INTO addon_groups (name) VALUES ('Lanches'),('Açaí');
INSERT INTO addons (addon_group_id,name,price) VALUES
(1,'bacon',4.00),(1,'queijo extra',3.50),(1,'ovo',2.50),(1,'catupiry',3.00),(1,'cheddar',3.00),
(2,'granola',2.00),(2,'banana',2.00),(2,'morango',3.00),(2,'leite condensado',2.00),(2,'paçoca',2.00);
INSERT INTO product_addons (product_id,addon_id) VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),
(2,1),(2,2),(2,3),(2,4),(2,5),
(5,6),(5,7),(5,8),(5,9),(5,10);

INSERT INTO stock_items (name,unit,current_stock,min_stock,average_cost) VALUES
('Pão brioche','un',150,20,1.20),('Carne 150g','un',100,20,4.50),('Cheddar','kg',8,2,26.00),('Batata congelada','kg',30,5,11.00),('Base de açaí','kg',25,5,15.00),('Lata refri','un',300,50,3.00);

INSERT INTO product_recipes (product_id,stock_item_id,quantity_used) VALUES
(1,1,1),(1,2,1),(1,3,0.040),
(2,2,1),
(3,4,0.220),
(4,6,1),
(5,5,0.450);

INSERT INTO customers (name,phone,neighborhood,address,birth_date,total_spent,orders_count) VALUES
('João Silva','11988887777','Centro','Rua A, 100','1992-03-10',0,0),
('Maria Souza','11977776666','Jardins','Rua B, 45','1989-09-21',0,0);
