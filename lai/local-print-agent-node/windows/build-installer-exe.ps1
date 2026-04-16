param(
  [string]$OutputExe = '.\LAI-Print-Agent-Installer.exe'
)

$ErrorActionPreference = 'Stop'

Write-Host '[LAI BUILD] Verificando módulo ps2exe...' -ForegroundColor Cyan
$module = Get-Module -ListAvailable -Name ps2exe
if (-not $module) {
  Install-Module -Name ps2exe -Scope CurrentUser -Force
}

Import-Module ps2exe

$scriptPath = Join-Path $PSScriptRoot 'install-agent.ps1'
if (-not (Test-Path $scriptPath)) {
  throw "No existe install-agent.ps1 en: $scriptPath"
}

Invoke-PS2EXE -InputFile $scriptPath -OutputFile $OutputExe -NoConsole -RequireAdmin -Title 'LAI Print Agent Installer'

Write-Host "[LAI BUILD] Instalador generado en: $OutputExe" -ForegroundColor Green
Write-Host '[LAI BUILD] Importante: el EXE debe distribuirse junto a la carpeta del agente (agent.js, scripts, etc.).' -ForegroundColor Yellow
