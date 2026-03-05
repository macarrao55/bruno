from __future__ import annotations

import tkinter as tk
from tkinter import messagebox, ttk

from app.services.app_state import AppState
from app.services.backup_service import BackupService
from app.ui.catalog_screen import CatalogScreen
from app.ui.customers_screen import CustomersScreen
from app.ui.dashboard_screen import DashboardScreen
from app.ui.deliveries_screen import DeliveriesScreen
from app.ui.finance_screen import FinanceScreen
from app.ui.login_screen import LoginScreen
from app.ui.pos_screen import POSScreen
from app.ui.reports_screen import ReportsScreen


class MainApp(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("BoldriniSystem")
        self.geometry("1100x700")
        self.state = AppState()
        self.backups = BackupService()
        self.protocol("WM_DELETE_WINDOW", self.on_close)
        self._show_login()

    def _show_login(self):
        self._clear()
        login = LoginScreen(self, self._on_login_success)
        login.pack(fill="both", expand=True)

    def _on_login_success(self, user):
        self.state.current_user = user
        self._show_main_menu()

    def _show_main_menu(self):
        self._clear()
        menubar = tk.Menu(self)
        inicio = tk.Menu(menubar, tearoff=0)
        inicio.add_command(label="POS", command=lambda: self._open_frame(POSScreen))
        inicio.add_command(label="Painel do Dono", command=lambda: self._open_frame(DashboardScreen))
        menubar.add_cascade(label="Início", menu=inicio)

        modules = tk.Menu(menubar, tearoff=0)
        modules.add_command(label="Produtos", command=lambda: self._open_frame(CatalogScreen))
        modules.add_command(label="Clientes", command=lambda: self._open_frame(CustomersScreen))
        modules.add_command(label="Entregas", command=lambda: self._open_frame(DeliveriesScreen))
        modules.add_command(label="Financeiro", command=lambda: self._open_frame(FinanceScreen))
        modules.add_command(label="Relatórios", command=lambda: self._open_frame(ReportsScreen))
        menubar.add_cascade(label="Módulos", menu=modules)

        self.config(menu=menubar)
        self.container = ttk.Frame(self)
        self.container.pack(fill="both", expand=True)
        self._open_frame(POSScreen)

    def _open_frame(self, frame_cls):
        for w in self.container.winfo_children():
            w.destroy()
        kwargs = {"app_state": self.state} if frame_cls is POSScreen else {}
        frame = frame_cls(self.container, **kwargs)
        frame.pack(fill="both", expand=True)

    def _clear(self):
        self.config(menu=tk.Menu(self))
        for w in self.winfo_children():
            w.destroy()

    def on_close(self):
        try:
            bkp, usb = self.backups.backup_now()
            extra = f"\nUSB: {len(usb)} cópias" if usb else ""
            messagebox.showinfo("Backup", f"Backup salvo em {bkp}{extra}")
        except Exception as e:
            messagebox.showwarning("Backup", f"Falha no backup de saída: {e}")
        self.destroy()
