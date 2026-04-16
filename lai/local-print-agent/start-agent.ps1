Set-Location $PSScriptRoot
if (-not (Test-Path "$PSScriptRoot/node_modules")) {
  Write-Host "Instalando dependencias..."
  npm install
}
Write-Host "Iniciando agente local de impresion..."
npm start
