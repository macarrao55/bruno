from __future__ import annotations

from datetime import datetime, timedelta

from app.config import CONFIG
from app.db import db
from app.services.audit_service import AuditService
from app.utils.validation import cpf_is_valid_format


class POSService:
    def __init__(self) -> None:
        self.audit = AuditService()

    def open_sale(self, user_id: int, customer_id: int | None = None) -> int:
        with db.session() as conn:
            cur = conn.execute(
                "INSERT INTO sales(customer_id, user_id, status) VALUES(?,?,'open')",
                (customer_id, user_id),
            )
            return int(cur.lastrowid)

    def find_product(self, query: str) -> dict | None:
        with db.session() as conn:
            row = conn.execute(
                """SELECT id, name, sale_price, stock FROM products
                WHERE barcode=? OR internal_code=? OR name LIKE ?
                ORDER BY name LIMIT 1""",
                (query, query, f"%{query}%"),
            ).fetchone()
            return dict(row) if row else None

    def add_item(self, sale_id: int, product_id: int, qty: float) -> None:
        if qty <= 0:
            raise ValueError("Quantidade deve ser maior que zero")
        with db.session() as conn:
            product = conn.execute(
                "SELECT sale_price, stock FROM products WHERE id=?", (product_id,)
            ).fetchone()
            if not product:
                raise ValueError("Produto não encontrado")
            if float(product["stock"]) < qty:
                raise ValueError("Estoque insuficiente")
            total = float(product["sale_price"]) * qty
            conn.execute(
                "INSERT INTO sale_items(sale_id, product_id, qty, unit_price, total) VALUES(?,?,?,?,?)",
                (sale_id, product_id, qty, float(product["sale_price"]), total),
            )
            conn.execute("UPDATE products SET stock = stock - ? WHERE id=?", (qty, product_id))
            conn.execute(
                "INSERT INTO inventory_movements(product_id, movement_type, qty, reference_type, reference_id) VALUES(?,?,?,?,?)",
                (product_id, "sale", -qty, "sale", sale_id),
            )
            self._recalculate_sale(conn, sale_id)

    def apply_discount(self, sale_id: int, discount_pct: float, authorized: bool = False) -> None:
        if discount_pct < 0:
            raise ValueError("Desconto inválido")
        if discount_pct > 5 and not authorized:
            raise PermissionError("Desconto acima de 5% requer autorização")
        with db.session() as conn:
            conn.execute("UPDATE sales SET discount_pct=? WHERE id=?", (discount_pct, sale_id))
            self._recalculate_sale(conn, sale_id)

    def register_payment(self, sale_id: int, method: str, amount: float, details: str = "", installments: int = 1) -> None:
        with db.session() as conn:
            conn.execute(
                "INSERT INTO payments(sale_id, method, amount, details, installments) VALUES(?,?,?,?,?)",
                (sale_id, method, amount, details, installments),
            )

    def finalize_sale(
        self,
        sale_id: int,
        user_id: int,
        payment_method: str,
        amount_paid: float,
        customer_id: int | None,
        cpf: str | None = None,
        installments: int = 1,
    ) -> dict:
        with db.session() as conn:
            sale = conn.execute("SELECT total FROM sales WHERE id=?", (sale_id,)).fetchone()
            if not sale:
                raise ValueError("Venda não encontrada")
            total = float(sale["total"])
            if payment_method == "fiado":
                if CONFIG.block_fiado_without_cpf and not cpf:
                    raise ValueError("Fiado bloqueado sem CPF")
                if not cpf_is_valid_format(cpf):
                    raise ValueError("CPF inválido")
                if not customer_id:
                    raise ValueError("Fiado requer cliente")
                interest_rate = CONFIG.default_interest_rate_pct
                final_total = total * (1 + interest_rate / 100)
                cur = conn.execute(
                    """INSERT INTO accounts_receivable(customer_id, sale_id, principal_amount, interest_rate, total_amount, outstanding_amount)
                    VALUES(?,?,?,?,?,?)""",
                    (customer_id, sale_id, total, interest_rate, final_total, final_total),
                )
                ar_id = int(cur.lastrowid)
                each = round(final_total / installments, 2)
                today = datetime.now().date()
                for i in range(installments):
                    due = today + timedelta(days=30 * (i + 1))
                    conn.execute(
                        "INSERT INTO installments(account_receivable_id, due_date, amount) VALUES(?,?,?)",
                        (ar_id, due.isoformat(), each),
                    )
                conn.execute(
                    "INSERT INTO payments(sale_id, method, amount, details, installments) VALUES(?,?,?,?,?)",
                    (sale_id, payment_method, total, f"fiado parcelas:{installments}", installments),
                )
            else:
                conn.execute(
                    "INSERT INTO payments(sale_id, method, amount, details, installments) VALUES(?,?,?,?,?)",
                    (sale_id, payment_method, amount_paid, "", 1),
                )

            conn.execute(
                "UPDATE sales SET status='finalized', finalized_at=CURRENT_TIMESTAMP, customer_id=? WHERE id=?",
                (customer_id, sale_id),
            )
            if customer_id:
                conn.execute(
                    "UPDATE customers SET last_purchase_at=CURRENT_TIMESTAMP WHERE id=?", (customer_id,)
                )

        change = max(0.0, amount_paid - total) if payment_method == "cash" else 0.0
        self.audit.log(user_id, "sale.finalized", f"sale_id={sale_id} total={total:.2f} payment={payment_method}")
        return {"sale_id": sale_id, "total": total, "change": change}

    def sale_details(self, sale_id: int) -> dict:
        with db.session() as conn:
            sale = conn.execute("SELECT * FROM sales WHERE id=?", (sale_id,)).fetchone()
            items = conn.execute(
                """SELECT si.qty, si.unit_price, si.total, p.name
                FROM sale_items si JOIN products p ON p.id=si.product_id WHERE si.sale_id=?""",
                (sale_id,),
            ).fetchall()
            pays = conn.execute("SELECT method, amount, installments FROM payments WHERE sale_id=?", (sale_id,)).fetchall()
            return {
                "sale": dict(sale) if sale else {},
                "items": [dict(i) for i in items],
                "payments": [dict(p) for p in pays],
            }

    def recent_sales(self, limit: int = 50) -> list[dict]:
        with db.session() as conn:
            rows = conn.execute(
                """SELECT s.id, s.status, s.total, s.created_at, c.name AS customer
                FROM sales s LEFT JOIN customers c ON c.id=s.customer_id
                ORDER BY s.id DESC LIMIT ?""",
                (limit,),
            ).fetchall()
            return [dict(r) for r in rows]

    def cancel_sale(self, sale_id: int, user_id: int) -> None:
        with db.session() as conn:
            conn.execute("UPDATE sales SET status='cancelled' WHERE id=?", (sale_id,))
        self.audit.log(user_id, "sale.cancelled", f"sale_id={sale_id}")

    @staticmethod
    def _recalculate_sale(conn, sale_id: int) -> None:
        subtotal = conn.execute(
            "SELECT COALESCE(SUM(total),0) AS subtotal FROM sale_items WHERE sale_id=?", (sale_id,)
        ).fetchone()["subtotal"]
        discount_pct = conn.execute("SELECT discount_pct FROM sales WHERE id=?", (sale_id,)).fetchone()["discount_pct"]
        total = float(subtotal) * (1 - float(discount_pct) / 100)
        conn.execute("UPDATE sales SET subtotal=?, total=? WHERE id=?", (subtotal, total, sale_id))
