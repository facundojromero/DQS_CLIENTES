<?php
/** Safe bridge to the UNI-048 installer CLIs (UNI-049.2 through UNI-049.8). */

const DQS_INSTALL_EXECUTOR_CONTRACT = 'DQS_INSTALL_EXECUTOR_V1';

function dqs_install_executor_result($operation, $status, $error, $started = null, $mode = 'dry-run')
{
    return array(
        'contract_version' => DQS_INSTALL_EXECUTOR_CONTRACT,
        'operation' => is_string($operation) ? preg_replace('/[^a-z_]/', '', $operation) : '',
        'mode' => $mode, 'status' => $status, 'exit_code' => null,
        'timed_out' => false,
        'duration_ms' => $started === null ? 0 : (int) round((microtime(true) - $started) * 1000),
        'run_id' => bin2hex(random_bytes(16)),
        'summary' => array('source_status' => null, 'php_binary_source' => null, 'check_count' => 0, 'warning_count' => 0, 'blocked_count' => 0, 'failed_count' => 0),
        'checks' => array(), 'errors' => $error === null ? array() : array($error),
    );
}

function dqs_install_executor_redact($value, $key = '')
{
    if (preg_match('/password|passwd|pwd|dbname|username|servername|host|email/i', (string) $key)) return '[REDACTED]';
    if (is_array($value)) {
        $safe = array();
        foreach ($value as $k => $v) $safe[$k] = dqs_install_executor_redact($v, (string) $k);
        return $safe;
    }
    if (!is_string($value)) return is_scalar($value) || $value === null ? $value : '[REDACTED]';
    $patterns = array(
        '/\.install\.lock\.pending/i',
        '/install\.lock/i',
        '/admin_publish\.json/i',
        '/admin_config\.json/i',
        '/bootstrap\.json/i',
        '/connection\.php/i',
        '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i',
        '/\bu\d{6,}(?:_[A-Za-z0-9_]+)?\b/i',
        '~(?<![A-Za-z0-9])/(?:home|tmp)(?:/[^\s,;:)\]}]+)*~',
        '~(?<![A-Za-z0-9])' . preg_quote(dirname(__DIR__, 2), '~') . '(?:/[^\s,;:)\]}]+)*~',
        '/\b(?:password|passwd|pwd|dbname|username|servername|host|email)\b\s*[:=]\s*[^\s,;]+/i',
        '/\b(?:password|passwd|pwd|dbname|username|servername|host|email)\b/i',
        '/\b[A-Za-z0-9_-]{32,}\b/',
    );
    $replacements = array('[install-lock-pending]','[install-lock]','[admin-publish-file]','[admin-config-file]','[bootstrap-file]','[connection-file]',
        '[REDACTED]','[REDACTED]','[REDACTED]','[REDACTED]','[REDACTED]','[REDACTED]','[REDACTED]');
    return preg_replace($patterns, $replacements, $value);
}

function dqs_install_executor_inside($path, $root)
{
    return $path === $root || strpos($path, $root . DIRECTORY_SEPARATOR) === 0;
}

/**
 * Check IDs are machine labels, not free-form diagnostics. SHA-1 suffixes are a
 * documented part of preflight's manifest.reference IDs and are not secrets.
 */
function dqs_install_executor_check_id_is_safe($id)
{
    if (!is_string($id) || $id === '' || strlen($id) > 160
        || preg_match('/[\x00-\x1F\x7F]/', $id)
        || !preg_match('/\A[A-Za-z0-9_.:=-]+\z/', $id)
        || preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $id)) return false;

    $withoutKnownDigest = preg_replace('/\Amanifest\.reference\.[a-f0-9]{40}\z/', '', $id);
    return $withoutKnownDigest === '' || !preg_match('/\b[A-Za-z0-9_-]{32,}\b/', $id);
}

