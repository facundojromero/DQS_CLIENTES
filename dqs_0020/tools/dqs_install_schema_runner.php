<?php
/**
 * DQS canonical schema runner (UNI-048.3).
 *
 * CLI-only, dry-run by default, and deliberately limited to the canonical
 * schema and seed package in database/install.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is available from CLI only.\n");
    exit(2);
}

$allowed = array('--using-current-connection', '--include-default-content', '--apply', '--confirm-empty-install', '--json', '--help');
$connectionFile = null;
foreach (array_slice($argv, 1) as $argument) {
    if (strpos($argument, '--connection-file=') === 0) {
        if ($connectionFile !== null) {
            fwrite(STDERR, "The connection-file option may be provided only once.\n");
            exit(2);
        }
        $connectionFile = substr($argument, strlen('--connection-file='));
    } elseif (!in_array($argument, $allowed, true)) {
        fwrite(STDERR, "Unknown option.\n");
        exit(2);
    }
}
$has = static function ($option) use ($argv) { return in_array($option, $argv, true); };
$json = $has('--json');
$apply = $has('--apply');
$confirmed = $has('--confirm-empty-install');
$includeDefault = $has('--include-default-content');

if ($has('--help')) {
    echo "Usage: php tools/dqs_install_schema_runner.php (--using-current-connection|--connection-file=/path/to/file.php) [--include-default-content] [--apply --confirm-empty-install] [--json]\n";
    exit(0);
}
$usingCurrentConnection = $has('--using-current-connection');
if (($usingCurrentConnection && $connectionFile !== null) || (!$usingCurrentConnection && $connectionFile === null)
    || ($connectionFile !== null && trim($connectionFile) === '') || ($apply && !$confirmed) || (!$apply && $confirmed)) {
    fwrite(STDERR, "Choose exactly one connection source. Apply requires both --apply and --confirm-empty-install.\n");
    exit(2);
}

$root = dirname(__DIR__);
$installDir = $root . '/database/install';
$checks = array();
$add = static function ($id, $status, $message, array $details = array()) use (&$checks) {
    $checks[] = array('id' => $id, 'status' => $status, 'message' => $message, 'details' => $details);
};
$blocked = false;
$failed = false;

$phpSupported = version_compare(PHP_VERSION, '7.4.0', '>=');
$add('php.version', $phpSupported ? 'OK' : 'BLOCKED', $phpSupported ? 'PHP ' . PHP_VERSION . ' meets the CLI minimum >= 7.4.' : 'PHP 7.4 or newer is required.');
if (!$phpSupported) $blocked = true;
if (PHP_VERSION_ID < 70400 || PHP_VERSION_ID >= 70500) {
    $add('php.runtime_compatibility', 'WARN', 'La tienda históricamente requiere PHP 7.4; validar versión runtime del dominio antes de producción.');
}
$lockExists = is_file($root . '/install.lock') || is_file($installDir . '/install.lock');
$add('local.install_lock', $lockExists ? 'BLOCKED' : 'OK', $lockExists ? 'An install.lock exists; installation is blocked.' : 'No install.lock was found.');
if ($lockExists) $blocked = true;
$adminDirs = glob($root . '/admin*', GLOB_ONLYDIR) ?: array();
$adminActive = count(array_filter($adminDirs, static function ($path) {
    return basename($path) !== 'admin_tmp' && basename($path) !== 'admin_config';
})) > 0;
$add('local.admin_active', $adminActive ? 'WARN' : 'OK', $adminActive ? 'An active admin directory exists and will not be touched.' : 'No active admin directory was detected.');

/* Parse mysql-client-style DELIMITER directives without splitting trigger bodies. */
$parseSql = static function ($path) {
    $lines = file($path);
    if ($lines === false) return false;
    $delimiter = ';';
    $buffer = '';
    $statements = array();
    $inBlockComment = false;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($inBlockComment) {
            if (strpos($trimmed, '*/') !== false) $inBlockComment = false;
            continue;
        }
        if (strpos($trimmed, '/*') === 0) {
            if (strpos($trimmed, '*/') === false) $inBlockComment = true;
            continue;
        }
        if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) continue;
        if (preg_match('/^DELIMITER\s+(\S+)\s*$/i', $trimmed, $match)) {
            if (trim($buffer) !== '') throw new RuntimeException('DELIMITER changed while a statement was incomplete.');
            $delimiter = $match[1];
            continue;
        }
        $buffer .= $line;
        $candidate = rtrim($buffer);
        if ($delimiter !== '' && substr($candidate, -strlen($delimiter)) === $delimiter) {
            $statement = trim(substr($candidate, 0, -strlen($delimiter)));
            if ($statement !== '') $statements[] = $statement;
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') throw new RuntimeException('SQL file ends with an incomplete statement.');
    return $statements;
};
$stripComments = static function ($sql) {
    $sql = preg_replace('~/\*.*?\*/~s', '', $sql);
    return preg_replace('/^\s*(?:--|#).*$/m', '', $sql);
};

