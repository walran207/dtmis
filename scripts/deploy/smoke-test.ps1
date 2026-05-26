param(
    [string]$BaseUrl = 'http://localhost/DTMIS'
)

. (Join-Path $PSScriptRoot 'common.ps1')

$exitCode = Invoke-SmokeTests -BaseUrl $BaseUrl
exit $exitCode
