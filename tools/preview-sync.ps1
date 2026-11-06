param([switch]$Start,[string]$SshTarget=$env:ISN_PREVIEW_SSH_TARGET)
$ErrorActionPreference='Stop'
$repoRoot=Split-Path -Parent $PSScriptRoot
$connectionFile=Join-Path $repoRoot '.tmp/cinematic-preview-connection.json'
if(-not $SshTarget -and (Test-Path -LiteralPath $connectionFile)){$SshTarget=(Get-Content -LiteralPath $connectionFile -Raw | ConvertFrom-Json).ssh_target}
if($SshTarget -notmatch '^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+$'){throw 'Set ISN_PREVIEW_SSH_TARGET or the ignored .tmp/cinematic-preview-connection.json ssh_target before using the preview.'}
$key=Join-Path $repoRoot '.tmp/isn-live.pem'
$archive=Join-Path $repoRoot '.tmp/cinematic-preview-theme.tar.gz'
$stage='/tmp/isn-cinematic-preview-20261106'
Push-Location $repoRoot
try {
    & tar -czf $archive -C wp-content/themes infosecnexus
    if($LASTEXITCODE -ne 0){throw 'Preview theme archive failed.'}
    & scp -i $key -o BatchMode=yes $archive ($SshTarget+':/tmp/isn-cinematic-preview-theme.tar.gz')
    if($LASTEXITCODE -ne 0){throw 'Preview theme transfer failed.'}
    & ssh -i $key -o BatchMode=yes $SshTarget "mkdir -p $stage/wp-content/themes; tar -xzf /tmp/isn-cinematic-preview-theme.tar.gz -C $stage/wp-content/themes; touch $stage/.theme-synced"
    if($LASTEXITCODE -ne 0){throw 'Preview theme sync failed.'}
    if($Start){
        & ssh -i $key -o BatchMode=yes $SshTarget "if ! test -f $stage/.server.pid || ! kill -0 `$(cat $stage/.server.pid) 2>/dev/null; then cd $stage; PHP_CLI_SERVER_WORKERS=4 nohup php -S 127.0.0.1:18889 -t $stage /tmp/isn-preview-router.php > /tmp/isn-cinematic-preview.log 2>&1 < /dev/null & echo `$! > $stage/.server.pid; fi"
        $existing=Get-NetTCPConnection -LocalAddress 127.0.0.1 -LocalPort 8890 -State Listen -ErrorAction SilentlyContinue
        if(-not $existing){
            $arguments=@('-i',('"'+$key+'"'),'-o','BatchMode=yes','-o','ExitOnForwardFailure=yes','-o','ServerAliveInterval=30','-N','-L','127.0.0.1:8890:127.0.0.1:18889',$SshTarget)
            $process=Start-Process -FilePath 'ssh.exe' -ArgumentList $arguments -WindowStyle Hidden -PassThru -RedirectStandardError (Join-Path $repoRoot '.tmp/cinematic-preview-tunnel.log')
            $process.Id | Set-Content (Join-Path $repoRoot '.tmp/cinematic-preview-tunnel.pid') -Encoding ASCII
        }
    }
    Write-Output 'Preview: http://localhost:8890/'
    Write-Output 'Production was not modified.'
} finally {Pop-Location}
