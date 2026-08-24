<?php
/**
 * UNI-017: planificación interna de persistencia futura para RSVP formulario.
 *
 * Este helper solo diagnostica schema en modo lectura y construye planes no
 * ejecutables. No guarda datos, no genera SQL de escritura y no modifica tablas.
 */

declare(strict_types=1);

require_once __DIR__ . '/rsvp_form_contract.php';

function dqs_rsvp_form_persistence_target_tables(): array
{
    return [
        'pre_invitados',
        'pre_invitados_listado_mesa',
        'pre_invitados_tel',
    ];
}

function dqs_rsvp_form_persistence_required_columns(): array
{
    return [
        'pre_invitados' => [
            'id',
            'nombre',
            'apellido',
            'confirmacion',
            'restriccion_alimentaria',
            'comentario',
        ],
        'pre_invitados_listado_mesa' => [
            'id',
            'id_pre_invitado',
            'nombre',
            'apellido',
            'restriccion_alimentaria',
            'comentario',
        ],
        'pre_invitados_tel' => [
            'id',
            'id_pre_invitado',
            'telefono',
        ],
    ];
}

function dqs_rsvp_form_persistence_schema_diagnostics(mysqli $conn): array
{
    $requiredColumns = dqs_rsvp_form_persistence_required_columns();
    $existingTables = [];
    $missingTables = [];
    $existingColumns = [];
    $missingColumns = [];
    $warnings = [];

    foreach (dqs_rsvp_form_persistence_target_tables() as $tableName) {
        if (!dqs_rsvp_form_persistence_table_exists($conn, $tableName)) {
            $missingTables[] = $tableName;
            $existingColumns[$tableName] = [];
            $missingColumns[$tableName] = $requiredColumns[$tableName] ?? [];
            $warnings[] = [
                'table' => $tableName,
                'message' => 'Tabla pre_* ausente; se informa como advertencia no fatal porque el modo activo puede seguir en codigo.',
            ];
            continue;
        }

        $existingTables[] = $tableName;
        $columns = dqs_rsvp_form_persistence_table_columns($conn, $tableName);
        $existingColumns[$tableName] = $columns;
        $missingColumns[$tableName] = array_values(array_diff($requiredColumns[$tableName] ?? [], $columns));

        foreach ($missingColumns[$tableName] as $columnName) {
            $warnings[] = [
                'table' => $tableName,
                'column' => $columnName,
                'message' => 'Columna mínima ausente para una persistencia futura; no se considera error fatal en UNI-017.',
            ];
        }
    }

    return [
        'read_only' => true,
        'target_tables' => dqs_rsvp_form_persistence_target_tables(),
        'required_columns' => $requiredColumns,
        'existing_tables' => $existingTables,
        'missing_tables' => $missingTables,
        'existing_columns' => $existingColumns,
        'missing_columns' => $missingColumns,
        'warnings' => $warnings,
        'ready' => count($missingTables) === 0 && dqs_rsvp_form_persistence_missing_column_count($missingColumns) === 0,
    ];
}

function dqs_rsvp_form_persistence_build_plan(array $normalizedPayload): array
{
    $principal = $normalizedPayload['principal'] ?? [];
    $operations = [];

    $operations[] = [
        'type' => 'future_insert',
        'table' => 'pre_invitados',
        'description' => 'Crear invitado principal en una etapa futura.',
        'data_preview' => [
            'nombre' => $principal['nombre'] ?? '',
            'apellido' => $principal['apellido'] ?? '',
            'confirmacion' => $principal['confirmacion'] ?? '',
            'restriccion_alimentaria' => $principal['restriccion_alimentaria'] ?? 'No',
            'comentario' => $principal['comentario'] ?? '',
        ],
    ];

    if (($principal['telefono'] ?? '') !== '') {
        $operations[] = [
            'type' => 'future_insert',
            'table' => 'pre_invitados_tel',
            'description' => 'Guardar teléfono del invitado principal en una etapa futura.',
            'data_preview' => [
                'id_pre_invitado' => '<future_pre_invitados_id>',
                'telefono' => $principal['telefono'],
            ],
        ];
    }

    foreach (($normalizedPayload['acompanantes'] ?? []) as $index => $acompanante) {
        $operations[] = [
            'type' => 'future_insert',
            'table' => 'pre_invitados_listado_mesa',
            'description' => 'Crear acompañante ' . ($index + 1) . ' en una etapa futura.',
            'data_preview' => [
                'id_pre_invitado' => '<future_pre_invitados_id>',
                'nombre' => $acompanante['nombre'] ?? '',
                'apellido' => $acompanante['apellido'] ?? '',
                'restriccion_alimentaria' => $acompanante['restriccion_alimentaria'] ?? 'No',
                'comentario' => $acompanante['comentario'] ?? '',
            ],
        ];
    }

    return [
        'executable' => false,
        'write_enabled' => false,
        'contains_sql' => false,
        'status' => 'planning_only',
        'summary' => 'Plan no ejecutable para persistencia futura; UNI-017 no escribe datos.',
        'totals' => $normalizedPayload['totales'] ?? ['total_personas' => 0, 'total_acompanantes' => 0],
        'operations' => $operations,
    ];
}

function dqs_rsvp_form_persistence_is_ready(array $diagnostics): bool
{
    return empty($diagnostics['missing_tables'])
        && dqs_rsvp_form_persistence_missing_column_count($diagnostics['missing_columns'] ?? []) === 0;
}

function dqs_rsvp_form_persistence_table_exists(mysqli $conn, string $tableName): bool
{
    $sql = 'SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?';
    $statement = @$conn->prepare($sql);
    if (!$statement) {
        return false;
    }

    $statement->bind_param('s', $tableName);
    $exists = false;
    if ($statement->execute()) {
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $exists = isset($row['total']) && (int) $row['total'] > 0;
    }
    $statement->close();

    return $exists;
}

function dqs_rsvp_form_persistence_table_columns(mysqli $conn, string $tableName): array
{
    $sql = 'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION';
    $statement = @$conn->prepare($sql);
    if (!$statement) {
        return [];
    }

    $statement->bind_param('s', $tableName);
    $columns = [];
    if ($statement->execute()) {
        $result = $statement->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columns[] = (string) $row['COLUMN_NAME'];
            }
        }
    }
    $statement->close();

    return $columns;
}

function dqs_rsvp_form_persistence_missing_column_count(array $missingColumns): int
{
    $count = 0;
    foreach ($missingColumns as $columns) {
        $count += is_array($columns) ? count($columns) : 0;
    }

    return $count;
}
