<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/role-office-rules.php';
require_once dirname(__DIR__, 2) . '/config/super-admin.php';
require_once dirname(__DIR__, 2) . '/config/user-recycle-bin.php';
require_once dirname(__DIR__, 2) . '/auth/security.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

if (!super_admin_has_access()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Super Admin access required.']);
    exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

function super_admin_import_normalize_key(string $value): string
{
    $normalized = strtolower(trim($value));
    $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
    return trim($normalized, '_');
}

function super_admin_import_row_is_empty(array $row): bool
{
    foreach ($row as $cell) {
        if (trim((string)$cell) !== '') {
            return false;
        }
    }

    return true;
}

function super_admin_import_parse_csv(string $filePath): array
{
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Unable to open the uploaded CSV file.');
    }

    $rows = [];
    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = array_map(static fn($value): string => trim((string)$value), $row);
    }
    fclose($handle);

    return $rows;
}

function super_admin_import_column_letters_to_index(string $letters): int
{
    $letters = strtoupper(trim($letters));
    $index = 0;
    $length = strlen($letters);
    for ($i = 0; $i < $length; $i += 1) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }

    return max(0, $index - 1);
}

function super_admin_import_extract_shared_string(SimpleXMLElement $item, array $namespaces): string
{
    $mainNamespace = $namespaces[''] ?? ($namespaces['main'] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $itemChildren = $item->children($mainNamespace);
    $text = '';
    if (isset($itemChildren->t)) {
        $text .= (string)$itemChildren->t;
    }

    foreach ($itemChildren->r as $run) {
        $runChildren = $run->children($mainNamespace);
        if (isset($runChildren->t)) {
            $text .= (string)$runChildren->t;
            continue;
        }
    }

    return trim($text);
}

function super_admin_import_resolve_powershell_command(): ?string
{
    static $resolvedCommand = false;

    if ($resolvedCommand !== false) {
        return is_string($resolvedCommand) ? $resolvedCommand : null;
    }

    if (PHP_OS_FAMILY !== 'Windows' || !function_exists('exec')) {
        $resolvedCommand = null;
        return null;
    }

    foreach (['powershell', 'pwsh'] as $candidate) {
        $output = [];
        $exitCode = 0;
        @exec(
            $candidate . ' -NoProfile -Command "[Console]::OutputEncoding = [System.Text.UTF8Encoding]::new($false); Write-Output \'ready\'" 2>&1',
            $output,
            $exitCode
        );

        if ($exitCode === 0 && trim(implode("\n", $output)) === 'ready') {
            $resolvedCommand = $candidate;
            return $candidate;
        }
    }

    $resolvedCommand = null;
    return null;
}

function super_admin_import_xlsx_supported(): bool
{
    return class_exists('ZipArchive') || super_admin_import_resolve_powershell_command() !== null;
}

function super_admin_import_parse_xlsx_via_powershell(string $filePath): array
{
    $powershellCommand = super_admin_import_resolve_powershell_command();
    if ($powershellCommand === null) {
        throw new RuntimeException('Excel import is not available because the server cannot open .xlsx files right now. Please import a CSV file instead.');
    }

    $script = <<<'POWERSHELL'
param(
    [Parameter(Mandatory = $true)]
    [string]$FilePath
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.UTF8Encoding]::new($false)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Get-ZipEntryText {
    param(
        [System.IO.Compression.ZipArchive]$Archive,
        [string]$EntryName
    )

    $entry = $Archive.GetEntry($EntryName)
    if ($null -eq $entry) {
        return $null
    }

    $stream = $entry.Open()
    try {
        $reader = [System.IO.StreamReader]::new($stream, [System.Text.UTF8Encoding]::new($false))
        try {
            return $reader.ReadToEnd()
        } finally {
            $reader.Dispose()
        }
    } finally {
        $stream.Dispose()
    }
}

function Get-CellText {
    param([System.Xml.XmlNode]$Node)

    if ($null -eq $Node) {
        return ''
    }

    $textNodes = @($Node.SelectNodes(".//*[local-name()='t']"))
    if ($textNodes.Count -gt 0) {
        $parts = @()
        foreach ($textNode in $textNodes) {
            $parts += [string]$textNode.InnerText
        }
        return ([string]::Join('', $parts)).Trim()
    }

    return ([string]$Node.InnerText).Trim()
}

function Get-ColumnIndex {
    param([string]$Reference)

    if ([string]::IsNullOrWhiteSpace($Reference)) {
        return -1
    }

    $match = [regex]::Match($Reference, '[A-Za-z]+')
    if (-not $match.Success) {
        return -1
    }

    $letters = $match.Value.ToUpperInvariant()
    $index = 0
    foreach ($character in $letters.ToCharArray()) {
        $index = ($index * 26) + ([int][char]$character - 64)
    }

    return [Math]::Max(0, $index - 1)
}

$archive = [System.IO.Compression.ZipFile]::OpenRead($FilePath)
try {
    $workbookText = Get-ZipEntryText -Archive $archive -EntryName 'xl/workbook.xml'
    $relationshipsText = Get-ZipEntryText -Archive $archive -EntryName 'xl/_rels/workbook.xml.rels'
    if ([string]::IsNullOrWhiteSpace($workbookText) -or [string]::IsNullOrWhiteSpace($relationshipsText)) {
        throw 'The uploaded Excel file is missing workbook metadata.'
    }

    [xml]$workbook = $workbookText
    [xml]$relationships = $relationshipsText

    $firstSheet = $workbook.SelectSingleNode("/*[local-name()='workbook']/*[local-name()='sheets']/*[local-name()='sheet'][1]")
    if ($null -eq $firstSheet) {
        throw 'The uploaded Excel file does not contain any worksheets.'
    }

    $sheetRelationshipId = $firstSheet.GetAttribute('id', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships')
    if ([string]::IsNullOrWhiteSpace($sheetRelationshipId)) {
        throw 'Unable to resolve the first worksheet in the uploaded Excel file.'
    }

    $sheetTarget = ''
    foreach ($relationship in $relationships.SelectNodes("/*[local-name()='Relationships']/*[local-name()='Relationship']")) {
        if ($relationship.GetAttribute('Id') -eq $sheetRelationshipId) {
            $sheetTarget = [string]$relationship.GetAttribute('Target')
            break
        }
    }

    if ([string]::IsNullOrWhiteSpace($sheetTarget)) {
        throw 'Unable to locate the first worksheet in the uploaded Excel file.'
    }

    $sheetPath = if ($sheetTarget.StartsWith('/')) {
        $sheetTarget.TrimStart('/')
    } else {
        'xl/' + (($sheetTarget -replace '\\', '/').TrimStart('/'))
    }

    $sheetText = Get-ZipEntryText -Archive $archive -EntryName $sheetPath
    if ([string]::IsNullOrWhiteSpace($sheetText)) {
        throw 'Unable to read the first worksheet in the uploaded Excel file.'
    }

    $sharedStrings = @()
    $sharedStringsText = Get-ZipEntryText -Archive $archive -EntryName 'xl/sharedStrings.xml'
    if (-not [string]::IsNullOrWhiteSpace($sharedStringsText)) {
        [xml]$sharedStringsDocument = $sharedStringsText
        foreach ($item in $sharedStringsDocument.SelectNodes("/*[local-name()='sst']/*[local-name()='si']")) {
            $sharedStrings += ,(Get-CellText -Node $item)
        }
    }
} finally {
    $archive.Dispose()
}

[xml]$sheet = $sheetText
$rowNodes = $sheet.SelectNodes("/*[local-name()='worksheet']/*[local-name()='sheetData']/*[local-name()='row']")
$rows = New-Object System.Collections.ArrayList

if ($null -ne $rowNodes) {
    foreach ($rowNode in $rowNodes) {
        $cells = @{}
        $maxIndex = -1
        $positionIndex = 0
        $cellNodes = @($rowNode.SelectNodes("./*[local-name()='c']"))

        foreach ($cellNode in $cellNodes) {
            $reference = [string]$cellNode.GetAttribute('r')
            $type = [string]$cellNode.GetAttribute('t')
            $columnIndex = Get-ColumnIndex -Reference $reference
            if ($columnIndex -lt 0) {
                $columnIndex = $positionIndex
            }

            $value = ''
            if ($type -eq 'inlineStr') {
                $value = Get-CellText -Node ($cellNode.SelectSingleNode("./*[local-name()='is']"))
            } elseif ($type -eq 's') {
                $valueNode = $cellNode.SelectSingleNode("./*[local-name()='v']")
                $sharedIndex = if ($null -ne $valueNode -and $valueNode.InnerText -match '^-?\d+$') { [int]$valueNode.InnerText } else { -1 }
                if ($sharedIndex -ge 0 -and $sharedIndex -lt $sharedStrings.Count) {
                    $value = [string]$sharedStrings[$sharedIndex]
                }
            } else {
                $valueNode = $cellNode.SelectSingleNode("./*[local-name()='v']")
                if ($null -ne $valueNode) {
                    $value = [string]$valueNode.InnerText
                }
            }

            $cells[$columnIndex] = $value.Trim()
            if ($columnIndex -gt $maxIndex) {
                $maxIndex = $columnIndex
            }

            $positionIndex += 1
        }

        if ($maxIndex -lt 0) {
            [void]$rows.Add(@())
            continue
        }

        $normalizedRow = New-Object System.Collections.ArrayList
        for ($columnIndex = 0; $columnIndex -le $maxIndex; $columnIndex += 1) {
            $cellValue = if ($cells.ContainsKey($columnIndex)) { [string]$cells[$columnIndex] } else { '' }
            [void]$normalizedRow.Add($cellValue.Trim())
        }

        [void]$rows.Add($normalizedRow.ToArray())
    }
}

[Console]::Write((ConvertTo-Json -InputObject $rows.ToArray() -Depth 6 -Compress))
POWERSHELL;

    $scriptBasePath = tempnam(sys_get_temp_dir(), 'DTMIS_xlsx_import_');
    if ($scriptBasePath === false) {
        throw new RuntimeException('Unable to allocate a temporary Excel import script.');
    }

    $scriptPath = $scriptBasePath . '.ps1';
    if (file_put_contents($scriptPath, $script) === false) {
        @unlink($scriptBasePath);
        throw new RuntimeException('Unable to prepare the Excel import helper.');
    }

    $command = $powershellCommand
        . ' -NoProfile -ExecutionPolicy Bypass -File ' . escapeshellarg($scriptPath)
        . ' -FilePath ' . escapeshellarg($filePath);

    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    @unlink($scriptPath);
    @unlink($scriptBasePath);

    if ($exitCode !== 0) {
        $message = trim(implode("\n", $output));
        if ($message === '') {
            $message = 'Unknown Excel parsing failure.';
        }

        throw new RuntimeException('Unable to read the uploaded Excel file. ' . $message);
    }

    $json = trim(implode("\n", $output));
    if ($json === '') {
        return [];
    }

    $decodedRows = json_decode($json, true);
    if (!is_array($decodedRows)) {
        throw new RuntimeException('Unable to read the uploaded Excel file. Invalid parser response.');
    }

    $rows = [];
    foreach ($decodedRows as $row) {
        if (!is_array($row)) {
            $rows[] = [];
            continue;
        }

        $rows[] = array_map(static fn($value): string => trim((string)$value), $row);
    }

    return $rows;
}

function super_admin_import_parse_xlsx(string $filePath): array
{
    if (!class_exists('ZipArchive')) {
        return super_admin_import_parse_xlsx_via_powershell($filePath);
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('Unable to open the uploaded Excel file.');
    }

    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml === false || $relsXml === false) {
        $zip->close();
        throw new RuntimeException('The uploaded Excel file is missing workbook metadata.');
    }

    $workbook = simplexml_load_string($workbookXml);
    $rels = simplexml_load_string($relsXml);
    if ($workbook === false || $rels === false) {
        $zip->close();
        throw new RuntimeException('The uploaded Excel file could not be read.');
    }

    $workbookNamespaces = $workbook->getNamespaces(true);
    $mainNamespace = $workbookNamespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $relationshipsNamespace = $workbookNamespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    $workbookChildren = $workbook->children($mainNamespace);
    $sheetsNode = $workbookChildren->sheets;
    $firstSheet = isset($sheetsNode->sheet[0]) ? $sheetsNode->sheet[0] : null;
    if ($firstSheet === null) {
        $zip->close();
        throw new RuntimeException('The uploaded Excel file does not contain any worksheets.');
    }

    $sheetAttributes = $firstSheet->attributes($relationshipsNamespace);
    $sheetRelationshipId = trim((string)($sheetAttributes['id'] ?? ''));
    if ($sheetRelationshipId === '') {
        $zip->close();
        throw new RuntimeException('Unable to resolve the first worksheet in the uploaded Excel file.');
    }

    $sheetTarget = '';
    foreach ($rels->Relationship as $relationship) {
        $attributes = $relationship->attributes();
        if ((string)($attributes['Id'] ?? '') === $sheetRelationshipId) {
            $sheetTarget = (string)($attributes['Target'] ?? '');
            break;
        }
    }

    if ($sheetTarget === '') {
        $zip->close();
        throw new RuntimeException('Unable to locate the first worksheet in the uploaded Excel file.');
    }

    $sheetPath = str_starts_with($sheetTarget, '/')
        ? ltrim($sheetTarget, '/')
        : 'xl/' . ltrim(str_replace('\\', '/', $sheetTarget), '/');

    $sheetXml = $zip->getFromName($sheetPath);
    if ($sheetXml === false) {
        $zip->close();
        throw new RuntimeException('Unable to read the first worksheet in the uploaded Excel file.');
    }

    $sharedStrings = [];
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsXml !== false) {
        $sharedStringsDocument = simplexml_load_string($sharedStringsXml);
        if ($sharedStringsDocument !== false) {
            $sharedNamespaces = $sharedStringsDocument->getNamespaces(true);
            $sharedChildren = $sharedStringsDocument->children($sharedNamespaces[''] ?? $mainNamespace);
            foreach ($sharedChildren->si as $item) {
                $sharedStrings[] = super_admin_import_extract_shared_string($item, $sharedNamespaces);
            }
        }
    }

    $zip->close();

    $sheet = simplexml_load_string($sheetXml);
    if ($sheet === false) {
        throw new RuntimeException('Unable to parse the first worksheet in the uploaded Excel file.');
    }

    $sheetNamespaces = $sheet->getNamespaces(true);
    $sheetMainNamespace = $sheetNamespaces[''] ?? $mainNamespace;
    $sheetChildren = $sheet->children($sheetMainNamespace);
    $rows = [];
    if (!isset($sheetChildren->sheetData)) {
        return $rows;
    }

    foreach ($sheetChildren->sheetData->row as $rowNode) {
        $rowChildren = $rowNode->children($sheetMainNamespace);
        $cells = [];
        $maxIndex = -1;

        foreach ($rowChildren->c as $cell) {
            $cellChildren = $cell->children($sheetMainNamespace);
            $attributes = $cell->attributes();
            $reference = (string)($attributes['r'] ?? '');
            $type = (string)($attributes['t'] ?? '');
            preg_match('/[A-Z]+/i', $reference, $matches);
            $columnLetters = $matches[0] ?? '';
            $columnIndex = $columnLetters === '' ? count($cells) : super_admin_import_column_letters_to_index($columnLetters);

            $value = '';
            if ($type === 'inlineStr' && isset($cellChildren->is)) {
                $value = super_admin_import_extract_shared_string($cellChildren->is, ['' => $sheetMainNamespace]);
            } elseif ($type === 's') {
                $sharedIndex = (int)($cellChildren->v ?? 0);
                $value = (string)($sharedStrings[$sharedIndex] ?? '');
            } elseif (isset($cellChildren->v)) {
                $value = trim((string)$cellChildren->v);
            }

            $cells[$columnIndex] = $value;
            if ($columnIndex > $maxIndex) {
                $maxIndex = $columnIndex;
            }
        }

        if ($maxIndex < 0) {
            $rows[] = [];
            continue;
        }

        $normalizedRow = [];
        for ($columnIndex = 0; $columnIndex <= $maxIndex; $columnIndex += 1) {
            $normalizedRow[] = isset($cells[$columnIndex]) ? trim((string)$cells[$columnIndex]) : '';
        }
        $rows[] = $normalizedRow;
    }

    return $rows;
}

