<?php
/**
 * UNI-015: helper dry-run para validar payloads del futuro RSVP formulario.
 *
 * Este archivo es seguro de incluir: no imprime, no abre DB, no consulta tablas,
 * no escribe datos, no llama endpoints y no ejecuta WhatsApp ni Node.
 */

declare(strict_types=1);

require_once __DIR__ . '/rsvp_form_contract.php';

function dqs_rsvp_form_dry_run_validate(array $payload, array $config = []): array
{
    $validation = dqs_rsvp_form_validate_payload($payload, $config);
    $normalized = $validation['normalized'] ?? [];
    $principal = isset($normalized['principal']) && is_array($normalized['principal']) ? $normalized['principal'] : [];
    $totales = isset($normalized['totales']) && is_array($normalized['totales']) ? $normalized['totales'] : [];
    $valid = (bool) ($validation['valid'] ?? false);

    return [
        'ok' => $valid,
        'dry_run' => true,
        'persisted' => false,
        'valid' => $valid,
        'message' => $valid
            ? 'Payload válido. Dry-run: no se guardaron datos.'
            : 'Payload inválido. Dry-run: no se guardaron datos.',
        'errors' => isset($validation['errors']) && is_array($validation['errors']) ? $validation['errors'] : [],
        'warnings' => isset($validation['warnings']) && is_array($validation['warnings']) ? $validation['warnings'] : [],
        'summary' => [
            'confirmacion' => isset($principal['confirmacion']) ? (string) $principal['confirmacion'] : '',
            'total_personas' => isset($totales['total_personas']) ? (int) $totales['total_personas'] : 0,
            'total_acompanantes' => isset($totales['total_acompanantes']) ? (int) $totales['total_acompanantes'] : 0,
            'total_adultos' => isset($totales['total_adultos']) ? (int) $totales['total_adultos'] : 0,
            'total_menores' => isset($totales['total_menores']) ? (int) $totales['total_menores'] : 0,
        ],
    ];
}

function dqs_rsvp_form_dry_run_http_status(array $response): int
{
    if (isset($response['http_status'])) {
        return (int) $response['http_status'];
    }

    if (isset($response['valid'])) {
        return $response['valid'] === true ? 200 : 422;
    }

    if (($response['message'] ?? '') === 'JSON inválido.') {
        return 400;
    }

    if (($response['message'] ?? '') === 'Método no permitido. Usar POST.') {
        return 405;
    }

    return 400;
}

function dqs_rsvp_form_dry_run_read_json_or_post(): array
{
    $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');

    if (stripos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || trim($rawBody) === '') {
            return ['ok' => true, 'payload' => []];
        }

        $decoded = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [
                'ok' => false,
                'http_status' => 400,
                'response' => dqs_rsvp_form_dry_run_error_response('JSON inválido.'),
            ];
        }

        return ['ok' => true, 'payload' => $decoded];
    }

    return ['ok' => true, 'payload' => $_POST];
}

function dqs_rsvp_form_dry_run_error_response(string $message): array
{
    return [
        'ok' => false,
        'dry_run' => true,
        'persisted' => false,
        'message' => $message,
    ];
}
