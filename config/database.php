<?php
declare(strict_types=1);

final class CompatPdo extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        [$preparedQuery, $duplicateParamMap] = db_sqlserver_rewrite_duplicate_named_placeholders($this, $query);

        if ($duplicateParamMap !== []) {
            $options[PDO::ATTR_STATEMENT_CLASS] = [CompatPdoStatement::class, [$duplicateParamMap]];
        }

        return parent::prepare($preparedQuery, $options);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        if ($fetchMode === null) {
            return parent::query($query);
        }

        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    public function exec(string $statement): int|false
    {
        return parent::exec($statement);
    }
}

final class CompatPdoStatement extends PDOStatement
{
    /** @var array<string,string> */
    private array $duplicateParamMap = [];

    protected function __construct(array $duplicateParamMap = [])
    {
        $this->duplicateParamMap = $duplicateParamMap;
    }

    public function execute(?array $params = null): bool
    {
        if (is_array($params) && $this->duplicateParamMap !== []) {
            foreach ($this->duplicateParamMap as $generatedName => $originalName) {
                if (array_key_exists($generatedName, $params) || array_key_exists(':' . $generatedName, $params)) {
                    continue;
                }

                if (array_key_exists($originalName, $params)) {
                    $params[$generatedName] = $params[$originalName];
                    continue;
                }

                if (array_key_exists(':' . $originalName, $params)) {
                    $params[':' . $generatedName] = $params[':' . $originalName];
                }
            }
        }

        return parent::execute($params);
    }
}

function db_resolve_driver_name(): string
{
    return 'sqlsrv';
}

function db_is_sql_server(PDO $pdo): bool
{
    try {
        return strtolower((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) === 'sqlsrv';
    } catch (Throwable $exception) {
        return false;
    }
}

function db_table_exists(PDO $pdo, string $tableName): bool
{
    $tableName = trim($tableName);
    if ($tableName === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT 1
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_TYPE = 'BASE TABLE'
           AND TABLE_NAME = :table_name"
    );
    $stmt->execute(['table_name' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function db_column_exists(PDO $pdo, string $tableName, string $columnName): bool
{
    $tableName = trim($tableName);
    $columnName = trim($columnName);
    if ($tableName === '' || $columnName === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name"
    );
    $stmt->execute([
        'table_name' => $tableName,
        'column_name' => $columnName,
    ]);
    return (bool)$stmt->fetchColumn();
}

function db_sqlserver_rewrite_duplicate_named_placeholders(PDO $pdo, string $sql): array
{
    if (!db_is_sql_server($pdo) || $sql === '' || strpos($sql, ':') === false) {
        return [$sql, []];
    }

    $length = strlen($sql);
    $result = '';
    $counts = [];
    $duplicates = [];
    $quote = null;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $nextChar = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($inLineComment) {
            $result .= $char;
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            $result .= $char;
            if ($char === '*' && $nextChar === '/') {
                $result .= $nextChar;
                $i++;
                $inBlockComment = false;
            }
            continue;
        }

        if ($quote !== null) {
            $result .= $char;
            if ($char === $quote) {
                if ($nextChar === $quote) {
                    $result .= $nextChar;
                    $i++;
                    continue;
                }
                $quote = null;
            }
            continue;
        }

        if ($char === '-' && $nextChar === '-') {
            $result .= $char . $nextChar;
            $i++;
            $inLineComment = true;
            continue;
        }

        if ($char === '/' && $nextChar === '*') {
            $result .= $char . $nextChar;
            $i++;
            $inBlockComment = true;
            continue;
        }

        if ($char === "'" || $char === '"') {
            $result .= $char;
            $quote = $char;
            continue;
        }

        if (
            $char === ':'
            && $nextChar !== ''
            && preg_match('/[A-Za-z_]/', $nextChar) === 1
        ) {
            $start = $i + 1;
            $end = $start;
            while ($end < $length && preg_match('/[A-Za-z0-9_]/', $sql[$end]) === 1) {
                $end++;
            }

            $name = substr($sql, $start, $end - $start);
            if ($name === '') {
                $result .= $char;
                continue;
            }

            $counts[$name] = ($counts[$name] ?? 0) + 1;
            if ($counts[$name] === 1) {
                $result .= ':' . $name;
            } else {
                $generated = $name . '__dup' . $counts[$name];
                $duplicates[$generated] = $name;
                $result .= ':' . $generated;
            }

            $i = $end - 1;
            continue;
        }

        $result .= $char;
    }

    return [$result, $duplicates];
}

function db_assert_pdo_driver_available(string $driver): void
{
    $normalizedDriver = strtolower(trim($driver));
    $availableDrivers = array_map(
        static fn (mixed $value): string => strtolower((string)$value),
        PDO::getAvailableDrivers()
    );

    if (in_array($normalizedDriver, $availableDrivers, true)) {
        return;
    }

    $installedList = $availableDrivers !== []
        ? implode(', ', $availableDrivers)
        : 'none';

    if ($normalizedDriver === 'sqlsrv') {
        throw new RuntimeException(
            'SQL Server PDO driver is not installed. Enable the Microsoft PHP extensions '
            . '`sqlsrv` and `pdo_sqlsrv` for this PHP runtime. Installed PDO drivers: '
            . $installedList
        );
    }

    throw new RuntimeException(
        'Required PDO driver `' . $normalizedDriver . '` is not installed. Installed PDO drivers: ' . $installedList
    );
}

function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = db_resolve_driver_name();
    db_assert_pdo_driver_available($driver);

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = trim((string)(getenv('DB_PORT') ?: ''));
    if ($port === '' || $port === '3306') {
        $port = trim((string)(getenv('DB_SQLSRV_PORT') ?: '1433'));
    }
    $dbName = getenv('DB_NAME') ?: 'denr_db';
    $username = trim((string)(getenv('DB_SQLSRV_USER') ?: ''));
    if ($username === '') {
        $username = 'sa';
    }

    $password = (string)(getenv('DB_PASSWORD') ?: '12345678');

    $serverTarget = $host;
    if ($port !== '') {
        $serverTarget .= ',' . $port;
    }
    $dsn = sprintf(
        'sqlsrv:Server=%s;Database=%s;TrustServerCertificate=1',
        $serverTarget,
        $dbName
    );

    $pdo = new CompatPdo($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
