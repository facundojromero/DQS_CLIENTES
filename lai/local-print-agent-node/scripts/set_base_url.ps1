param(
  [string]$InstallDir = 'C:\Program Files\LAI Print Agent',
  [Parameter(Mandatory = $true)]
  [string]$BaseUrl
)

$ErrorActionPreference = 'Stop'

$configPath = Join-Path $InstallDir 'config.json'
if (-not (Test-Path -Path $configPath)) {
  throw "No existe config.json en: $configPath"
}

$config = Get-Content -Path $configPath -Raw | ConvertFrom-Json
if ($null -eq $config.integration) {
  $config | Add-Member -MemberType NoteProperty -Name integration -Value ([PSCustomObject]@{})
}

$config.integration.baseUrl = $BaseUrl
$config | ConvertTo-Json -Depth 8 | Set-Content -Path $configPath -Encoding UTF8

Write-Output "[LAI-CONFIG] baseUrl actualizada a: $BaseUrl"
