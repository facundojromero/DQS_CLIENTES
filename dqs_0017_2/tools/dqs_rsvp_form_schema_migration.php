<?php
/**
 * UNI-019 CLI: dry-run/apply controlado para preparar tablas pre_* contract_v1.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once __DIR__ . '/../includes/rsvp_form_schema_migration.php';

function dqs_rsvp_form_schema_migration_cli_usage(): string
{
    return implode(PHP_EOL, [
        'Uso: php tools/dqs_rsvp_form_schema_migration.php [opciones]',
        '',
        'Opciones:',
        '  --help                                      Muestra esta ayuda.',
        '  --plan                                      Dry-run: genera plan sin modificar DB (default).',
        '  --profile=contract_v1                       Perfil objetivo UNI-019.',
        '  --apply                                     Ejecuta SQL planificado solo con confirmación explícita.',
        '  --i-understand-this-changes-db              Confirmación obligatoria para --apply.',
        '',
        'Ejemplos:',
        '  php tools/dqs_rsvp_form_schema_migration.php --plan',
        '  php tools/dqs_rsvp_form_schema_migration.php --profile=contract_v1 --plan',
        '  php tools/dqs_rsvp_form_schema_migration.php --profile=contract_v1 --apply --i-understand-this-changes-db',
    ]) . PHP_EOL;
}

function dqs_rsvp_form_schema_migration_cli_json(array $payload, int $exitCode = 0): void
{
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($exitCode);
}


function dqs_rsvp_form_schema_migration_cli_connection_settings(string $path): array
{
    $settings = ['servername' => '', 'username' => '', 'password' => '', 'dbname' => ''];
    $contents = @file_get_contents($path);
    if ($contents === false) {
        return $settings;
    }
    foreach (array_keys($settings) as $name) {
        if (preg_match('/\$' . preg_quote($name, '/') . '\s*=\s*(["\'])(.*?)\1\s*;/', $contents, $matches)) {
            $settings[$name] = $matches[2];
        }
    }
    return $settings;
}

function dqs_rsvp_form_schema_migration_cli_connect(array $settings)
{
    if (!class_exists('mysqli')) {
        return null;
    }
    $conn = mysqli_init();
    if ($conn === false) {
        return null;
    }
    @$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    @$conn->real_connect($settings['servername'] ?? '', $settings['username'] ?? '', $settings['password'] ?? '', $settings['dbname'] ?? '');
    return $conn;
}

$options = getopt('', ['help', 'plan', 'profile:', 'apply', 'i-understand-this-changes-db']);
if (isset($options['help'])) {
    echo dqs_rsvp_form_schema_migration_cli_usage();
    exit(0);
}

$profile = (string) ($options['profile'] ?? 'contract_v1');
$apply = isset($options['apply']);
$confirmed = isset($options['i-understand-this-changes-db']);
$dryRun = !$apply || !$confirmed;

if ($apply && !$confirmed) {
    dqs_rsvp_form_schema_migration_cli_json([
        'dry_run' => true,
        'apply' => false,
        'profile' => $profile,
        'operations' => [],
        'sql_preview' => [],
        'executed' => [],
        'skipped' => ['Falta --i-understand-this-changes-db; no se abrió DB ni se ejecutó SQL.'],
        'warnings' => ['--apply requiere confirmación explícita.'],
        'destructive' => false,
    ], 2);
}

$settings = dqs_rsvp_form_schema_migration_cli_connection_settings(__DIR__ . '/../conexion.php');
$conn = dqs_rsvp_form_schema_migration_cli_connect($settings);
$connectionWarning = null;
if (!($conn instanceof mysqli) || $conn->connect_errno) {
    $connectionWarning = $conn instanceof mysqli ? $conn->connect_error : 'mysqli no disponible';
    if ($apply) {
        dqs_rsvp_form_schema_migration_cli_json([
            'dry_run' => true,
            'apply' => false,
            'profile' => $profile,
            'operations' => [],
            'sql_preview' => [],
            'executed' => [],
            'skipped' => ['No se pudo conectar a la DB configurada por conexion.php; no se ejecutó SQL.'],
            'warnings' => [$connectionWarning],
            'destructive' => false,
        ], 1);
    }
    $plan = dqs_rsvp_form_schema_migration_plan_without_connection($profile);
} else {
    $plan = dqs_rsvp_form_schema_migration_plan($conn, $profile);
}
$executed = [];
$skipped = [];

if ($apply && $confirmed && $profile === 'contract_v1') {
    $result = dqs_rsvp_form_schema_migration_execute($conn, $plan);
    $executed = $result['executed'];
    $skipped = $result['skipped'];
} elseif ($apply && $profile !== 'contract_v1') {
    $skipped[] = 'UNI-019 no aplica migraciones para perfiles distintos de contract_v1.';
}

dqs_rsvp_form_schema_migration_cli_json([
    'dry_run' => !$apply,
    'apply' => $apply && $confirmed && $profile === 'contract_v1',
    'profile' => $profile,
    'operations' => $plan['operations'] ?? [],
    'sql_preview' => $plan['sql_preview'] ?? [],
    'executed' => $executed,
    'skipped' => $skipped,
    'warnings' => array_values(array_filter(array_merge($plan['warnings'] ?? [], $connectionWarning !== null ? [['message' => 'Conexión DB no disponible: ' . $connectionWarning]] : []))),
    'destructive' => false,
]);