$requiredFiles = array('schema.sql', 'seed.sql', 'seed_default_content.sql', 'manifest.json');
foreach ($requiredFiles as $name) {
    $exists = is_file($installDir . '/' . $name) && is_readable($installDir . '/' . $name);
    $add('file.' . $name, $exists ? 'OK' : 'BLOCKED', $exists ? "Canonical file is readable: database/install/{$name}." : "Canonical file is missing or unreadable: database/install/{$name}.");
    if (!$exists) $blocked = true;
}

$manifest = null;
if (!$blocked) {
    $manifest = json_decode((string) file_get_contents($installDir . '/manifest.json'), true);
    $manifestValid = is_array($manifest)
        && isset($manifest['tables'], $manifest['triggers'], $manifest['seeds']['required']['file'], $manifest['seeds']['default_content']['file'])
        && is_array($manifest['tables']) && is_array($manifest['triggers'])
        && $manifest['seeds']['required']['file'] === 'database/install/seed.sql'
        && $manifest['seeds']['default_content']['file'] === 'database/install/seed_default_content.sql';
    $add('manifest.valid', $manifestValid ? 'OK' : 'BLOCKED', $manifestValid ? 'manifest.json is valid and references the canonical seeds.' : 'manifest.json is invalid or has an unexpected installation contract.');
    if (!$manifestValid) $blocked = true;
}

$sqlSets = array();
if (!$blocked) {
    try {
        foreach (array('schema.sql', 'seed.sql', 'seed_default_content.sql') as $name) {
            $sqlSets[$name] = $parseSql($installDir . '/' . $name);
            $add('parser.' . $name, 'OK', "Detected " . count($sqlSets[$name]) . " statement(s) in {$name}.", array('statements' => count($sqlSets[$name])));
        }
    } catch (RuntimeException $exception) {
        $add('parser.sql', 'BLOCKED', 'SQL parser rejected the canonical package: ' . $exception->getMessage());
        $blocked = true;
    }
}

