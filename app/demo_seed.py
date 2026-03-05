from __future__ import annotations

from app.db import db


def seed() -> None:
    with db.session() as conn:
        exists = conn.execute("SELECT COUNT(*) c FROM products").fetchone()["c"]
        if exists:
            return
        cat_water = conn.execute("SELECT id FROM categories WHERE name='Water'").fetchone()["id"]
        cat_gas = conn.execute("SELECT id FROM categories WHERE name='Gas'").fetchone()["id"]
        conn.execute("INSERT INTO products(name, category_id, sale_price, cost, stock, min_stock, barcode, internal_code) VALUES(?,?,?,?,?,?,?,?)", ("Água 20L", cat_water, 12, 8, 50, 10, "789000000001", "AG20"))
        conn.execute("INSERT INTO products(name, category_id, sale_price, cost, stock, min_stock, barcode, internal_code) VALUES(?,?,?,?,?,?,?,?)", ("Gás P13", cat_gas, 110, 95, 20, 5, "789000000002", "GP13"))
        conn.execute("INSERT INTO customers(name, phone, cpf) VALUES('João Silva', '11999999999', '12345678901')")


if __name__ == '__main__':
    db.initialize()
    seed()
