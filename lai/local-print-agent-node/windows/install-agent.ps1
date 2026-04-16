param(
  [string]$ApiKey = 'CAMBIAR_ESTA_CLAVE_LOCAL',
  [string]$PrinterName = '',
  [ValidateSet(58,80)]
  [int]$TicketWidthMm = 58,
  [int]$Port = 5399,
  [string]$InstallDir = 'C:\LAI\PrintAgent',
  [string]$ServiceName = 'LAIPrintAgent'
)

$ErrorActionPreference = 'Stop'

function Write-Step([string]$msg) {
  Write-Host "[LAI INSTALL] $msg" -ForegroundColor Cyan
}

function Assert-Admin {
  $currentUser = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
  if (-not $currentUser.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Ejecutá este instalador como Administrador.'
  }
}

function Ensure-Node {
  $nodeCmd = Get-Command node -ErrorAction SilentlyContinue
  if ($nodeCmd) {
    $version = (& node -v).TrimStart('v')
    $major = [int]($version.Split('.')[0])
    if ($major -ge 18) {
      Write-Step "Node.js detectado: v$version"
      return
    }
  }

  Write-Step 'Node.js >= 18 no detectado. Intentando instalar con winget...'
  $wingetCmd = Get-Command winget -ErrorAction SilentlyContinue
  if (-not $wingetCmd) {
    throw 'No se encontró winget. Instalá Node.js LTS manualmente y reintentá.'
  }

  winget install --id OpenJS.NodeJS.LTS --source winget --accept-package-agreements --accept-source-agreements --silent

  $env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')
  $nodeCmd = Get-Command node -ErrorAction SilentlyContinue
  if (-not $nodeCmd) {
    throw 'Node.js no quedó disponible en PATH luego de instalar. Reiniciá sesión e intentá nuevamente.'
  }

  $version = (& node -v).TrimStart('v')
  $major = [int]($version.Split('.')[0])
  if ($major -lt 18) {
    throw "La versión de Node.js detectada ($version) es menor a 18."
  }

  Write-Step "Node.js instalado: v$version"
}

function Stop-And-RemoveService([string]$name) {
  $svc = Get-Service -Name $name -ErrorAction SilentlyContinue
  if ($svc) {
    if ($svc.Status -ne 'Stopped') {
      Write-Step "Deteniendo servicio existente: $name"
      Stop-Service -Name $name -Force
    }

    Write-Step "Eliminando servicio existente: $name"
    sc.exe delete $name | Out-Null
    Start-Sleep -Seconds 1
  }
}

Assert-Admin
Ensure-Node

$scriptDir = Split-Path -Parent $PSCommandPath
$sourceRoot = Resolve-Path (Join-Path $scriptDir '..')

Write-Step "Instalando en: $InstallDir"
New-Item -Path $InstallDir -ItemType Directory -Force | Out-Null

$sourceFiles = @('agent.js', 'package.json', 'config.example.json', 'scripts')
foreach ($item in $sourceFiles) {
  $sourcePath = Join-Path $sourceRoot $item
  if (-not (Test-Path $sourcePath)) {
    throw "No se encontró el archivo requerido: $sourcePath"
  }

  $destPath = Join-Path $InstallDir $item
  if (Test-Path $destPath) {
    Remove-Item -Path $destPath -Recurse -Force
  }

  Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Force
}

$charsPerLine58 = 32
$charsPerLine80 = 42
if ($TicketWidthMm -eq 80) {
  $charsPerLine58 = 32
  $charsPerLine80 = 42
}

$config = @{
  server = @{
    host = '127.0.0.1'
    port = $Port
    apiKey = $ApiKey
  }
  printDefaults = @{
    printerName = $PrinterName
    copies = 1
    fontName = 'Consolas'
    fontSize = 9
    marginLeftMm = 2
    marginRightMm = 2
    marginTopMm = 2
    marginBottomMm = 6
    ticketWidthMm = $TicketWidthMm
    charsPerLine58 = $charsPerLine58
    charsPerLine80 = $charsPerLine80
  }
}

$configPath = Join-Path $InstallDir 'config.json'
$config | ConvertTo-Json -Depth 5 | Out-File -FilePath $configPath -Encoding UTF8

$logDir = Join-Path $InstallDir 'logs'
New-Item -Path $logDir -ItemType Directory -Force | Out-Null

$nodePath = (Get-Command node).Source
$agentPath = Join-Path $InstallDir 'agent.js'
$binPath = "\"$nodePath\" \"$agentPath\""

Stop-And-RemoveService -name $ServiceName

Write-Step "Creando servicio Windows: $ServiceName"
sc.exe create $ServiceName binPath= $binPath start= auto DisplayName= 'LAI Local Print Agent' | Out-Null
sc.exe failure $ServiceName reset= 60 actions= restart/5000 | Out-Null

Write-Step 'Iniciando servicio...'
Start-Service -Name $ServiceName

Start-Sleep -Seconds 2

try {
  $healthUrl = "http://127.0.0.1:$Port/health"
  $health = Invoke-RestMethod -Uri $healthUrl -Method GET -TimeoutSec 3
  if ($health.status -ne 'ok') {
    throw 'El endpoint /health no devolvió ok.'
  }
} catch {
  throw "Servicio instalado, pero /health falló. Revisá logs en $logDir. Error: $($_.Exception.Message)"
}

$summaryPath = Join-Path $InstallDir 'INSTALL_SUMMARY.txt'
@(
  "LAI Local Print Agent instalado correctamente.",
  "",
  "Servicio: $ServiceName",
  "Carpeta: $InstallDir",
  "URL local: http://127.0.0.1:$Port/print",
  "API Key: $ApiKey",
  "",
  "Asegurate de actualizar lai/print_agent_config.php con esta misma API Key y puerto."
) | Out-File -FilePath $summaryPath -Encoding UTF8

Write-Step 'Instalación completada OK.'
Write-Host "Resumen: $summaryPath" -ForegroundColor Green
