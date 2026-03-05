from __future__ import annotations

from dataclasses import dataclass

from app.services.auth_service import AuthUser


@dataclass
class AppState:
    current_user: AuthUser | None = None
