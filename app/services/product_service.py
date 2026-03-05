from __future__ import annotations

from app.db import db


class ProductService:
    def list_products(self, search: str = "") -> list[dict]:
        with db.session() as conn:
            if search.strip():
                like = f"%{search.strip()}%"
                rows = conn.execute(
                    """SELECT p.id, p.name, p.sale_price, p.stock, p.min_stock, p.barcode, p.internal_code, c.name AS category
                    FROM products p LEFT JOIN categories c ON c.id=p.category_id
                    WHERE p.name LIKE ? OR p.barcode LIKE ? OR p.internal_code LIKE ?
                    ORDER BY p.name""",
                    (like, like, like),
                ).fetchall()
            else:
                rows = conn.execute(
                    """SELECT p.id, p.name, p.sale_price, p.stock, p.min_stock, p.barcode, p.internal_code, c.name AS category
                    FROM products p LEFT JOIN categories c ON c.id=p.category_id
                    ORDER BY p.name"""
                ).fetchall()
            return [dict(r) for r in rows]

    def create_product(self, data: dict) -> int:
        with db.session() as conn:
            cur = conn.execute(
                """INSERT INTO products(name, category_id, sale_price, cost, barcode, internal_code, unit, stock, min_stock, commission_rule)
                VALUES(?,?,?,?,?,?,?,?,?,?)""",
                (
                    data["name"],
                    data.get("category_id"),
                    float(data.get("sale_price", 0)),
                    float(data.get("cost", 0)),
                    data.get("barcode", ""),
                    data.get("internal_code", ""),
                    data.get("unit", "un"),
                    float(data.get("stock", 0)),
                    float(data.get("min_stock", 0)),
                    data.get("commission_rule", ""),
                ),
            )
            return int(cur.lastrowid)

    def low_stock(self) -> list[dict]:
        with db.session() as conn:
            rows = conn.execute(
                "SELECT id, name, stock, min_stock FROM products WHERE stock <= min_stock ORDER BY stock ASC"
            ).fetchall()
            return [dict(r) for r in rows]
