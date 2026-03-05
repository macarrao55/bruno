from __future__ import annotations

from pathlib import Path

from reportlab.lib.pagesizes import mm
from reportlab.pdfgen import canvas

from app.utils.validation import mask_cpf


class ReceiptBuilder:
    def generate(self, sale: dict, items: list[dict], payments: list[dict], customer: dict | None, output: Path) -> Path:
        output.parent.mkdir(parents=True, exist_ok=True)
        width, height = 80 * mm, 220 * mm
        c = canvas.Canvas(str(output), pagesize=(width, height))

        for copy_name, show_full_cpf in [("CLIENTE", False), ("LOJA", True)]:
            y = height - 10 * mm
            c.setFont("Helvetica-Bold", 10)
            c.drawString(5 * mm, y, f"BOLDRINISYSTEM - {copy_name}")
            y -= 6 * mm
            c.setFont("Helvetica", 8)
            c.drawString(5 * mm, y, f"Venda #{sale.get('id')}  Total: R$ {sale.get('total',0):.2f}")
            y -= 5 * mm
            if customer:
                cpf = customer.get("cpf", "")
                cpf_print = cpf if show_full_cpf else mask_cpf(cpf)
                c.drawString(5 * mm, y, f"Cliente: {customer.get('name','')}")
                y -= 4 * mm
                if cpf_print:
                    c.drawString(5 * mm, y, f"CPF: {cpf_print}")
                    y -= 4 * mm

            c.drawString(5 * mm, y, "Itens:")
            y -= 4 * mm
            for it in items:
                c.drawString(5 * mm, y, f"{it['name']} {it['qty']}x R${it['unit_price']:.2f} = R${it['total']:.2f}")
                y -= 4 * mm

            c.drawString(5 * mm, y, "Pagamentos:")
            y -= 4 * mm
            for p in payments:
                c.drawString(5 * mm, y, f"{p['method']} R${p['amount']:.2f}")
                y -= 4 * mm
                if p.get("installments", 1) and int(p.get("installments", 1)) > 1:
                    for n in range(1, int(p["installments"]) + 1):
                        c.drawString(8 * mm, y, f"Parcela {n}/{p['installments']}")
                        y -= 4 * mm

            if any(p["method"] == "fiado" for p in payments):
                y -= 6 * mm
                c.drawString(5 * mm, y, "Assinatura (Fiado): __________________")

            c.showPage()

        c.save()
        return output
