<?php
/**
 * DQS installer preflight (UNI-048.2).
 *
 * This CLI utility is deliberately read-only. It never executes installation SQL.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is available from CLI only.\n");
    exit(2);
}

$options = getopt('', array('no-db', 'using-current-connection', 'connection-file:', 'json', 'help'));
$json = isset($options['json']);
$noDb = isset($options['no-db']);
$usingCurrentConnection = isset($options['using-current-connection']);
$hasConnectionFile = array_key_exists('connection-file', $options);
$withDb = $usingCurrentConnection || $hasConnectionFile;

if (isset($options['help'])) {
    echo "Usage: php tools/dqs_install_preflight.php (--no-db|--using-current-connection|--connection-file=/path/to/file.php) [--json]\n";
    exit(0);
}
if (($noDb && $withDb) || (!$noDb && !$withDb) || ($usingCurrentConnection && $hasConnectionFile)
    || ($hasConnectionFile && (!is_string($options['connection-file']) || trim($options['connection-file']) === ''))) {
    fwrite(STDERR, "Choose exactly one mode/source: --no-db, --using-current-connection, or a non-empty --connection-file.\n");
    exit(2);
}

$root = dirname(__DIR__);
$checks = array();
$add = static function ($id, $status, $message, array $details = array()) use (&$checks) {
    $checks[] = array('id' => $id, 'status' => $status, 'message' => $message, 'details' => $details);
};
$stripSqlComments = static function ($sql) {
    $sql = preg_replace('~/\*.*?\*/~s', '', $sql);
    return preg_replace('/^\s*--.*$/m', '', $sql);
};

$add('php.version', version_compare(PHP_VERSION, '7.4.0', '>=') ? 'OK' : 'BLOCKED',
    'PHP ' . PHP_VERSION . (version_compare(PHP_VERSION, '7.4.0', '>=') ? ' meets the CLI minimum >= 7.4.' : ' is older than the required PHP 7.4.'));
if (PHP_VERSION_ID < 70400 || PHP_VERSION_ID >= 70500) {
    $add('php.runtime_compatibility', 'WARN', 'La tienda históricamente requiere PHP 7.4; validar versión runtime del dominio antes de producción.');
}

$requiredExtensions = array('mysqli', 'json', 'session', 'filter', 'hash', 'openssl');
$suggestedExtensions = array('pdo_mysql', 'mbstring', 'gd', 'fileinfo', 'zip');
foreach ($requiredExtensions as $extension) {
    $loaded = extension_loaded($extension);
    $add('php.extension.' . $extension, $loaded ? 'OK' : 'BLOCKED',
        $loaded ? "Required extension {$extension} is loaded." : "Required extension {$extension} is missing.");
}
foreach ($suggestedExtensions as $extension) {
    $loaded = extension_loaded($extension);
    $add('php.extension.' . $extension, $loaded ? 'OK' : 'WARN',
        $loaded ? "Suggested extension {$extension} is loaded." : "Suggested extension {$extension} is missing.");
}
$zipArchive = class_exists('ZipArchive', false);
$add('php.class.ZipArchive', $zipArchive ? 'OK' : 'WARN',
    $zipArchive ? 'ZipArchive is available.' : 'ZipArchive is not available.');

$requiredFiles = array(
    'database/install/schema.sql',
    'database/install/seed.sql',
    'database/install/seed_default_content.sql',
    'database/install/manifest.json',
);
foreach ($requiredFiles as $relative) {
    $exists = is_file($root . '/' . $relative);
    $add('file.' . str_replace('/', '.', $relative), $exists ? 'OK' : 'BLOCKED',
        $exists ? "Required file exists: {$relative}." : "Required file is missing: {$relative}.");
}

$manifestPath = $root . '/database/install/manifest.json';
$manifest = null;
if (is_file($manifestPath)) {
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    $valid = is_array($manifest) && json_last_error() === JSON_ERROR_NONE;
    $add('manifest.valid', $valid ? 'OK' : 'BLOCKED',
        $valid ? 'manifest.json contains valid JSON.' : 'manifest.json is invalid: ' . json_last_error_msg() . '.');
}
if (is_array($manifest)) {
    $references = array();
    $collectFiles = static function ($value) use (&$collectFiles, &$references) {
        if (!is_array($value)) {
            return;
        }
        foreach ($value as $key => $item) {
            if (($key === 'file' || $key === 'snapshot_file') && is_string($item)) {
                $references[] = $item;
            }
            $collectFiles($item);
        }
    };
    $collectFiles($manifest);
    foreach (array_unique($references) as $relative) {
        $safe = strpos($relative, '..') === false && substr($relative, 0, 1) !== '/';
        $exists = $safe && is_file($root . '/' . $relative);
        $add('manifest.reference.' . sha1($relative), $exists ? 'OK' : 'BLOCKED',
            $exists ? "Manifest reference exists: {$relative}." : "Manifest reference is missing or unsafe: {$relative}.");
    }
}

