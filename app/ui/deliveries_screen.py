from __future__ import annotations

from tkinter import messagebox, simpledialog, ttk

from app.services.delivery_service import DeliveryService
from app.services.pos_service import POSService


class DeliveriesScreen(ttk.Frame):
    def __init__(self, master):
        super().__init__(master, padding=8)
        self.svc = DeliveryService()
        self.pos = POSService()

        btns = ttk.Frame(self)
        btns.pack(fill="x")
        ttk.Button(btns, text="Criar Motorista", command=self.new_driver).pack(side="left")
        ttk.Button(btns, text="Criar Entrega de Venda", command=self.new_delivery).pack(side="left")

        self.tree = ttk.Treeview(self, columns=("id", "status", "total", "customer"), show="headings")
        for c in ("id", "status", "total", "customer"):
            self.tree.heading(c, text=c.upper())
        self.tree.pack(fill="both", expand=True)
        self.refresh()

    def refresh(self):
        for i in self.tree.get_children():
            self.tree.delete(i)
        for s in self.pos.recent_sales(30):
            self.tree.insert("", "end", values=(s["id"], s["status"], s["total"], s["customer"]))

    def new_driver(self):
        name = simpledialog.askstring("Motorista", "Nome")
        if name:
            self.svc.create_driver(name)
            messagebox.showinfo("Motorista", "Motorista criado")

    def new_delivery(self):
        sale_id = simpledialog.askinteger("Entrega", "ID da venda")
        driver_id = simpledialog.askinteger("Entrega", "ID do motorista")
        if sale_id and driver_id:
            self.svc.assign_delivery(sale_id, driver_id)
            messagebox.showinfo("Entrega", "Entrega criada")
