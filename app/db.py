from __future__ import annotations

import sqlite3
from contextlib import contextmanager
from pathlib import Path
from typing import Iterator

from app.config import CONFIG


SCHEMA_VERSION = 1


class Database:
    def __init__(self, db_path: Path | None = None) -> None:
        self.db_path = db_path or CONFIG.db_path
        self.db_path.parent.mkdir(parents=True, exist_ok=True)

    def connect(self) -> sqlite3.Connection:
        conn = sqlite3.connect(self.db_path)
        conn.row_factory = sqlite3.Row
        conn.execute("PRAGMA foreign_keys=ON")
        return conn

    @contextmanager
    def session(self) -> Iterator[sqlite3.Connection]:
        conn = self.connect()
        try:
            yield conn
            conn.commit()
        except Exception:
            conn.rollback()
            raise
        finally:
            conn.close()

    def initialize(self) -> None:
        with self.session() as conn:
            conn.execute(
                """CREATE TABLE IF NOT EXISTS schema_version (version INTEGER NOT NULL)"""
            )
            row = conn.execute("SELECT version FROM schema_version LIMIT 1").fetchone()
            if row is None:
                conn.execute("INSERT INTO schema_version(version) VALUES(0)")
                version = 0
            else:
                version = int(row["version"])

            if version < 1:
                self._migration_v1(conn)
                conn.execute("UPDATE schema_version SET version=?", (1,))

    def _migration_v1(self, conn: sqlite3.Connection) -> None:
        ddl = [
            """CREATE TABLE IF NOT EXISTS roles(id INTEGER PRIMARY KEY, name TEXT UNIQUE NOT NULL)""",
            """CREATE TABLE IF NOT EXISTS users(
                id INTEGER PRIMARY KEY,
                username TEXT UNIQUE NOT NULL,
                full_name TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                role_id INTEGER NOT NULL,
                active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(role_id) REFERENCES roles(id)
            )""",
            """CREATE TABLE IF NOT EXISTS permissions(
                id INTEGER PRIMARY KEY,
                code TEXT UNIQUE NOT NULL,
                description TEXT NOT NULL
            )""",
            """CREATE TABLE IF NOT EXISTS role_permissions(
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                allowed INTEGER NOT NULL DEFAULT 1,
                PRIMARY KEY(role_id, permission_id),
                FOREIGN KEY(role_id) REFERENCES roles(id),
                FOREIGN KEY(permission_id) REFERENCES permissions(id)
            )""",
            """CREATE TABLE IF NOT EXISTS user_permissions(
                user_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                allowed INTEGER NOT NULL,
                PRIMARY KEY(user_id, permission_id),
                FOREIGN KEY(user_id) REFERENCES users(id),
                FOREIGN KEY(permission_id) REFERENCES permissions(id)
            )""",
            """CREATE TABLE IF NOT EXISTS audit_log(
                id INTEGER PRIMARY KEY,
                user_id INTEGER,
                action TEXT NOT NULL,
                details TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES users(id)
            )""",
            """CREATE TABLE IF NOT EXISTS addresses(
                id INTEGER PRIMARY KEY,
                street TEXT,
                number TEXT,
                district TEXT,
                reference TEXT,
                city TEXT DEFAULT 'Cidade',
                state TEXT DEFAULT 'SP'
            )""",
            """CREATE TABLE IF NOT EXISTS customers(
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                phone TEXT,
                cpf TEXT,
                customer_type TEXT NOT NULL DEFAULT 'common',
                marketing_status TEXT NOT NULL DEFAULT 'active',
                address_id INTEGER,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_purchase_at TEXT,
                FOREIGN KEY(address_id) REFERENCES addresses(id)
            )""",
            """CREATE TABLE IF NOT EXISTS categories(
                id INTEGER PRIMARY KEY,
                name TEXT UNIQUE NOT NULL
            )""",
            """CREATE TABLE IF NOT EXISTS products(
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                category_id INTEGER,
                sale_price REAL NOT NULL,
                cost REAL NOT NULL DEFAULT 0,
                barcode TEXT,
                internal_code TEXT,
                unit TEXT NOT NULL DEFAULT 'un',
                stock REAL NOT NULL DEFAULT 0,
                min_stock REAL NOT NULL DEFAULT 0,
                commission_rule TEXT,
                is_fractional INTEGER NOT NULL DEFAULT 0,
                active INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY(category_id) REFERENCES categories(id)
            )""",
            """CREATE TABLE IF NOT EXISTS sales(
                id INTEGER PRIMARY KEY,
                customer_id INTEGER,
                user_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'open',
                discount_pct REAL NOT NULL DEFAULT 0,
                subtotal REAL NOT NULL DEFAULT 0,
                total REAL NOT NULL DEFAULT 0,
                notes TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                finalized_at TEXT,
                FOREIGN KEY(customer_id) REFERENCES customers(id),
                FOREIGN KEY(user_id) REFERENCES users(id)
            )""",
            """CREATE TABLE IF NOT EXISTS sale_items(
                id INTEGER PRIMARY KEY,
                sale_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                qty REAL NOT NULL,
                unit_price REAL NOT NULL,
                total REAL NOT NULL,
                FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                FOREIGN KEY(product_id) REFERENCES products(id)
            )""",
            """CREATE TABLE IF NOT EXISTS payments(
                id INTEGER PRIMARY KEY,
                sale_id INTEGER NOT NULL,
                method TEXT NOT NULL,
                amount REAL NOT NULL,
                details TEXT,
                installments INTEGER DEFAULT 1,
                status TEXT NOT NULL DEFAULT 'confirmed',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE CASCADE
            )""",
            """CREATE TABLE IF NOT EXISTS accounts_receivable(
                id INTEGER PRIMARY KEY,
                customer_id INTEGER NOT NULL,
                sale_id INTEGER,
                principal_amount REAL NOT NULL,
                interest_rate REAL NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL,
                outstanding_amount REAL NOT NULL,
                status TEXT NOT NULL DEFAULT 'open',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(customer_id) REFERENCES customers(id),
                FOREIGN KEY(sale_id) REFERENCES sales(id)
            )""",
            """CREATE TABLE IF NOT EXISTS installments(
                id INTEGER PRIMARY KEY,
                account_receivable_id INTEGER NOT NULL,
                due_date TEXT NOT NULL,
                amount REAL NOT NULL,
                status TEXT NOT NULL DEFAULT 'open',
                FOREIGN KEY(account_receivable_id) REFERENCES accounts_receivable(id) ON DELETE CASCADE
            )""",
            """CREATE TABLE IF NOT EXISTS havers(
                id INTEGER PRIMARY KEY,
                customer_id INTEGER NOT NULL,
                amount REAL NOT NULL,
                notes TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(customer_id) REFERENCES customers(id)
            )""",
            """CREATE TABLE IF NOT EXISTS accounts_payable(
                id INTEGER PRIMARY KEY,
                supplier TEXT NOT NULL,
                description TEXT NOT NULL,
                amount REAL NOT NULL,
                due_date TEXT,
                status TEXT NOT NULL DEFAULT 'open',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )""",
            """CREATE TABLE IF NOT EXISTS purchases(
                id INTEGER PRIMARY KEY,
                supplier TEXT NOT NULL,
                freight REAL NOT NULL DEFAULT 0,
                total REAL NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )""",
            """CREATE TABLE IF NOT EXISTS purchase_items(
                id INTEGER PRIMARY KEY,
                purchase_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                qty REAL NOT NULL,
                unit_cost REAL NOT NULL,
                total REAL NOT NULL,
                FOREIGN KEY(purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
                FOREIGN KEY(product_id) REFERENCES products(id)
            )""",
            """CREATE TABLE IF NOT EXISTS inventory_movements(
                id INTEGER PRIMARY KEY,
                product_id INTEGER NOT NULL,
                movement_type TEXT NOT NULL,
                qty REAL NOT NULL,
                reference_type TEXT,
                reference_id INTEGER,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(product_id) REFERENCES products(id)
            )""",
            """CREATE TABLE IF NOT EXISTS drivers(
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                phone TEXT,
                active INTEGER NOT NULL DEFAULT 1
            )""",
            """CREATE TABLE IF NOT EXISTS deliveries(
                id INTEGER PRIMARY KEY,
                sale_id INTEGER,
                driver_id INTEGER,
                status TEXT NOT NULL DEFAULT 'Preparing',
                payment_status TEXT NOT NULL DEFAULT 'pending',
                expected_change REAL DEFAULT 0,
                notes TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(sale_id) REFERENCES sales(id),
                FOREIGN KEY(driver_id) REFERENCES drivers(id)
            )""",
            """CREATE TABLE IF NOT EXISTS loads(
                id INTEGER PRIMARY KEY,
                driver_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'open',
                checked_out_at TEXT,
                checked_in_at TEXT,
                cash_collected REAL DEFAULT 0,
                card_collected REAL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(driver_id) REFERENCES drivers(id)
            )""",
            """CREATE TABLE IF NOT EXISTS load_items(
                id INTEGER PRIMARY KEY,
                load_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                qty REAL NOT NULL,
                returned_qty REAL NOT NULL DEFAULT 0,
                FOREIGN KEY(load_id) REFERENCES loads(id) ON DELETE CASCADE,
                FOREIGN KEY(product_id) REFERENCES products(id)
            )""",
            """CREATE TABLE IF NOT EXISTS commissions(
                id INTEGER PRIMARY KEY,
                driver_id INTEGER,
                sale_id INTEGER,
                commission_amount REAL NOT NULL,
                rule_description TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(driver_id) REFERENCES drivers(id),
                FOREIGN KEY(sale_id) REFERENCES sales(id)
            )""",
            """CREATE TABLE IF NOT EXISTS goals(
                id INTEGER PRIMARY KEY,
                month TEXT NOT NULL,
                goal_type TEXT NOT NULL,
                target_value REAL NOT NULL,
                current_value REAL NOT NULL DEFAULT 0,
                UNIQUE(month, goal_type)
            )""",
            """CREATE TABLE IF NOT EXISTS returnables_loans(
                id INTEGER PRIMARY KEY,
                customer_id INTEGER NOT NULL,
                item_type TEXT NOT NULL,
                qty REAL NOT NULL,
                status TEXT NOT NULL DEFAULT 'open',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(customer_id) REFERENCES customers(id)
            )""",
            """CREATE TABLE IF NOT EXISTS returnables_returns(
                id INTEGER PRIMARY KEY,
                loan_id INTEGER NOT NULL,
                qty REAL NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(loan_id) REFERENCES returnables_loans(id) ON DELETE CASCADE
            )""",
            """CREATE TABLE IF NOT EXISTS returnables_losses(
                id INTEGER PRIMARY KEY,
                customer_id INTEGER,
                item_type TEXT NOT NULL,
                qty REAL NOT NULL,
                reason TEXT,
                original_expiration TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(customer_id) REFERENCES customers(id)
            )""",
            """CREATE TABLE IF NOT EXISTS settings(
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )""",
        ]
        for stmt in ddl:
            conn.execute(stmt)

        default_categories = [
            "Water", "Gas", "Ice", "Charcoal", "Beverages", "Groceries", "Cleaning", "Others"
        ]
        for cat in default_categories:
            conn.execute("INSERT OR IGNORE INTO categories(name) VALUES(?)", (cat,))

        roles = ["Administrator", "Manager", "Seller", "Cashier", "Delivery"]
        for role in roles:
            conn.execute("INSERT OR IGNORE INTO roles(name) VALUES(?)", (role,))

        permissions = [
            ("sale.cancel", "Cancel sale"),
            ("sale.edit", "Edit sale"),
            ("sale.discount.high", "Approve discount >5%"),
            ("fiado.approve", "Approve fiado"),
            ("cash.close", "Close cash"),
            ("settings.manage", "Manage settings"),
            ("reports.view", "View reports"),
        ]
        for code, desc in permissions:
            conn.execute("INSERT OR IGNORE INTO permissions(code, description) VALUES(?,?)", (code, desc))

        admin_role = conn.execute("SELECT id FROM roles WHERE name='Administrator'").fetchone()
        if admin_role:
            role_id = int(admin_role["id"])
            for row in conn.execute("SELECT id FROM permissions").fetchall():
                conn.execute(
                    "INSERT OR IGNORE INTO role_permissions(role_id, permission_id, allowed) VALUES(?,?,1)",
                    (role_id, int(row["id"])),
                )

        existing_admin = conn.execute("SELECT id FROM users WHERE username='admin'").fetchone()
        if not existing_admin and admin_role:
            import hashlib

            pwd_hash = hashlib.sha256("admin123".encode("utf-8")).hexdigest()
            conn.execute(
                """INSERT INTO users(username, full_name, password_hash, role_id)
                VALUES('admin', 'Administrador', ?, ?)""",
                (pwd_hash, role_id),
            )


db = Database()
