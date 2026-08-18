<?php
/** DQS initial customer bootstrap (UNI-048.4). CLI-only and dry-run by default. */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is available from CLI only.\n");
    exit(2);
}

$usage = 'Usage: php tools/dqs_install_bootstrap.php (--connection-file=/path/file.php|--using-current-connection) [--bootstrap-file=/path/data.json] [--apply --confirm-bootstrap] [--json|--print-template]';
$options = getopt('', array('connection-file:', 'using-current-connection', 'bootstrap-file:', 'apply', 'confirm-bootstrap', 'json', 'print-template', 'help'));
$jsonOutput = isset($options['json']);
$apply = isset($options['apply']);
$confirmed = isset($options['confirm-bootstrap']);
$template = isset($options['print-template']);
$root = dirname(__DIR__);

$usageError = static function ($message) use ($usage, $jsonOutput) {
    if ($jsonOutput) echo json_encode(array('status' => 'BLOCKED', 'usage_error' => $message), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    else fwrite(STDERR, $message . "\n" . $usage . "\n");
    exit(2);
};
if (isset($options['help'])) { echo $usage . "\n"; exit(0); }
$hasFile = array_key_exists('connection-file', $options);
$current = isset($options['using-current-connection']);
if (($hasFile ? 1 : 0) + ($current ? 1 : 0) !== 1) $usageError('Choose exactly one connection source.');
if ($hasFile && (!is_string($options['connection-file']) || trim($options['connection-file']) === '')) $usageError('--connection-file requires a path.');
if ($apply !== $confirmed) $usageError('Writes require both --apply and --confirm-bootstrap; confirmation alone is also invalid.');
if ($template && ($apply || $confirmed || $jsonOutput)) $usageError('--print-template cannot be combined with apply, confirmation, or --json.');
if (!$template && (!isset($options['bootstrap-file']) || !is_string($options['bootstrap-file']) || trim($options['bootstrap-file']) === '')) $usageError('--bootstrap-file is required.');

$checks = array();
$add = static function ($id, $status, $message, array $details = array()) use (&$checks) {
    $checks[] = array('id' => $id, 'status' => $status, 'message' => $message, 'details' => $details);
};
$blocked = static function () use (&$checks) {
    foreach ($checks as $check) if (in_array($check['status'], array('BLOCKED', 'FAILED'), true)) return true;
    return false;
};
$count = static function (mysqli $db, $table) {
    $result = $db->query('SELECT COUNT(*) FROM `' . $table . '`');
    if (!$result) return null;
    $row = $result->fetch_row(); $result->free(); return (int) $row[0];
};

// Parse four simple literal assignments. Never include/execute the connection file.
$connectionPath = $current ? $root . '/conexion.php' : $options['connection-file'];
if (!is_file($connectionPath) || !is_readable($connectionPath)) {
    $add('connection.file', 'BLOCKED', 'The selected connection file is missing or unreadable (path hidden).');
} else {
    $values = array();
    $tokens = token_get_all((string) file_get_contents($connectionPath));
    for ($i = 0, $n = count($tokens); $i < $n; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_VARIABLE) continue;
        $name = substr($tokens[$i][1], 1);
        if (!in_array($name, array('servername', 'username', 'password', 'dbname'), true)) continue;
        $j = $i + 1;
        while ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
        if (($tokens[$j] ?? null) !== '=') continue;
        $j++;
        while ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
        if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
            $literal = $tokens[$j][1];
            $values[$name] = stripcslashes(substr($literal, 1, -1));
        }
    }
    if (array_diff(array('servername', 'username', 'password', 'dbname'), array_keys($values))) {
        $add('connection.file', 'BLOCKED', 'Connection file must contain four literal assignments (values hidden).');
    } else {
        mysqli_report(MYSQLI_REPORT_OFF);
        $db = @new mysqli($values['servername'], $values['username'], $values['password'], $values['dbname']);
        if ($db->connect_errno) {
            $add('connection.db', 'BLOCKED', 'Could not establish the selected database connection (credentials hidden).');
            $db = null;
        } elseif (!$db->set_charset('utf8mb4')) $add('connection.charset', 'BLOCKED', 'Could not set utf8mb4.');
        else $add('connection.db', 'OK', 'Connection established from parsed literals; credentials and database name are hidden.');
        unset($values);
    }
}

