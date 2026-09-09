<?php
/**
 * UNI-021.1: helper experimental de persistencia RSVP formulario.
 *
 * pre_* no es destino final de confirmación RSVP; la persistencia definitiva
 * debe implementarse hacia invitados* en el próximo paso. Este helper conserva
 * previews/diagnósticos de la etapa UNI-020, pero la escritura real queda
 * deshabilitada para no confirmar formularios en tablas staging.
 */

declare(strict_types=1);

require_once __DIR__ . '/rsvp_form_schema_profiles.php';

function dqs_rsvp_form_persistence_is_enabled(array $effectiveConfig): bool
{
    return false;
}

function dqs_rsvp_form_persistence_disabled_reason(array $effectiveConfig): string
{
    if ((string)($effectiveConfig['rsvp_modo'] ?? '') !== 'form') {
        return 'persistence_disabled_by_mode';
    }

    if ((string)($effectiveConfig['rsvp_form_persist_enabled'] ?? '0') !== '1') {
        return 'persistence_feature_disabled';
    }

    return 'persistence_target_not_finalized';
}

function dqs_rsvp_form_persistence_schema_ready(mysqli $conn): array
{
    $diagnostics = dqs_rsvp_form_schema_diagnostics_by_profile($conn);
    $contract = $diagnostics['profiles']['contract_v1'] ?? [];

    return [
        'ready' => (bool)($contract['ready'] ?? false),
        'profile' => 'contract_v1',
        'warnings' => isset($contract['warnings']) && is_array($contract['warnings']) ? $contract['warnings'] : [],
        'score' => (float)($contract['score'] ?? 0.0),
    ];
}

function dqs_rsvp_form_persistence_build_insert_preview(array $normalizedPayload): array
{
    $principal = isset($normalizedPayload['principal']) && is_array($normalizedPayload['principal']) ? $normalizedPayload['principal'] : [];
    $companions = isset($normalizedPayload['acompanantes']) && is_array($normalizedPayload['acompanantes']) ? $normalizedPayload['acompanantes'] : [];
    $confirmacion = (string)($principal['confirmacion'] ?? '');
    if ($confirmacion === 'No') {
        $companions = [];
    }

    $totalPersonas = $confirmacion === 'Si' ? 1 + count($companions) : 0;
    $preview = [
        'contains_sql' => false,
        'writes_database' => false,
        'operations' => [[
            'operation' => 'insert_principal',
            'table' => 'pre_invitados',
            'fields' => [
                'nombre' => (string)($principal['nombre'] ?? ''),
                'apellido' => (string)($principal['apellido'] ?? ''),
                'confirmacion' => $confirmacion,
                'restriccion_alimentaria' => (string)($principal['restriccion_alimentaria'] ?? 'No'),
                'comentario' => (string)($principal['comentario'] ?? ''),
                'cantidad_acompanantes' => count($companions),
                'total_personas' => $totalPersonas,
                'origen' => 'form_public',
                'activo' => 1,
            ],
        ]],
    ];

    if ((string)($principal['telefono'] ?? '') !== '') {
        $preview['operations'][] = [
            'operation' => 'insert_phone',
            'table' => 'pre_invitados_tel',
            'fields' => ['id_pre_invitado' => '<pre_invitados.id>', 'telefono' => (string)$principal['telefono']],
        ];
    }

    foreach ($companions as $index => $companion) {
        $preview['operations'][] = [
            'operation' => 'insert_companion',
            'table' => 'pre_invitados_listado_mesa',
            'fields' => [
                'id_pre_invitado' => '<pre_invitados.id>',
                'nombre' => (string)($companion['nombre'] ?? ''),
                'apellido' => (string)($companion['apellido'] ?? ''),
                'restriccion_alimentaria' => (string)($companion['restriccion_alimentaria'] ?? 'No'),
                'comentario' => (string)($companion['comentario'] ?? ''),
                'orden' => $index + 1,
            ],
        ];
    }

    return $preview;
}

function dqs_rsvp_form_persistence_save(mysqli $conn, array $normalizedPayload, array $options = []): array
{
    throw new RuntimeException('pre_* no es destino final de confirmación RSVP; escritura deshabilitada en UNI-021.1');
}
function dqs_rsvp_form_persistence_find_recent_duplicate(mysqli $conn, string $nombre, string $apellido, string $confirmacion, string $telefono): ?int
{
    if ($telefono !== '') {
        $sql = 'SELECT pi.id FROM pre_invitados pi INNER JOIN pre_invitados_tel pit ON pit.id_pre_invitado = pi.id WHERE pi.nombre = ? AND pi.apellido = ? AND pi.confirmacion = ? AND pit.telefono = ? AND pi.fecha_registro >= (NOW() - INTERVAL 5 MINUTE) ORDER BY pi.id DESC LIMIT 1';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ssss', $nombre, $apellido, $confirmacion, $telefono);
    } else {
        $sql = 'SELECT pi.id FROM pre_invitados pi WHERE pi.nombre = ? AND pi.apellido = ? AND pi.confirmacion = ? AND pi.fecha_registro >= (NOW() - INTERVAL 5 MINUTE) AND NOT EXISTS (SELECT 1 FROM pre_invitados_tel pit WHERE pit.id_pre_invitado = pi.id) ORDER BY pi.id DESC LIMIT 1';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('sss', $nombre, $apellido, $confirmacion);
    }

    $id = null;
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && ($row = $result->fetch_assoc())) {
            $id = (int)$row['id'];
        }
    }
    $stmt->close();

    return $id;
}
