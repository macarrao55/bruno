from __future__ import annotations

from datetime import datetime
from pathlib import Path
from tkinter import messagebox, ttk

from app.services.report_service import ReportService


class ReportsScreen(ttk.Frame):
    def __init__(self, master):
        super().__init__(master, padding=8)
        self.svc = ReportService()
        ttk.Button(self, text="Top clientes", command=self.show_top).pack(anchor="w")
        ttk.Button(self, text="Clientes perdidos", command=self.show_lost).pack(anchor="w")
        ttk.Button(self, text="Exportar contador CSV", command=self.export_csv).pack(anchor="w")
        self.text = tk = ttk.Label(self, text="")
        tk.pack(anchor="w", pady=8)

    def show_top(self):
        rows = self.svc.top_customers()
        self.text.config(text="\n".join([f"{r['name']}: R$ {r['revenue']:.2f}" for r in rows[:10]]) or "Sem dados")

    def show_lost(self):
        rows = self.svc.lost_customers()
        self.text.config(text="\n".join([f"{r['name']} ({r['days_without']:.0f} dias)" for r in rows[:10]]) or "Sem dados")

    def export_csv(self):
        month = datetime.now().strftime("%Y-%m")
        p = self.svc.export_accountant_csv(Path("exports") / f"accountant_{month}.csv", month)
        messagebox.showinfo("Export", f"Arquivo gerado: {p}")
