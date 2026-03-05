from __future__ import annotations

import shutil
from datetime import datetime
from pathlib import Path


def detect_usb_drives_windows() -> list[Path]:
    roots = []
    for letter in "DEFGHIJKLMNOPQRSTUVWXYZ":
        p = Path(f"{letter}:/")
        if p.exists():
            roots.append(p)
    return roots


def create_backup(db_path: Path, backup_dir: Path) -> Path:
    backup_dir.mkdir(parents=True, exist_ok=True)
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    target = backup_dir / f"backup_{stamp}.db"
    shutil.copy2(db_path, target)
    return target


def replicate_backup_to_usb(backup_file: Path) -> list[Path]:
    copied: list[Path] = []
    for root in detect_usb_drives_windows():
        usb_dir = root / "BoldriniBackups"
        usb_dir.mkdir(exist_ok=True)
        target = usb_dir / backup_file.name
        shutil.copy2(backup_file, target)
        copied.append(target)
    return copied
