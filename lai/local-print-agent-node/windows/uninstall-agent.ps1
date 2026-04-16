param(
  [string]$InstallDir = 'C:\LAI\PrintAgent',
  [string]$ServiceName = 'LAIPrintAgent'
)

$ErrorActionPreference = 'Stop'

Write-Host "[LAI UNINSTALL] Deteniendo y eliminando servicio $ServiceName" -ForegroundColor Yellow

$svc = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($svc) {
  if ($svc.Status -ne 'Stopped') {
    Stop-Service -Name $ServiceName -Force
  }
  sc.exe delete $ServiceName | Out-Null
}

if (Test-Path $InstallDir) {
  Write-Host "[LAI UNINSTALL] Eliminando carpeta $InstallDir" -ForegroundColor Yellow
  Remove-Item -Path $InstallDir -Recurse -Force
}

Write-Host '[LAI UNINSTALL] Desinstalación completada.' -ForegroundColor Green
