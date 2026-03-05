from __future__ import annotations

from app.db import db


class SettingsService:
    def get(self, key: str, default: str = "") -> str:
        with db.session() as conn:
            row = conn.execute("SELECT value FROM settings WHERE key=?", (key,)).fetchone()
            return row["value"] if row else default

    def set(self, key: str, value: str) -> None:
        with db.session() as conn:
            conn.execute(
                """INSERT INTO settings(key, value, updated_at) VALUES(?,?,CURRENT_TIMESTAMP)
                ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=CURRENT_TIMESTAMP""",
                (key, value),
            )
