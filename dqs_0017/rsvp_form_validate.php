<?php
/**
 * UNI-015/022: endpoint público para validar payloads RSVP formulario.
 * Conserva dry-run por defecto y solo persiste hacia invitados* cuando modo,
 * feature flag, payload y schema final están habilitados/listos.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/rsvp_form_dry_run.php';
require_once __DIR__ . '/includes/plan_config.php';
require_once __DIR__ . '/includes/rsvp_form_final_persistence.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $response = dqs_rsvp_form_dry_run_error_response('Método no permitido. Usar POST.');
    http_response_code(405);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$readResult = dqs_rsvp_form_dry_run_read_json_or_post();
if (($readResult['ok'] ?? false) !== true) {
    $response = $readResult['response'];
    http_response_code(dqs_rsvp_form_dry_run_http_status($readResult));
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$conn = dqs_rsvp_form_validate_connect_safely();
$effectiveConfig = dqs_get_effective_plan_config($conn instanceof mysqli ? $conn : null);

$contractValidation = dqs_rsvp_form_validate_payload($readResult['payload'], $effectiveConfig);
$normalizedPayload = isset($contractValidation['normalized']) && is_array($contractValidation['normalized'])
    ? $contractValidation['normalized']
    : [];

$response = dqs_rsvp_form_dry_run_validate($readResult['payload'], $effectiveConfig);
if (($response['valid'] ?? false) !== true) {
    $response['reason'] = 'payload_invalid';
    http_response_code(dqs_rsvp_form_dry_run_http_status($response));
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ((string)($effectiveConfig['rsvp_modo'] ?? '') !== 'form') {
    $response['dry_run'] = true;
    $response['persisted'] = false;
    $response['reason'] = 'persistence_disabled_by_mode';
    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ((string)($effectiveConfig['rsvp_form_persist_enabled'] ?? '0') !== '1') {
    $response['dry_run'] = true;
    $response['persisted'] = false;
    $response['reason'] = 'persistence_feature_disabled';
    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$persistable = dqs_rsvp_form_final_persistence_validate_persistable_payload($normalizedPayload);
if (($persistable['valid'] ?? false) !== true) {
    $response['dry_run'] = true;
    $response['persisted'] = false;
    $response['reason'] = 'payload_invalid';
    $response['errors'] = $persistable['errors'] ?? [];
    http_response_code(422);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$conn instanceof mysqli) {
    $response['dry_run'] = true;
    $response['persisted'] = false;
    $response['reason'] = 'final_persistence_schema_not_ready';
    $response['message'] = 'No se pudo guardar la confirmación en este momento.';
    http_response_code(503);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$schema = dqs_rsvp_form_final_persistence_schema_ready($conn);
if (($schema['ready'] ?? false) !== true) {
    $response['dry_run'] = true;
    $response['persisted'] = false;
    $response['reason'] = 'final_persistence_schema_not_ready';
    $response['message'] = 'No se pudo guardar la confirmación en este momento.';
    http_response_code(503);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $save = dqs_rsvp_form_final_persistence_save($conn, $normalizedPayload, ['schema' => $schema]);
    $response['ok'] = true;
    $response['dry_run'] = false;
    $response['persisted'] = (bool)($save['persisted'] ?? false);
    $response['deduped'] = (bool)($save['deduped'] ?? false);
    $response['reason'] = 'final_persisted';
    $response['message'] = 'Confirmación guardada correctamente.';
    http_response_code(200);
} catch (Throwable $exception) {
    $response['ok'] = false;
    $response['dry_run'] = true;
    $response['persisted'] = false;
    $response['reason'] = 'final_persistence_failed';
    $response['message'] = 'No se pudo guardar la confirmación en este momento.';
    http_response_code(503);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


function dqs_rsvp_form_validate_connect_safely()
{
    if (!class_exists('mysqli')) {
        return null;
    }
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    $path = __DIR__ . '/conexion.php';
    $contents = @file_get_contents($path);
    if ($contents === false) {
        return null;
    }

    $settings = ['servername' => '', 'username' => '', 'password' => '', 'dbname' => ''];
    foreach (array_keys($settings) as $name) {
        if (preg_match('/\$' . preg_quote($name, '/') . '\s*=\s*(["\'])(.*?)\1\s*;/', $contents, $matches)) {
            $settings[$name] = $matches[2];
        }
    }

    $conn = mysqli_init();
    if ($conn === false) {
        return null;
    }
    @$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    @$conn->real_connect($settings['servername'], $settings['username'], $settings['password'], $settings['dbname']);
    if ($conn->connect_errno) {
        return null;
    }
    @$conn->set_charset('utf8mb4');
    return $conn;
}
