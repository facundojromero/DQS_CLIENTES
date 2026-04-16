param(
  [Parameter(Mandatory = $true)]
  [string]$PayloadPath
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path -Path $PayloadPath)) {
  throw "No existe el payload: $PayloadPath"
}

$payload = Get-Content -Path $PayloadPath -Raw | ConvertFrom-Json
$text = [string]$payload.ticketText
$config = $payload.printConfig

if ([string]::IsNullOrWhiteSpace($text)) {
  throw "ticketText vacío"
}

Add-Type -AssemblyName System.Drawing

function Convert-MmToHundredthsInch([double]$mm) {
  return [int][Math]::Round($mm * 3.937)
}

$ticketWidthMm = [double]$config.ticketWidthMm
if ($ticketWidthMm -le 0) { $ticketWidthMm = 58 }

$marginLeft = Convert-MmToHundredthsInch([double]$config.marginLeftMm)
$marginRight = Convert-MmToHundredthsInch([double]$config.marginRightMm)
$marginTop = Convert-MmToHundredthsInch([double]$config.marginTopMm)
$marginBottom = Convert-MmToHundredthsInch([double]$config.marginBottomMm)

$fontName = [string]$config.fontName
if ([string]::IsNullOrWhiteSpace($fontName)) { $fontName = 'Consolas' }
$fontSize = [float]$config.fontSize
if ($fontSize -le 0) { $fontSize = 9 }

$copies = [int]$config.copies
if ($copies -lt 1) { $copies = 1 }

$printerName = [string]$config.printerName

$lines = $text -split "`n"
$lineIndex = 0

$font = New-Object System.Drawing.Font($fontName, $fontSize)
$brush = [System.Drawing.Brushes]::Black

$printDoc = New-Object System.Drawing.Printing.PrintDocument
$printDoc.DefaultPageSettings.Margins = New-Object System.Drawing.Printing.Margins($marginLeft, $marginRight, $marginTop, $marginBottom)
$paperWidth = Convert-MmToHundredthsInch($ticketWidthMm)
$paperHeight = 1200
$printDoc.DefaultPageSettings.PaperSize = New-Object System.Drawing.Printing.PaperSize('Ticket', $paperWidth, $paperHeight)

if (-not [string]::IsNullOrWhiteSpace($printerName)) {
  $printDoc.PrinterSettings.PrinterName = $printerName
}

if (-not $printDoc.PrinterSettings.IsValid) {
  throw "Impresora inválida o no disponible: '$printerName'"
}

$handler = [System.Drawing.Printing.PrintPageEventHandler]{
  param($sender, $e)

  $x = $e.MarginBounds.Left
  $y = $e.MarginBounds.Top
  $lineHeight = $font.GetHeight($e.Graphics)

  while ($lineIndex -lt $lines.Length) {
    if (($y + $lineHeight) -gt $e.MarginBounds.Bottom) {
      $e.HasMorePages = $true
      return
    }

    $line = $lines[$lineIndex]
    $e.Graphics.DrawString($line, $font, $brush, $x, $y)
    $y += $lineHeight
    $lineIndex++
  }

  $e.HasMorePages = $false
}

$printDoc.add_PrintPage($handler)

for ($i = 1; $i -le $copies; $i++) {
  $lineIndex = 0
  $printDoc.Print()
}

$printDoc.remove_PrintPage($handler)
$printDoc.Dispose()
$font.Dispose()

Write-Output 'PRINT_OK'
