$ErrorActionPreference = "Stop"
Set-Location "D:\www\dtmis"

git config --global --add safe.directory D:/www/dtmis

if (!(Test-Path ".git")) { throw "No .git folder in D:\www\dtmis" }

git fetch origin main --prune
if ($LASTEXITCODE -ne 0) { throw "git fetch failed" }

$deployed = (git rev-parse HEAD).Trim()
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($deployed)) { throw "Unable to read deployed commit (HEAD)." }

$latest = (git rev-parse origin/main).Trim()
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($latest)) { throw "Unable to read latest commit (origin/main)." }

if ($deployed -ne $latest) {
    $pendingCount = (git rev-list --count HEAD..origin/main).Trim()
    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($pendingCount)) { $pendingCount = "unknown" }

    Write-Host "PENDING DEPLOY: production is behind main."
    Write-Host "Current (HEAD): $deployed"
    Write-Host "Latest (origin/main): $latest"
    Write-Host "Commits pending: $pendingCount"
    exit 1
}

Write-Host "UP TO DATE: production matches origin/main."
Write-Host "Commit: $deployed"
