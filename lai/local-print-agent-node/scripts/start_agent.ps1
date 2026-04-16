param(
  [string]$InstallDir = 'C:\Program Files\LAI Print Agent'
)

$ErrorActionPreference = 'Stop'

function Write-Info([string]$message) {
  Write-Output "[LAI-START] $message"
}

if (-not (Test-Path -Path $InstallDir)) {
  throw "No existe InstallDir: $InstallDir"
}

$nodeExe = Join-Path $InstallDir 'runtime\node.exe'
$agentJs = Join-Path $InstallDir 'agent.js'
$logDir = Join-Path $InstallDir 'logs'
$stdoutLog = Join-Path $logDir 'agent-stdout.log'
$stderrLog = Join-Path $logDir 'agent-stderr.log'

if (-not (Test-Path -Path $nodeExe)) {
  throw "No existe runtime\\node.exe en: $nodeExe"
}

if (-not (Test-Path -Path $agentJs)) {
  throw "No existe agent.js en: $agentJs"
}

if (-not (Test-Path -Path $logDir)) {
  New-Item -Path $logDir -ItemType Directory -Force | Out-Null
}

$escapedInstallDir = [Regex]::Escape($InstallDir)
$existing = Get-CimInstance Win32_Process |
  Where-Object {
    $_.Name -ieq 'node.exe' -and
    $_.CommandLine -match "$escapedInstallDir.*agent\.js"
  }

foreach ($proc in $existing) {
  try {
    Stop-Process -Id $proc.ProcessId -Force -ErrorAction Stop
    Write-Info "Proceso previo detenido (PID: $($proc.ProcessId))"
  } catch {
    Write-Info "No se pudo detener PID $($proc.ProcessId): $($_.Exception.Message)"
  }
}

$proc = Start-Process -FilePath $nodeExe `
  -ArgumentList @('agent.js') `
  -WorkingDirectory $InstallDir `
  -WindowStyle Hidden `
  -RedirectStandardOutput $stdoutLog `
  -RedirectStandardError $stderrLog `
  -PassThru

Write-Info "Agente iniciado (PID: $($proc.Id))"
