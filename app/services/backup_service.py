from __future__ import annotations

from pathlib import Path

from app.config import CONFIG
from app.utils.backup import create_backup, replicate_backup_to_usb


class BackupService:
    def backup_now(self) -> tuple[Path, list[Path]]:
        file = create_backup(CONFIG.db_path, CONFIG.backup_dir)
        copies = replicate_backup_to_usb(file)
        return file, copies
