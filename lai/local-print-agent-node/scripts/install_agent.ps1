param(
  [string]$InstallDir = 'C:\Program Files\LAI Print Agent',
  [string]$ServerHost = '127.0.0.1',
  [int]$ServerPort = 5399,
  [string]$ApiKey = 'CAMBIAR_ESTA_CLAVE_LOCAL',
  [string]$PrinterName = '',
  [int]$TicketWidthMm = 58,
  [switch]$AutoStart
)

$ErrorActionPreference = 'Stop'

function Write-Info([string]$message) {
  Write-Output "[LAI-INSTALL] $message"
}

if (-not (Test-Path -Path $InstallDir)) {
  throw "No existe InstallDir: $InstallDir"
}

$configPath = Join-Path $InstallDir 'config.json'
if (-not (Test-Path -Path $configPath)) {
  $examplePath = Join-Path $InstallDir 'config.example.json'
  if (-not (Test-Path -Path $examplePath)) {
    throw 'No existe config.json ni config.example.json'
  }

  Copy-Item -Path $examplePath -Destination $configPath -Force
}

$config = Get-Content -Path $configPath -Raw | ConvertFrom-Json

$config.server.host = $ServerHost
$config.server.port = $ServerPort
$config.server.apiKey = $ApiKey
$config.printDefaults.printerName = $PrinterName
$config.printDefaults.ticketWidthMm = $TicketWidthMm

$config | ConvertTo-Json -Depth 8 | Set-Content -Path $configPath -Encoding UTF8
Write-Info "Config actualizada en $configPath"

if ($AutoStart) {
  $taskName = 'LAI-Print-Agent'
  $runCmdPath = Join-Path $InstallDir 'run-agent.cmd'

  if (-not (Test-Path -Path $runCmdPath)) {
    throw "No existe run-agent.cmd en $InstallDir"
  }

  $action = New-ScheduledTaskAction -Execute $runCmdPath
  $trigger = New-ScheduledTaskTrigger -AtLogOn
  $principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Limited
  $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

  Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Force | Out-Null

  Write-Info "Tarea programada '$taskName' creada/actualizada"
}

Write-Info 'Instalación lógica finalizada'
