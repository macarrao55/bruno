# Sistema de Gestão (PHP + MySQL)

Sistema completo (base funcional) para distribuidora/comércio, pronto para rodar no XAMPP.

## Requisitos
- XAMPP (Apache + MySQL + PHP 8.1+)
- Extensão PDO MySQL habilitada

## Instalação rápida
1. Copie esta pasta para `htdocs`.
2. Crie o banco e tabelas:
   - Importe `database/schema.sql` no phpMyAdmin.
3. Ajuste as credenciais em `config/config.php` se necessário.
4. Abra `http://localhost/bruno`.

## Usuário inicial
- **Email:** `admin@sistema.local`
- **Senha:** `123456`

> A senha está armazenada com `password_hash` e pode ser alterada no banco.

## Estrutura
- `config/` conexão e configurações
- `database/` script SQL
- `includes/` autenticação, helpers e layout
- `pages/` módulos do sistema
- `actions/` endpoints de criação/atualização

## Funcionalidades implementadas
- Login por e-mail e senha com hash
- Controle de permissões por nível
- Dashboard com indicadores + gráficos (Chart.js)
- PDV com carrinho lateral, desconto, formas de pagamento, status de recebimento e troco
- Cadastros: clientes e produtos (clientes com editar/excluir/bloquear)
- Compras com atualização de estoque
- Estoque com alerta de reposição e dias para acabar
- Financeiro (receber, pagar, despesas)
- Entregas com status e comissões
- Controle de galões e botijões
- DRE automático
- Relatórios com exportação CSV (Excel) e tela de vendas para reimpressão

## Segurança
- PDO + prepared statements
- Sessão protegida e validação de login
- Escape de saída com `htmlspecialchars`