/** Validate a PHP executable without a shell and without exposing its path/output. */
function dqs_install_executor_validate_php_cli($candidate, $repo)
{
    if (!is_string($candidate) || $candidate === '' || $candidate[0] !== '/' || strpos($candidate, "\0") !== false
        || is_link($candidate) || !is_file($candidate) || !is_executable($candidate)) return null;
    $binary = realpath($candidate); $repoReal = realpath($repo);
    if ($binary === false || $repoReal === false || dqs_install_executor_inside($binary, $repoReal)) return null;

    $pipes = array();
    $process = @proc_open(array($binary, '-r', 'echo PHP_SAPI;'), array(
        0=>array('pipe','r'), 1=>array('pipe','w'), 2=>array('pipe','w'),
    ), $pipes, $repoReal, array('PATH'=>'/usr/bin:/bin','LANG'=>'C','LC_ALL'=>'C'), array('bypass_shell'=>true));
    if (!is_resource($process)) return null;
    fclose($pipes[0]); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
    $stdout=''; $stderr=''; $started=microtime(true); $lastExit=null; $timedOut=false;
    while (true) {
        $stdout.=(string)stream_get_contents($pipes[1]); $stderr.=(string)stream_get_contents($pipes[2]);
        $state=proc_get_status($process);
        if (!$state['running']) { $lastExit=$state['exitcode']; break; }
        if (microtime(true)-$started>2.0 || strlen($stdout)+strlen($stderr)>1024) { $timedOut=true; @proc_terminate($process, 9); break; }
        usleep(10000);
    }
    $stdout.=(string)stream_get_contents($pipes[1]); $stderr.=(string)stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $closed=proc_close($process);
    $exit=$lastExit!==null && $lastExit>=0 ? $lastExit : $closed;
    return !$timedOut && $exit===0 && $stdout==='cli' && $stderr==='' ? $binary : null;
}

/** Resolve a CLI SAPI binary in explicit-to-fallback priority order. */
function dqs_install_executor_resolve_php_cli($repo, array $policy = array())
{
    $groups=array();
    if (array_key_exists('php_binary',$policy)) $groups[]=array($policy['php_binary'],'policy');
    $environment=getenv('DQS_INSTALLER_PHP_CLI');
    if (is_string($environment) && $environment!=='') $groups[]=array($environment,'env');
    if (PHP_SAPI==='cli') $groups[]=array(PHP_BINARY,'php_binary');
    $groups[]=array(PHP_BINDIR . DIRECTORY_SEPARATOR . 'php','php_bindir');
    foreach (array('/usr/bin/php','/bin/php','/usr/local/bin/php','/opt/alt/php74/usr/bin/php','/opt/alt/php80/usr/bin/php',
        '/opt/alt/php81/usr/bin/php','/opt/alt/php82/usr/bin/php','/opt/alt/php83/usr/bin/php') as $known) $groups[]=array($known,'known_path');
    $seen=array();
    foreach ($groups as $group) {
        $candidate=$group[0];
        if (!is_string($candidate) || isset($seen[$candidate])) continue;
        $seen[$candidate]=true; $valid=dqs_install_executor_validate_php_cli($candidate,$repo);
        if ($valid!==null) return array('path'=>$valid,'source'=>$group[1]);
    }
    return null;
}

function dqs_install_executor_path($value, $kind, $repo, array $targetRoots, $allowRepoTarget = false)
{
    if (!is_string($value) || $value === '' || strpos($value, "\0") !== false) return null;
    $real = realpath($value);
    if ($real === false || is_link($value)) return null;
    if ($kind === 'template') {
        return is_dir($real) && dqs_install_executor_inside($real, $repo) && basename($real) !== 'admin_tmp' ? $real : null;
    }
    if ($kind === 'target') {
        if (!is_dir($real) || (dqs_install_executor_inside($real, $repo) && !($allowRepoTarget && $real === $repo))) return null;
        foreach ($targetRoots as $allowed) {
            $allowedReal = is_string($allowed) ? realpath($allowed) : false;
            if ($allowedReal !== false && !is_link($allowed) && $real === $allowedReal) return $real;
        }
        return null;
    }
    return is_file($real) && is_readable($real) && !dqs_install_executor_inside($real, $repo) ? $real : null;
}

