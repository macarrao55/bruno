from __future__ import annotations

from dataclasses import dataclass

from app.db import db
from app.utils.security import verify_password


@dataclass
class AuthUser:
    id: int
    username: str
    full_name: str
    role_name: str


class AuthService:
    def login(self, username: str, password: str) -> AuthUser | None:
        with db.session() as conn:
            row = conn.execute(
                """SELECT u.id, u.username, u.full_name, u.password_hash, r.name AS role_name
                FROM users u JOIN roles r ON r.id=u.role_id
                WHERE u.username=? AND u.active=1""",
                (username.strip(),),
            ).fetchone()
            if not row:
                return None
            if not verify_password(password, row["password_hash"]):
                return None
            return AuthUser(
                id=int(row["id"]),
                username=row["username"],
                full_name=row["full_name"],
                role_name=row["role_name"],
            )

    def has_permission(self, user_id: int, permission_code: str) -> bool:
        with db.session() as conn:
            row = conn.execute(
                """SELECT COALESCE(up.allowed, rp.allowed, 0) AS allowed
                FROM users u
                JOIN roles r ON r.id=u.role_id
                LEFT JOIN permissions p ON p.code=?
                LEFT JOIN role_permissions rp ON rp.role_id=r.id AND rp.permission_id=p.id
                LEFT JOIN user_permissions up ON up.user_id=u.id AND up.permission_id=p.id
                WHERE u.id=?""",
                (permission_code, user_id),
            ).fetchone()
            return bool(row and row["allowed"])