$data = null;
if (!$template) {
    $bootstrapPath = $options['bootstrap-file'];
    $realRoot = realpath($root);
    $realBootstrap = realpath($bootstrapPath);
    if (!$realBootstrap || !is_file($realBootstrap) || !is_readable($realBootstrap)) {
        $add('bootstrap.file', 'BLOCKED', 'Bootstrap input is missing or unreadable (' . basename($bootstrapPath) . ').');
    } elseif ($realRoot !== false && ($realBootstrap === $realRoot || strpos($realBootstrap, $realRoot . DIRECTORY_SEPARATOR) === 0)) {
        $add('bootstrap.location', 'BLOCKED', 'Bootstrap input must be stored outside the repository (' . basename($realBootstrap) . ').');
    } else {
        $data = json_decode((string) file_get_contents($realBootstrap), true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) $add('bootstrap.json', 'BLOCKED', 'Bootstrap input is not a valid JSON object (' . basename($realBootstrap) . ').');
        else $add('bootstrap.json', 'OK', 'Bootstrap input loaded (' . basename($realBootstrap) . '); values are hidden.');
    }
}

$manifestPath = $root . '/database/install/manifest.json';
$manifest = null;
$manifestValid = true;
if (!is_file($manifestPath)) {
    $add('manifest.file', 'BLOCKED', 'database/install/manifest.json is missing.');
    $manifestValid = false;
} elseif (!is_readable($manifestPath)) {
    $add('manifest.file', 'BLOCKED', 'database/install/manifest.json is not readable.');
    $manifestValid = false;
} else {
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    if (!is_array($manifest) || json_last_error() !== JSON_ERROR_NONE) {
        $add('manifest.json', 'BLOCKED', 'database/install/manifest.json contains invalid JSON.');
        $manifestValid = false;
    } else {
        $manifestRequirements = array(
            'tables' => isset($manifest['tables']) && is_array($manifest['tables']),
            'triggers' => isset($manifest['triggers']) && is_array($manifest['triggers']),
            'seeds.required.file' => isset($manifest['seeds']['required']['file']) && is_string($manifest['seeds']['required']['file']) && trim($manifest['seeds']['required']['file']) !== '',
            'seeds.default_content.file' => isset($manifest['seeds']['default_content']['file']) && is_string($manifest['seeds']['default_content']['file']) && trim($manifest['seeds']['default_content']['file']) !== '',
        );
        foreach ($manifestRequirements as $field => $valid) {
            if (!$valid) {
                $add('manifest.' . str_replace('.', '_', $field), 'BLOCKED', "manifest.json must contain {$field} with the expected type.");
                $manifestValid = false;
            }
        }
        if ($manifestValid) $add('manifest.valid', 'OK', 'manifest.json contains all required installer fields.');
    }
}

