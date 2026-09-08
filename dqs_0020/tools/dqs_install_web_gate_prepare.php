<?php
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(2); }
require_once dirname(__DIR__) . '/includes/install/install_web_gate.php';

$operation = null; $ttl = 60; $override = null; $debugSafe = false; $error = null;
foreach (array_slice($argv, 1) as $arg) {
    if (in_array($arg, array('--create','--revoke','--status'), true)) {
        if ($operation !== null) { $error = 'Choose exactly one operation.'; break; }
        $operation = substr($arg, 2);
    } elseif ($arg === '--debug-safe') {
        $debugSafe = true;
    } elseif (strpos($arg, '--ttl-minutes=') === 0 && ctype_digit(substr($arg, 14))) {
        $ttl = (int) substr($arg, 14);
    } elseif (strpos($arg, '--runtime-dir=') === 0 && substr($arg, 14) !== '') {
        $override = substr($arg, 14);
    } else { $error = 'Unknown or malformed option.'; break; }
}
if ($operation === null) $error = 'Choose --create, --revoke, or --status.';
if ($ttl < 1 || $ttl > 1440) $error = 'TTL must be between 1 and 1440 minutes.';
if ($error !== null) { fwrite(STDERR, $error . "\n"); exit(2); }

$repo = realpath(dirname(__DIR__));
$resolution = dqs_install_web_runtime_resolve($repo, $override);
$candidate = $resolution['path'];
if ($operation === 'create' && is_string($candidate) && !file_exists($candidate)) {
    $parent = realpath(dirname($candidate));
    if ($parent === false || is_link(dirname($candidate)) || dqs_install_web_path_inside($parent, $repo)
        || !@mkdir($candidate, 0700, false)) {
        fwrite(STDERR, "The private runtime directory could not be created safely.\n"); exit(1);
    }
    @chmod($candidate, 0700);
}
$documentRoot = getenv('DOCUMENT_ROOT');
$runtime = dqs_install_web_runtime_validate($candidate, $repo, $documentRoot ?: null, $operation !== 'status');
if ($debugSafe) {
    // Report web usability (the web process must be able to consume the token),
    // while plain --status itself remains a read-only operation.
    $webUsableRuntime = dqs_install_web_runtime_validate($candidate, $repo, $documentRoot ?: null, true);
    echo 'runtime_source: ' . $resolution['source'] . "\n";
    echo 'runtime_basename: ' . (is_string($candidate) && $candidate !== '' ? basename($candidate) : 'unavailable') . "\n";
    echo 'valid: ' . ($webUsableRuntime !== null ? 'yes' : 'no') . "\n";
    echo 'gate_present: ' . ($runtime !== null && is_file($runtime . DIRECTORY_SEPARATOR . 'gate.json') && !is_link($runtime . DIRECTORY_SEPARATOR . 'gate.json') ? 'yes' : 'no') . "\n";
}
if ($runtime === null) { fwrite(STDERR, "The private runtime directory is unavailable or unsafe.\n"); exit(1); }

if ($operation === 'create') {
    dqs_install_web_cleanup_sessions($runtime);
    $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $gate = array(
        'format'=>DQS_INSTALL_WEB_GATE_FORMAT, 'version'=>DQS_INSTALL_WEB_GATE_VERSION,
        'enabled'=>true, 'secret_hash'=>password_hash($secret, PASSWORD_DEFAULT),
        'created_at_utc'=>gmdate('c'), 'expires_at_utc'=>gmdate('c', time()+($ttl*60)),
        'used_at_utc'=>null, 'attempt_count'=>0, 'last_attempt_at_utc'=>null,
    );
    if (!dqs_install_web_write_gate($runtime, $gate)) { fwrite(STDERR, "Gate creation failed.\n"); exit(1); }
    echo "Gate created. Copy this one-time secret now; it will not be shown again:\n" . $secret . "\n";
    exit(0);
}
if ($operation === 'revoke') {
    dqs_install_web_cleanup_sessions($runtime);
    $gate = dqs_install_web_read_gate($runtime);
    if (is_array($gate)) { $gate['enabled'] = false; $gate['revoked_at_utc'] = gmdate('c'); if (!dqs_install_web_write_gate($runtime, $gate)) { fwrite(STDERR, "Gate revocation failed.\n"); exit(1); } }
    echo "Gate revoked.\n"; exit(0);
}
$gate = dqs_install_web_read_gate($runtime);
if (!is_array($gate)) { echo "Gate status: disabled.\n"; exit(0); }
$state = !empty($gate['used_at_utc']) ? 'used' : (dqs_install_web_gate_available($gate) ? 'enabled' : 'disabled');
echo 'Gate status: ' . $state . ".\n";
echo 'Attempts: ' . min(DQS_INSTALL_WEB_GATE_MAX_ATTEMPTS, max(0, (int) ($gate['attempt_count'] ?? 0))) . '/' . DQS_INSTALL_WEB_GATE_MAX_ATTEMPTS . ".\n";
