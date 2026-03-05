from __future__ import annotations

import tkinter as tk
from tkinter import messagebox, simpledialog, ttk

from app.reports.receipt import ReceiptBuilder
from app.services.customer_service import CustomerService
from app.services.pos_service import POSService


class POSScreen(ttk.Frame):
    def __init__(self, master, app_state):
        super().__init__(master, padding=8)
        self.app_state = app_state
        self.pos = POSService()
        self.customers = CustomerService()
        self.receipt = ReceiptBuilder()
        self.sale_id = self.pos.open_sale(app_state.current_user.id)
        self.selected_customer_id = None
        self.cart = []

        top = ttk.Frame(self)
        top.pack(fill="x")
        ttk.Label(top, text="Busca produto (cód./barra/nome):").pack(side="left")
        self.product_query = ttk.Entry(top)
        self.product_query.pack(side="left", fill="x", expand=True, padx=4)
        self.product_query.bind("<Return>", lambda _: self.add_product())
        ttk.Button(top, text="Adicionar", command=self.add_product).pack(side="left")

        cframe = ttk.Frame(self)
        cframe.pack(fill="x", pady=4)
        ttk.Label(cframe, text="Cliente:").pack(side="left")
        self.customer_search = ttk.Entry(cframe)
        self.customer_search.pack(side="left", fill="x", expand=True)
        self.customer_search.bind("<KeyRelease>", self.update_customer_list)
        self.customer_combo = ttk.Combobox(cframe, state="readonly")
        self.customer_combo.pack(side="left", padx=4)
        ttk.Button(cframe, text="Selecionar", command=self.pick_customer).pack(side="left")

        self.tree = ttk.Treeview(self, columns=("item", "qty", "price", "total"), show="headings", height=13)
        for c in ("item", "qty", "price", "total"):
            self.tree.heading(c, text=c.upper())
        self.tree.pack(fill="both", expand=True)

        actions = ttk.Frame(self)
        actions.pack(fill="x", pady=6)
        ttk.Button(actions, text="Desconto", command=self.discount).pack(side="left")
        ttk.Button(actions, text="Remover Item", command=self.remove_selected).pack(side="left", padx=4)
        ttk.Button(actions, text="Escolher Pagamento", command=self.choose_payment).pack(side="left")
        ttk.Button(actions, text="FINALIZAR VENDA", command=self.finalize).pack(side="right")

        self.lbl_total = ttk.Label(self, text="Total: R$ 0.00", font=("Segoe UI", 12, "bold"))
        self.lbl_total.pack(anchor="e")
        self.pay_method = None

    def update_customer_list(self, _event=None):
        term = self.customer_search.get().strip()
        if not term:
            self.customer_combo["values"] = []
            return
        rows = self.customers.search(term)
        self.customer_map = {f"{r['id']} - {r['name']}": r["id"] for r in rows}
        self.customer_combo["values"] = list(self.customer_map.keys())

    def pick_customer(self):
        selected = self.customer_combo.get()
        if selected and selected in self.customer_map:
            self.selected_customer_id = self.customer_map[selected]
            messagebox.showinfo("Cliente", f"Cliente selecionado: {selected}")

    def add_product(self):
        q = self.product_query.get().strip()
        if not q:
            return
        prod = self.pos.find_product(q)
        if not prod:
            messagebox.showwarning("Produto", "Produto não encontrado")
            return
        qty = simpledialog.askfloat("Qtd", "Quantidade", initialvalue=1.0, minvalue=0.001)
        if not qty:
            return
        try:
            self.pos.add_item(self.sale_id, prod["id"], qty)
            self.refresh()
        except Exception as e:
            messagebox.showerror("Erro", str(e))

    def refresh(self):
        details = self.pos.sale_details(self.sale_id)
        for item in self.tree.get_children():
            self.tree.delete(item)
        for i in details["items"]:
            self.tree.insert("", "end", values=(i["name"], i["qty"], f"{i['unit_price']:.2f}", f"{i['total']:.2f}"))
        self.lbl_total.config(text=f"Total: R$ {details['sale'].get('total', 0):.2f}")

    def discount(self):
        pct = simpledialog.askfloat("Desconto", "% desconto", initialvalue=0, minvalue=0, maxvalue=100)
        if pct is None:
            return
        authorized = False
        if pct > 5:
            pwd = simpledialog.askstring("Autorização", "Senha gerente/admin", show="*")
            authorized = bool(pwd)
        try:
            self.pos.apply_discount(self.sale_id, pct, authorized=authorized)
            self.refresh()
        except Exception as e:
            messagebox.showerror("Erro", str(e))

    def remove_selected(self):
        messagebox.showinfo("Ação", "Remoção direta simplificada não implementada nesta tela MVP.")

    def choose_payment(self):
        method = simpledialog.askstring("Pagamento", "Método (cash/pix/card_debit/card_credit/fiado)")
        if method:
            self.pay_method = method
            messagebox.showinfo("Pagamento", f"Pagamento selecionado: {method}. Venda ainda NÃO finalizada.")

    def finalize(self):
        if not self.pay_method:
            messagebox.showwarning("Pagamento", "Escolha pagamento antes de finalizar")
            return
        if not messagebox.askyesno("Confirmar", "Confirmar finalização da venda?"):
            return
        details = self.pos.sale_details(self.sale_id)
        total = details["sale"].get("total", 0)
        amount = simpledialog.askfloat("Valor pago", "Valor recebido", initialvalue=total, minvalue=0)
        if amount is None:
            return
        inst = 1
        if self.pay_method in ("card_credit", "fiado"):
            inst = simpledialog.askinteger("Parcelas", "Qtde parcelas", initialvalue=1, minvalue=1, maxvalue=24) or 1
        try:
            result = self.pos.finalize_sale(
                self.sale_id,
                self.app_state.current_user.id,
                self.pay_method,
                float(amount),
                self.selected_customer_id,
                installments=inst,
            )
            details = self.pos.sale_details(self.sale_id)
            customer = None
            if self.selected_customer_id:
                customer = {"id": self.selected_customer_id, "name": self.customer_combo.get(), "cpf": ""}
            pdf = self.receipt.generate(details["sale"], details["items"], details["payments"], customer, output=__import__("pathlib").Path("receipts") / f"sale_{self.sale_id}.pdf")
            messagebox.showinfo("Venda", f"Finalizada! Troco R$ {result['change']:.2f}\nRecibo: {pdf}")
            self.sale_id = self.pos.open_sale(self.app_state.current_user.id, self.selected_customer_id)
            self.pay_method = None
            self.refresh()
        except Exception as e:
            messagebox.showerror("Erro", str(e))