/**
 * @param array $params Typed operation parameters (never argv fragments).
 * @param array $policy Internal policy: allow_current_connection and the operation-specific apply policies, target_roots, timeout_seconds, max_output_bytes, php_binary.
 */
function dqs_install_execute($operation, array $params = array(), array $policy = array())
{
    $started = microtime(true);
    $repo = realpath(dirname(__DIR__, 2));
    $specs = array(
        'preflight' => array('script'=>'tools/dqs_install_preflight.php', 'keys'=>array('mode','no_db','connection_file','using_current_connection')),
        'schema_runner' => array('script'=>'tools/dqs_install_schema_runner.php', 'keys'=>array('mode','connection_file','include_default_content','confirm_empty_install')),
        'bootstrap' => array('script'=>'tools/dqs_install_bootstrap.php', 'keys'=>array('mode','connection_file','bootstrap_file','confirm_bootstrap')),
        'admin_publish' => array('script'=>'tools/dqs_install_admin_publish.php', 'keys'=>array('mode','connection_file','admin_template_dir','target_root','admin_config_file','admin_slug','confirm_admin_publish')),
        'finalize' => array('script'=>'tools/dqs_install_finalize.php', 'keys'=>array('mode','connection_file','target_root','admin_slug','confirm_finalize')),
    );
    if (!is_string($operation) || !isset($specs[$operation])) return dqs_install_executor_result($operation, 'FAILED', 'Unknown operation.', $started);
    foreach ($params as $key => $unused) if (!in_array($key, $specs[$operation]['keys'], true)) return dqs_install_executor_result($operation, 'BLOCKED', 'Unknown or forbidden parameter.', $started);
    $mode = $params['mode'] ?? 'dry-run';
    if (!in_array($mode, array('dry-run','apply'), true)) return dqs_install_executor_result($operation, 'BLOCKED', 'Invalid execution mode.', $started);
    $schemaApply = $operation === 'schema_runner' && $mode === 'apply';
    $bootstrapApply = $operation === 'bootstrap' && $mode === 'apply';
    $adminPublishApply = $operation === 'admin_publish' && $mode === 'apply';
    $finalizeApply = $operation === 'finalize' && $mode === 'apply';
    $applyAllowed = ($schemaApply && ($policy['allow_schema_apply'] ?? false) === true)
        || ($bootstrapApply && ($policy['allow_bootstrap_apply'] ?? false) === true)
        || ($adminPublishApply && ($policy['allow_admin_publish_apply'] ?? false) === true)
        || ($finalizeApply && ($policy['allow_finalize_apply'] ?? false) === true);
    if ($mode === 'apply' && !$applyAllowed) return dqs_install_executor_result($operation, 'BLOCKED', 'Apply is not enabled for this operation.', $started, $mode);
    if ($schemaApply && ($params['confirm_empty_install'] ?? false) !== true) return dqs_install_executor_result($operation, 'BLOCKED', 'Schema apply requires its dedicated confirmation.', $started, $mode);
    if ($bootstrapApply && ($params['confirm_bootstrap'] ?? false) !== true) return dqs_install_executor_result($operation, 'BLOCKED', 'Bootstrap apply requires its dedicated confirmation.', $started, $mode);
    if ($adminPublishApply && ($params['confirm_admin_publish'] ?? false) !== true) return dqs_install_executor_result($operation, 'BLOCKED', 'Admin publish apply requires its dedicated confirmation.', $started, $mode);
    if ($finalizeApply && ($params['confirm_finalize'] ?? false) !== true) return dqs_install_executor_result($operation, 'BLOCKED', 'Finalize apply requires its dedicated confirmation.', $started, $mode);
    if (!$schemaApply && array_key_exists('confirm_empty_install', $params)) return dqs_install_executor_result($operation, 'BLOCKED', 'A confirmation flag is not allowed in this mode.', $started, $mode);
    if (!$bootstrapApply && array_key_exists('confirm_bootstrap', $params)) return dqs_install_executor_result($operation, 'BLOCKED', 'A confirmation flag is not allowed in this mode.', $started, $mode);
    if (!$adminPublishApply && array_key_exists('confirm_admin_publish', $params)) return dqs_install_executor_result($operation, 'BLOCKED', 'A confirmation flag is not allowed in this mode.', $started, $mode);
    if (!$finalizeApply && array_key_exists('confirm_finalize', $params)) return dqs_install_executor_result($operation, 'BLOCKED', 'A confirmation flag is not allowed in this mode.', $started, $mode);
    foreach (array('no_db','using_current_connection','include_default_content','confirm_empty_install','confirm_bootstrap','confirm_admin_publish','confirm_finalize') as $boolean) {
        if (isset($params[$boolean]) && !is_bool($params[$boolean])) return dqs_install_executor_result($operation, 'BLOCKED', 'A flag has an invalid type.', $started);
    }
    if (!empty($params['using_current_connection']) && empty($policy['allow_current_connection'])) return dqs_install_executor_result($operation, 'BLOCKED', 'Current connection use is not enabled.', $started);
    if (isset($params['admin_slug']) && (!is_string($params['admin_slug']) || !preg_match('/\Aadmin[A-Za-z0-9]{4,28}\z/', $params['admin_slug']) || strtolower($params['admin_slug']) === 'admin_tmp')) return dqs_install_executor_result($operation, 'BLOCKED', 'Invalid admin slug.', $started);

    $pathKinds = array('connection_file'=>'file','bootstrap_file'=>'file','admin_config_file'=>'file','admin_template_dir'=>'template','target_root'=>'target');
    $paths = array(); $targetRoots = isset($policy['target_roots']) && is_array($policy['target_roots']) ? $policy['target_roots'] : array();
    foreach ($pathKinds as $key => $kind) if (array_key_exists($key, $params)) {
        $paths[$key] = dqs_install_executor_path($params[$key], $kind, $repo, $targetRoots, ($policy['allow_repo_target'] ?? false) === true);
        if ($paths[$key] === null) return dqs_install_executor_result($operation, 'BLOCKED', 'A path did not satisfy the internal policy.', $started);
    }

    $phpCli=dqs_install_executor_resolve_php_cli($repo,$policy);
    if ($phpCli===null) return dqs_install_executor_result($operation,'FAILED','PHP CLI binary could not be resolved.',$started);
    $command = array($phpCli['path'], $repo . '/' . $specs[$operation]['script']);
    if (!empty($params['no_db'])) $command[] = '--no-db';
    if (!empty($params['using_current_connection'])) $command[] = '--using-current-connection';
    if (!empty($params['include_default_content'])) $command[] = '--include-default-content';
    if ($schemaApply) { $command[] = '--apply'; $command[] = '--confirm-empty-install'; }
    if ($bootstrapApply) { $command[] = '--apply'; $command[] = '--confirm-bootstrap'; }
    if ($adminPublishApply) { $command[] = '--apply'; $command[] = '--confirm-admin-publish'; }
    if ($finalizeApply) { $command[] = '--apply'; $command[] = '--confirm-finalize'; }
    foreach (array('connection_file','bootstrap_file','admin_template_dir','target_root','admin_config_file') as $key) if (isset($paths[$key])) $command[] = '--' . str_replace('_', '-', $key) . '=' . $paths[$key];
    if (isset($params['admin_slug'])) $command[] = '--admin-slug=' . $params['admin_slug'];
    $command[] = '--json';

    $pipes = array();
    $process = @proc_open($command, array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')), $pipes, $repo, array('PATH'=>'/usr/bin:/bin','LANG'=>'C','LC_ALL'=>'C'), array('bypass_shell'=>true));
    if (!is_resource($process)) return dqs_install_executor_result($operation, 'FAILED', 'CLI process could not be started.', $started);
    fclose($pipes[0]); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
    $stdout = ''; $stderr = ''; $timedOut = false; $overflow = false; $lastExit = null;
    $timeout = isset($policy['timeout_seconds']) ? max(1, min(120, (int)$policy['timeout_seconds'])) : 30;
    $limit = isset($policy['max_output_bytes']) ? max(1024, min(1048576, (int)$policy['max_output_bytes'])) : 262144;
    while (true) {
        $stdout .= (string) stream_get_contents($pipes[1]); $stderr .= (string) stream_get_contents($pipes[2]);
        $state = proc_get_status($process); if (!$state['running']) { $lastExit = $state['exitcode']; break; }
        if ((microtime(true)-$started) > $timeout) { $timedOut=true; proc_terminate($process, 9); break; }
        if (strlen($stdout)+strlen($stderr) > $limit) { $overflow=true; proc_terminate($process, 9); break; }
        usleep(10000);
    }
    $stdout .= (string) stream_get_contents($pipes[1]); $stderr .= (string) stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $closed = proc_close($process); $exit = $lastExit !== null && $lastExit >= 0 ? $lastExit : $closed;
    $result = dqs_install_executor_result($operation, 'FAILED', null, $started, $mode); $result['exit_code']=$exit; $result['timed_out']=$timedOut; $result['summary']['php_binary_source']=$phpCli['source'];
    if ($timedOut) { $result['errors'][]='CLI execution timed out.'; return $result; }
    if ($overflow || strlen($stdout)+strlen($stderr)>$limit) { $result['errors'][]='CLI output exceeded the safe limit.'; return $result; }
    if (trim($stderr)!=='') { $result['errors'][]='CLI produced unexpected diagnostic output.'; return $result; }
    $source = json_decode(trim($stdout), true);
    if (!is_array($source) || json_last_error()!==JSON_ERROR_NONE || !isset($source['status']) || !in_array($source['status'], array('OK','WARN','BLOCKED','FAILED'), true)) { $result['errors'][]='CLI returned an invalid JSON contract.'; return $result; }
    $sourceStatus=$source['status']; $coherent=($exit===0 && in_array($sourceStatus,array('OK','WARN'),true)) || ($exit===1 && in_array($sourceStatus,array('BLOCKED','FAILED'),true));
    if (!$coherent) { $result['summary']['source_status']=$sourceStatus; $result['errors'][]='CLI exit code and status were inconsistent.'; return $result; }
    $result['status']=$sourceStatus; $result['summary']['source_status']=$sourceStatus;
    foreach (($source['checks'] ?? array()) as $index => $check) {
        $id = is_array($check) && array_key_exists('id', $check) ? $check['id'] : null;
        $status = is_array($check) && array_key_exists('status', $check) ? $check['status'] : null;
        if (!dqs_install_executor_check_id_is_safe($id) || !is_string($status) || !in_array($status, array('OK','WARN','BLOCKED','FAILED'), true)) {
            $result['status']='FAILED'; $result['checks']=array(); $result['errors'][]='Invalid check at index ' . (int) $index . '.'; return $result;
        }
        $message = array_key_exists('message', $check) ? $check['message'] : '';
        if (!is_string($message)) $message = is_scalar($message) || $message === null ? (string) $message : '[non-scalar message]';
        $details = isset($check['details']) && is_array($check['details']) ? $check['details'] : array();
        $safeId = preg_replace('/\b(?:password|passwd|pwd|dbname|username|servername|host|email)\b/i', '[REDACTED]', $id);
        $result['checks'][]=array('id'=>dqs_install_executor_redact($safeId),'status'=>$status,'message'=>dqs_install_executor_redact($message),'details'=>dqs_install_executor_redact($details));
    }
    $result['summary']['check_count']=count($result['checks']);
    foreach ($result['checks'] as $check) { if($check['status']==='WARN')$result['summary']['warning_count']++; elseif($check['status']==='BLOCKED')$result['summary']['blocked_count']++; elseif($check['status']==='FAILED')$result['summary']['failed_count']++; }
    $result['duration_ms']=(int)round((microtime(true)-$started)*1000);
    return $result;
}
