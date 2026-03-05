from __future__ import annotations

import re


def only_digits(value: str) -> str:
    return re.sub(r"\D", "", value or "")


def cpf_is_valid_format(cpf: str | None) -> bool:
    if not cpf:
        return True
    digits = only_digits(cpf)
    return len(digits) == 11


def mask_cpf(cpf: str | None) -> str:
    digits = only_digits(cpf or "")
    if len(digits) != 11:
        return cpf or ""
    return f"***.***.{digits[6:9]}-{digits[9:]}"
