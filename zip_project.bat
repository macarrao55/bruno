@echo off
cd /d %~dp0
powershell -Command "Compress-Archive -Path * -DestinationPath mega_lanches.zip -Force"
echo ZIP criado: mega_lanches.zip
