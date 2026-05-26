Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Resolve-ProjectRoot {
    param(
        [string]$ProjectRoot
    )

    if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
        return (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
    }

    return (Resolve-Path $ProjectRoot).Path
}

function Write-Section {
    param(
        [string]$Title
    )

    Write-Host ''
    Write-Host "== $Title =="
}

function Get-PHPCommand {
    $php = Get-Command php -ErrorAction SilentlyContinue
    if ($null -eq $php) {
        return $null
    }

    return $php.Source
}

function Get-LintTargetFiles {
    param(
        [string]$ProjectRoot
    )

    $excludedSegments = @(
        '\legacy code\',
        '\docs\',
        '\.vs\',
        '\scripts\debug\'
    )

    $allPhpFiles = Get-ChildItem -Path $ProjectRoot -Recurse -File -Filter *.php

    return $allPhpFiles | Where-Object {
        $fullName = $_.FullName.ToLowerInvariant()
        foreach ($segment in $excludedSegments) {
            if ($fullName.Contains($segment)) {
                return $false
            }
        }
        return $true
    }
}

function Test-PHPFileLint {
    param(
        [string]$PhpCommand,
        [string]$FilePath
    )

    $output = & $PhpCommand -l $FilePath 2>&1
    $exitCode = $LASTEXITCODE

    return [pscustomobject]@{
        ExitCode = $exitCode
        Output   = ($output -join [Environment]::NewLine).Trim()
    }
}

function Test-RequiredFiles {
    param(
        [string]$ProjectRoot
    )

    $requiredFiles = @(
        'index.php',
        'auth/login.php',
        'config/app.php',
        'config/database.php',
        'app/pages/dashboard.php',
        'app/pages/tracking-slip.php',
        'app/pages/print-package.php'
    )

    $missing = @()
    foreach ($relative in $requiredFiles) {
        $fullPath = Join-Path $ProjectRoot $relative
        if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
            $missing += $relative
        }
    }

    return $missing
}

function Test-WritableDirectories {
    param(
        [string]$ProjectRoot
    )

    $requiredDirectories = @(
        'storage',
        'storage/uploads',
        'storage/signatures'
    )

    $issues = @()
    foreach ($relative in $requiredDirectories) {
        $fullPath = Join-Path $ProjectRoot $relative
        if (-not (Test-Path -LiteralPath $fullPath -PathType Container)) {
            $issues += "Missing directory: $relative"
            continue
        }

        $probeFileName = ".write-test-$([Guid]::NewGuid().ToString('N')).tmp"
        $probePath = Join-Path $fullPath $probeFileName

        try {
            Set-Content -Path $probePath -Value 'ok' -Encoding ASCII
            Remove-Item -LiteralPath $probePath -Force
        } catch {
            $issues += "Not writable: $relative"
        }
    }

    return $issues
}

function Test-DatabaseConnectivity {
    param(
        [string]$PhpCommand,
        [string]$ProjectRoot
    )

    $dbConfigPath = Join-Path $ProjectRoot 'config/database.php'
    $dbConfigForPhp = $dbConfigPath.Replace('\', '/').Replace("'", "\'")

    $phpSnippetTemplate = @'
require '__DB_CONFIG__';
try {
    $pdo = getDatabaseConnection();
    $pdo->query('SELECT 1');
    echo 'DB_OK';
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage());
    exit(1);
}
'@

    $phpSnippet = $phpSnippetTemplate.Replace('__DB_CONFIG__', $dbConfigForPhp)
    $output = & $PhpCommand -r $phpSnippet 2>&1
    $exitCode = $LASTEXITCODE

    return [pscustomobject]@{
        ExitCode = $exitCode
        Output   = ($output -join [Environment]::NewLine).Trim()
    }
}