$schemaPath = $root . '/database/install/schema.sql';
if (is_file($schemaPath)) {
    $schema = $stripSqlComments((string) file_get_contents($schemaPath));
    $schemaRules = array(
        'definer' => array('/\bDEFINER\s*=/i', 'DEFINER='),
        'create_database' => array('/\bCREATE\s+DATABASE\b/i', 'CREATE DATABASE'),
        'use_database' => array('/^\s*USE\s+(?:`[^`]+`|[A-Za-z0-9_$-]+)\s*;/im', 'USE database'),
        'insert' => array('/\bINSERT\s+INTO\b/i', 'INSERT INTO'),
        'hostinger_user' => array('/\bu\d{4,}_[A-Za-z0-9_]+\b/i', 'a Hostinger-style user/database identifier'),
    );
    foreach ($schemaRules as $id => $rule) {
        $found = preg_match($rule[0], $schema) === 1;
        $add('schema.forbidden.' . $id, $found ? 'BLOCKED' : 'OK',
            $found ? "schema.sql contains forbidden {$rule[1]}." : "schema.sql does not contain {$rule[1]}.");
    }
}

foreach (array('seed.sql', 'seed_default_content.sql') as $seedName) {
    $path = $root . '/database/install/' . $seedName;
    if (!is_file($path)) {
        continue;
    }
    $seed = $stripSqlComments((string) file_get_contents($path));
    // ISO dates are neutral seed placeholders, not telephone numbers.
    $seedForPhoneScan = preg_replace('/\b\d{4}-\d{2}-\d{2}\b/', '', $seed);
    // Numeric image basenames (for example timestamp-prefixed uploads) are not
    // telephone data. Mask digits only inside conservatively named image files;
    // the phone rule still scans every other number in the seed unchanged.
    $seedForPhoneScan = preg_replace_callback(
        '/(?<![A-Za-z0-9_.-])(?:[A-Za-z0-9_.-]+\/)*[A-Za-z0-9_.-]+\.(?:jpe?g|png|webp)\b/i',
        static function ($matches) {
            return preg_replace('/\d/', 'x', $matches[0]);
        },
        $seedForPhoneScan
    );
    $rules = array(
        'banking_data' => array('/\b(?:CBU|CVU|IBAN|SWIFT|alias\s+bancario|cuenta\s+bancaria)\b/i', 'banking data'),
        'email' => array('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', 'an email address'),
        'phone' => array('/(?<!\d)(?:\+?\d[\s().-]*){8,15}(?!\d)/', 'a possible real phone number'),
        'password' => array('/\b(?:password|passwd|contrasena|contraseña)\b\s*(?:,|=|\)|VALUES)/i', 'a password field/value'),
        'secret' => array('/\b(?:api[_-]?key|client[_-]?secret|secret[_-]?key|access[_-]?token)\b/i', 'a secret field/value'),
        'hostinger_user' => array('/\bu\d{4,}_[A-Za-z0-9_]+\b/i', 'a Hostinger-style user'),
    );
    foreach ($rules as $id => $rule) {
        $haystack = $id === 'phone' ? $seedForPhoneScan : $seed;
        $found = preg_match($rule[0], $haystack) === 1;
        $add("seed.{$seedName}.{$id}", $found ? 'BLOCKED' : 'OK',
            $found ? "{$seedName} contains {$rule[1]}." : "{$seedName} does not contain {$rule[1]}.");
    }
}

$lockCandidates = array($root . '/install.lock', $root . '/database/install/install.lock');
$lockExists = false;
foreach ($lockCandidates as $candidate) {
    $lockExists = $lockExists || is_file($candidate);
}
$add('local.install_lock', $lockExists ? 'BLOCKED' : 'OK',
    $lockExists ? 'An install.lock file exists; installation must not continue.' : 'No install.lock file was found.');

$adminConfig = is_file($root . '/admin_config.php') || is_dir($root . '/admin_config');
$adminDirs = glob($root . '/admin*', GLOB_ONLYDIR) ?: array();
$adminActive = count(array_filter($adminDirs, static function ($path) {
    return basename($path) !== 'admin_tmp' && basename($path) !== 'admin_config';
})) > 0;
$add('local.admin_config', $adminConfig ? 'WARN' : 'OK', $adminConfig ? 'Local admin_config evidence was detected.' : 'No local admin_config evidence was detected.');
$add('local.admin_active', $adminActive ? 'WARN' : 'OK', $adminActive ? 'An active admin directory appears to exist; it was not touched.' : 'No active admin directory was detected locally.');