$columns = array();
if (isset($db) && $db instanceof mysqli && !$db->connect_errno) {
    $tables = array();
    $result = $db->query('SHOW TABLES');
    if ($result) { while ($row = $result->fetch_row()) $tables[] = (string) $row[0]; $result->free(); }
    if ($manifestValid) {
        $missing = array_values(array_diff($manifest['tables'], $tables));
        $add('schema.tables', $missing ? 'BLOCKED' : 'OK', $missing ? 'Installed schema is incomplete; missing tables: ' . implode(', ', $missing) . '.' : 'All manifest tables are installed.');
    }
    $trigger = $db->query("SHOW TRIGGERS WHERE `Trigger` = 'generar_codigo_invitado'");
    $hasTrigger = $trigger && $trigger->num_rows === 1; if ($trigger) $trigger->free();
    $add('schema.trigger', $hasTrigger ? 'OK' : 'BLOCKED', $hasTrigger ? 'Required trigger generar_codigo_invitado exists.' : 'Required trigger generar_codigo_invitado is missing.');
    foreach (array('info_mostrar' => 8, 'intivados_acompanante' => 5, 'invitados_prioridad' => 4) as $tableName => $expected) {
        $actual = in_array($tableName, $tables, true) ? $count($db, $tableName) : null;
        $add('seed.' . $tableName, $actual === $expected ? 'OK' : 'BLOCKED', $actual === $expected ? "{$tableName} technical seed count is {$expected}." : "{$tableName} technical seed count must be {$expected}.");
    }
    $technicalKeys = array('plan_servicio','rsvp_modo','fuente_envios_whatsapp','whatsapp_enabled','regalos_enabled','rsvp_form_persist_enabled');
    $foundKeys = array();
    if (in_array('site_settings', $tables, true)) {
        $result = $db->query('SELECT setting_key FROM site_settings');
        if ($result) { while ($row = $result->fetch_row()) $foundKeys[] = (string) $row[0]; $result->free(); }
    }
    $missingKeys = array_values(array_diff($technicalKeys, $foundKeys));
    $add('seed.site_settings', $missingKeys ? 'BLOCKED' : 'OK', $missingKeys ? 'Technical site_settings keys are missing: ' . implode(', ', $missingKeys) . '.' : 'All technical site_settings keys exist.');

    if (in_array('user', $tables, true)) {
        $userColumns = array();
        $result = $db->query('SHOW COLUMNS FROM `user`');
        if ($result) { while ($row = $result->fetch_assoc()) $userColumns[$row['Field']] = $row; $result->free(); }
        $canonicalUser = isset($userColumns['id'], $userColumns['email'], $userColumns['password'])
            && stripos($userColumns['id']['Extra'], 'auto_increment') !== false;
        $add('schema.user_columns', $canonicalUser ? 'OK' : 'BLOCKED', $canonicalUser ? 'user has installer-required id, email, and password columns.' : 'user does not have the installer-required canonical columns.');
        $emailUnique = false;
        $result = $db->query('SHOW INDEX FROM `user`');
        if ($result) {
            while ($row = $result->fetch_assoc()) if ((int)$row['Non_unique'] === 0 && $row['Column_name'] === 'email') $emailUnique = true;
            $result->free();
        }
        $add('schema.user_email_unique', $emailUnique ? 'OK' : 'BLOCKED', $emailUnique ? 'user.email has a UNIQUE index.' : 'user.email must have a UNIQUE index.');
    }
    foreach (array('user', 'cliente', 'admin_config') as $tableName) {
        $actual = in_array($tableName, $tables, true) ? $count($db, $tableName) : null;
        $expected = 0;
        $add('empty.' . $tableName, $actual === $expected ? 'OK' : 'BLOCKED', $actual === $expected ? "{$tableName} is empty." : "{$tableName} already contains data; bootstrap is blocked.", array('count' => $actual));
    }
    if (in_array('cliente', $tables, true)) {
        $result = $db->query('SHOW FULL COLUMNS FROM `cliente`');
        if ($result) { while ($row = $result->fetch_assoc()) $columns[$row['Field']] = $row; $result->free(); }
    }
}

