<?php
/**
 * UNI-018 CLI probe de perfiles de schema RSVP formulario.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once __DIR__ . '/../includes/rsvp_form_schema_profiles.php';

function dqs_rsvp_form_schema_profiles_probe_usage(): string
{
    return implode(PHP_EOL, [
        'Uso: php tools/dqs_rsvp_form_schema_profiles_probe.php [opciones]',
        '',
        'Opciones:',
        '  --help                              Muestra esta ayuda.',
        '  --profiles                          Lista perfiles disponibles.',
        '  --schema                            Diagnostica schema pre_* en modo solo lectura.',
        '  --detect                            Muestra best_profile detectado.',
        '  --sample=valid --profile=contract_v1',
        '  --sample=valid --profile=legacy_pre_v1',
        '  --sample=companions --profile=all',
        '  --sample=no --profile=legacy_pre_v1',
        '',
        'Garantías UNI-018:',
        '  No guarda datos, no crea ni cambia tablas, no envía WhatsApp y no llama Node.',
    ]) . PHP_EOL;
}

function dqs_rsvp_form_schema_profiles_probe_samples(): array
{
    return [
        'valid' => [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'telefono' => '+5491100000000',
            'confirmacion' => 'Si',
            'restriccion_alimentaria' => 'No',
            'comentario' => 'Mesa cerca de familia.',
            'cantidad_acompanantes' => 0,
            'acompanantes' => [],
        ],
        'no' => [
            'nombre' => 'Luis',
            'apellido' => 'Pérez',
            'telefono' => '',
            'confirmacion' => 'No',
            'restriccion_alimentaria' => 'No',
            'comentario' => 'No podré asistir.',
            'cantidad_acompanantes' => 2,
            'acompanantes' => [
                ['nombre' => 'Ignorado', 'apellido' => 'Uno'],
                ['nombre' => 'Ignorado', 'apellido' => 'Dos'],
            ],
        ],
        'companions' => [
            'nombre' => 'María',
            'apellido' => 'López',
            'telefono' => '+5491122222222',
            'confirmacion' => 'Si',
            'restriccion_alimentaria' => 'Vegetariano',
            'comentario' => '',
            'cantidad_acompanantes' => 2,
            'acompanantes' => [
                1 => ['nombre' => 'Carlos', 'apellido' => 'López', 'restriccion_alimentaria' => 'No', 'comentario' => ''],
                2 => ['nombre' => 'Sofía', 'apellido' => 'López', 'restriccion_alimentaria' => 'Celíaco', 'comentario' => 'Sin gluten.'],
            ],
        ],
    ];
}

function dqs_rsvp_form_schema_profiles_probe_connection_settings(string $path): array
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

function dqs_rsvp_form_schema_profiles_probe_connect(array $settings)
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

function dqs_rsvp_form_schema_profiles_probe_schema(): array
{
    $settings = dqs_rsvp_form_schema_profiles_probe_connection_settings(__DIR__ . '/../conexion.php');
    $conn = dqs_rsvp_form_schema_profiles_probe_connect($settings);
    if (!($conn instanceof mysqli) || $conn->connect_errno) {
        return [
            'read_only' => true,
            'connection_available' => false,
            'ready' => false,
            'warning' => 'No se pudo conectar a la base configurada por conexion.php; no se ejecutó diagnóstico de schema.',
            'mysqli_error' => $conn instanceof mysqli ? $conn->connect_error : 'mysqli no disponible',
            'profiles' => dqs_rsvp_form_schema_profiles(),
        ];
    }
    return dqs_rsvp_form_schema_diagnostics_by_profile($conn);
}

function dqs_rsvp_form_schema_profiles_probe_print_json(array $data): void
{
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

$options = getopt('', ['help', 'profiles', 'schema', 'detect', 'sample:', 'profile:']);

if (isset($options['help']) || $options === []) {
    echo dqs_rsvp_form_schema_profiles_probe_usage();
    exit(0);
}

if (isset($options['profiles'])) {
    dqs_rsvp_form_schema_profiles_probe_print_json(['profiles' => dqs_rsvp_form_schema_profiles()]);
    exit(0);
}

if (isset($options['schema'])) {
    dqs_rsvp_form_schema_profiles_probe_print_json(dqs_rsvp_form_schema_profiles_probe_schema());
    exit(0);
}

if (isset($options['detect'])) {
    $schema = dqs_rsvp_form_schema_profiles_probe_schema();
    dqs_rsvp_form_schema_profiles_probe_print_json($schema['detection'] ?? dqs_rsvp_form_schema_detect_profile($schema));
    exit(0);
}

if (isset($options['sample'])) {
    $sampleName = (string) $options['sample'];
    $profile = (string) ($options['profile'] ?? 'contract_v1');
    $samples = dqs_rsvp_form_schema_profiles_probe_samples();
    if (!isset($samples[$sampleName]) || ($profile !== 'all' && !in_array($profile, dqs_rsvp_form_schema_profile_names(), true))) {
        fwrite(STDERR, dqs_rsvp_form_schema_profiles_probe_usage());
        exit(1);
    }
    $validation = dqs_rsvp_form_validate_payload($samples[$sampleName]);
    $plans = $profile === 'all'
        ? dqs_rsvp_form_schema_build_all_mapping_plans($validation['normalized'])
        : [$profile => dqs_rsvp_form_schema_build_mapping_plan($validation['normalized'], $profile)];
    dqs_rsvp_form_schema_profiles_probe_print_json([
        'sample' => $sampleName,
        'valid' => $validation['valid'],
        'errors' => $validation['errors'],
        'warnings' => $validation['warnings'],
        'normalized' => $validation['normalized'],
        'mapping_plans' => $plans,
    ]);
    exit(0);
}

fwrite(STDERR, dqs_rsvp_form_schema_profiles_probe_usage());
exit(1);
