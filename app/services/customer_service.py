from __future__ import annotations

from app.db import db
from app.utils.validation import cpf_is_valid_format


class CustomerService:
    def create_or_get_manual(self, name: str, phone: str = "", cpf: str = "", address: dict | None = None) -> int:
        if not name.strip():
            raise ValueError("Nome do cliente é obrigatório")
        if not cpf_is_valid_format(cpf):
            raise ValueError("CPF inválido")
        address_id = None
        if address:
            with db.session() as conn:
                cur = conn.execute(
                    "INSERT INTO addresses(street, number, district, reference) VALUES(?,?,?,?)",
                    (
                        address.get("street", ""),
                        address.get("number", ""),
                        address.get("district", ""),
                        address.get("reference", ""),
                    ),
                )
                address_id = int(cur.lastrowid)
                cur = conn.execute(
                    "INSERT INTO customers(name, phone, cpf, address_id) VALUES(?,?,?,?)",
                    (name.strip(), phone.strip(), cpf.strip(), address_id),
                )
                return int(cur.lastrowid)

        with db.session() as conn:
            cur = conn.execute(
                "INSERT INTO customers(name, phone, cpf) VALUES(?,?,?)",
                (name.strip(), phone.strip(), cpf.strip()),
            )
            return int(cur.lastrowid)

    def search(self, term: str) -> list[dict]:
        like = f"%{term.strip()}%"
        with db.session() as conn:
            rows = conn.execute(
                "SELECT id, name, phone, cpf FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY name LIMIT 20",
                (like, like),
            ).fetchall()
            return [dict(r) for r in rows]
