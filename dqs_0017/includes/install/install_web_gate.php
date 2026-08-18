<?php
/** Security primitives for the read-only web installer gate (UNI-049.3). */

const DQS_INSTALL_WEB_GATE_FORMAT = 'dqs_installer_gate';
const DQS_INSTALL_WEB_GATE_VERSION = 'UNI-049.3';
const DQS_INSTALL_WEB_GATE_MAX_ATTEMPTS = 5;

function dqs_install_web_path_inside($path, $root)
{
    $path = rtrim(str_replace('\\', '/', $path), '/');
    $root = rtrim(str_replace('\\', '/', $root), '/');
    return $root !== '' && ($path === $root || strpos($path, $root . '/') === 0);
}

/** Resolve the same private runtime in CLI and web SAPIs without relying on web HOME. */
function dqs_install_web_runtime_resolve($repoRoot, $override = null)
{
    if (is_string($override) && $override !== '') return array('path'=>$override, 'source'=>'explicit');
    $configured = getenv('DQS_INSTALLER_RUNTIME_DIR');
    if (is_string($configured) && $configured !== '') return array('path'=>$configured, 'source'=>'env');

    $repo = realpath($repoRoot);
    if ($repo !== false) {
        $normalized = str_replace('\\', '/', $repo);
        $marker = '/public_html/';
        $position = strpos($normalized . '/', $marker);
        if ($position !== false) {
            $domainRoot = substr($normalized, 0, $position);
            $repoBasename = basename($normalized);
            $safeBasename = preg_replace('/[^A-Za-z0-9._-]/', '_', $repoBasename);
            if ($domainRoot !== '' && $safeBasename !== '' && $safeBasename !== '.' && $safeBasename !== '..') {
                return array(
                    'path'=>$domainRoot . '/.dqs_installer_runtime_' . $safeBasename,
                    'source'=>'hostinger_public_html_fallback',
                );
            }
        }
    }
    $home = getenv('HOME');
    if (is_string($home) && $home !== '') {
        return array('path'=>rtrim($home, '/\\') . '/.dqs_installer_runtime', 'source'=>'home');
    }
    return array('path'=>null, 'source'=>'unavailable');
}

/** Backwards-compatible path-only wrapper. */
function dqs_install_web_runtime_candidate($override = null, $repoRoot = null)
{
    $resolved = dqs_install_web_runtime_resolve($repoRoot === null ? dirname(__DIR__, 2) : $repoRoot, $override);
    return $resolved['path'];
}

function dqs_install_web_runtime_validate($candidate, $repoRoot, $documentRoot = null, $needsWrite = false)
{
    if (!is_string($candidate) || $candidate === '' || strpos($candidate, "\0") !== false || is_link($candidate)) return null;
    $runtime = realpath($candidate);
    $repo = realpath($repoRoot);
    if ($runtime === false || $repo === false || !is_dir($runtime) || !is_readable($runtime)) return null;
    if ($needsWrite && !is_writable($runtime)) return null;
    if (dqs_install_web_path_inside($runtime, $repo)) return null;
    if (is_string($documentRoot) && $documentRoot !== '') {
        $doc = realpath($documentRoot);
        if ($doc !== false && dqs_install_web_path_inside($runtime, $doc)) return null;
    }
    return $runtime;
}

function dqs_install_web_read_gate($runtime)
{
    $file = $runtime . DIRECTORY_SEPARATOR . 'gate.json';
    if (is_link($file) || !is_file($file) || !is_readable($file)) return null;
    $raw = @file_get_contents($file);
    $gate = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($gate) || ($gate['format'] ?? null) !== DQS_INSTALL_WEB_GATE_FORMAT
        || ($gate['version'] ?? null) !== DQS_INSTALL_WEB_GATE_VERSION
        || !isset($gate['secret_hash']) || !is_string($gate['secret_hash'])) return null;
    return $gate;
}

function dqs_install_web_gate_available(array $gate, $allowUsed = false)
{
    $expiry = isset($gate['expires_at_utc']) ? strtotime($gate['expires_at_utc']) : false;
    return ($gate['enabled'] ?? false) === true && $expiry !== false && $expiry > time()
        && ($allowUsed || empty($gate['used_at_utc']))
        && (int) ($gate['attempt_count'] ?? 0) < DQS_INSTALL_WEB_GATE_MAX_ATTEMPTS;
}

