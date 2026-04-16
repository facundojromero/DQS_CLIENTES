param(
  [string]$InnoSetupCompiler = ''
)

$ErrorActionPreference = 'Stop'

function Resolve-IsccPath([string]$PreferredPath) {
  if (-not [string]::IsNullOrWhiteSpace($PreferredPath) -and (Test-Path -Path $PreferredPath)) {
    return $PreferredPath
  }

  $fromCommand = Get-Command ISCC.exe -ErrorAction SilentlyContinue
  if ($fromCommand) {
    return $fromCommand.Source
  }

  $candidates = @(
    'C:\Program Files (x86)\Inno Setup 6\ISCC.exe',
    'C:\Program Files\Inno Setup 6\ISCC.exe',
    'C:\Program Files (x86)\Inno Setup 5\ISCC.exe',
    'C:\Program Files\Inno Setup 5\ISCC.exe'
  )

  foreach ($candidate in $candidates) {
    if (Test-Path -Path $candidate) {
      return $candidate
    }
  }

  $registryUninstallPaths = @(
    'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*',
    'HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*',
    'HKCU:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*'
  )

  foreach ($regPath in $registryUninstallPaths) {
    $items = Get-ItemProperty -Path $regPath -ErrorAction SilentlyContinue |
      Where-Object { $_.DisplayName -like 'Inno Setup*' }

    foreach ($item in $items) {
      if ($item.InstallLocation) {
        $regCandidate = Join-Path $item.InstallLocation 'ISCC.exe'
        if (Test-Path -Path $regCandidate) {
          return $regCandidate
        }
      }
    }
  }

  $wingetRoots = @(
    (Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages'),
    (Join-Path $env:ProgramFiles 'WindowsApps')
  )

  foreach ($root in $wingetRoots) {
    if (Test-Path -Path $root) {
      $wingetIscc = Get-ChildItem -Path $root -Filter ISCC.exe -Recurse -ErrorAction SilentlyContinue |
        Select-Object -First 1 -ExpandProperty FullName
      if ($wingetIscc) {
        return $wingetIscc
      }
    }
  }

  return $null
}

$rootDir = Split-Path -Path $PSScriptRoot -Parent
$issPath = Join-Path $rootDir 'installer\LAIPrintAgent.iss'

if (-not (Test-Path -Path $issPath)) {
  throw "No existe el .iss: $issPath"
}

$resolvedIscc = Resolve-IsccPath -PreferredPath $InnoSetupCompiler
if (-not $resolvedIscc) {
  throw "ISCC.exe not found. Install Inno Setup (v6 recommended) or pass -InnoSetupCompiler with full path."
}

Write-Output "[LAI-BUILD] Compiling installer with $resolvedIscc"
& "$resolvedIscc" "$issPath"

$outputExe = Join-Path (Join-Path $rootDir 'installer') 'LAI-Print-Agent-Setup.exe'
if (Test-Path -Path $outputExe) {
  Write-Output "[LAI-BUILD] EXE generated: $outputExe"
} else {
  Write-Output "[LAI-BUILD] Compilation finished. Check output in installer folder."
}