if (!$blocked) {
    $schema = $stripComments((string) file_get_contents($installDir . '/schema.sql'));
    $schemaRules = array('DEFINER=' => '/\bDEFINER\s*=/i', 'CREATE DATABASE' => '/\bCREATE\s+DATABASE\b/i', 'USE' => '/^\s*USE\s+/im', 'INSERT INTO' => '/\bINSERT\s+INTO\b/i');
    foreach ($schemaRules as $label => $pattern) {
        $found = preg_match($pattern, $schema) === 1;
        $add('schema.forbidden.' . strtolower(str_replace(' ', '_', $label)), $found ? 'BLOCKED' : 'OK', $found ? "schema.sql contains forbidden {$label}." : "schema.sql does not contain {$label}.");
        if ($found) $blocked = true;
    }
    foreach (array('seed.sql', 'seed_default_content.sql') as $name) {
        $seed = $stripComments((string) file_get_contents($installDir . '/' . $name));
        $phoneInput = preg_replace(array('/\b\d{4}-\d{2}-\d{2}\b/', '/\b\d{8,}_[A-Za-z0-9._-]+\b/'), '', $seed);
        $rules = array(
            'banking' => array('/\b(?:CBU|CVU|IBAN|SWIFT|alias\s+bancario|cuenta\s+bancaria)\b/i', $seed),
            'email' => array('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $seed),
            'phone' => array('/(?<!\d)(?:\+?\d[\s().-]*){8,15}(?!\d)/', $phoneInput),
            'password' => array('/\b(?:password|passwd|contrasena|contraseña)\b\s*(?:,|=|\)|VALUES)/i', $seed),
            'secret' => array('/\b(?:api[_-]?key|client[_-]?secret|secret[_-]?key|access[_-]?token)\b/i', $seed),
            'hostinger_user' => array('/\bu\d{4,}_[A-Za-z0-9_]+\b/i', $seed),
        );
        foreach ($rules as $id => $rule) {
            $found = preg_match($rule[0], $rule[1]) === 1;
            $add("seed.{$name}.{$id}", $found ? 'BLOCKED' : 'OK', $found ? "{$name} contains possible sensitive data ({$id})." : "{$name} passed the {$id} scan.");
            if ($found) $blocked = true;
        }
    }
}

$connection = null;
$tablesBefore = array();
if (!$blocked) {
    $connectionPath = $usingCurrentConnection ? $root . '/conexion.php' : $connectionFile;
    $connectionLabel = $usingCurrentConnection ? 'current conexion.php' : 'alternate connection-file (' . basename($connectionPath) . ')';
    if (!is_file($connectionPath) || !is_readable($connectionPath)) {
        $add('db.connection_file', 'BLOCKED', $usingCurrentConnection ? 'conexion.php is missing or unreadable.' : 'The alternate connection-file is missing or unreadable.');
        $blocked = true;
    } elseif (!extension_loaded('mysqli')) {
        $add('php.extension.mysqli', 'BLOCKED', 'The required mysqli extension is missing.');
        $blocked = true;
    } else {
        // Load literal connection settings without executing legacy output/die code.
        $values = array();
        $tokens = token_get_all((string) file_get_contents($connectionPath));
        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_VARIABLE) continue;
            $name = substr($tokens[$i][1], 1);
            if (!in_array($name, array('servername', 'username', 'password', 'dbname'), true)) continue;
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if (($tokens[$j] ?? null) !== '=') continue;
            do { $j++; } while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE);
            if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) $values[$name] = stripcslashes(substr($tokens[$j][1], 1, -1));
        }
        mysqli_report(MYSQLI_REPORT_OFF);
        $missingValues = array_values(array_diff(array('servername', 'username', 'password', 'dbname'), array_keys($values)));
        if ($missingValues) {
            $add('db.connection_file', 'BLOCKED', 'The ' . $connectionLabel . ' does not contain all four required literal assignments (values hidden).');
            $blocked = true;
        } else {
            $connection = @new mysqli($values['servername'], $values['username'], $values['password'], $values['dbname']);
        }
        if (!($connection instanceof mysqli) || $connection->connect_errno) {
            $add('db.connection', 'BLOCKED', 'Could not establish the selected mysqli connection (credentials hidden).');
            $blocked = true;
            // PHP 8 may already have closed an object whose constructor failed.
            $connection = null;
        } elseif (!$connection->set_charset('utf8mb4')) {
            $add('db.charset', 'BLOCKED', 'Could not set the connection charset to utf8mb4.');
            $blocked = true;
        } else {
            $add('db.connection', 'OK', ucfirst($connectionLabel) . ' was loaded without execution; mysqli connection established with utf8mb4 (credentials hidden).');
            $result = $connection->query('SHOW TABLES');
            if (!$result) {
                $add('db.tables', 'BLOCKED', 'SHOW TABLES failed.');
                $blocked = true;
            } else {
                while ($row = $result->fetch_row()) $tablesBefore[] = (string) $row[0];
                $result->free();
                $empty = count($tablesBefore) === 0;
                $add('db.empty', $empty ? 'OK' : 'BLOCKED', $empty ? 'The selected database is empty.' : 'The selected database is not empty (' . count($tablesBefore) . ' table(s)); no SQL will be executed.', array('table_count' => count($tablesBefore)));
                if (!$empty) $blocked = true;
            }
        }
    }
}

$executed = 0;
if (!$blocked && $apply) {
    $filesToApply = array('schema.sql', 'seed.sql');
    if ($includeDefault) $filesToApply[] = 'seed_default_content.sql';
    foreach ($filesToApply as $name) {
        foreach ($sqlSets[$name] as $index => $statement) {
            if (!$connection->query($statement)) {
                $add('apply.sql', 'FAILED', "Execution failed in {$name}, statement " . ($index + 1) . ': ' . $connection->error, array('file' => $name, 'statement' => $index + 1));
                $failed = true;
                break 2;
            }
            $executed++;
        }
        $add('apply.' . $name, 'OK', "Applied {$name} successfully.");
    }
}

