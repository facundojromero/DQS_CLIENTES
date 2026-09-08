<?php
/** Controlled Schema dry-run/apply web helpers (UNI-049.5). */

require_once __DIR__ . '/install_web_preflight.php';

function dqs_install_web_schema_safe_result(array $result)
{
    return dqs_install_web_preflight_safe_result($result);
}

function dqs_install_web_schema_blocked($message)
{
    return dqs_install_web_preflight_blocked($message);
}

/** Resolve only the connection.php owned by this private runtime session. */
function dqs_install_web_schema_connection($path, $runtime)
{
    if (!is_string($path) || !is_string($runtime) || $path === '' || is_link($path) || !is_file($path) || !is_readable($path)) return null;
    $real = realpath($path); $sessions = realpath($runtime . DIRECTORY_SEPARATOR . 'web_sessions');
    if ($real === false || $sessions === false || basename($real) !== 'connection.php' || !dqs_install_web_path_inside($real, $sessions)) return null;
    $parent = dirname($real);
    return dirname($parent) === $sessions && preg_match('/\A[a-f0-9]{48}\z/', basename($parent)) ? $real : null;
}

function dqs_install_web_schema_fingerprint($connection, $includeDefault, $createdAt)
{
    return array(
        'connection_id'=>hash('sha256', $connection),
        'include_default_content'=>(bool) $includeDefault,
        'operation'=>'schema_runner', 'mode'=>'dry-run', 'created_at_utc'=>$createdAt,
    );
}

function dqs_install_web_schema_fingerprint_valid($stored, $connection, $includeDefault)
{
    if (!is_array($stored) || !isset($stored['created_at_utc']) || !is_string($stored['created_at_utc'])) return false;
    $expected=dqs_install_web_schema_fingerprint($connection,$includeDefault,$stored['created_at_utc']);
    return hash_equals(hash('sha256',json_encode($expected)),hash('sha256',json_encode($stored)));
}

function dqs_install_web_schema_run_dry($connection, $includeDefault)
{
    return dqs_install_web_schema_safe_result(dqs_install_execute('schema_runner', array(
        'mode'=>'dry-run','connection_file'=>$connection,'include_default_content'=>(bool)$includeDefault,
    ), array('timeout_seconds'=>60,'max_output_bytes'=>262144)));
}

function dqs_install_web_schema_run_apply($connection, $includeDefault)
{
    return dqs_install_web_schema_safe_result(dqs_install_execute('schema_runner', array(
        'mode'=>'apply','connection_file'=>$connection,'include_default_content'=>(bool)$includeDefault,'confirm_empty_install'=>true,
    ), array('allow_schema_apply'=>true,'timeout_seconds'=>120,'max_output_bytes'=>262144)));
}