function Invoke-RunTests {
    param(
        [string]$ProjectRoot = '',
        [switch]$SkipDb
    )

    $root = Resolve-ProjectRoot -ProjectRoot $ProjectRoot
    $failed = $false

    Write-Section -Title 'run-tests'
    Write-Host "Project root: $root"

    $phpCommand = Get-PHPCommand
    if ($null -eq $phpCommand) {
        Write-Host '[FAIL] PHP CLI not found in PATH.'
        return 1
    }

    Write-Host "[PASS] PHP CLI found: $phpCommand"

    $phpVersion = & $phpCommand -v 2>&1 | Select-Object -First 1
    Write-Host "PHP version: $phpVersion"

    Write-Section -Title 'PHP lint'
    $lintTargets = @(Get-LintTargetFiles -ProjectRoot $root)
    if ($lintTargets.Count -eq 0) {
        Write-Host '[FAIL] No PHP files found for linting.'
        return 1
    }

    $lintFailures = @()
    $index = 0
    foreach ($file in $lintTargets) {
        $index++
        if ($index % 50 -eq 0 -or $index -eq $lintTargets.Count) {
            Write-Host "Lint progress: $index / $($lintTargets.Count)"
        }

        $result = Test-PHPFileLint -PhpCommand $phpCommand -FilePath $file.FullName
        if ($result.ExitCode -ne 0) {
            $relativePath = $file.FullName.Substring($root.Length).TrimStart('\', '/')
            $lintFailures += [pscustomobject]@{
                File   = $relativePath
                Output = $result.Output
            }
        }
    }

    if ($lintFailures.Count -eq 0) {
        Write-Host "[PASS] PHP lint passed ($($lintTargets.Count) files)."
    } else {
        $failed = $true
        Write-Host "[FAIL] PHP lint failed ($($lintFailures.Count) file(s))."
        foreach ($failure in $lintFailures) {
            Write-Host "- $($failure.File)"
            if ($failure.Output -ne '') {
                Write-Host "  $($failure.Output)"
            }
        }
    }

    Write-Section -Title 'Required files'
    $missingFiles = @(Test-RequiredFiles -ProjectRoot $root)
    if ($missingFiles.Count -eq 0) {
        Write-Host '[PASS] Required deployment files are present.'
    } else {
        $failed = $true
        Write-Host '[FAIL] Missing required files:'
        foreach ($file in $missingFiles) {
            Write-Host "- $file"
        }
    }

    Write-Section -Title 'Writable directories'
    $directoryIssues = @(Test-WritableDirectories -ProjectRoot $root)
    if ($directoryIssues.Count -eq 0) {
        Write-Host '[PASS] Storage directories are writable.'
    } else {
        $failed = $true
        Write-Host '[FAIL] Directory checks failed:'
        foreach ($issue in $directoryIssues) {
            Write-Host "- $issue"
        }
    }

    Write-Section -Title 'Database connectivity'
    if ($SkipDb) {
        Write-Host '[SKIP] Database check skipped by flag.'
    } else {
        $dbResult = Test-DatabaseConnectivity -PhpCommand $phpCommand -ProjectRoot $root
        if ($dbResult.ExitCode -eq 0 -and $dbResult.Output -match 'DB_OK') {
            Write-Host '[PASS] Database connectivity check passed.'
        } else {
            $failed = $true
            Write-Host '[FAIL] Database connectivity check failed.'
            if ($dbResult.Output -ne '') {
                Write-Host $dbResult.Output
            }
        }
    }

    Write-Section -Title 'run-tests summary'
    if ($failed) {
        Write-Host 'run-tests FAILED'
        return 1
    }

    Write-Host 'run-tests PASSED'
    return 0
}

function Invoke-SmokeRequest {
    param(
        [string]$Url,
        [int]$TimeoutSeconds = 20
    )

    $response = $null
    $request = [System.Net.HttpWebRequest]::Create($Url)
    $request.Method = 'GET'
    $request.AllowAutoRedirect = $false
    $request.Timeout = $TimeoutSeconds * 1000
    $request.ReadWriteTimeout = $TimeoutSeconds * 1000
    $request.UserAgent = 'DTMIS-smoke-test'

    try {
        $response = [System.Net.HttpWebResponse]$request.GetResponse()
        $location = ''
        if ($response.Headers['Location']) {
            $location = [string]$response.Headers['Location']
        }

        $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
        $body = $reader.ReadToEnd()
        $reader.Close()

        return [pscustomobject]@{
            StatusCode = [int]$response.StatusCode
            Location   = $location
            Body       = $body
            Error      = ''
        }
    } catch [System.Net.WebException] {
        $webException = $_.Exception
        if ($null -eq $webException.Response) {
            return [pscustomobject]@{
                StatusCode = 0
                Location   = ''
                Body       = ''
                Error      = $webException.Message
            }
        }

        $response = [System.Net.HttpWebResponse]$webException.Response
        $location = ''
        if ($response.Headers['Location']) {
            $location = [string]$response.Headers['Location']
        }

        $body = ''
        $stream = $response.GetResponseStream()
        if ($null -ne $stream) {
            $reader = New-Object System.IO.StreamReader($stream)
            $body = $reader.ReadToEnd()
            $reader.Close()
        }

        return [pscustomobject]@{
            StatusCode = [int]$response.StatusCode
            Location   = $location
            Body       = $body
            Error      = $webException.Message
        }
    } catch {
        return [pscustomobject]@{
            StatusCode = 0
            Location   = ''
            Body       = ''
            Error      = $_.Exception.Message
        }
    } finally {
        if ($null -ne $response) {
            $response.Close()
        }
    }
}

function Join-BaseUrlPath {
    param(
        [string]$BaseUrl,
        [string]$Path
    )

    $left = $BaseUrl.TrimEnd('/')
    $right = $Path.TrimStart('/')
    return "$left/$right"
}

function Invoke-SmokeTests {
    param(
        [string]$BaseUrl = 'http://localhost/DTMIS'
    )

    Write-Section -Title 'smoke-test'
    Write-Host "Base URL: $BaseUrl"

    $tests = @(
        [pscustomobject]@{
            Name = 'Index redirects to login'
            Path = 'index.php'
            AllowedStatus = @(301, 302)
            RequireLocationContains = '/auth/login.php'
        },
        [pscustomobject]@{
            Name = 'Login page loads'
            Path = 'auth/login.php'
            AllowedStatus = @(200)
            RequireBodyContains = 'csrf_token'
        },
        [pscustomobject]@{
            Name = 'Dashboard is protected for guests'
            Path = 'app/pages/dashboard.php'
            AllowedStatus = @(301, 302)
            RequireLocationContains = '/auth/login.php'
        },
        [pscustomobject]@{
            Name = 'Tracking slip endpoint responds'
            Path = 'app/pages/tracking-slip.php'
            AllowedStatus = @(200, 301, 302, 400, 401, 403)
        }
    )

    $failures = @()
    foreach ($test in $tests) {
        $url = Join-BaseUrlPath -BaseUrl $BaseUrl -Path $test.Path
        $response = Invoke-SmokeRequest -Url $url

        $statusOk = $test.AllowedStatus -contains $response.StatusCode
        $locationOk = $true
        $bodyOk = $true

        if ($test.PSObject.Properties.Name -contains 'RequireLocationContains') {
            $needle = [string]$test.RequireLocationContains
            $locationOk = $response.Location -like "*$needle*"
        }

        if ($test.PSObject.Properties.Name -contains 'RequireBodyContains') {
            $bodyNeedle = [string]$test.RequireBodyContains
            $bodyOk = $response.Body -like "*$bodyNeedle*"
        }

        if ($statusOk -and $locationOk -and $bodyOk) {
            Write-Host "[PASS] $($test.Name) (status: $($response.StatusCode))"
            continue
        }

        $failureParts = @()
        if (-not $statusOk) {
            $failureParts += "status $($response.StatusCode) not in [$($test.AllowedStatus -join ', ')]"
        }
        if (-not $locationOk) {
            $failureParts += "location '$($response.Location)' missing '$($test.RequireLocationContains)'"
        }
        if (-not $bodyOk) {
            $failureParts += "response body missing '$($test.RequireBodyContains)'"
        }
        if ($response.Error -ne '') {
            $failureParts += "error: $($response.Error)"
        }

        $failures += "$($test.Name): $($failureParts -join '; ')"
        Write-Host "[FAIL] $($test.Name)"
    }

    Write-Section -Title 'smoke-test summary'
    if ($failures.Count -eq 0) {
        Write-Host 'smoke-test PASSED'
        return 0
    }

    Write-Host 'smoke-test FAILED'
    foreach ($failure in $failures) {
        Write-Host "- $failure"
    }
    return 1
}

function Invoke-Predeploy {
    param(
        [string]$ProjectRoot = '',
        [string]$BaseUrl = 'http://localhost/DTMIS',
        [switch]$SkipDb,
        [switch]$SkipSmoke
    )

    Write-Section -Title 'predeploy'

    $runTestsExitCode = Invoke-RunTests -ProjectRoot $ProjectRoot -SkipDb:$SkipDb
    if ($runTestsExitCode -ne 0) {
        Write-Host 'predeploy FAILED (run-tests failed)'
        return 1
    }

    if ($SkipSmoke) {
        Write-Host '[SKIP] smoke-test skipped by flag.'
        Write-Host 'predeploy PASSED'
        return 0
    }

    $smokeExitCode = Invoke-SmokeTests -BaseUrl $BaseUrl
    if ($smokeExitCode -ne 0) {
        Write-Host 'predeploy FAILED (smoke-test failed)'
        return 1
    }

    Write-Host 'predeploy PASSED'
    return 0
}
