param(
  [string]$InstallDir = 'C:\Program Files\LAI Print Agent'
)

$ErrorActionPreference = 'Continue'

function Write-Info([string]$message) {
  Write-Output "[LAI-STOP] $message"
}

if (-not (Test-Path -Path $InstallDir)) {
  Write-Info "InstallDir no existe, se omite: $InstallDir"
  exit 0
}

$escapedInstallDir = [Regex]::Escape($InstallDir)
$existing = Get-CimInstance Win32_Process |
  Where-Object {
    $_.Name -ieq 'node.exe' -and
    $_.CommandLine -match "$escapedInstallDir.*agent\.js"
  }

if (-not $existing) {
  Write-Info 'No hay proceso del agente en ejecución'
  exit 0
}

foreach ($proc in $existing) {
  try {
    Stop-Process -Id $proc.ProcessId -Force -ErrorAction Stop
    Write-Info "Proceso detenido (PID: $($proc.ProcessId))"
  } catch {
    Write-Info "No se pudo detener PID $($proc.ProcessId): $($_.Exception.Message)"
  }
}