$bankColumns = array('cbu_titular','cbu','alias','cbu_dolar','alias_dolar','cotizacion_dolar','cbu_dolar_2','alias_dolar_2');
$editableColumns = array();
foreach ($columns as $name => $meta) if (!in_array($name, array_merge(array('id','user_id'), $bankColumns), true)) $editableColumns[] = $name;
if ($template && isset($db) && !$blocked()) {
    $clientTemplate = array();
    foreach ($editableColumns as $name) {
        $type = strtolower($columns[$name]['Type']);
        if ($columns[$name]['Null'] === 'YES') $placeholder = null;
        elseif (preg_match('/^date$/', $type)) $placeholder = 'YYYY-MM-DD';
        elseif (preg_match('/^(?:datetime|timestamp)/', $type)) $placeholder = 'YYYY-MM-DD 00:00:00';
        elseif (preg_match('/^(?:tiny|small|medium|big)?int/', $type)) $placeholder = '0';
        elseif (preg_match('/^(?:decimal|numeric|float|double)/', $type)) $placeholder = '0';
        else $placeholder = 'REEMPLAZAR';
        $clientTemplate[$name] = $placeholder;
    }
    $passwordPlaceholder = 'REEMPLAZAR_PASSWORD_MINIMO_6';
    $out = array('admin' => array('email' => 'admin@example.invalid', 'password' => $passwordPlaceholder, 'password_confirm' => $passwordPlaceholder), 'cliente' => $clientTemplate, 'settings' => array('plan_servicio'=>'oro','rsvp_modo'=>'codigo','fuente_envios_whatsapp'=>'invitados','whatsapp_enabled'=>'1','regalos_enabled'=>'1','rsvp_form_persist_enabled'=>'0'));
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

$clientValues = array(); $settings = array(); $email = null; $plainPassword = null;
if (is_array($data)) {
    $topUnknown = array_diff(array_keys($data), array('admin','cliente','settings'));
    if ($topUnknown) $add('input.top_level', 'BLOCKED', 'Unknown top-level keys: ' . implode(', ', $topUnknown) . '.');
    if (!isset($data['admin']) || !is_array($data['admin']) || !isset($data['cliente']) || !is_array($data['cliente']) || !isset($data['settings']) || !is_array($data['settings'])) {
        $add('input.shape', 'BLOCKED', 'admin, cliente, and settings must be JSON objects.');
    } else {
        $adminUnknown = array_diff(array_keys($data['admin']), array('email','password','password_confirm'));
        if ($adminUnknown) $add('admin.fields', 'BLOCKED', 'Admin contains unsupported fields (values hidden).');
        $email = strtolower(trim((string) ($data['admin']['email'] ?? '')));
        $plainPassword = (string) ($data['admin']['password'] ?? '');
        $confirm = (string) ($data['admin']['password_confirm'] ?? '');
        $add('admin.email', filter_var($email, FILTER_VALIDATE_EMAIL) ? 'OK' : 'BLOCKED', filter_var($email, FILTER_VALIDATE_EMAIL) ? 'Admin email is valid (value hidden).' : 'Admin email is invalid (value hidden).');
        $passwordOk = strlen($plainPassword) >= 6 && hash_equals($plainPassword, $confirm);
        $add('admin.password', $passwordOk ? 'OK' : 'BLOCKED', $passwordOk ? 'Admin password and confirmation are valid (values hidden).' : 'Password must be at least 6 characters and match confirmation (values hidden).');

        foreach ($data['cliente'] as $name => $value) {
            if (!is_string($name) || !isset($columns[$name])) { $add('cliente.unknown', 'BLOCKED', 'Unknown cliente column: ' . (string) $name . '.'); continue; }
            if (in_array($name, array('id','user_id'), true)) { $add('cliente.managed', 'BLOCKED', "cliente.{$name} is installer-managed and cannot be supplied."); continue; }
            if (in_array($name, $bankColumns, true)) { $add('cliente.banking', 'BLOCKED', "cliente.{$name} is blocked for UNI-048.4."); continue; }
            if (is_array($value) || is_object($value) || (is_string($value) && (preg_match('/<[^>]*>/', $value) || preg_match('~(?:^|[\\/])\.\.(?:[\\/]|$)~', $value)))) { $add('cliente.unsafe.' . $name, 'BLOCKED', "cliente.{$name} contains an unsupported value."); continue; }
            $type = strtolower($columns[$name]['Type']);
            $string = $value === null ? null : (string) $value;
            if ($string === null && $columns[$name]['Null'] !== 'YES') $add('cliente.null.' . $name, 'BLOCKED', "cliente.{$name} cannot be null.");
            elseif (preg_match('/^(?:var)?char\((\d+)\)/', $type, $m) && strlen((string) $string) > (int) $m[1]) $add('cliente.length.' . $name, 'BLOCKED', "cliente.{$name} exceeds maximum length {$m[1]}.");
            elseif (preg_match('/^(?:tiny|small|medium|big)?int/', $type) && $string !== null && !preg_match('/^-?\d+$/', $string)) $add('cliente.type.' . $name, 'BLOCKED', "cliente.{$name} must be an integer.");
            elseif (preg_match('/^(?:decimal|numeric)\((\d+),(\d+)\)/', $type, $m) && $string !== null && !preg_match('/^-?\d{1,' . ((int)$m[1]-(int)$m[2]) . '}(?:\.\d{1,' . (int)$m[2] . '})?$/', $string)) $add('cliente.type.' . $name, 'BLOCKED', "cliente.{$name} has an invalid decimal format.");
            elseif (preg_match('/^date$/', $type) && $string !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $string)) $add('cliente.type.' . $name, 'BLOCKED', "cliente.{$name} must use YYYY-MM-DD.");
            elseif (preg_match('/^(?:datetime|timestamp)/', $type) && $string !== null && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $string)) $add('cliente.type.' . $name, 'BLOCKED', "cliente.{$name} must use YYYY-MM-DD HH:MM:SS.");
            else $clientValues[$name] = $string;
        }
        foreach ($columns as $name => $meta) {
            if (in_array($name, array('id','user_id'), true) || array_key_exists($name, $clientValues)) continue;
            if ($meta['Null'] === 'NO' && $meta['Default'] === null && stripos($meta['Extra'], 'auto_increment') === false) $add('cliente.required.' . $name, 'BLOCKED', "Missing required cliente column: {$name}.");
        }
        if (!isset($columns['user_id'])) $add('cliente.user_id', 'WARN', 'cliente has no user_id column; schema permits an unlinked row.');

        $planConfigPath = $root . '/includes/plan_config.php';
        if (!is_file($planConfigPath) || !is_readable($planConfigPath)) {
            $add('settings.plan_config_file', 'BLOCKED', 'includes/plan_config.php is missing or unreadable.');
        } else {
            require_once $planConfigPath;
            if (!defined('DQS_PLAN_CONFIG_DEFAULTS') || !is_array(DQS_PLAN_CONFIG_DEFAULTS) || !function_exists('dqs_is_valid_plan_config_value')) {
                $add('settings.plan_config_contract', 'BLOCKED', 'includes/plan_config.php does not expose the required settings contract.');
            } else {
                foreach ($data['settings'] as $key => $value) {
                    $value = is_scalar($value) ? (string) $value : '';
                    if (!array_key_exists($key, DQS_PLAN_CONFIG_DEFAULTS)) $add('settings.unknown.' . $key, 'BLOCKED', "Unsupported site setting: {$key}.");
                    elseif (!dqs_is_valid_plan_config_value($key, $value)) $add('settings.invalid.' . $key, 'BLOCKED', "Invalid value for site setting {$key} (value hidden).");
                    else $settings[$key] = $value;
                }
            }
        }
        $add('settings.valid', count($settings) === count($data['settings']) ? 'OK' : 'BLOCKED', 'Site setting keys and values were validated against plan_config (values hidden).', array('count' => count($settings)));
    }
}

