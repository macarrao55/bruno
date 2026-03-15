# MEGA LANCHES ERP (MVP OPERACIONAL)

Sistema PHP 8 + MySQL (PDO, MVC simples) pronto para XAMPP com fluxo real:
1) login → 2) abertura de caixa → 3) venda PDV → 4) pedido/itens/adicionais
5) baixa automática de estoque por ficha técnica → 6) financeiro/caixa/crediário
7) pontos de fidelidade → 8) relatórios e DRE.

## Instalação no XAMPP
1. Copie para `C:\xampp\htdocs\bruno`.
2. Inicie Apache/MySQL.
3. Importe:
   - `database/schema.sql`
   - `database/seed.sql`
4. Ajuste `config/database.php`.
5. Acesse `http://localhost/bruno/public`.

## Login inicial
- Email: `admin@megalanches.com`
- Senha: `admin123`

## Regras implementadas
- Login seguro com `password_hash` e `password_verify`.
- Só vende com caixa aberto.
- Pagamento crediário gera conta a receber.
- Venda normal gera lançamento financeiro e movimento de caixa.
- Produto com ficha técnica baixa estoque automaticamente.
- DRE usa receita, descontos, CMV e despesas reais.
