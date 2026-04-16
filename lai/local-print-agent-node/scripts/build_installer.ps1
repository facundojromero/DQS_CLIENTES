param(
  [string]$InnoSetupCompiler = 'C:\Program Files (x86)\Inno Setup 6\ISCC.exe'
)

$ErrorActionPreference = 'Stop'

$rootDir = Split-Path -Path $PSScriptRoot -Parent
$issPath = Join-Path $rootDir 'installer\LAIPrintAgent.iss'

if (-not (Test-Path -Path $issPath)) {
  throw "No existe el .iss: $issPath"
}

if (-not (Test-Path -Path $InnoSetupCompiler)) {
  throw "No existe ISCC.exe en '$InnoSetupCompiler'. Instalá Inno Setup 6 o pasá -InnoSetupCompiler."
}

Write-Output "[LAI-BUILD] Compilando instalador con $InnoSetupCompiler"
& "$InnoSetupCompiler" "$issPath"

$outputExe = Join-Path (Join-Path $rootDir 'installer') 'LAI-Print-Agent-Setup.exe'
if (Test-Path -Path $outputExe) {
  Write-Output "[LAI-BUILD] EXE generado: $outputExe"
} else {
  Write-Output "[LAI-BUILD] Compilación terminada. Revisá output en carpeta installer."
}