$bind = static function (mysqli_stmt $stmt, array &$params) {
    $refs = array(); foreach ($params as $key => &$value) $refs[$key] = &$value;
    return call_user_func_array(array($stmt, 'bind_param'), $refs);
};
if ($apply && !$blocked()) {
    $engines = array(); $result = $db->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('user','cliente','site_settings')");
    if ($result) { while ($row = $result->fetch_assoc()) $engines[$row['TABLE_NAME']] = strtoupper((string)$row['ENGINE']); $result->free(); }
    if (count($engines) !== 3 || array_diff($engines, array('INNODB'))) $add('transaction.engines', 'BLOCKED', 'user, cliente, and site_settings must all use InnoDB for atomic apply.');
    else {
        $db->begin_transaction();
        try {
            $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
            if ($hash === false) throw new RuntimeException('Could not hash password.');
            $stmt = $db->prepare('INSERT INTO `user` (`email`,`password`) VALUES (?,?)');
            if (!$stmt) throw new RuntimeException('Could not prepare user insert.');
            $stmt->bind_param('ss', $email, $hash); if (!$stmt->execute()) throw new RuntimeException('Could not insert user.');
            $userId = $db->insert_id; $stmt->close();

            $insertClient = $clientValues;
            if (isset($columns['user_id'])) $insertClient = array_merge(array('user_id' => (string)$userId), $insertClient);
            $names = array_keys($insertClient);
            $sql = 'INSERT INTO `cliente` (`' . implode('`,`', $names) . '`) VALUES (' . implode(',', array_fill(0, count($names), '?')) . ')';
            $stmt = $db->prepare($sql); if (!$stmt) throw new RuntimeException('Could not prepare cliente insert.');
            $params = array_merge(array(str_repeat('s', count($insertClient))), array_values($insertClient));
            if (!$bind($stmt, $params) || !$stmt->execute()) throw new RuntimeException('Could not insert cliente.'); $stmt->close();

            $stmt = $db->prepare('INSERT INTO `site_settings` (`setting_key`,`setting_value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`)');
            if (!$stmt) throw new RuntimeException('Could not prepare settings upsert.');
            foreach ($settings as $key => $value) { $stmt->bind_param('ss', $key, $value); if (!$stmt->execute()) throw new RuntimeException('Could not upsert settings.'); }
            $stmt->close(); $db->commit();
            $add('apply', 'OK', 'Atomic bootstrap committed; no secrets were emitted.', array('writes' => 2 + count($settings)));
        } catch (Throwable $e) {
            $db->rollback();
            $add('apply', 'FAILED', 'Bootstrap failed and was rolled back; diagnostic details and secrets are hidden.');
        }
        unset($confirm, $hash);
    }
}
if (!$apply) $add('mode', 'OK', 'Dry-run completed with no INSERT or UPDATE statements.', array('writes' => 0));

