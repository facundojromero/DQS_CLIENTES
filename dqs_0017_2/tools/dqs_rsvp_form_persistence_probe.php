<?php
/**
 * UNI-021.1 CLI probe de persistencia RSVP formulario. No escribe datos reales; pre_* se informa como staging, no destino final.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once __DIR__ . '/../includes/plan_config.php';
require_once __DIR__ . '/../includes/rsvp_form_persistence.php';

function dqs_rsvp_form_persistence_probe_usage(): string
{
    return implode(PHP_EOL, [
        'Uso: php tools/dqs_rsvp_form_persistence_probe.php [opciones]',
        '',
        'Opciones:',
        '  --help                 Muestra esta ayuda.',
        '  --status               Muestra config efectiva, schema y conteos pre_*/invitados*.',
        '  --sample=empty         Valida payload vacío sin escritura.',
        '  --sample=valid         Preview sin escritura.',
        '  --sample=no            Preview sin escritura.',
        '  --sample=companions    Preview sin escritura.',
        '',
        'Garantía UNI-021.1: este probe no inserta, no actualiza, no borra y no envía mensajes; would_persist siempre es false porque pre_* no es destino final.',
    ]) . PHP_EOL;
}

function dqs_rsvp_form_persistence_probe_samples(): array
{
    return [
        'empty' => [],
        'valid' => [
            'nombre' => 'Ana', 'apellido' => 'García', 'telefono' => '+5491100000000',
            'confirmacion' => 'Si', 'restriccion_alimentaria' => 'No', 'comentario' => 'Preview UNI-020',
            'cantidad_acompanantes' => 0, 'acompanantes' => [],
        ],
        'no' => [
            'nombre' => 'Luis', 'apellido' => 'Pérez', 'telefono' => '',
            'confirmacion' => 'No', 'restriccion_alimentaria' => 'No', 'comentario' => 'No podré asistir.',
            'cantidad_acompanantes' => 2,
            'acompanantes' => [['nombre' => 'Ignorado', 'apellido' => 'Uno'], ['nombre' => 'Ignorado', 'apellido' => 'Dos']],
        ],
        'companions' => [
            'nombre' => 'María', 'apellido' => 'López', 'telefono' => '+5491122222222',
            'confirmacion' => 'Si', 'restriccion_alimentaria' => 'Vegetariano', 'comentario' => '',
            'cantidad_acompanantes' => 2,
            'acompanantes' => [
                1 => ['nombre' => 'Carlos', 'apellido' => 'López', 'restriccion_alimentaria' => 'No', 'comentario' => ''],
                2 => ['nombre' => 'Sofía', 'apellido' => 'López', 'restriccion_alimentaria' => 'Celíaco', 'comentario' => 'Sin gluten.'],
            ],
        ],
    ];
}

function dqs_rsvp_form_persistence_probe_connection_settings(string $path): array
{
    $settings = ['servername' => '', 'username' => '', 'password' => '', 'dbname' => ''];
    $contents = @file_get_contents($path);
    if ($contents === false) {
        return $settings;
    }
    foreach (array_keys($settings) as $name) {
        if (preg_match('/\$' . preg_quote($name, '/') . '\s*=\s*([\"\'])(.*?)\1\s*;/', $contents, $matches)) {
            $settings[$name] = $matches[2];
        }
    }
    return $settings;
}

function dqs_rsvp_form_persistence_probe_connect()
{
    if (!class_exists('mysqli')) {
        return null;
    }
    $settings = dqs_rsvp_form_persistence_probe_connection_settings(__DIR__ . '/../conexion.php');
    $conn = mysqli_init();
    if ($conn === false) {
        return null;
    }
    @$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    @$conn->real_connect($settings['servername'] ?? '', $settings['username'] ?? '', $settings['password'] ?? '', $settings['dbname'] ?? '');
    if ($conn->connect_errno) {
        return null;
    }
    @$conn->set_charset('utf8mb4');
    return $conn;
}

function dqs_rsvp_form_persistence_probe_counts($conn): array
{
    $counts = [];
    if (!$conn instanceof mysqli) {
        return $counts;
    }
    foreach (['pre_invitados', 'pre_invitados_listado_mesa', 'pre_invitados_tel', 'invitados', 'invitados_listado_mesa', 'invitados_tel'] as $table) {
        $result = @$conn->query('SELECT COUNT(*) AS c FROM `' . $table . '`');
        $row = $result ? $result->fetch_assoc() : ['c' => 'ERROR'];
        $counts[$table] = $row['c'];
    }
    return $counts;
}

function dqs_rsvp_form_persistence_probe_reason(array $config, bool $schemaReady, bool $valid): string
{
    if (!$valid) {
        return 'payload_invalid';
    }
    $reason = dqs_rsvp_form_persistence_disabled_reason($config);
    if ($reason !== 'persistence_enabled') {
        return $reason;
    }
    return 'persistence_target_not_finalized';
}

function dqs_rsvp_form_persistence_probe_print(array $data): void
{
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

$options = getopt('', ['help', 'status', 'sample:']);
if (isset($options['help']) || $options === []) {
    echo dqs_rsvp_form_persistence_probe_usage();
    exit(0);
}

$conn = dqs_rsvp_form_persistence_probe_connect();
$config = dqs_get_effective_plan_config($conn instanceof mysqli ? $conn : null);
$persistenceEnabled = dqs_rsvp_form_persistence_is_enabled($config);
$schema = $conn instanceof mysqli ? dqs_rsvp_form_persistence_schema_ready($conn) : ['ready' => false, 'profile' => 'contract_v1', 'warnings' => [['message' => 'Sin conexión DB.']]];
$schemaReady = (bool)($schema['ready'] ?? false);

if (isset($options['status'])) {
    dqs_rsvp_form_persistence_probe_print([
        'read_only' => true,
        'effective_config' => $config,
        'persistence_enabled' => $persistenceEnabled,
        'target_current' => 'pre_*',
        'target_status' => 'staging_only',
        'schema_ready' => $schemaReady,
        'schema' => $schema,
        'would_persist' => false,
        'reason' => dqs_rsvp_form_persistence_probe_reason($config, $schemaReady, true),
        'counts' => dqs_rsvp_form_persistence_probe_counts($conn),
    ]);
    exit(0);
}

if (isset($options['sample'])) {
    $samples = dqs_rsvp_form_persistence_probe_samples();
    $sample = (string)$options['sample'];
    if (!isset($samples[$sample])) {
        fwrite(STDERR, dqs_rsvp_form_persistence_probe_usage());
        exit(1);
    }
    $validation = dqs_rsvp_form_validate_payload($samples[$sample]);
    $valid = (bool)$validation['valid'];
    $wouldPersist = false;
    dqs_rsvp_form_persistence_probe_print([
        'read_only' => true,
        'sample' => $sample,
        'valid' => $valid,
        'errors' => $validation['errors'],
        'warnings' => $validation['warnings'],
        'normalized' => $validation['normalized'],
        'insert_preview' => dqs_rsvp_form_persistence_build_insert_preview($validation['normalized']),
        'effective_config' => $config,
        'persistence_enabled' => $persistenceEnabled,
        'target_current' => 'pre_*',
        'target_status' => 'staging_only',
        'schema_ready' => $schemaReady,
        'would_persist' => $wouldPersist,
        'reason' => dqs_rsvp_form_persistence_probe_reason($config, $schemaReady, $valid),
        'counts' => dqs_rsvp_form_persistence_probe_counts($conn),
    ]);
    exit(0);
}

fwrite(STDERR, dqs_rsvp_form_persistence_probe_usage());
exit(1);
