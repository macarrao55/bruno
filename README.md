# Sistema Financeiro em PHP

Aplicação simples (sem framework) para controle financeiro com SQLite, cobrindo:

- Dashboard com indicadores e gráfico por período.
- Fluxo de caixa (entradas/saídas) com filtros e saldo acumulado.
- Análise de fluxo de caixa por categoria e subcategoria.
- Contas a pagar/receber.
- Contas a pagar com edição e baixa financeira conciliada.
- Cadastro de fornecedores e formas de pagamento configuráveis.
- Empresas e tipos de conta a pagar configuráveis.
- Controle de cartões e cheques.
- Conciliação bancária.
- DRE gerencial.
- Fechamento de caixa.
- Relatórios.
- Módulo de fornecedores com análise prévia financeira.
- Configurações para bancos/saldos iniciais e categorias/subcategorias do fluxo.

## Requisitos

- PHP 8.1+
- Extensão PDO SQLite habilitada.

## Como executar

```bash
php -S localhost:8080
```

Acesse `http://localhost:8080/index.php`.

## Estrutura

- `index.php`: aplicação principal e roteamento por módulo.
- `db.php`: conexão e inicialização automática do banco.
- `schema.sql`: estrutura das tabelas.
- `style.css`: estilos básicos.

## Observações

- Banco SQLite criado automaticamente em `data/finance.db` na primeira execução.
- Projeto pensado como base inicial para evoluir para autenticação, permissões e API.
