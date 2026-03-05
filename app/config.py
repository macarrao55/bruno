from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class AppConfig:
    app_name: str = "BoldriniSystem"
    db_path: Path = Path("boldrini.db")
    backup_dir: Path = Path("backups")
    feature_local_api: bool = False
    block_fiado_without_cpf: bool = False
    default_interest_rate_pct: float = 2.0
    low_stock_days_forecast: int = 7


CONFIG = AppConfig()
