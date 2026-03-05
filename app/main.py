from __future__ import annotations

from app.db import db
from app.ui.main_window import MainApp


def main() -> None:
    db.initialize()
    app = MainApp()
    app.mainloop()


if __name__ == "__main__":
    main()
