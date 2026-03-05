# Mega Lanches - Sistema Local (Flask + SQLite)

Sistema completo local para lanchonete/hamburgueria/delivery com módulos: **Dashboard, PDV, Cozinha (KDS), Delivery, Cadastros, Estoque, Financeiro, Relatórios, Configurações e Auditoria**.

## Dados iniciais preenchidos
- Nome: **Mega Lanches**
- Cidade/UF: **Montanha-ES**
- WhatsApp: **(27) 99999-0000** (editável)
- Tema: **Vermelho + Preto + Branco**
- Moeda: **BRL (R$)**
- Impressora: **sem impressora por enquanto**

## Árvore do projeto
```text
bruno/
├─ app.py
├─ schema.sql
├─ seeds.sql
├─ requirements.txt
├─ run_windows.bat
├─ zip_project.bat
├─ README.md
├─ data/
│  └─ mega_lanches.db (gerado automaticamente)
├─ static/
│  ├─ css/style.css
│  └─ js/
│     ├─ app.js
│     └─ pdv.js
└─ templates/
   ├─ base.html
   ├─ login.html
   ├─ dashboard.html
   ├─ pdv.html
   ├─ kitchen.html
   ├─ delivery.html
   ├─ cadastros.html
   ├─ estoque.html
   ├─ financeiro.html
   ├─ relatorios.html
   ├─ configuracoes.html
   └─ auditoria.html
```

## Como rodar no Windows (1 computador)
1. Instale Python 3.10+.
2. Dê duplo clique em `run_windows.bat`.
3. Acesse `http://localhost:8000`.

## Login padrão
- `admin / admin123`
- `caixa / admin123`
- `cozinha / admin123`
- `entregador / admin123`

## Recursos principais
- Login + permissões (Admin/Caixa/Cozinha/Entregador)
- PDV com atalhos: F2, F3, F4, ESC, CTRL+S
- KDS com colunas e alerta sonoro
- Delivery com status e entregador
- Estoque com baixa automática via ficha técnica
- Financeiro com abertura/fechamento de caixa, sangria/suprimento, despesas
- Relatórios + exportação CSV
- Configurações editáveis via tela
- Auditoria de ações

## Banco SQLite
- Criação automática em `data/mega_lanches.db` ao iniciar.
- Schema: `schema.sql`
- Seeds (produtos, categorias, 3 clientes, 3 entregadores): `seeds.sql`

## Gerar ZIP
- Execute `zip_project.bat` no Windows.
- Ou comando PowerShell:
```powershell
Compress-Archive -Path * -DestinationPath mega_lanches.zip -Force
```
