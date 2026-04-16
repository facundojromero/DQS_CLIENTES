param(
  [string]$InstallDir = 'C:\Program Files\LAI Print Agent',
  [string]$ServerHost = '127.0.0.1',
  [int]$ServerPort = 3000,
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
if ($null -eq $config.server) {
  $config | Add-Member -MemberType NoteProperty -Name server -Value ([PSCustomObject]@{})
}
if ($null -eq $config.printDefaults) {
  $config | Add-Member -MemberType NoteProperty -Name printDefaults -Value ([PSCustomObject]@{})
}

$config.server.host = $ServerHost
$config.server.port = $ServerPort
$config.server.apiKey = $ApiKey
$config.printDefaults.printerName = $PrinterName
$config.printDefaults.ticketWidthMm = $TicketWidthMm

$config | ConvertTo-Json -Depth 8 | Set-Content -Path $configPath -Encoding UTF8
Write-Info "Config actualizada en $configPath"

$startScriptPath = Join-Path $InstallDir 'scripts\start_agent.ps1'
if (-not (Test-Path -Path $startScriptPath)) {
  throw "No existe scripts\\start_agent.ps1 en $InstallDir"
}

if ($AutoStart) {
  $taskName = 'LAI-Print-Agent'
  $actionArgs = "-NoProfile -ExecutionPolicy Bypass -File `"$startScriptPath`" -InstallDir `"$InstallDir`""
  $action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $actionArgs
  $trigger = New-ScheduledTaskTrigger -AtLogOn
  $currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
  $principal = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType InteractiveToken -RunLevel Limited
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

& powershell.exe -NoProfile -ExecutionPolicy Bypass -File $startScriptPath -InstallDir $InstallDir

Write-Info 'Instalación lógica finalizada'
