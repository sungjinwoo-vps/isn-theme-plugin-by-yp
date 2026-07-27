param(
    [string]$DownloadBaseUrl = "https://raw.githubusercontent.com/sungjinwoo-vps/isn-theme-plugin-by-yp/stable/dist",
    [string]$ThemeVersion = "",
    [string]$ToolkitVersion = "",
    [string]$ElementorKitVersion = "0.1.0"
)

$ErrorActionPreference = "Stop"

$repoRoot = (git rev-parse --show-toplevel).Trim()
Set-Location $repoRoot

$dist = Join-Path $repoRoot "dist"
New-Item -ItemType Directory -Path $dist -Force | Out-Null

function Get-FileVersion {
    param(
        [string]$Path,
        [string]$Pattern
    )

    $text = Get-Content -LiteralPath $Path -Raw
    $match = [regex]::Match($text, $Pattern)
    if (-not $match.Success) {
        throw "Unable to find version in $Path"
    }

    return $match.Groups[1].Value.Trim()
}

if ([string]::IsNullOrWhiteSpace($ThemeVersion)) {
    $ThemeVersion = Get-FileVersion -Path (Join-Path $repoRoot "wp-content/themes/infosecnexus/style.css") -Pattern 'Version:\s*([^\r\n]+)'
}

if ([string]::IsNullOrWhiteSpace($ToolkitVersion)) {
    $ToolkitVersion = Get-FileVersion -Path (Join-Path $repoRoot "wp-content/plugins/infosecnexus-toolkit/infosecnexus-toolkit.php") -Pattern 'Version:\s*([^\r\n]+)'
}

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

$downloadBase = $DownloadBaseUrl.TrimEnd("/")
$releaseManifest = @{
    manifestVersion = 1
    generatedAt = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
    theme = @{
        slug = "infosecnexus"
        version = $ThemeVersion
        homepage = "https://infosecnexus.com/"
        package = "$downloadBase/infosecnexus-theme.zip"
        requires = "6.5"
        tested = "7.0"
        requires_php = "8.1"
        last_updated = (Get-Date).ToUniversalTime().ToString("yyyy-MM-dd")
        sections = @{
            description = "Cybersecurity newsroom theme for InfoSecNexus."
            changelog = "Purges stale anonymous page caches immediately after each installed theme version changes."
        }
    }
    plugin = @{
        slug = "infosecnexus-toolkit"
        plugin = "infosecnexus-toolkit/infosecnexus-toolkit.php"
        version = $ToolkitVersion
        homepage = "https://infosecnexus.com/"
        package = "$downloadBase/infosecnexus-toolkit.zip"
        requires = "6.5"
        tested = "7.0"
        requires_php = "8.1"
        last_updated = (Get-Date).ToUniversalTime().ToString("yyyy-MM-dd")
        sections = @{
            description = "Legacy bridge for older InfoSecNexus installs. Current toolkit features are bundled into the InfoSecNexus theme."
            changelog = "Keeps the legacy bridge version aligned with the theme post-update cache purge release."
        }
    }
}
$releaseManifest | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath (Join-Path $dist "infosecnexus-releases.json") -Encoding ASCII

$sbom = @{
    spdxVersion = "SPDX-2.3"
    dataLicense = "CC0-1.0"
    name = "InfoSecNexus WordPress Theme Ecosystem"
    documentNamespace = "https://infosecnexus.com/spdx/infosecnexus-$ThemeVersion"
    packages = @(
        @{ name = "infosecnexus"; versionInfo = $ThemeVersion; licenseDeclared = "GPL-2.0-or-later" },
        @{ name = "infosecnexus-toolkit"; versionInfo = $ToolkitVersion; licenseDeclared = "GPL-2.0-or-later" },
        @{ name = "infosecnexus-elementor-kit"; versionInfo = $ElementorKitVersion; licenseDeclared = "GPL-2.0-or-later" }
    )
}
$sbom | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath (Join-Path $dist "infosecnexus.sbom.json") -Encoding ASCII

Write-Host "Created release ZIPs and SHA256SUMS in $dist"
