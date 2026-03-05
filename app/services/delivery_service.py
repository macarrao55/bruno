from __future__ import annotations

from app.db import db


class DeliveryService:
    def create_driver(self, name: str, phone: str = "") -> int:
        with db.session() as conn:
            cur = conn.execute("INSERT INTO drivers(name, phone) VALUES(?,?)", (name, phone))
            return int(cur.lastrowid)

    def assign_delivery(self, sale_id: int, driver_id: int, paid_at_counter: bool = False) -> int:
        with db.session() as conn:
            cur = conn.execute(
                "INSERT INTO deliveries(sale_id, driver_id, status, payment_status) VALUES(?,?,?,?)",
                (sale_id, driver_id, "Preparing", "paid_at_counter" if paid_at_counter else "pending"),
            )
            return int(cur.lastrowid)

    def update_status(self, delivery_id: int, status: str) -> None:
        with db.session() as conn:
            conn.execute("UPDATE deliveries SET status=? WHERE id=?", (status, delivery_id))

    def open_load(self, driver_id: int) -> int:
        with db.session() as conn:
            cur = conn.execute(
                "INSERT INTO loads(driver_id, status, checked_out_at) VALUES(?, 'open', CURRENT_TIMESTAMP)",
                (driver_id,),
            )
            return int(cur.lastrowid)
