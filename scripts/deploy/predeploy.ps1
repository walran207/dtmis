param(
    [string]$ProjectRoot = '',
    [string]$BaseUrl = 'http://localhost/edats',
    [switch]$SkipDb,
    [switch]$SkipSmoke
)

. (Join-Path $PSScriptRoot 'common.ps1')

$exitCode = Invoke-Predeploy -ProjectRoot $ProjectRoot -BaseUrl $BaseUrl -SkipDb:$SkipDb -SkipSmoke:$SkipSmoke
exit $exitCode
