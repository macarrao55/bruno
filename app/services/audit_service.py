from __future__ import annotations

from app.db import db


class AuditService:
    def log(self, user_id: int | None, action: str, details: str = "") -> None:
        with db.session() as conn:
            conn.execute(
                "INSERT INTO audit_log(user_id, action, details) VALUES(?,?,?)",
                (user_id, action, details),
            )
