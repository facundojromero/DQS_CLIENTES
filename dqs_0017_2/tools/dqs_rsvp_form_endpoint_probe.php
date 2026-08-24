<?php
/**
 * UNI-015: herramienta CLI-only para simular el endpoint dry-run RSVP formulario.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.' . PHP_EOL);
}

require_once __DIR__ . '/../includes/rsvp_form_dry_run.php';

function dqs_rsvp_form_endpoint_probe_usage(): string
{
    return implode(PHP_EOL, [
        'Uso: php tools/dqs_rsvp_form_endpoint_probe.php [opcion]',
        '',
        'Opciones:',
        '  --help                 Muestra esta ayuda.',
        '  --sample=valid         Simula un POST válido sin acompañantes.',
        '  --sample=invalid       Simula un POST inválido.',
        '  --sample=no            Simula un POST con confirmacion=No.',
        '  --sample=companions    Simula un POST válido con acompañantes.',
        '',
        'Solo CLI. No abre DB, no consulta tablas, no escribe datos y no usa datos reales de invitados.',
    ]) . PHP_EOL;
}

function dqs_rsvp_form_endpoint_probe_samples(): array
{
    return [
        'valid' => [
            'nombre' => 'Ana',
            'apellido' => 'Demo',
            'telefono' => '111-222',
            'confirmacion' => 'Si',
            'restriccion_alimentaria' => 'No',
            'comentario' => 'Payload local de prueba.',
            'cantidad_acompanantes' => '0',
            'acompanantes' => [],
        ],
        'invalid' => [
            'nombre' => '',
            'apellido' => '',
            'telefono' => '',
            'confirmacion' => 'Tal vez',
            'restriccion_alimentaria' => 'Opción inexistente',
            'comentario' => str_repeat('x', 501),
            'cantidad_acompanantes' => '21',
            'acompanantes' => [],
        ],
        'no' => [
            'nombre' => 'Persona',
            'apellido' => 'Demo',
            'telefono' => '',
            'confirmacion' => 'No',
            'restriccion_alimentaria' => 'No',
            'comentario' => 'No asistiré.',
            'cantidad_acompanantes' => '2',
            'acompanantes' => [
                1 => ['nombre' => 'Ignorado', 'apellido' => 'Demo', 'restriccion_alimentaria' => 'Vegano', 'comentario' => ''],
            ],
        ],
        'companions' => [
            'nombre' => 'Invitada',
            'apellido' => 'Demo',
            'telefono' => '555-000',
            'confirmacion' => 'Si',
            'restriccion_alimentaria' => 'Vegetariano',
            'comentario' => '',
            'cantidad_acompanantes' => '2',
            'acompanantes' => [
                1 => ['nombre' => 'Acompañante', 'apellido' => 'Uno', 'restriccion_alimentaria' => 'No', 'comentario' => ''],
                2 => ['nombre' => 'Acompañante', 'apellido' => 'Dos', 'restriccion_alimentaria' => 'Celíaco', 'comentario' => ''],
            ],
        ],
    ];
}

function dqs_rsvp_form_endpoint_probe_print_json(array $data): void
{
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

$options = getopt('', ['help', 'sample:']);

if (isset($options['help']) || $options === []) {
    echo dqs_rsvp_form_endpoint_probe_usage();
    exit(0);
}

if (isset($options['sample'])) {
    $sampleName = (string) $options['sample'];
    $samples = dqs_rsvp_form_endpoint_probe_samples();

    if (!isset($samples[$sampleName])) {
        fwrite(STDERR, 'Sample no reconocido: ' . $sampleName . PHP_EOL);
        fwrite(STDERR, dqs_rsvp_form_endpoint_probe_usage());
        exit(1);
    }

    $response = dqs_rsvp_form_dry_run_validate($samples[$sampleName]);
    dqs_rsvp_form_endpoint_probe_print_json([
        'sample' => $sampleName,
        'simulated_http_status' => dqs_rsvp_form_dry_run_http_status($response),
        'response' => $response,
    ]);
    exit(0);
}

fwrite(STDERR, dqs_rsvp_form_endpoint_probe_usage());
exit(1);
