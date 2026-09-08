<?php
/** Web-only DB preflight helpers (UNI-049.4). No database writes are performed here. */

require_once __DIR__ . '/install_cli_executor.php';

function dqs_install_web_preflight_redact($value, $key = '')
{
    if (preg_match('/password|passwd|pwd|dbname|username|servername|host|email/i', (string) $key)) return '[REDACTED]';
    if (is_array($value)) {
        $safe = array();
        foreach ($value as $k => $v) $safe[$k] = dqs_install_web_preflight_redact($v, (string) $k);
        return $safe;
    }
    if (!is_string($value)) return is_int($value) || is_float($value) || is_bool($value) || $value === null ? $value : '[REDACTED]';
    $value = preg_replace('/connection\.php/i', '[connection-file]', $value);
    $value = preg_replace('/bootstrap\.json/i', '[bootstrap-file]', $value);
    $patterns = array(
        '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i',
        '/\bu\d{4,}(?:_[A-Za-z0-9_-]+)?\b/i',
        '~(?<![A-Za-z0-9])/(?:home|tmp|var|workspace)(?:/[^\s,;:)\]}]+)*~',
        '/\b(?:password|passwd|pwd|dbname|username|servername|host|email)\b\s*[:=]?\s*[^\s,;]*/i',
        '/\b[A-Za-z0-9_=-]{32,}\b/',
    );
    return preg_replace($patterns, '[REDACTED]', $value);
}

/** Reduce an executor response to the only fields the web UI may retain. */
function dqs_install_web_preflight_safe_result(array $result)
{
    $statuses = array('OK','WARN','BLOCKED','FAILED');
    $status = in_array($result['status'] ?? null, $statuses, true) ? $result['status'] : 'FAILED';
    $summary = is_array($result['summary'] ?? null) ? $result['summary'] : array();
    $safe = array(
        'status'=>$status,
        'exit_code'=>is_int($result['exit_code'] ?? null) ? $result['exit_code'] : null,
        'duration_ms'=>max(0, (int) ($result['duration_ms'] ?? 0)),
        'summary'=>array(), 'checks'=>array(), 'errors'=>array(),
    );
    foreach (array('check_count','warning_count','blocked_count','failed_count') as $key) $safe['summary'][$key] = max(0, (int) ($summary[$key] ?? 0));
    foreach (is_array($result['checks'] ?? null) ? $result['checks'] : array() as $check) {
        if (!is_array($check)) continue;
        $checkStatus = in_array($check['status'] ?? null, $statuses, true) ? $check['status'] : 'FAILED';
        $safe['checks'][] = array(
            'id'=>dqs_install_web_preflight_redact((string) ($check['id'] ?? 'check')),
            'status'=>$checkStatus,
            'message'=>dqs_install_web_preflight_redact((string) ($check['message'] ?? '')),
            'details'=>dqs_install_web_preflight_redact(is_array($check['details'] ?? null) ? $check['details'] : array()),
        );
    }
    foreach (is_array($result['errors'] ?? null) ? $result['errors'] : array() as $error) {
        if (is_scalar($error) || $error === null) $safe['errors'][] = dqs_install_web_preflight_redact((string) $error);
    }
    $safe['summary']['check_count'] = count($safe['checks']);
    return $safe;
}

function dqs_install_web_preflight_blocked($message)
{
    return dqs_install_web_preflight_safe_result(array('status'=>'BLOCKED', 'exit_code'=>null, 'duration_ms'=>0,
        'summary'=>array(), 'checks'=>array(array('id'=>'web_input','status'=>'BLOCKED','message'=>$message,'details'=>array())), 'errors'=>array()));
}

function dqs_install_web_preflight_validate(array $input)
{
    $limits = array('host'=>255, 'dbname'=>128, 'username'=>128, 'password'=>512);
    foreach ($limits as $key=>$limit) {
        if (!isset($input[$key]) || !is_string($input[$key]) || $input[$key] === '' || strlen($input[$key]) > $limit
            || strpos($input[$key], "\0") !== false || ($key !== 'password' && preg_match('/[\x00-\x1F\x7F]/', $input[$key]))
            || preg_match('/<\s*\/?\s*(?:script|html|iframe|object|svg)\b/i', $input[$key])) return false;
    }
    if (!preg_match('/\A[A-Za-z0-9_.:\[\]-]+\z/', $input['host'])) return false;
    if (!preg_match('/\A[A-Za-z0-9_-]+\z/', $input['dbname']) || !preg_match('/\A[A-Za-z0-9_.-]+\z/', $input['username'])) return false;
    return !preg_match('/[\x00-\x1F\x7F]/', $input['password']);
}

function dqs_install_web_preflight_cleanup($path, $runtime)
{
    if (!is_string($path) || !is_string($runtime) || $path === '' || is_link($path)) return;
    $parent = realpath(dirname($path)); $sessions = realpath($runtime . DIRECTORY_SEPARATOR . 'web_sessions');
    if ($parent === false || $sessions === false || !dqs_install_web_path_inside($parent, $sessions)) return;
    if (is_file($path)) @unlink($path);
    if ($parent !== $sessions && is_dir($parent)) @rmdir($parent);
}

function dqs_install_web_preflight_create_connection($runtime, array $input)
{
    $sessions = $runtime . DIRECTORY_SEPARATOR . 'web_sessions';
    if ((file_exists($sessions) && (is_link($sessions) || !is_dir($sessions))) || (!is_dir($sessions) && !@mkdir($sessions, 0700))) return null;
    @chmod($sessions, 0700);
    $dir = $sessions . DIRECTORY_SEPARATOR . bin2hex(random_bytes(24));
    if (!@mkdir($dir, 0700) || is_link($dir)) return null;
    @chmod($dir, 0700);
    $path = $dir . DIRECTORY_SEPARATOR . 'connection.php';
    $handle = @fopen($path, 'x');
    if ($handle === false) { @rmdir($dir); return null; }
    $php = "<?php\n\$servername = " . var_export($input['host'], true) . ";\n"
        . "\$username = " . var_export($input['username'], true) . ";\n"
        . "\$password = " . var_export($input['password'], true) . ";\n"
        . "\$dbname = " . var_export($input['dbname'], true) . ";\n";
    $written = fwrite($handle, $php); fflush($handle); fclose($handle); @chmod($path, 0600);
    if ($written !== strlen($php) || is_link($path)) { dqs_install_web_preflight_cleanup($path, $runtime); return null; }
    return $path;
}

function dqs_install_web_preflight_run($runtime, array $input)
{
    if (!dqs_install_web_preflight_validate($input)) return array(dqs_install_web_preflight_blocked('Los campos de conexión no cumplen el formato seguro.'), null);
    $path = dqs_install_web_preflight_create_connection($runtime, $input);
    if ($path === null) return array(dqs_install_web_preflight_blocked('No fue posible preparar el runtime privado.'), null);
    $result = dqs_install_web_preflight_safe_result(dqs_install_execute('preflight', array('connection_file'=>$path), array('timeout_seconds'=>30, 'max_output_bytes'=>262144)));
    if (!in_array($result['status'], array('OK','WARN'), true)) { dqs_install_web_preflight_cleanup($path, $runtime); $path = null; }
    return array($result, $path);
}
