@echo off
setlocal

REM Ajusta estos valores antes de ejecutar si lo necesitás:
set API_KEY=CAMBIAR_ESTA_CLAVE_LOCAL
set PRINTER_NAME=
set TICKET_WIDTH=58
set AGENT_PORT=5399

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install-agent.ps1" -ApiKey "%API_KEY%" -PrinterName "%PRINTER_NAME%" -TicketWidthMm %TICKET_WIDTH% -Port %AGENT_PORT%

if %ERRORLEVEL% neq 0 (
  echo.
  echo [ERROR] La instalacion fallo. Revisa el mensaje de PowerShell.
  pause
  exit /b %ERRORLEVEL%
)

echo.
echo [OK] Instalacion completada.
pause
