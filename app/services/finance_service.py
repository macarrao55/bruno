from __future__ import annotations

from app.db import db


class FinanceService:
    def add_account_payable(self, supplier: str, description: str, amount: float, due_date: str = "") -> int:
        with db.session() as conn:
            cur = conn.execute(
                "INSERT INTO accounts_payable(supplier, description, amount, due_date) VALUES(?,?,?,?)",
                (supplier, description, amount, due_date),
            )
            return int(cur.lastrowid)

    def daily_summary(self) -> dict:
        with db.session() as conn:
            sales = conn.execute(
                "SELECT COALESCE(SUM(total),0) AS total FROM sales WHERE date(created_at)=date('now') AND status='finalized'"
            ).fetchone()["total"]
            expenses = conn.execute(
                "SELECT COALESCE(SUM(amount),0) AS total FROM accounts_payable WHERE date(created_at)=date('now')"
            ).fetchone()["total"]
            return {"revenue_today": float(sales), "expenses_today": float(expenses), "profit_estimate": float(sales) - float(expenses)}

    def dre_monthly(self, month_yyyy_mm: str) -> dict:
        with db.session() as conn:
            rev = conn.execute(
                "SELECT COALESCE(SUM(total),0) AS v FROM sales WHERE status='finalized' AND strftime('%Y-%m', finalized_at)=?",
                (month_yyyy_mm,),
            ).fetchone()["v"]
            cogs = conn.execute(
                """SELECT COALESCE(SUM(si.qty * p.cost),0) AS v
                FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id
                WHERE s.status='finalized' AND strftime('%Y-%m', s.finalized_at)=?""",
                (month_yyyy_mm,),
            ).fetchone()["v"]
            exp = conn.execute(
                "SELECT COALESCE(SUM(amount),0) AS v FROM accounts_payable WHERE strftime('%Y-%m', created_at)=?",
                (month_yyyy_mm,),
            ).fetchone()["v"]
        gross = float(rev) - float(cogs)
        net = gross - float(exp)
        return {"revenue": float(rev), "cogs": float(cogs), "gross_profit": gross, "expenses": float(exp), "net_profit": net}