function super_admin_import_extract_records(array $rows): array
{
    $headerAliases = [
        'first_name' => ['first_name', 'firstname', 'first', 'given_name'],
        'last_name' => ['last_name', 'lastname', 'last', 'surname'],
        'username' => ['username', 'user_name', 'login'],
        'email' => ['email', 'email_address', 'mail'],
        'role' => ['role', 'role_name'],
        'office' => ['office', 'office_name'],
        'is_active' => ['is_active', 'active', 'status'],
    ];

    $headerRowIndex = null;
    $headerMap = [];
    $records = [];
    $errors = [];

    foreach ($rows as $index => $row) {
        if (super_admin_import_row_is_empty($row)) {
            continue;
        }

        if ($headerRowIndex === null) {
            $headerRowIndex = $index + 1;
            foreach ($row as $columnIndex => $header) {
                $normalizedHeader = super_admin_import_normalize_key((string)$header);
                foreach ($headerAliases as $canonicalKey => $aliases) {
                    if (in_array($normalizedHeader, $aliases, true)) {
                        $headerMap[$canonicalKey] = (int)$columnIndex;
                        break;
                    }
                }
            }

            foreach (['first_name', 'last_name', 'email', 'role', 'office'] as $requiredHeader) {
                if (!array_key_exists($requiredHeader, $headerMap)) {
                    $errors[] = sprintf('Missing required column: %s.', $requiredHeader);
                }
            }

            if ($errors !== []) {
                return ['records' => [], 'errors' => $errors];
            }

            continue;
        }

        $record = ['row_number' => $index + 1];
        foreach ($headerMap as $canonicalKey => $columnIndex) {
            $record[$canonicalKey] = trim((string)($row[$columnIndex] ?? ''));
        }
        $records[] = $record;
    }

    if ($headerRowIndex === null) {
        $errors[] = 'The import file is empty.';
    }

    return ['records' => $records, 'errors' => $errors];
}

