param(
    [string]$BaseUrl = 'http://localhost/edats'
)

. (Join-Path $PSScriptRoot 'common.ps1')

$exitCode = Invoke-SmokeTests -BaseUrl $BaseUrl
exit $exitCode
