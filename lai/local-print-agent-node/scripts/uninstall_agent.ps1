$ErrorActionPreference = 'Continue'
$taskName = 'LAI-Print-Agent'

try {
  Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction Stop
  Write-Output "[LAI-UNINSTALL] Tarea '$taskName' eliminada"
} catch {
  Write-Output "[LAI-UNINSTALL] Tarea '$taskName' no encontrada o no se pudo eliminar"
}
