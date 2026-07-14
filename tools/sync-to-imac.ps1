param(
    [string]$HostAlias = "yash-imac",
    [string]$RemoteRoot = "/Users/yp/Projects/infosecnexus"
)

$ErrorActionPreference = "Stop"

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    throw "git is required for the safe file list."
}

$repoRoot = (git rev-parse --show-toplevel).Trim()
Set-Location $repoRoot

$excludeRegex = '(^|/)(\.git|node_modules|vendor|coverage|playwright-report|test-results|docker-data|logs)(/|$)|(^|/)\.env(\.|$)|\.log$|\.tmp$|\.bak$|(^|/)wp-content/(uploads|upgrade|cache)(/|$)'
$files = git ls-files -co --exclude-standard |
    Where-Object { $_ -and ($_ -notmatch $excludeRegex) } |
    Sort-Object -Unique

if (-not $files) {
    throw "No files selected for synchronization."
}

$tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("infosecnexus-sync-" + [System.Guid]::NewGuid().ToString("N"))
$stage = Join-Path $tempRoot "payload"
$tarPath = Join-Path $tempRoot "infosecnexus-source.tar"
New-Item -ItemType Directory -Path $stage | Out-Null

foreach ($file in $files) {
    $destination = Join-Path $stage $file
    New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
    Copy-Item -LiteralPath (Join-Path $repoRoot $file) -Destination $destination -Force
}

Push-Location $stage
try {
    tar -cf $tarPath .
}
finally {
    Pop-Location
}

$localHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $tarPath).Hash.ToLowerInvariant()

ssh -o BatchMode=yes $HostAlias "mkdir -p '$RemoteRoot' && chmod 700 '$RemoteRoot'"
scp $tarPath "${HostAlias}:$RemoteRoot/.infosecnexus-source.tar"

$remoteHash = (ssh -o BatchMode=yes $HostAlias "shasum -a 256 '$RemoteRoot/.infosecnexus-source.tar' | awk '{print `$1}'").Trim().ToLowerInvariant()
if ($remoteHash -ne $localHash) {
    throw "Checksum mismatch after upload. Local $localHash remote $remoteHash"
}

ssh -o BatchMode=yes $HostAlias "cd '$RemoteRoot' && tar -xf .infosecnexus-source.tar && rm .infosecnexus-source.tar && find . -type d -name .git -prune -o -type f | wc -l"

Remove-Item -LiteralPath $tempRoot -Recurse -Force
Write-Host "Synced $($files.Count) files to $RemoteRoot. SHA256 $localHash"

