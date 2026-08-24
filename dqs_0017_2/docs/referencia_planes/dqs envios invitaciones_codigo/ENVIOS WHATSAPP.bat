@echo off
cd /d "%~dp0"

REM Si no existe node_modules, instalar dependencias
if not exist node_modules (
    echo Instalando dependencias por primera vez...
    npm install
) else (
    echo Dependencias ya instaladas, continuando...
)

REM Iniciar el servidor en la misma consola y dejar ventana abierta
cmd /k "npm start"