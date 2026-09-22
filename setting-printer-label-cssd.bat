@echo off
setlocal DisableDelayedExpansion
title Setting Printer Label CSSD - 45 x 20 mm
set "CSSD_LABEL_SETUP_FILE=%~f0"
set "CSSD_LABEL_SETUP_MODE=%~1"
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoLogo -NoProfile -Command "$source = [IO.File]::ReadAllText($env:CSSD_LABEL_SETUP_FILE); $marker = '# BEGIN CSSD PRINTER SETUP'; & ([scriptblock]::Create($source.Substring($source.LastIndexOf($marker) + $marker.Length)))"
set "CSSD_LABEL_EXIT_CODE=%ERRORLEVEL%"
if /I "%CSSD_LABEL_SETUP_MODE%"=="--check" exit /b %CSSD_LABEL_EXIT_CODE%
echo.
pause
exit /b %CSSD_LABEL_EXIT_CODE%

# BEGIN CSSD PRINTER SETUP
$ErrorActionPreference = 'Stop'
$checkOnly = $env:CSSD_LABEL_SETUP_MODE -eq '--check'
$server = $null
$queue = $null

function Read-PrintXml($stream) {
    try {
        $xml = New-Object System.Xml.XmlDocument
        $xml.XmlResolver = $null
        $xml.Load($stream)
        return ,$xml
    } finally {
        $stream.Dispose()
    }
}

function Assert-LabelTicket($ticket) {
    $media = $ticket.PageMediaSize
    if (!$media -or $null -eq $media.Width -or $null -eq $media.Height -or
        [Math]::Abs($media.Width * 25.4 / 96 - 45) -gt 0.05 -or
        [Math]::Abs($media.Height * 25.4 / 96 - 20) -gt 0.05) {
        throw 'Driver tidak menerima ukuran tepat 45 x 20 mm.'
    }
    if ($ticket.PageOrientation -ne [System.Printing.PageOrientation]::Portrait) {
        throw 'Driver tidak menerima orientasi Portrait.'
    }

    $xml = Read-PrintXml $ticket.GetXmlStream()
    $scale = $xml.SelectSingleNode('//*[local-name()="Feature" and substring-after(@name, ":")="PageScaling"]/*[local-name()="Option"]')
    if ($scale -and $scale.GetAttribute('name').Split(':')[-1] -ne 'None') {
        throw 'Driver masih menggunakan pembesaran atau pengecilan halaman.'
    }
    if ($null -ne $ticket.PageScalingFactor -and $ticket.PageScalingFactor -ne 100) {
        throw 'Skala driver belum 100 persen.'
    }
}

function New-LabelTicket($baseTicket, $mediaOption) {
    $xml = Read-PrintXml $baseTicket.GetXmlStream()
    $mediaFeature = $xml.SelectSingleNode('//*[local-name()="Feature" and substring-after(@name, ":")="PageMediaSize"]')
    if (!$mediaFeature) {
        throw 'Driver belum menyediakan pengaturan ukuran kertas. Periksa instalasi driver Blueprint.'
    }

    # Nama opsi custom berbeda antar-driver; ambil dari kemampuan printer yang terpasang.
    $prefix = $mediaOption.GetAttribute('name').Split(':')[0]
    if ($xml.DocumentElement.GetNamespaceOfPrefix($prefix) -ne $mediaOption.GetNamespaceOfPrefix($prefix)) {
        throw 'Format ukuran kertas dari driver tidak cocok dengan pengaturannya.'
    }
    $oldOption = $mediaFeature.SelectSingleNode('*[local-name()="Option"]')
    if (!$oldOption) { throw 'Opsi ukuran kertas tidak ditemukan pada driver.' }
    $null = $mediaFeature.ReplaceChild($xml.ImportNode($mediaOption, $true), $oldOption)

    $schema = 'http://schemas.microsoft.com/windows/2003/08/printing/printschemaframework'
    $keywords = 'http://schemas.microsoft.com/windows/2003/08/printing/printschemakeywords'
    $keywordPrefix = $xml.DocumentElement.GetPrefixOfNamespace($keywords)
    if (!$keywordPrefix) { throw 'Format pengaturan printer Windows tidak dikenali.' }

    foreach ($setting in @{'PageOrientation' = 'Portrait'; 'PageScaling' = 'None'}.GetEnumerator()) {
        $feature = $xml.SelectSingleNode('//*[local-name()="Feature" and substring-after(@name, ":")="' + $setting.Key + '"]')
        if ($feature) { $null = $feature.ParentNode.RemoveChild($feature) }
        $feature = $xml.CreateElement('psf', 'Feature', $schema)
        $feature.SetAttribute('name', $keywordPrefix + ':' + $setting.Key)
        $option = $xml.CreateElement('psf', 'Option', $schema)
        $option.SetAttribute('name', $keywordPrefix + ':' + $setting.Value)
        $null = $feature.AppendChild($option)
        $null = $xml.DocumentElement.AppendChild($feature)
    }

    $stream = New-Object System.IO.MemoryStream
    try {
        $xml.Save($stream)
        $stream.Position = 0
        $candidate = New-Object System.Printing.PrintTicket($stream)
        $result = $queue.MergeAndValidatePrintTicket($baseTicket, $candidate)
        Assert-LabelTicket $result.ValidatedPrintTicket
        return $result.ValidatedPrintTicket
    } finally {
        $stream.Dispose()
    }
}

