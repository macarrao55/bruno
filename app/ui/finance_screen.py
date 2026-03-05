from __future__ import annotations

from datetime import datetime
from tkinter import messagebox, simpledialog, ttk

from app.services.finance_service import FinanceService


class FinanceScreen(ttk.Frame):
    def __init__(self, master):
        super().__init__(master, padding=8)
        self.svc = FinanceService()
        ttk.Button(self, text="Adicionar conta a pagar", command=self.add_payable).pack(anchor="w")
        ttk.Button(self, text="Exibir resumo diário", command=self.show_daily).pack(anchor="w", pady=4)
        ttk.Button(self, text="Exibir DRE mensal", command=self.show_dre).pack(anchor="w")
        self.output = tk = ttk.Label(self, text="")
        tk.pack(anchor="w", pady=8)

    def add_payable(self):
        desc = simpledialog.askstring("Conta", "Descrição")
        amount = simpledialog.askfloat("Conta", "Valor", minvalue=0)
        if desc and amount is not None:
            self.svc.add_account_payable("Diversos", desc, amount)
            messagebox.showinfo("Financeiro", "Conta cadastrada")

    def show_daily(self):
        d = self.svc.daily_summary()
        self.output.config(text=f"Receita: {d['revenue_today']:.2f} | Despesas: {d['expenses_today']:.2f} | Lucro: {d['profit_estimate']:.2f}")

    def show_dre(self):
        month = datetime.now().strftime("%Y-%m")
        dre = self.svc.dre_monthly(month)
        self.output.config(text=f"DRE {month}: Receita {dre['revenue']:.2f} COGS {dre['cogs']:.2f} Líquido {dre['net_profit']:.2f}")
