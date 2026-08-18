<?php
/**
 * UNI-014: herramienta CLI para probar el contrato puro de RSVP formulario.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.' . PHP_EOL);
}

require_once __DIR__ . '/../includes/rsvp_form_contract.php';

function dqs_rsvp_form_contract_check_usage(): string
{
    return implode(PHP_EOL, [
        'Uso: php tools/dqs_rsvp_form_contract_check.php [opcion]',
        '',
        'Opciones:',
        '  --help                 Muestra esta ayuda.',
        '  --schema               Imprime el contrato interno y el plan documental de persistencia.',
        '  --sample=valid         Valida un payload local válido sin acompañantes.',
        '  --sample=invalid       Valida un payload local inválido.',
        '  --sample=no            Valida un payload local con confirmacion=No.',
        '  --sample=companions    Valida un payload local válido con acompañantes.',
        '',
        'No abre DB, no consulta tablas, no escribe datos y no usa datos reales de invitados.',
    ]) . PHP_EOL;
}

function dqs_rsvp_form_contract_check_samples(): array
{
    return [
        'valid' => [
            'nombre' => ' Ana ',
            'apellido' => ' Prueba ',
            'telefono' => ' 111-222 ',
            'confirmacion' => 'si',
            'restriccion_alimentaria' => 'No',
            'comentario' => 'Sin comentarios.',
            'cantidad_acompanantes' => '0',
            'acompanantes' => [],
        ],
        'invalid' => [
            'nombre' => '',
            'apellido' => '',
            'telefono' => '',
            'confirmacion' => 'Tal vez',
            'restriccion_alimentaria' => 'Sin gluten inventado',
            'comentario' => str_repeat('x', 501),
            'cantidad_acompanantes' => '25',
            'acompanantes' => [],
        ],
        'no' => [
            'nombre' => 'Persona',
            'apellido' => 'Demo',
            'telefono' => '',
            'confirmacion' => 'No',
            'restriccion_alimentaria' => 'No',
            'comentario' => 'No podré asistir.',
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

function dqs_rsvp_form_contract_check_print_json(array $data): void
{
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

$options = getopt('', ['help', 'schema', 'sample:']);

if (isset($options['help']) || $options === []) {
    echo dqs_rsvp_form_contract_check_usage();
    exit(0);
}

if (isset($options['schema'])) {
    dqs_rsvp_form_contract_check_print_json([
        'schema' => dqs_rsvp_form_contract_schema(),
        'persistence_plan' => dqs_rsvp_form_persistence_plan(),
    ]);
    exit(0);
}

if (isset($options['sample'])) {
    $sampleName = (string) $options['sample'];
    $samples = dqs_rsvp_form_contract_check_samples();

    if (!isset($samples[$sampleName])) {
        fwrite(STDERR, 'Sample no reconocido: ' . $sampleName . PHP_EOL);
        fwrite(STDERR, dqs_rsvp_form_contract_check_usage());
        exit(1);
    }

    dqs_rsvp_form_contract_check_print_json([
        'sample' => $sampleName,
        'result' => dqs_rsvp_form_validate_payload($samples[$sampleName]),
    ]);
    exit(0);
}

fwrite(STDERR, dqs_rsvp_form_contract_check_usage());
exit(1);