if ($withDb) {
    $connectionPath = $usingCurrentConnection ? $root . '/conexion.php' : $options['connection-file'];
    $connectionLabel = $usingCurrentConnection ? 'current conexion.php' : 'alternate connection-file (' . basename($connectionPath) . ')';
    if (!is_file($connectionPath) || !is_readable($connectionPath)) {
        $add('db.connection_file', 'BLOCKED', $usingCurrentConnection ? 'conexion.php is missing or unreadable.' : 'The alternate connection-file is missing or unreadable.');
    } else {
        // Read simple literal assignments instead of executing legacy code, whose
        // die()/warning output could expose connection metadata or corrupt JSON.
        $connectionValues = array();
        $tokens = token_get_all((string) file_get_contents($connectionPath));
        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_VARIABLE) continue;
            $name = substr($tokens[$i][1], 1);
            if (!in_array($name, array('servername', 'username', 'password', 'dbname'), true)) continue;
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if (($tokens[$j] ?? null) !== '=') continue;
            $j++;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
                $connectionValues[$name] = stripcslashes(substr($tokens[$j][1], 1, -1));
            }
        }
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = null;
        $missingConnectionValues = array_values(array_diff(array('servername', 'username', 'password', 'dbname'), array_keys($connectionValues)));
        if ($missingConnectionValues) {
            $add('db.connection_file', 'BLOCKED', 'The ' . $connectionLabel . ' does not contain all four required literal assignments (values hidden).');
        } else {
            $conn = @new mysqli($connectionValues['servername'], $connectionValues['username'], $connectionValues['password'], $connectionValues['dbname']);
        }
        if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_errno) {
            $add('db.connection', 'BLOCKED', 'The selected mysqli connection configuration could not be loaded or the connection could not be established (credentials hidden).');
        } else {
            $add('db.connection', 'OK', ucfirst($connectionLabel) . ' was loaded without execution and the mysqli connection was established (credentials hidden).');
            $charset = @$conn->set_charset('utf8mb4');
            $add('db.charset', $charset ? 'OK' : 'WARN', $charset ? 'Connection charset set to utf8mb4.' : 'Could not set connection charset to utf8mb4.');
            $queryOne = static function (mysqli $connection, $sql) {
                $result = $connection->query($sql);
                if (!$result) return null;
                $row = $result->fetch_row();
                $result->free();
                return $row ? $row[0] : null;
            };
            $database = $queryOne($conn, 'SELECT DATABASE()');
            $version = $queryOne($conn, 'SELECT VERSION()');
            $add('db.database', $database !== null ? 'OK' : 'WARN', $database !== null ? 'Current database selected (name hidden).' : 'No current database is selected.');
            $add('db.version', $version !== null ? 'OK' : 'WARN', $version !== null ? 'Database server version: ' . (string) $version . '.' : 'Database version could not be read.');
            $tables = array();
            $tableResult = $conn->query('SHOW TABLES');
            if ($tableResult) {
                while ($row = $tableResult->fetch_row()) $tables[] = (string) $row[0];
                $tableResult->free();
                $add('db.tables_query', 'OK', 'SHOW TABLES completed in read-only mode.');
            } else {
                $add('db.tables_query', 'BLOCKED', 'SHOW TABLES could not be completed.');
            }
            $notEmpty = count($tables) > 0;
            $add('db.clean_install', $notEmpty ? 'BLOCKED' : 'OK', $notEmpty ? 'status=not_empty: the database contains ' . count($tables) . ' table(s) and is not clean for a new installation.' : 'status=empty: the database is empty.');
            foreach (array('admin_config', 'site_settings') as $table) {
                $exists = in_array($table, $tables, true);
                $add('db.existing.' . $table, $exists ? 'WARN' : 'OK', $exists ? "Existing installation/configuration evidence detected: {$table}." : "Table {$table} was not detected.");
            }
            $triggerExists = false;
            $triggerResult = $conn->query("SHOW TRIGGERS WHERE `Trigger` = 'generar_codigo_invitado'");
            if ($triggerResult) {
                $triggerExists = $triggerResult->num_rows > 0;
                $triggerResult->free();
            }
            $add('db.trigger.generar_codigo_invitado', $triggerExists ? 'WARN' : 'OK', $triggerExists ? 'Existing trigger generar_codigo_invitado was detected.' : 'Trigger generar_codigo_invitado was not detected.');
            $conn->close();
        }
    }
}

$overall = 'OK';
foreach ($checks as $check) {
    if ($check['status'] === 'BLOCKED') { $overall = 'BLOCKED'; break; }
    if ($check['status'] === 'WARN') $overall = 'WARN';
}
$report = array(
    'tool' => 'DQS installer preflight',
    'version' => 'UNI-048.2',
    'mode' => $usingCurrentConnection ? 'using-current-connection' : ($hasConnectionFile ? 'connection-file' : 'no-db'),
    'status' => $overall,
    'read_only' => true,
    'checks' => $checks,
);

if ($json) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "DQS installer preflight (read-only)\nMode: {$report['mode']}\n\n";
    foreach ($checks as $check) echo sprintf("[%-7s] %s\n", $check['status'], $check['message']);
    echo "\nOverall status: {$overall}\n";
}
exit($overall === 'BLOCKED' ? 1 : 0);
