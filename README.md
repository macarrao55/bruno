# MEGA LANCHES ERP

Sistema ERP modular para hamburgueria/lanchonete/açaiteria com PHP 8, MySQL, Bootstrap 5, PDO e MVC simples.

## Estrutura
- `public/` front controller e assets
- `app/` controllers, models, views, core e helpers
- `config/` configurações de app e banco
- `routes/` rotas web
- `database/` schema e seed
- `storage/` logs e backup

## Instalação no XAMPP (localhost)
1. Copie a pasta do projeto para `C:\xampp\htdocs\bruno`.
2. Inicie Apache e MySQL no painel do XAMPP.
3. Crie o banco no MySQL:
   ```sql
   CREATE DATABASE mega_lanches_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Importe o schema:
   - phpMyAdmin > mega_lanches_erp > Importar > `database/schema.sql`.
   - ou terminal:
   ```bash
   mysql -u root -p mega_lanches_erp < database/schema.sql
   ```
5. Importe os dados iniciais:
   ```bash
   mysql -u root -p mega_lanches_erp < database/seed.sql
   ```
6. Configure acesso ao banco em `config/database.php`.
7. Acesse no navegador:
   - `http://localhost/bruno/public`
8. Login inicial:
   - **Usuário:** `admin@megalanches.com`
   - **Senha:** `admin123`

## Backup manual
No painel Backup há comando para dump. Exemplo:
```bash
mysqldump -u root mega_lanches_erp > storage/backup/backup_YYYY-MM-DD.sql
```

## Módulos disponíveis
Autenticação, Dashboard, Produtos, Clientes, PDV, Pedidos, Impressão 80mm, Caixa, Financeiro, DRE, Conciliação, Estoque/Ficha Técnica, Fidelidade, Marketing, Funcionários, Relatórios, Configurações, Auditoria e Backup.
