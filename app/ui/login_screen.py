from __future__ import annotations

import tkinter as tk
from tkinter import messagebox, ttk

from app.services.auth_service import AuthService


class LoginScreen(ttk.Frame):
    def __init__(self, master, on_success):
        super().__init__(master, padding=20)
        self.auth = AuthService()
        self.on_success = on_success

        ttk.Label(self, text="BoldriniSystem", font=("Segoe UI", 16, "bold")).grid(row=0, column=0, columnspan=2, pady=(0, 20))
        ttk.Label(self, text="Usuário").grid(row=1, column=0, sticky="w")
        self.username = ttk.Entry(self)
        self.username.grid(row=1, column=1, sticky="ew")
        ttk.Label(self, text="Senha").grid(row=2, column=0, sticky="w")
        self.password = ttk.Entry(self, show="*")
        self.password.grid(row=2, column=1, sticky="ew")
        ttk.Button(self, text="Entrar", command=self.do_login).grid(row=3, column=0, columnspan=2, pady=10)
        self.columnconfigure(1, weight=1)

    def do_login(self):
        user = self.auth.login(self.username.get(), self.password.get())
        if not user:
            messagebox.showerror("Login", "Usuário/senha inválidos")
            return
        self.on_success(user)