try {
    Write-Host 'SETTING PRINTER LABEL CSSD' -ForegroundColor Cyan
    Write-Host 'Blueprint BP-TR110(ZPL) | 45 x 20 mm | Portrait | Skala 100%'
    Write-Host ''

    if ($env:CSSD_LABEL_SETUP_MODE -and $env:CSSD_LABEL_SETUP_MODE -notin @('--check', '--apply')) {
        throw 'Pilihan tidak dikenal. Klik dua kali untuk setting, atau gunakan --check untuk pemeriksaan saja.'
    }

    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (!$checkOnly -and !$principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Write-Host 'Windows akan meminta izin administrator untuk menyimpan pengaturan printer.'
        $process = Start-Process -FilePath $env:CSSD_LABEL_SETUP_FILE -Verb RunAs -Wait -PassThru
        exit $process.ExitCode
    }

    if ((Get-Service Spooler).Status -ne 'Running') {
        throw 'Layanan Print Spooler belum berjalan. Aktifkan layanan printer Windows, lalu jalankan file ini lagi.'
    }

    Import-Module PrintManagement -ErrorAction Stop
    Add-Type -AssemblyName System.Printing
    Add-Type -AssemblyName ReachFramework

    $printers = @(Get-Printer | Where-Object {
        $_.DriverName -match 'Blueprint.*BP[- ]?TR110.*ZPL'
    } | Sort-Object Name)

    if ($printers.Count -eq 0) {
        throw 'Printer tidak ditemukan. Hubungkan printer dan install driver Blueprint BP-TR110(ZPL), lalu jalankan file ini lagi.'
    }

    $printer = $printers[0]
    if ($printers.Count -gt 1) {
        Write-Host 'Ditemukan beberapa printer Blueprint:'
        for ($i = 0; $i -lt $printers.Count; $i++) {
            Write-Host ('  {0}. {1} ({2})' -f ($i + 1), $printers[$i].Name, $printers[$i].PortName)
        }
        if ($checkOnly) { throw 'Jalankan tanpa --check untuk memilih printer yang akan disetting.' }
        $choice = 0
        if (![int]::TryParse((Read-Host 'Pilih nomor printer'), [ref]$choice) -or $choice -lt 1 -or $choice -gt $printers.Count) {
            throw 'Nomor printer tidak valid. Tidak ada pengaturan yang diubah.'
        }
        $printer = $printers[$choice - 1]
    }
    Write-Host ('Printer ditemukan: ' + $printer.Name)

    $server = New-Object System.Printing.LocalPrintServer
    $queue = $server.GetPrintQueue($printer.Name)
    $caps = Read-PrintXml $queue.GetPrintCapabilitiesAsXml()
    $mediaOption = $null
    $options = $caps.SelectNodes('//*[local-name()="Feature" and substring-after(@name, ":")="PageMediaSize"]/*[local-name()="Option"]')
    foreach ($option in $options) {
        $width = $option.SelectSingleNode('*[local-name()="ScoredProperty" and substring-after(@name, ":")="MediaSizeWidth"]/*[local-name()="Value"]')
        $height = $option.SelectSingleNode('*[local-name()="ScoredProperty" and substring-after(@name, ":")="MediaSizeHeight"]/*[local-name()="Value"]')
        if ($width -and $height -and [int]$width.InnerText -eq 45000 -and [int]$height.InnerText -eq 20000) {
            $mediaOption = $option
            break
        }
    }
    if (!$mediaOption) {
        throw 'Ukuran 45 x 20 mm belum tersedia. Tambahkan ukuran tersebut pada Printing Preferences atau gunakan driver Blueprint yang sesuai.'
    }

    $oldDefault = $queue.DefaultPrintTicket.Clone()
    $oldUser = $queue.UserPrintTicket
    if ($oldUser) { $oldUser = $oldUser.Clone() }
    $newDefault = New-LabelTicket $oldDefault $mediaOption
    $userBase = $oldDefault
    if ($oldUser) { $userBase = $oldUser }
    $newUser = New-LabelTicket $userBase $mediaOption

    if ($checkOnly) {
        Write-Host 'CHECK BERHASIL: driver menerima label 45 x 20 mm, Portrait, tanpa pembesaran.' -ForegroundColor Green
        Write-Host 'Mode pemeriksaan: tidak ada pengaturan yang diubah dan tidak ada label yang dicetak.'
    } else {
        $backupFolder = Join-Path $env:LOCALAPPDATA ('CSSD\PrinterBackup\' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [Guid]::NewGuid().ToString('N').Substring(0, 8))
        $null = New-Item -ItemType Directory -Path $backupFolder -Force
        $originalConfig = Get-PrintConfiguration -PrinterName $printer.Name
        $originalConfig.PrintTicketXML | Set-Content -LiteralPath (Join-Path $backupFolder 'default-before.xml') -Encoding UTF8
        if ($oldUser) {
            (Read-PrintXml $oldUser.GetXmlStream()).Save((Join-Path $backupFolder 'user-before.xml'))
        }

        try {
            $newXml = Read-PrintXml $newDefault.GetXmlStream()
            Set-PrintConfiguration -PrinterName $printer.Name -PrintTicketXml $newXml.OuterXml -ErrorAction Stop
            $queue.UserPrintTicket = $newUser
            $queue.Commit()
            $queue.Refresh()
            Assert-LabelTicket $queue.DefaultPrintTicket
            Assert-LabelTicket $queue.UserPrintTicket
        } catch {
            $failure = $_.Exception.Message
            try {
                Set-PrintConfiguration -PrinterName $printer.Name -PrintTicketXml $originalConfig.PrintTicketXML -ErrorAction Stop
                $queue.UserPrintTicket = $oldUser
                $queue.Commit()
                Write-Host 'Pengaturan sebelumnya sudah dipulihkan.' -ForegroundColor Yellow
            } catch {
                Write-Host ('Pemulihan otomatis gagal. Cadangan tersedia di: ' + $backupFolder) -ForegroundColor Red
            }
            throw ('Pengaturan label gagal disimpan: ' + $failure)
        }

        Write-Host ''
        Write-Host 'BERHASIL: ukuran label 45 x 20 mm, Portrait, skala driver tanpa pembesaran.' -ForegroundColor Green
        Write-Host ('Cadangan pengaturan: ' + $backupFolder)
    }

    Write-Host ''
    Write-Host 'PENGATURAN CHROME SAAT CETAK LABEL:' -ForegroundColor Cyan
    Write-Host '  Tutup preview lama, lalu buka Cetak Label kembali.'
    Write-Host ('  Destination       : ' + $printer.Name)
    Write-Host '  Paper size        : 45mm x 20mm'
    Write-Host '  Scale             : Custom 100 (bukan 200)'
    Write-Host '  Margins           : None'
    Write-Host '  Pages per sheet   : 1'
    Write-Host '  Headers / footers : Nonaktif, jika tersedia'
    Write-Host '  Pastikan preview hanya 1 sheet sebelum mencetak.'
    Write-Host ''
    Write-Host 'Skala Chrome terpisah dari driver; file ini tidak mengubah profil browser.'
    Write-Host 'Pengaturan kecepatan, darkness, gap label, dan printer lain tidak diubah.'
    exit 0
} catch {
    Write-Host ''
    Write-Host ('GAGAL: ' + $_.Exception.Message) -ForegroundColor Red
    exit 1
} finally {
    if ($queue) { $queue.Dispose() }
    if ($server) { $server.Dispose() }
}
