from __future__ import annotations

import hashlib


def hash_password(password: str) -> str:
    return hashlib.sha256(password.encode("utf-8")).hexdigest()


def verify_password(password: str, expected_hash: str) -> bool:
    return hash_password(password) == expected_hash