if ($apply && !$blocked()) {
    foreach (array('user'=>1,'cliente'=>1,'admin_config'=>0,'invitados'=>0,'productos'=>0,'regalos'=>0) as $tableName => $expected) {
        $actual = $count($db, $tableName);
        $add('post.' . $tableName, $actual === $expected ? 'OK' : 'FAILED', "Post-apply {$tableName} count is {$actual}; expected {$expected}.", array('count'=>$actual));
    }
    $result = $db->query('SELECT `password` FROM `user` LIMIT 1'); $row = $result ? $result->fetch_assoc() : null; if ($result) $result->free();
    $storedPassword = is_array($row) && isset($row['password']) ? (string) $row['password'] : '';
    $passwordInfo = password_get_info($storedPassword);
    $safeHash = $storedPassword !== ''
        && isset($passwordInfo['algo'])
        && $passwordInfo['algo'] !== null
        && $passwordInfo['algo'] !== 0
        && !hash_equals($storedPassword, (string) $plainPassword)
        && password_verify((string) $plainPassword, $storedPassword);
    $add('post.password', $safeHash ? 'OK' : 'FAILED', $safeHash ? 'Stored password is a recognized one-way hash; plaintext is not stored.' : 'Stored password hash verification failed.');
    foreach (array_keys($settings) as $key) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM site_settings WHERE setting_key=?'); $stmt->bind_param('s',$key); $stmt->execute(); $result=$stmt->get_result(); $row=$result->fetch_row(); $stmt->close();
        if ((int)$row[0] !== 1) $add('post.setting.' . $key, 'FAILED', "Expected site setting {$key} is absent.");
    }
}

$overall = 'OK';
foreach ($checks as $check) { if ($check['status'] === 'FAILED') { $overall = 'FAILED'; break; } if ($check['status'] === 'BLOCKED') $overall = 'BLOCKED'; elseif ($check['status'] === 'WARN' && $overall === 'OK') $overall = 'WARN'; }
if ($jsonOutput) echo json_encode(array('status'=>$overall,'mode'=>$apply?'apply':'dry-run','checks'=>$checks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
else { echo "DQS bootstrap: {$overall} (" . ($apply ? 'apply' : 'dry-run') . ")\n"; foreach ($checks as $check) echo '[' . $check['status'] . '] ' . $check['message'] . "\n"; }
unset($plainPassword);
if (isset($db) && $db instanceof mysqli) $db->close();
exit(in_array($overall, array('BLOCKED','FAILED'), true) ? 1 : 0);
