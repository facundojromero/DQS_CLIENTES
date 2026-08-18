<?php
/** Read-only static audit for the UNI-049 web installer. CLI only. */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$root = realpath(dirname(__DIR__));
$webFiles = array(
    'install/index.php',
    'includes/install/install_web_gate.php',
    'includes/install/install_web_preflight.php',
    'includes/install/install_web_schema.php',
    'includes/install/install_web_bootstrap.php',
    'includes/install/install_web_admin_publish.php',
    'includes/install/install_web_finalize.php',
    'tools/dqs_install_web_gate_prepare.php',
);
$checks = array();

function dqs_web_audit_add(&$checks, $id, $ok, $detail)
{
    $checks[] = array('id'=>$id, 'status'=>$ok ? 'OK' : 'FAILED', 'detail'=>$detail);
}

$sources = array();
foreach ($webFiles as $relative) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $sources[$relative] = is_file($path) ? file_get_contents($path) : false;
    dqs_web_audit_add($checks, 'file.' . str_replace(array('/','.php'), array('.',''), $relative), $sources[$relative] !== false, 'required source is readable');
}

$forbidden = '/\b(?:shell_exec|passthru|system|exec|popen|proc_open)\s*\(/i';
foreach ($sources as $relative=>$source) {
    dqs_web_audit_add($checks, 'process.' . str_replace(array('/','.php'), array('.',''), $relative),
        is_string($source) && !preg_match($forbidden, $source), 'web code has no process primitive');
}

$executorPath = $root . '/includes/install/install_cli_executor.php';
$executor = is_file($executorPath) ? file_get_contents($executorPath) : false;
dqs_web_audit_add($checks, 'executor.proc_open', is_string($executor) && substr_count($executor, 'proc_open(') === 2,
    'only the executor owns its CLI validation and operation proc_open calls');
dqs_web_audit_add($checks, 'executor.argv_array', is_string($executor) && strpos($executor, 'proc_open($command,') !== false,
    'operation command is passed as an argv array');
dqs_web_audit_add($checks, 'executor.bypass_shell', is_string($executor) && substr_count($executor, "'bypass_shell'=>true") >= 2,
    'both executor process launches bypass the shell');
dqs_web_audit_add($checks, 'executor.allowlist', is_string($executor) && strpos($executor, 'Unknown or forbidden parameter.') !== false,
    'typed operation parameters reject unknown keys');

$gate = $sources['includes/install/install_web_gate.php'];
foreach (array('connection.php','bootstrap.json','admin_publish.json') as $privateFile) {
    dqs_web_audit_add($checks, 'cleanup.' . str_replace('.', '_', $privateFile),
        is_string($gate) && strpos($gate, "DIRECTORY_SEPARATOR . '" . $privateFile . "'") !== false,
        'gate create/revoke cleanup covers the private session file');
}

$failed = 0;
foreach ($checks as $check) {
    if ($check['status'] !== 'OK') ++$failed;
    echo $check['status'] . ' ' . $check['id'] . ' - ' . $check['detail'] . PHP_EOL;
}
echo ($failed === 0 ? 'OK' : 'FAILED') . ' web_installer_static_audit checks=' . count($checks) . ' failed=' . $failed . PHP_EOL;
exit($failed === 0 ? 0 : 1);