/** Bind an authenticated browser session to one concrete, non-secret gate state. */
function dqs_install_web_gate_session_fingerprint(array $gate)
{
    $canonical = array(
        'format'=>isset($gate['format']) && is_string($gate['format']) ? $gate['format'] : null,
        'version'=>isset($gate['version']) && is_string($gate['version']) ? $gate['version'] : null,
        'enabled'=>($gate['enabled'] ?? false) === true,
        'created_at_utc'=>isset($gate['created_at_utc']) && is_string($gate['created_at_utc']) ? $gate['created_at_utc'] : null,
        'expires_at_utc'=>isset($gate['expires_at_utc']) && is_string($gate['expires_at_utc']) ? $gate['expires_at_utc'] : null,
        'used_at_utc'=>isset($gate['used_at_utc']) && is_string($gate['used_at_utc']) ? $gate['used_at_utc'] : null,
        'revoked_at_utc'=>isset($gate['revoked_at_utc']) && is_string($gate['revoked_at_utc']) ? $gate['revoked_at_utc'] : null,
    );
    return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES));
}

function dqs_install_web_write_gate($runtime, array $gate)
{
    $file = $runtime . DIRECTORY_SEPARATOR . 'gate.json';
    if (is_link($file)) return false;
    $temporary = $runtime . DIRECTORY_SEPARATOR . '.gate.' . bin2hex(random_bytes(8)) . '.tmp';
    $json = json_encode($gate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if (@file_put_contents($temporary, $json, LOCK_EX) === false) return false;
    @chmod($temporary, 0600);
    if (!@rename($temporary, $file)) { @unlink($temporary); return false; }
    @chmod($file, 0600);
    return true;
}

/** Remove only installer-owned session directories; never follows links. */
function dqs_install_web_cleanup_sessions($runtime)
{
    $root = $runtime . DIRECTORY_SEPARATOR . 'web_sessions';
    if (is_link($root) || !is_dir($root)) return;
    foreach (new DirectoryIterator($root) as $entry) {
        if ($entry->isDot() || $entry->isLink() || !$entry->isDir()) continue;
        $dir = $entry->getPathname();
        $connection = $dir . DIRECTORY_SEPARATOR . 'connection.php';
        $bootstrap = $dir . DIRECTORY_SEPARATOR . 'bootstrap.json';
        $adminPublish = $dir . DIRECTORY_SEPARATOR . 'admin_publish.json';
        if (!is_link($adminPublish) && is_file($adminPublish)) @unlink($adminPublish);
        if (!is_link($bootstrap) && is_file($bootstrap)) @unlink($bootstrap);
        if (!is_link($connection) && is_file($connection)) @unlink($connection);
        @rmdir($dir);
    }
    @rmdir($root);
}

/** Atomically consumes a valid secret. All failures intentionally look alike. */
function dqs_install_web_consume_secret($runtime, $secret)
{
    if (!is_string($secret) || $secret === '' || strlen($secret) > 512) return false;
    $lock = @fopen($runtime . DIRECTORY_SEPARATOR . '.gate.lock', 'c');
    if ($lock === false || !@flock($lock, LOCK_EX)) { if (is_resource($lock)) fclose($lock); return false; }
    @chmod($runtime . DIRECTORY_SEPARATOR . '.gate.lock', 0600);
    $gate = dqs_install_web_read_gate($runtime);
    $ok = is_array($gate) && dqs_install_web_gate_available($gate, false) && password_verify($secret, $gate['secret_hash']);
    if (is_array($gate)) {
        $gate['attempt_count'] = (int) ($gate['attempt_count'] ?? 0) + 1;
        $gate['last_attempt_at_utc'] = gmdate('c');
        if ($ok) $gate['used_at_utc'] = gmdate('c');
        if (!dqs_install_web_write_gate($runtime, $gate)) $ok = false;
    }
    @flock($lock, LOCK_UN); fclose($lock);
    return $ok;
}
