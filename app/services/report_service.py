from __future__ import annotations

import csv
from pathlib import Path

from app.db import db
from app.services.finance_service import FinanceService


class ReportService:
    def sales_by_product(self) -> list[dict]:
        with db.session() as conn:
            rows = conn.execute(
                """SELECT p.name, SUM(si.qty) AS qty, SUM(si.total) AS revenue
                FROM sale_items si JOIN products p ON p.id=si.product_id
                JOIN sales s ON s.id=si.sale_id AND s.status='finalized'
                GROUP BY p.id ORDER BY revenue DESC"""
            ).fetchall()
            return [dict(r) for r in rows]

    def top_customers(self) -> list[dict]:
        with db.session() as conn:
            rows = conn.execute(
                """SELECT c.name, COUNT(s.id) AS purchases, SUM(s.total) AS revenue
                FROM customers c JOIN sales s ON s.customer_id=c.id
                WHERE s.status='finalized'
                GROUP BY c.id ORDER BY revenue DESC LIMIT 20"""
            ).fetchall()
            return [dict(r) for r in rows]

    def sales_by_district(self) -> list[dict]:
        with db.session() as conn:
            rows = conn.execute(
                """SELECT a.district, SUM(s.total) AS revenue
                FROM sales s JOIN customers c ON c.id=s.customer_id
                LEFT JOIN addresses a ON a.id=c.address_id
                WHERE s.status='finalized'
                GROUP BY a.district ORDER BY revenue DESC"""
            ).fetchall()
            return [dict(r) for r in rows]

    def lost_customers(self, days: int = 45) -> list[dict]:
        with db.session() as conn:
            rows = conn.execute(
                """SELECT c.id, c.name, c.last_purchase_at,
                julianday('now') - julianday(COALESCE(c.last_purchase_at, c.created_at)) AS days_without
                FROM customers c
                WHERE days_without > ?
                ORDER BY days_without DESC""",
                (days,),
            ).fetchall()
            return [dict(r) for r in rows]

    def inventory_forecast(self) -> list[dict]:
        with db.session() as conn:
            rows = conn.execute(
                """SELECT p.name, p.stock,
                COALESCE((SELECT AVG(ABS(im.qty)) FROM inventory_movements im
                WHERE im.product_id=p.id AND im.movement_type='sale' AND im.created_at >= datetime('now','-30 day')),0) AS avg_daily_out
                FROM products p"""
            ).fetchall()
            out = []
            for r in rows:
                avg = float(r["avg_daily_out"])
                days = (float(r["stock"]) / avg) if avg > 0 else 999
                out.append({"name": r["name"], "stock": float(r["stock"]), "days_remaining": round(days, 1)})
            return out

    def export_accountant_csv(self, target: Path, month: str) -> Path:
        fs = FinanceService()
        dre = fs.dre_monthly(month)
        target.parent.mkdir(parents=True, exist_ok=True)
        with target.open("w", newline="", encoding="utf-8") as f:
            wr = csv.writer(f)
            wr.writerow(["metric", "value"])
            for k, v in dre.items():
                wr.writerow([k, v])
        return target
