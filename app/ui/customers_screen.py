from __future__ import annotations

from tkinter import ttk

from app.services.customer_service import CustomerService


class CustomersScreen(ttk.Frame):
    def __init__(self, master):
        super().__init__(master, padding=8)
        self.svc = CustomerService()
        top = ttk.Frame(self)
        top.pack(fill="x")
        self.search = ttk.Entry(top)
        self.search.pack(side="left", fill="x", expand=True)
        self.search.bind("<KeyRelease>", lambda _e: self.refresh())
        ttk.Button(top, text="Novo Cliente Rápido", command=self.quick_add).pack(side="left")

        self.tree = ttk.Treeview(self, columns=("id", "name", "phone", "cpf"), show="headings")
        for c in ("id", "name", "phone", "cpf"):
            self.tree.heading(c, text=c.upper())
        self.tree.pack(fill="both", expand=True)

    def quick_add(self):
        self.svc.create_or_get_manual("Cliente Balcão", "", "")
        self.refresh()

    def refresh(self):
        rows = self.svc.search(self.search.get()) if self.search.get() else self.svc.search(" ")
        for i in self.tree.get_children():
            self.tree.delete(i)
        for r in rows:
            self.tree.insert("", "end", values=(r["id"], r["name"], r["phone"], r["cpf"]))
