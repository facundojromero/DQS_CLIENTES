@echo off
cd /d %~dp0
if not exist node_modules (
  echo Instalando dependencias...
  npm install
)
echo Iniciando agente local de impresion...
npm start
