param(
    [string]$ProjectRoot = '',
    [switch]$SkipDb
)

. (Join-Path $PSScriptRoot 'common.ps1')

$exitCode = Invoke-RunTests -ProjectRoot $ProjectRoot -SkipDb:$SkipDb
exit $exitCode
