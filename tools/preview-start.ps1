param([switch]$RefreshContent,[switch]$ResetChecks,[string]$SshTarget=$env:ISN_PREVIEW_SSH_TARGET)
$ErrorActionPreference='Stop'
$repoRoot=Split-Path -Parent $PSScriptRoot
$connectionFile=Join-Path $repoRoot '.tmp/cinematic-preview-connection.json'
if(-not (Test-Path -LiteralPath $connectionFile)){throw 'Restore the ignored .tmp/cinematic-preview-connection.json with ssh_target, source_path and source_user from secure project records.'}
$connection=Get-Content -LiteralPath $connectionFile -Raw | ConvertFrom-Json
if(-not $SshTarget){$SshTarget=$connection.ssh_target}
if($SshTarget -notmatch '^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+$' -or $connection.source_user -notmatch '^[A-Za-z0-9_-]+$' -or $connection.source_path -notmatch '^/[A-Za-z0-9_./-]+$'){throw 'Invalid preview connection configuration.'}
$key=Join-Path $repoRoot '.tmp/isn-live.pem'
foreach($name in @('public-export','public-import','sandbox','router','check','reset-checks')){
    & scp -i $key -o BatchMode=yes (Join-Path $PSScriptRoot "preview-$name.php") ($SshTarget+":/tmp/isn-preview-$name.php")
    if($LASTEXITCODE -ne 0){throw "Preview helper transfer failed: $name"}
}
$stageReady=& ssh -i $key -o BatchMode=yes $SshTarget 'test -f /tmp/isn-cinematic-preview-data-20261106/wordpress.sqlite && echo ready'
if($RefreshContent -or $stageReady -ne 'ready'){
    $sourcePath=$connection.source_path
    $sourceUser=$connection.source_user
    & ssh -i $key -o BatchMode=yes $SshTarget "sudo -u $sourceUser wp --path=$sourcePath eval-file /tmp/isn-preview-public-export.php --skip-themes --skip-plugins > /tmp/isn-cinematic-public-20261106.json; chmod 600 /tmp/isn-cinematic-public-20261106.json; test -s /tmp/isn-cinematic-public-20261106.json"
    if($LASTEXITCODE -ne 0){throw 'Read-only public content export failed.'}
    & (Join-Path $PSScriptRoot 'preview-sync.ps1') -SshTarget $SshTarget
    Get-Content (Join-Path $PSScriptRoot 'preview-setup.sh') -Raw | & ssh -i $key -o BatchMode=yes $SshTarget "tr -d '\r' | bash -s"
    if($LASTEXITCODE -ne 0){throw 'Isolated preview setup failed.'}
}
& (Join-Path $PSScriptRoot 'preview-sync.ps1') -Start -SshTarget $SshTarget
if($ResetChecks){
    & ssh -i $key -o BatchMode=yes $SshTarget 'wp --path=/tmp/isn-cinematic-preview-20261106 eval-file /tmp/isn-preview-reset-checks.php --skip-plugins'
    if($LASTEXITCODE -ne 0){throw 'Isolated preview test preparation failed.'}
}
$readyFile=Join-Path $repoRoot '.tmp/cinematic-preview-ready.html'
$status=& curl.exe --silent --show-error --fail --retry 5 --retry-connrefused --retry-delay 1 --output $readyFile --write-out '%{http_code}' 'http://localhost:8890/'
if($LASTEXITCODE -ne 0){throw 'The preview server did not become ready. Check .tmp/cinematic-preview-tunnel.log and the isolated PHP log.'}
Write-Output "Ready: http://localhost:8890/ (HTTP $status)"
