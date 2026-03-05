@echo off
setlocal
py -3.11 -m venv .venv
call .venv\Scripts\activate
python -m pip install --upgrade pip
python -m pip install -r requirements.txt
python -m pip install pyinstaller
echo Ambiente pronto. Para rodar: run.bat
echo Para gerar EXE: pyinstaller --noconfirm --onefile --windowed --name BoldriniSystem app\main.py
