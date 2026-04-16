param(
  [string]$InstallDir = 'C:\Program Files\LAI Print Agent'
)

$ErrorActionPreference = 'Continue'
$taskName = 'LAI-Print-Agent'

$stopScriptPath = Join-Path $InstallDir 'scripts\stop_agent.ps1'
if (Test-Path -Path $stopScriptPath) {
  & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $stopScriptPath -InstallDir $InstallDir
}

try {
  Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction Stop
  Write-Output "[LAI-UNINSTALL] Tarea '$taskName' eliminada"
} catch {
  Write-Output "[LAI-UNINSTALL] Tarea '$taskName' no encontrada o no se pudo eliminar"
}
