from __future__ import annotations

from tkinter import ttk

from app.services.finance_service import FinanceService
from app.services.product_service import ProductService


class DashboardScreen(ttk.Frame):
    def __init__(self, master):
        super().__init__(master, padding=8)
        fs = FinanceService()
        ps = ProductService()
        summary = fs.daily_summary()
        low = ps.low_stock()

        notebook = ttk.Notebook(self)
        notebook.pack(fill="both", expand=True)
        tabs = {name: ttk.Frame(notebook, padding=8) for name in ["Operations", "Finance", "Inventory", "Marketing", "Goals"]}
        for name, frame in tabs.items():
            notebook.add(frame, text=name)

        ttk.Label(tabs["Finance"], text=f"Receita hoje: R$ {summary['revenue_today']:.2f}").pack(anchor="w")
        ttk.Label(tabs["Finance"], text=f"Despesas hoje: R$ {summary['expenses_today']:.2f}").pack(anchor="w")
        ttk.Label(tabs["Finance"], text=f"Lucro estimado: R$ {summary['profit_estimate']:.2f}").pack(anchor="w")

        ttk.Label(tabs["Inventory"], text="Estoque baixo:").pack(anchor="w")
        for p in low[:20]:
            ttk.Label(tabs["Inventory"], text=f"- {p['name']} ({p['stock']}/{p['min_stock']})").pack(anchor="w")

        ttk.Label(tabs["Operations"], text="Pedidos hoje e status de entregas disponíveis no módulo de relatórios.").pack(anchor="w")
        ttk.Label(tabs["Marketing"], text="Radar de clientes perdidos e lembretes de recompra no módulo de relatórios.").pack(anchor="w")
        ttk.Label(tabs["Goals"], text="Progresso de metas mensais via tabela goals.").pack(anchor="w")
