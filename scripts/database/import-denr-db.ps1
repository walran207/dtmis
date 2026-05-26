[CmdletBinding()]
param(
    [string]$DatabaseName = 'denr_db',
    [string]$DbHost = '127.0.0.1',
    [int]$Port = 1433,
    [string]$User = 'sa',
    [string]$Password = '',
    [string]$SqlCmdExe = '',
    [switch]$UseTrustedConnection,
    [switch]$KeepExistingDatabase
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$scriptRoot = Split-Path -Parent $PSCommandPath
$repoRoot = Split-Path -Parent (Split-Path -Parent $scriptRoot)
$sqlServerDumpPath = Join-Path $repoRoot 'denr_db_sqlserver.sql'

function Assert-DatabaseNameSafe {
    param([string]$Name)

    if ([string]::IsNullOrWhiteSpace($Name)) {
        throw 'Database name cannot be empty.'
    }

    if ($Name -notmatch '^[A-Za-z0-9_][A-Za-z0-9_-]{0,127}$') {
        throw "Unsafe database name: $Name"
    }
}

function Resolve-SqlCmdExe {
    param([string]$PreferredPath)

    if ($PreferredPath -and (Test-Path -LiteralPath $PreferredPath)) {
        return (Resolve-Path -LiteralPath $PreferredPath).Path
    }

    $command = Get-Command sqlcmd.exe -ErrorAction SilentlyContinue
    if ($command) {
        return $command.Source
    }

    $roots = @(
        'C:\Program Files\Microsoft SQL Server',
        'C:\Program Files\Microsoft SQL Server\Client SDK',
        'C:\Program Files\Microsoft SQL Server\150\Tools\Binn',
        'C:\Program Files\Microsoft SQL Server\160\Tools\Binn',
        'C:\Program Files\Microsoft SQL Server\170\Tools\Binn'
    ) | Where-Object { Test-Path -LiteralPath $_ }

    foreach ($root in $roots) {
        $candidate = Get-ChildItem -Path $root -Recurse -Filter sqlcmd.exe -ErrorAction SilentlyContinue |
            Sort-Object FullName -Descending |
            Select-Object -First 1
        if ($candidate) {
            return $candidate.FullName
        }
    }

    throw 'Unable to find sqlcmd.exe. Install SQL Server Command Line Utilities or pass -SqlCmdExe with the full path.'
}

function New-CombinedSqlServerImportFile {
    param(
        [string]$TargetDatabase,
        [switch]$KeepDatabase
    )

    if (-not (Test-Path -LiteralPath $sqlServerDumpPath)) {
        throw "Base SQL Server dump not found: $sqlServerDumpPath"
    }

    $combinedPath = Join-Path ([System.IO.Path]::GetTempPath()) ("{0}_{1:yyyyMMddHHmmss}_sqlserver_import.sql" -f $TargetDatabase, (Get-Date))
    $dumpContent = Get-Content -LiteralPath $sqlServerDumpPath -Raw
    $escapedDatabaseName = $TargetDatabase.Replace(']', ']]')

    $dumpContent = $dumpContent.Replace(
        "IF DB_ID(N'denr_db') IS NULL CREATE DATABASE [denr_db];",
        "IF DB_ID(N'$TargetDatabase') IS NULL CREATE DATABASE [$escapedDatabaseName];"
    )
    $dumpContent = $dumpContent.Replace(
        'USE [denr_db];',
        "USE [$escapedDatabaseName];"
    )

    $header = @(
        'SET NOCOUNT ON;',
        'GO'
    )

    if (-not $KeepDatabase) {
        $header += @(
            'USE [master];',
            'GO',
            "IF DB_ID(N'$TargetDatabase') IS NOT NULL",
            'BEGIN',
            "    ALTER DATABASE [$escapedDatabaseName] SET SINGLE_USER WITH ROLLBACK IMMEDIATE;",
            "    DROP DATABASE [$escapedDatabaseName];",
            'END',
            'GO'
        )
    }

    Set-Content -LiteralPath $combinedPath -Value $header -Encoding UTF8
    Add-Content -LiteralPath $combinedPath -Value $dumpContent -Encoding UTF8

    return $combinedPath
}

function Get-SqlServerAuthArgs {
    param(
        [string]$ServerName,
        [int]$ServerPort,
        [string]$Login,
        [string]$Secret,
        [switch]$TrustedConnection
    )

    $args = @(
        '-S', "$ServerName,$ServerPort",
        '-b'
    )

    if ($TrustedConnection) {
        $args += '-E'
        return $args
    }

    if ([string]::IsNullOrWhiteSpace($Login)) {
        throw 'SQL Server username is required unless -UseTrustedConnection is provided.'
    }

    $args += @('-U', $Login)
    if ($Secret -ne '') {
        $args += @('-P', $Secret)
    }

    return $args
}

Assert-DatabaseNameSafe -Name $DatabaseName

$sqlcmdPath = Resolve-SqlCmdExe -PreferredPath $SqlCmdExe
$combinedImportPath = New-CombinedSqlServerImportFile -TargetDatabase $DatabaseName -KeepDatabase:$KeepExistingDatabase
$sqlcmdAuthArgs = Get-SqlServerAuthArgs -ServerName $DbHost -ServerPort $Port -Login $User -Secret $Password -TrustedConnection:$UseTrustedConnection
$sqlcmdImportArgs = $sqlcmdAuthArgs + @('-i', $combinedImportPath)

Write-Host "Using sqlcmd client: $sqlcmdPath"
Write-Host 'Engine: SQL Server'
Write-Host "Target database: $DatabaseName"
Write-Host "Base dump: $sqlServerDumpPath"
Write-Host "Running combined import file: $combinedImportPath"
if ($KeepExistingDatabase) {
    Write-Host 'Preserving existing database before import.'
} else {
    Write-Host 'Dropping existing database before import.'
}

$importOutput = & $sqlcmdPath @sqlcmdImportArgs 2>&1
$exitCode = $LASTEXITCODE

if ($importOutput) {
    $importOutput | Write-Host
}

if ($exitCode -ne 0) {
    throw "SQL Server import failed with exit code $exitCode."
}

$summaryQuery = @"
SET NOCOUNT ON;
SELECT N'roles' AS table_name, COUNT(*) AS row_count FROM dbo.roles
UNION ALL
SELECT N'offices', COUNT(*) FROM dbo.offices
UNION ALL
SELECT N'users', COUNT(*) FROM dbo.users
UNION ALL
SELECT N'role_unit_mappings', COUNT(*) FROM dbo.role_unit_mappings
UNION ALL
SELECT N'workflow_transitions', COUNT(*) FROM dbo.workflow_transitions
UNION ALL
SELECT N'documents', COUNT(*) FROM dbo.documents;
"@

Write-Host ''
Write-Host 'Import complete. Current row counts:'
& $sqlcmdPath @sqlcmdAuthArgs -d $DatabaseName -Q $summaryQuery