function super_admin_import_parse_active_value(string $value): ?int
{
    $normalized = strtolower(trim($value));
    if ($normalized === '') {
        return 1;
    }

    if (in_array($normalized, ['1', 'true', 'yes', 'y', 'active'], true)) {
        return 1;
    }

    if (in_array($normalized, ['0', 'false', 'no', 'n', 'inactive'], true)) {
        return 0;
    }

    return null;
}

function super_admin_import_format_errors(array $errors, int $limit = 8): string
{
    if ($errors === []) {
        return 'Import failed.';
    }

    $visibleErrors = array_slice($errors, 0, $limit);
    $message = 'Import failed. ' . implode(' ', $visibleErrors);
    if (count($errors) > $limit) {
        $message .= sprintf(' Plus %d more issue(s).', count($errors) - $limit);
    }

    return $message;
}

try {
    $pdo = getDatabaseConnection();
    $roles = super_admin_fetch_roles($pdo);
    $offices = super_admin_fetch_offices($pdo, 600);
    $officeDirectory = DTMIS_build_office_directory($offices);

    if ($method === 'GET') {
        $users = super_admin_fetch_users($pdo, 600);

        echo json_encode([
            'ok' => true,
            'users' => $users,
            'roles' => $roles,
            'offices' => $offices,
            'office_options_payload' => $officeDirectory['office_options_payload'] ?? [],
            'fixed_office_id_by_role_name' => $officeDirectory['fixed_office_id_by_role_name'] ?? [],
            'xlsx_import_supported' => super_admin_import_xlsx_supported(),
            'server_time' => date('c'),
        ]);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token. Refresh and try again.']);
        exit;
    }

    $action = strtoupper(trim((string)($_POST['action'] ?? '')));

    if ($action === 'CREATE_USER') {
        $defaultPassword = 'denr@12';
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $roleId = (int)($_POST['role_id'] ?? 0);
        $officeId = (int)($_POST['office_id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
        $stringLength = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

        if ($firstName === '' || $lastName === '' || $email === '' || $roleId <= 0 || $officeId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'First name, last name, email, role, and office are required.']);
            exit;
        }

        if ($stringLength($firstName) > 50 || $stringLength($lastName) > 50) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'First name and last name must be 50 characters or fewer.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $stringLength($email) > 100) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Enter a valid email address with 100 characters or fewer.']);
            exit;
        }

        $roleStmt = $pdo->prepare('SELECT TOP (1) id FROM roles WHERE id = :id');
        $roleStmt->execute([':id' => $roleId]);
        $roleExists = (int)$roleStmt->fetchColumn();
        if (!$roleExists) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Selected role does not exist.']);
            exit;
        }

        $roleNameById = [];
        foreach ($roles as $role) {
            $roleNameById[(int)($role['id'] ?? 0)] = (string)($role['name'] ?? '');
        }
        $selectedRoleName = (string)($roleNameById[$roleId] ?? '');
        $roleOfficeError = DTMIS_validate_role_office_assignment($selectedRoleName, (string)$officeId, $officeDirectory);
        if ($roleOfficeError !== '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $roleOfficeError]);
            exit;
        }
        $username = auth_resolve_unique_username($pdo, $firstName, $lastName);

        $emailStmt = $pdo->prepare('SELECT TOP (1) id FROM users WHERE LOWER(email) = LOWER(:email)');
        $emailStmt->execute([':email' => $email]);
        if ((int)$emailStmt->fetchColumn() > 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'That email address is already used by another DTMIS account.']);
            exit;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (
                office_id,
                role_id,
                first_name,
                last_name,
                username,
                email,
                password_hash,
                must_change_password,
                is_seeded_demo,
                password_changed_at,
                failed_login_attempts,
                locked_until,
                last_login_at,
                last_login_ip,
                otp_code,
                otp_expires_at,
                mfa_otp_code,
                mfa_otp_expires_at,
                mfa_failed_attempts,
                mfa_locked_until,
                mfa_resend_count,
                mfa_resend_window_started_at,
                mfa_last_sent_at,
                is_active
             ) VALUES (
                :office_id,
                :role_id,
                :first_name,
                :last_name,
                :username,
                :email,
                :password_hash,
                1,
                0,
                NULL,
                0,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                0,
                NULL,
                0,
                NULL,
                NULL,
                :is_active
             )'
        );
        $stmt->execute([
            ':office_id' => $officeId,
            ':role_id' => $roleId,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => password_hash($defaultPassword, PASSWORD_DEFAULT),
            ':is_active' => $isActive,
        ]);

        echo json_encode([
            'ok' => true,
            'message' => sprintf('Local user account created successfully as @%s. Default password is denr@12 and must be changed on first login.', $username),
            'username' => $username,
            'users' => super_admin_fetch_users($pdo, 600),
            'roles' => $roles,
            'offices' => $offices,
            'office_options_payload' => $officeDirectory['office_options_payload'] ?? [],
            'fixed_office_id_by_role_name' => $officeDirectory['fixed_office_id_by_role_name'] ?? [],
        ]);
        exit;
    }

    if ($action === 'IMPORT_USERS') {
        $defaultPassword = 'denr@12';
        $upload = $_FILES['users_file'] ?? null;
        if (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Choose a CSV or Excel file to import.']);
            exit;
        }

        $originalName = (string)($upload['name'] ?? 'users-import');
        $temporaryPath = (string)($upload['tmp_name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Uploaded file could not be verified.']);
            exit;
        }

        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Only CSV and Excel .xlsx files are supported for user import.']);
            exit;
        }

        try {
            $rawRows = $extension === 'csv'
                ? super_admin_import_parse_csv($temporaryPath)
                : super_admin_import_parse_xlsx($temporaryPath);
        } catch (Throwable $exception) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
            exit;
        }

        $parsedImport = super_admin_import_extract_records($rawRows);
        $importRecords = is_array($parsedImport['records'] ?? null) ? $parsedImport['records'] : [];
        $importErrors = is_array($parsedImport['errors'] ?? null) ? $parsedImport['errors'] : [];
        if ($importErrors !== []) {
            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'message' => super_admin_import_format_errors($importErrors),
                'errors' => $importErrors,
            ]);
            exit;
        }

        if ($importRecords === []) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'The import file does not contain any user rows.']);
            exit;
        }

        if (count($importRecords) > 500) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Import up to 500 users per file only.']);
            exit;
        }

        $roleIdByName = [];
        foreach ($roles as $role) {
            $roleIdByName[super_admin_import_normalize_key((string)($role['name'] ?? ''))] = (int)($role['id'] ?? 0);
        }

        $officeIdByName = [];
        foreach ($offices as $office) {
            $officeIdByName[super_admin_import_normalize_key((string)($office['name'] ?? ''))] = (int)($office['id'] ?? 0);
        }

        $existingRows = $pdo->query('SELECT LOWER(username) AS username_key, LOWER(email) AS email_key FROM users')?->fetchAll() ?: [];
        $existingUsernameKeys = [];
        $existingEmailKeys = [];
        foreach ($existingRows as $existingRow) {
            $usernameKey = trim((string)($existingRow['username_key'] ?? ''));
            $emailKey = trim((string)($existingRow['email_key'] ?? ''));
            if ($usernameKey !== '') {
                $existingUsernameKeys[$usernameKey] = true;
            }
            if ($emailKey !== '') {
                $existingEmailKeys[$emailKey] = true;
            }
        }

        $stringLength = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        $fileUsernameKeys = [];
        $fileEmailKeys = [];
        $validatedRows = [];

        foreach ($importRecords as $record) {
            $rowNumber = (int)($record['row_number'] ?? 0);
            $firstName = trim((string)($record['first_name'] ?? ''));
            $lastName = trim((string)($record['last_name'] ?? ''));
            $email = strtolower(trim((string)($record['email'] ?? '')));
            $roleName = trim((string)($record['role'] ?? ''));
            $officeName = trim((string)($record['office'] ?? ''));
            $activeValue = super_admin_import_parse_active_value((string)($record['is_active'] ?? ''));

            if ($firstName === '' || $lastName === '' || $email === '' || $roleName === '' || $officeName === '') {
                $importErrors[] = sprintf('Row %d: first_name, last_name, email, role, and office are required.', $rowNumber);
                continue;
            }
            if ($stringLength($firstName) > 50 || $stringLength($lastName) > 50) {
                $importErrors[] = sprintf('Row %d: first name and last name must be 50 characters or fewer.', $rowNumber);
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $stringLength($email) > 100) {
                $importErrors[] = sprintf('Row %d: email must be a valid address with 100 characters or fewer.', $rowNumber);
                continue;
            }
            if ($activeValue === null) {
                $importErrors[] = sprintf('Row %d: is_active must be 1, 0, yes, no, active, or inactive.', $rowNumber);
                continue;
            }

            $roleKey = super_admin_import_normalize_key($roleName);
            $roleId = (int)($roleIdByName[$roleKey] ?? 0);
            if ($roleId <= 0) {
                $importErrors[] = sprintf('Row %d: role "%s" does not exist.', $rowNumber, $roleName);
                continue;
            }

            $officeKey = super_admin_import_normalize_key($officeName);
            $officeId = (int)($officeIdByName[$officeKey] ?? 0);
            if ($officeId <= 0) {
                $importErrors[] = sprintf('Row %d: office "%s" does not exist.', $rowNumber, $officeName);
                continue;
            }

            $selectedRoleName = '';
            foreach ($roles as $role) {
                if ((int)($role['id'] ?? 0) === $roleId) {
                    $selectedRoleName = (string)($role['name'] ?? '');
                    break;
                }
            }
            $roleOfficeError = DTMIS_validate_role_office_assignment($selectedRoleName, (string)$officeId, $officeDirectory);
            if ($roleOfficeError !== '') {
                $importErrors[] = sprintf('Row %d: %s', $rowNumber, $roleOfficeError);
                continue;
            }

            if (isset($existingEmailKeys[$email])) {
                $importErrors[] = sprintf('Row %d: email "%s" is already used by another DTMIS account.', $rowNumber, $email);
                continue;
            }
            if (isset($fileEmailKeys[$email])) {
                $importErrors[] = sprintf('Row %d: email "%s" is duplicated within the import file.', $rowNumber, $email);
                continue;
            }

            $username = auth_resolve_unique_username_from_reserved($existingUsernameKeys, $firstName, $lastName);
            $fileUsernameKeys[strtolower($username)] = true;
            $fileEmailKeys[$email] = true;
            $validatedRows[] = [
                'office_id' => $officeId,
                'role_id' => $roleId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $username,
                'email' => $email,
                'is_active' => $activeValue,
            ];
        }

        if ($importErrors !== []) {
            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'message' => super_admin_import_format_errors($importErrors),
                'errors' => $importErrors,
            ]);
            exit;
        }

        $insertStatement = $pdo->prepare(
            'INSERT INTO users (
                office_id,
                role_id,
                first_name,
                last_name,
                username,
                email,
                password_hash,
                must_change_password,
                is_seeded_demo,
                password_changed_at,
                failed_login_attempts,
                locked_until,
                last_login_at,
                last_login_ip,
                otp_code,
                otp_expires_at,
                mfa_otp_code,
                mfa_otp_expires_at,
                mfa_failed_attempts,
                mfa_locked_until,
                mfa_resend_count,
                mfa_resend_window_started_at,
                mfa_last_sent_at,
                is_active
             ) VALUES (
                :office_id,
                :role_id,
                :first_name,
                :last_name,
                :username,
                :email,
                :password_hash,
                1,
                0,
                NULL,
                0,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                0,
                NULL,
                0,
                NULL,
                NULL,
                :is_active
             )'
        );

        $pdo->beginTransaction();
        try {
            foreach ($validatedRows as $validatedRow) {
                $insertStatement->execute([
                    ':office_id' => $validatedRow['office_id'],
                    ':role_id' => $validatedRow['role_id'],
                    ':first_name' => $validatedRow['first_name'],
                    ':last_name' => $validatedRow['last_name'],
                    ':username' => $validatedRow['username'],
                    ':email' => $validatedRow['email'],
                    ':password_hash' => password_hash($defaultPassword, PASSWORD_DEFAULT),
                    ':is_active' => $validatedRow['is_active'],
                ]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        echo json_encode([
            'ok' => true,
            'message' => sprintf(
                '%d user%s imported successfully. Usernames were generated automatically. Default password is denr@12 and must be changed on first login.',
                count($validatedRows),
                count($validatedRows) === 1 ? '' : 's'
            ),
            'users' => super_admin_fetch_users($pdo, 600),
            'roles' => $roles,
            'offices' => $offices,
            'office_options_payload' => $officeDirectory['office_options_payload'] ?? [],
            'fixed_office_id_by_role_name' => $officeDirectory['fixed_office_id_by_role_name'] ?? [],
        ]);
        exit;
    }

    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if ($targetUserId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'A valid user is required.']);
        exit;
    }

    if ($action === 'RESET_SECURITY') {
        $targetUserStmt = $pdo->prepare('SELECT TOP (1) id FROM users WHERE id = :id');
        $targetUserStmt->execute(['id' => $targetUserId]);
        if (!$targetUserStmt->fetch()) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'User not found.']);
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE users
             SET failed_login_attempts = 0,
                 locked_until = NULL,
                 mfa_failed_attempts = 0,
                 mfa_locked_until = NULL,
                 mfa_resend_count = 0,
                 mfa_resend_window_started_at = NULL,
                 mfa_last_sent_at = NULL,
                 mfa_otp_code = NULL,
                 mfa_otp_expires_at = NULL
             WHERE id = :id'
        );
        $stmt->execute(['id' => $targetUserId]);

        echo json_encode([
            'ok' => true,
            'message' => 'User security lock counters were reset.',
            'users' => super_admin_fetch_users($pdo, 600),
            'roles' => $roles,
            'offices' => $offices,
            'office_options_payload' => $officeDirectory['office_options_payload'] ?? [],
            'fixed_office_id_by_role_name' => $officeDirectory['fixed_office_id_by_role_name'] ?? [],
        ]);
        exit;
    }

    if ($action === 'RESET_PASSWORD') {
        $defaultPassword = 'denr@12';
        $targetUserStmt = $pdo->prepare('SELECT TOP (1) id, email, username FROM users WHERE id = :id');
        $targetUserStmt->execute(['id' => $targetUserId]);
        $targetUser = $targetUserStmt->fetch();
        if (!$targetUser) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'User not found.']);
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 must_change_password = 1,
                 password_changed_at = NULL,
                 failed_login_attempts = 0,
                 locked_until = NULL,
                 otp_code = NULL,
                 otp_expires_at = NULL,
                 mfa_failed_attempts = 0,
                 mfa_locked_until = NULL,
                 mfa_resend_count = 0,
                 mfa_resend_window_started_at = NULL,
                 mfa_last_sent_at = NULL,
                 mfa_otp_code = NULL,
                 mfa_otp_expires_at = NULL
             WHERE id = :id'
        );
        $stmt->execute([
            'password_hash' => password_hash($defaultPassword, PASSWORD_DEFAULT),
            'id' => $targetUserId,
        ]);

        auth_log_security_event($pdo, [
            'user_id' => (int)($targetUser['id'] ?? 0),
            'email' => (string)($targetUser['email'] ?? ''),
            'event_type' => 'ADMIN_PASSWORD_RESET',
            'event_status' => 'SUCCESS',
            'remarks' => sprintf(
                'Password reset to the default temporary password by Super Admin user #%d. User must change password on next login.',
                (int)($_SESSION['user_id'] ?? 0)
            ),
        ]);

        echo json_encode([
            'ok' => true,
            'message' => sprintf(
                'Password for @%s was reset to denr@12. The user must change it on the next login.',
                (string)($targetUser['username'] ?? 'user')
            ),
            'users' => super_admin_fetch_users($pdo, 600),
            'roles' => $roles,
            'offices' => $offices,
            'office_options_payload' => $officeDirectory['office_options_payload'] ?? [],
            'fixed_office_id_by_role_name' => $officeDirectory['fixed_office_id_by_role_name'] ?? [],
        ]);
        exit;
    }

    if ($action === 'BLOCK_USER' || $action === 'UNBLOCK_USER') {
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($action === 'BLOCK_USER' && $targetUserId === $currentUserId) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'You cannot block your own account while signed in.']);
            exit;
        }

        $targetUserStmt = $pdo->prepare('SELECT TOP (1) id, email, username, is_active FROM users WHERE id = :id');
        $targetUserStmt->execute(['id' => $targetUserId]);
        $targetUser = $targetUserStmt->fetch();
        if (!$targetUser) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'User not found.']);
            exit;
        }

        $isActive = $action === 'UNBLOCK_USER' ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
        $stmt->execute([
            'is_active' => $isActive,
            'id' => $targetUserId,
        ]);

        auth_log_security_event($pdo, [
            'user_id' => (int)($targetUser['id'] ?? 0),
            'email' => (string)($targetUser['email'] ?? ''),
            'event_type' => $action === 'UNBLOCK_USER' ? 'ADMIN_USER_UNBLOCKED' : 'ADMIN_USER_BLOCKED',
            'event_status' => 'SUCCESS',
            'remarks' => sprintf(
                'Account %s by Super Admin user #%d.',
                $action === 'UNBLOCK_USER' ? 'unblocked' : 'blocked',
                $currentUserId
            ),
        ]);

        echo json_encode([
            'ok' => true,
            'message' => sprintf(
                'User @%s has been %s.',
                (string)($targetUser['username'] ?? 'user'),
                $action === 'UNBLOCK_USER' ? 'unblocked and can sign in again' : 'blocked and can no longer sign in'
            ),
            'users' => super_admin_fetch_users($pdo, 600),
            'roles' => $roles,
            'offices' => $offices,
            'office_options_payload' => $officeDirectory['office_options_payload'] ?? [],
            'fixed_office_id_by_role_name' => $officeDirectory['fixed_office_id_by_role_name'] ?? [],
        ]);
        exit;
    }

    if ($action === 'DELETE_USER') {
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($targetUserId === $currentUserId) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'You cannot delete your own account while signed in.']);
            exit;
        }

        $targetUserStmt = $pdo->prepare('SELECT TOP (1) id FROM users WHERE id = :id');
        $targetUserStmt->execute(['id' => $targetUserId]);
        if (!$targetUserStmt->fetch()) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'User not found.']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            user_recycle_bin_archive_user($pdo, $targetUserId, $currentUserId);
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $targetUserId]);
            if ($stmt->rowCount() < 1) {
                throw new RuntimeException('Unable to move user account to recycle bin.');
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $rawMessage = trim((string)$exception->getMessage());
            $message = $rawMessage !== ''
                ? $rawMessage
                : 'This account cannot be deleted right now because it is linked to existing records.';
            if ($message === 'The active result for the query contains no fields.' || str_contains(strtolower($message), 'foreign key')) {
                $message = 'This account cannot be deleted right now because it is linked to existing records.';
            }

            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'message' => $message,
            ]);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'message' => 'User account moved to recycle bin.',
            'users' => super_admin_fetch_users($pdo, 600),
            'roles' => $roles,
            'offices' => $offices,
            'office_options_payload' => $officeDirectory['office_options_payload'] ?? [],
            'fixed_office_id_by_role_name' => $officeDirectory['fixed_office_id_by_role_name'] ?? [],
        ]);
        exit;
    }

    if ($action !== 'UPDATE_USER') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Unsupported action.']);
        exit;
    }

    $roleId = (int)($_POST['role_id'] ?? 0);
    $officeId = (int)($_POST['office_id'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    $resetSecurity = (int)($_POST['reset_security'] ?? 0) === 1;

    if ($roleId <= 0 || $officeId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Role and office are required.']);
        exit;
    }

    $roleCheckStmt = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE id = :id');
    $roleCheckStmt->execute(['id' => $roleId]);
    if ((int)$roleCheckStmt->fetchColumn() <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Selected role does not exist.']);
        exit;
    }

    $roleNameById = [];
    foreach ($roles as $role) {
        $roleNameById[(int)($role['id'] ?? 0)] = (string)($role['name'] ?? '');
    }
    $selectedRoleName = (string)($roleNameById[$roleId] ?? '');
    $roleOfficeError = DTMIS_validate_role_office_assignment($selectedRoleName, (string)$officeId, $officeDirectory);
    if ($roleOfficeError !== '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => $roleOfficeError]);
        exit;
    }

    $targetUserStmt = $pdo->prepare('SELECT TOP (1) id, role_id FROM users WHERE id = :id');
    $targetUserStmt->execute(['id' => $targetUserId]);
    $targetUser = $targetUserStmt->fetch();
    if (!$targetUser) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'User not found.']);
        exit;
    }

    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    $currentRoleId = (int)($_SESSION['role_id'] ?? 0);
    if ($targetUserId === $currentUserId) {
        if ($isActive !== 1) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'You cannot deactivate your own Super Admin account.']);
            exit;
        }
        if ($roleId !== $currentRoleId) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'You cannot change your own role from this page.']);
            exit;
        }
    }

    $sql = 'UPDATE users
            SET role_id = :role_id,
                office_id = :office_id,
                is_active = :is_active';

    if ($resetSecurity) {
        $sql .= ',
                failed_login_attempts = 0,
                locked_until = NULL,
                mfa_failed_attempts = 0,
                mfa_locked_until = NULL,
                mfa_resend_count = 0,
                mfa_resend_window_started_at = NULL,
                mfa_last_sent_at = NULL,
                mfa_otp_code = NULL,
                mfa_otp_expires_at = NULL';
    }

    $sql .= ' WHERE id = :id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'role_id' => $roleId,
        'office_id' => $officeId,
        'is_active' => $isActive,
        'id' => $targetUserId,
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'User profile updated successfully.',
        'users' => super_admin_fetch_users($pdo, 600),
        'roles' => $roles,
        'offices' => $offices,
        'office_options_payload' => $officeDirectory['office_options_payload'] ?? [],
        'fixed_office_id_by_role_name' => $officeDirectory['fixed_office_id_by_role_name'] ?? [],
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to process Super Admin user request right now.',
    ]);
}