if (!$blocked && $apply && !$failed) {
    $result = $connection->query('SHOW TABLES');
    $actualTables = array();
    if ($result) { while ($row = $result->fetch_row()) $actualTables[] = (string) $row[0]; $result->free(); }
    $missing = array_values(array_diff($manifest['tables'], $actualTables));
    $add('verify.tables', $missing ? 'FAILED' : 'OK', $missing ? 'Missing manifest tables: ' . implode(', ', $missing) . '.' : 'All manifest tables exist.', array('missing' => $missing));
    if ($missing) $failed = true;
    $triggerResult = $connection->query("SHOW TRIGGERS WHERE `Trigger` = 'generar_codigo_invitado'");
    $triggerExists = $triggerResult && $triggerResult->num_rows > 0;
    if ($triggerResult) $triggerResult->free();
    $add('verify.trigger', $triggerExists ? 'OK' : 'FAILED', $triggerExists ? 'Trigger generar_codigo_invitado exists.' : 'Trigger generar_codigo_invitado is missing.');
    if (!$triggerExists) $failed = true;
    $expectedRows = array('info_mostrar' => 8, 'intivados_acompanante' => 5, 'invitados_prioridad' => 4, 'site_settings' => 6);
    if ($includeDefault) $expectedRows += array('info_casamiento' => 1, 'info_nosotros' => 2, 'info_historia' => 8, 'info_eventos' => 6, 'info_otra' => 4);
    foreach ($expectedRows as $table => $expected) {
        $result = $connection->query('SELECT COUNT(*) FROM `' . $table . '`');
        $row = $result ? $result->fetch_row() : null;
        if ($result) $result->free();
        $actual = $row ? (int) $row[0] : null;
        $ok = $actual === $expected;
        $add('verify.rows.' . $table, $ok ? 'OK' : 'FAILED', $ok ? "{$table} has {$expected} expected row(s)." : "{$table} row count mismatch (expected {$expected}, got " . ($actual === null ? 'query failure' : $actual) . ').');
        if (!$ok) $failed = true;
    }
    if (!$includeDefault) $add('verify.default_content', 'OK', 'Optional editorial row counts were not enforced because --include-default-content was not selected.');
}
if ($connection instanceof mysqli) $connection->close();

$hasWarning = count(array_filter($checks, static function ($check) { return $check['status'] === 'WARN'; })) > 0;
$status = $failed ? 'FAILED' : ($blocked ? 'BLOCKED' : ($hasWarning ? 'WARN' : 'OK'));
$report = array(
    'tool' => 'DQS install schema runner', 'version' => 'UNI-048.3',
    'status' => $status, 'mode' => $apply ? 'apply' : 'dry-run',
    'connection_source' => $usingCurrentConnection ? 'using-current-connection' : 'connection-file',
    'read_only' => !$apply || $blocked, 'include_default_content' => $includeDefault,
    'database_empty' => !$blocked && count($tablesBefore) === 0,
    'plan' => array(
        'schema_statements' => isset($sqlSets['schema.sql']) ? count($sqlSets['schema.sql']) : null,
        'seed_statements' => isset($sqlSets['seed.sql']) ? count($sqlSets['seed.sql']) : null,
        'default_content_statements' => isset($sqlSets['seed_default_content.sql']) ? count($sqlSets['seed_default_content.sql']) : null,
        'default_content_selected' => $includeDefault,
        'expected_tables' => is_array($manifest) ? $manifest['tables'] : array(),
        'expected_triggers' => is_array($manifest) ? $manifest['triggers'] : array(),
    ),
    'executed_statements' => $executed, 'checks' => $checks,
);
if ($json) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "DQS install schema runner (UNI-048.3)\nMode: " . ($apply ? 'APPLY' : 'DRY-RUN (default)') . "\n\n";
    foreach ($checks as $check) echo sprintf("[%-7s] %s\n", $check['status'], $check['message']);
    echo "\nPlan: schema=" . ($report['plan']['schema_statements'] ?? 'n/a') . ', technical seed=' . ($report['plan']['seed_statements'] ?? 'n/a') . ', default content=' . ($report['plan']['default_content_statements'] ?? 'n/a') . ($includeDefault ? ' (selected)' : ' (not selected)') . "\n";
    echo 'Expected manifest tables: ' . count($report['plan']['expected_tables']) . "\nExpected trigger: generar_codigo_invitado\n";
    echo "Executed statements: {$executed}\nOverall status: {$status}\n";
}
exit(($blocked || $failed) ? 1 : 0);
