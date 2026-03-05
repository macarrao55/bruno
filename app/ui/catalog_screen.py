from __future__ import annotations

from tkinter import messagebox, ttk

from app.services.product_service import ProductService


class CatalogScreen(ttk.Frame):
    def __init__(self, master):
        super().__init__(master, padding=8)
        self.svc = ProductService()

        top = ttk.Frame(self)
        top.pack(fill="x")
        self.search = ttk.Entry(top)
        self.search.pack(side="left", fill="x", expand=True)
        ttk.Button(top, text="Buscar", command=self.refresh).pack(side="left")
        ttk.Button(top, text="Novo Produto", command=self.add_product).pack(side="left", padx=4)

        self.tree = ttk.Treeview(self, columns=("id", "name", "category", "price", "stock"), show="headings")
        for c in ("id", "name", "category", "price", "stock"):
            self.tree.heading(c, text=c.upper())
        self.tree.pack(fill="both", expand=True)
        self.refresh()

    def refresh(self):
        for i in self.tree.get_children():
            self.tree.delete(i)
        for p in self.svc.list_products(self.search.get()):
            self.tree.insert("", "end", values=(p["id"], p["name"], p["category"], p["sale_price"], p["stock"]))

    def add_product(self):
        try:
            self.svc.create_product({"name": "Água 20L", "sale_price": 12, "cost": 8, "stock": 20, "min_stock": 5})
            self.refresh()
        except Exception as e:
            messagebox.showerror("Erro", str(e))
