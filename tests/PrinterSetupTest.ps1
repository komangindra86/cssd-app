$ErrorActionPreference = 'Stop'
$path = Join-Path $PSScriptRoot '..\setting-printer-label-cssd.bat'
$source = [IO.File]::ReadAllText($path)
$marker = '# BEGIN CSSD PRINTER SETUP'
$code = $source.Substring($source.LastIndexOf($marker) + $marker.Length)
$tokens = $null
$parseErrors = $null
$ast = [Management.Automation.Language.Parser]::ParseInput($code, [ref]$tokens, [ref]$parseErrors)
if ($parseErrors.Count -gt 0) { throw ($parseErrors | Out-String) }

# Muat hanya fungsi pemilihan; jangan jalankan blok yang mengubah printer Windows.
foreach ($name in @('Show-PrinterList', 'Select-LabelPrinter')) {
    $function = $ast.Find({ param($node)
        $node -is [Management.Automation.Language.FunctionDefinitionAst] -and $node.Name -eq $name
    }, $false)
    if (!$function) { throw ('Fungsi tidak ditemukan: ' + $name) }
    . ([scriptblock]::Create($function.Extent.Text))
}

$script:tests = 0
$script:choice = ''
function Read-Host { param($Prompt) return $script:choice }
function New-TestPrinter($name, $driver) {
    return [pscustomobject]@{ Name = $name; DriverName = $driver; PortName = 'TEST-ONLY' }
}
function Assert-Printer($printers, [bool]$check, $requested, $expected) {
    $selected = Select-LabelPrinter $printers $check $requested
    if ($selected.Name -ne $expected) { throw ('Printer salah: ' + $selected.Name) }
    $script:tests++
}
function Assert-Refused($printers, [bool]$check, $requested, $message) {
    $failure = $null
    try { $null = Select-LabelPrinter $printers $check $requested } catch { $failure = $_.Exception.Message }
    if (!$failure -or $failure -notlike ('*' + $message + '*')) {
        throw ('Seharusnya ditolak dengan pesan ' + $message + ', hasil: ' + $failure)
    }
    $script:tests++
}

$pdf = New-TestPrinter 'Microsoft Print to PDF' 'Microsoft Print To PDF'
$old = New-TestPrinter 'Printer label lama' 'Blueprint BP-TR110(ZPL)'
$short = New-TestPrinter 'Printer CSSD' 'BP-TR110'
$renamed = New-TestPrinter 'Blueprint Label CSSD' 'Generic Label Driver'
$separator = New-TestPrinter 'bp_tr_110 (Copy 1)' 'Label Driver'
$generic = New-TestPrinter 'Printer Label Ruang CSSD' 'Thermal Label Driver'
$specialName = New-TestPrinter 'CSSD [Label] (Copy 1)' 'Thermal Label Driver'

Assert-Printer @($pdf, $old) $true '' $old.Name
Assert-Printer @($pdf, $short) $true '' $short.Name
Assert-Printer @($pdf, $renamed) $true '' $renamed.Name
Assert-Printer @($pdf, $separator) $true '' $separator.Name
Assert-Printer @($pdf, $specialName) $true $specialName.Name $specialName.Name
Assert-Printer @($old, $generic) $true $generic.Name $generic.Name

Assert-Refused @() $true '' 'akun Windows ini'
Assert-Refused @($pdf, $generic) $true '' '--check "NAMA PRINTER"'
Assert-Refused @($pdf) $true '' '--check "NAMA PRINTER"'
Assert-Refused @($old, $short) $true '' '--check "NAMA PRINTER"'
Assert-Refused @($specialName) $true 'CSSD*' 'nama persis'

$script:choice = '2'
Assert-Printer @($pdf, $generic) $false '' $generic.Name
Assert-Printer @($old, $short) $false '' $short.Name
foreach ($choice in @('0', '-1', '3', 'abc', '1.5', '')) {
    $script:choice = $choice
    Assert-Refused @($pdf, $generic) $false '' 'Tidak ada pengaturan yang diubah'
}

Write-Host ('PASS: {0} pengujian deteksi/pemilihan printer. Tidak ada pengaturan printer yang diubah.' -f $script:tests) -ForegroundColor Green
