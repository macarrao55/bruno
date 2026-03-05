# BoldriniSystem (Desktop MVP)

Sistema desktop Windows (Python + Tkinter + SQLite) para água 20L, gás P13 e mercearia.

## Arquitetura
- `app/ui`: telas Tkinter
- `app/services`: regras de negócio
- `app/db.py`: banco SQLite, schema e migrações
- `app/reports`: geração de recibo PDF 80mm
- `app/utils`: validação/segurança/backup

## Árvore do projeto
```text
.
├── app
│   ├── __init__.py
│   ├── config.py
│   ├── db.py
│   ├── demo_seed.py
│   ├── local_api.py
│   ├── main.py
│   ├── reports
│   │   └── receipt.py
│   ├── services
│   │   ├── __init__.py
│   │   ├── app_state.py
│   │   ├── audit_service.py
│   │   ├── auth_service.py
│   │   ├── backup_service.py
│   │   ├── customer_service.py
│   │   ├── delivery_service.py
│   │   ├── finance_service.py
│   │   ├── pos_service.py
│   │   ├── product_service.py
│   │   ├── report_service.py
│   │   └── settings_service.py
│   ├── ui
│   │   ├── catalog_screen.py
│   │   ├── customers_screen.py
│   │   ├── dashboard_screen.py
│   │   ├── deliveries_screen.py
│   │   ├── finance_screen.py
│   │   ├── login_screen.py
│   │   ├── main_window.py
│   │   ├── pos_screen.py
│   │   └── reports_screen.py
│   └── utils
│       ├── backup.py
│       ├── security.py
│       └── validation.py
├── backups
├── requirements.txt
├── run.bat
└── setup_one_click.bat
```

## Instalação
1. Instale Python 3.11+ no Windows.
2. Abra cmd no projeto.
3. Execute `setup_one_click.bat`.

## Executar
- `run.bat`
- Login inicial: `admin` / `admin123`

## Gerar EXE (PyInstaller)
```bat
.venv\Scripts\activate
pyinstaller --noconfirm --onefile --windowed --name BoldriniSystem app\main.py
```
Saída em `dist\BoldriniSystem.exe`.

## Backup e Restore
- Backup automático no fechamento do app para `backups/`.
- Tentativa de cópia para USB (`D:` em diante) em pasta `BoldriniBackups`.
- Restore UI simples: substituir arquivo `boldrini.db` por um backup.

## Flags de configuração (app/config.py)
- `feature_local_api=False`
- `block_fiado_without_cpf=False`
- `default_interest_rate_pct=2.0`
- `low_stock_days_forecast=7`

## API local opcional
Se ativar flag, rode:
```bash
uvicorn app.local_api:api --host 127.0.0.1 --port 8000
```

## Checklist de testes rápidos
1. Login admin.
2. Criar produto e cliente.
3. Fazer venda no POS com desconto <=5%.
4. Fazer venda fiado e conferir parcelas no DB.
5. Gerar recibo PDF em `receipts/`.
6. Criar entrega e motorista.
7. Abrir painel do dono e conferir financeiro/estoque.
8. Fechar app e validar backup criado.

## Observações
- Cupom não fiscal (sem integração SEFAZ).
- SQL sempre parametrizado.
- Esquema com `schema_version` para migrações futuras.
