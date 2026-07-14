$ErrorActionPreference = "Stop"

$repoRoot = (git rev-parse --show-toplevel).Trim()
Set-Location $repoRoot

$dist = Join-Path $repoRoot "dist"
New-Item -ItemType Directory -Path $dist -Force | Out-Null

function New-CleanZip {
    param(
        [string]$Source,
        [string]$TopLevel,
        [string]$ZipName
    )

    $tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("infosecnexus-zip-" + [System.Guid]::NewGuid().ToString("N"))
    $stage = Join-Path $tempRoot $TopLevel
    New-Item -ItemType Directory -Path $stage -Force | Out-Null
    Get-ChildItem -LiteralPath $Source -Force | Copy-Item -Destination $stage -Recurse -Force
    Get-ChildItem -LiteralPath $stage -Recurse -Force |
        Where-Object {
            $_.FullName -match '\\(node_modules|vendor|coverage|test-results|playwright-report|\.git)($|\\)' -or
            $_.Name -match '^\.env' -or
            $_.Extension -in @(".log", ".tmp", ".bak")
        } |
        Sort-Object FullName -Descending |
        Remove-Item -Recurse -Force

    $zipPath = Join-Path $dist $ZipName
    if (Test-Path -LiteralPath $zipPath) {
        Remove-Item -LiteralPath $zipPath -Force
    }
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $stream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::CreateNew)
    $archive = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        foreach ($file in Get-ChildItem -LiteralPath $tempRoot -Recurse -File -Force) {
            $relative = $file.FullName.Substring($tempRoot.Length).TrimStart([System.IO.Path]::DirectorySeparatorChar, [System.IO.Path]::AltDirectorySeparatorChar)
            $entryName = $relative -replace '\\', '/'
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $file.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
        }
    }
    finally {
        $archive.Dispose()
        $stream.Dispose()
    }
    Remove-Item -LiteralPath $tempRoot -Recurse -Force
    return $zipPath
}

$themeZip = New-CleanZip -Source (Join-Path $repoRoot "wp-content/themes/infosecnexus") -TopLevel "infosecnexus" -ZipName "infosecnexus-theme.zip"
$pluginZip = New-CleanZip -Source (Join-Path $repoRoot "wp-content/plugins/infosecnexus-toolkit") -TopLevel "infosecnexus-toolkit" -ZipName "infosecnexus-toolkit.zip"
$kitZip = New-CleanZip -Source (Join-Path $repoRoot "elementor-kit") -TopLevel "infosecnexus-elementor-kit" -ZipName "infosecnexus-elementor-kit.zip"

$hashLines = foreach ($zip in @($themeZip, $pluginZip, $kitZip)) {
    $hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $zip).Hash.ToLowerInvariant()
    "$hash  $(Split-Path $zip -Leaf)"
}
$hashLines | Set-Content -LiteralPath (Join-Path $dist "SHA256SUMS") -Encoding ASCII

$sbom = @{
    spdxVersion = "SPDX-2.3"
    dataLicense = "CC0-1.0"
    name = "InfoSecNexus WordPress Theme Ecosystem"
    documentNamespace = "https://infosecnexus.com/spdx/infosecnexus-0.1.0"
    packages = @(
        @{ name = "infosecnexus"; versionInfo = "0.1.0"; licenseDeclared = "GPL-2.0-or-later" },
        @{ name = "infosecnexus-toolkit"; versionInfo = "0.1.0"; licenseDeclared = "GPL-2.0-or-later" },
        @{ name = "infosecnexus-elementor-kit"; versionInfo = "0.1.0"; licenseDeclared = "GPL-2.0-or-later" }
    )
}
$sbom | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath (Join-Path $dist "infosecnexus.sbom.json") -Encoding ASCII

Write-Host "Created release ZIPs and SHA256SUMS in $dist"
