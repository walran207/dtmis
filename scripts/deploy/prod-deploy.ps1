$ErrorActionPreference = "Stop"
Set-Location "D:\www\dtmis"

if (!(Test-Path ".git")) { throw "No .git folder in D:\www\dtmis" }

git fetch origin; if ($LASTEXITCODE -ne 0) { throw "git fetch failed" }
git reset --hard origin/main; if ($LASTEXITCODE -ne 0) { throw "git reset failed" }
git clean -fd -e "dtmis-attachments/" -e "app/storage/"; if ($LASTEXITCODE -ne 0) { throw "git clean failed" }

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
if ($LASTEXITCODE -ne 0) { throw "composer install failed" }

# Optional IIS restart
# Import-Module WebAdministration
# Restart-WebAppPool -Name "YourAppPoolName"