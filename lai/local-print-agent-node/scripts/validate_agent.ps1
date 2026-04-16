param(
  [string]$InstallDir = 'C:\Program Files\LAI Print Agent',
  [string]$Host = '127.0.0.1',
  [int]$Port = 3000,
  [string]$ApiKey = 'CAMBIAR_ESTA_CLAVE_LOCAL'
)

$ErrorActionPreference = 'Stop'

$healthUrl = "http://$Host`:$Port/health"
$printUrl = "http://$Host`:$Port/print"

Write-Output "[LAI-VALIDATE] Probando health: $healthUrl"
$health = Invoke-RestMethod -Method Get -Uri $healthUrl -TimeoutSec 8
$health | ConvertTo-Json -Depth 4

$payload = @{
  ticketText = "*** TEST LAI ***`nFecha: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')`nImpresion de validacion"
  printConfig = @{
    printerName = ""
    ticketWidthMm = 58
    copies = 1
  }
} | ConvertTo-Json -Depth 5

Write-Output "[LAI-VALIDATE] Enviando print de prueba: $printUrl"
$result = Invoke-RestMethod -Method Post -Uri $printUrl -TimeoutSec 15 -Headers @{
  'x-lai-api-key' = $ApiKey
  'Content-Type' = 'application/json'
} -Body $payload

$result | ConvertTo-Json -Depth 4
Write-Output '[LAI-VALIDATE] VALIDACION_OK'
