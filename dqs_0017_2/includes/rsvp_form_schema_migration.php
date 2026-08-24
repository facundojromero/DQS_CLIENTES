<?php
/**
 * UNI-019: planner seguro para migración de schema RSVP formulario.
 *
 * Este helper es side-effect free: no imprime, no abre DB y no ejecuta SQL al incluirse.
 */

declare(strict_types=1);

require_once __DIR__ . '/rsvp_form_schema_profiles.php';

function dqs_rsvp_form_schema_migration_contract_v1_definition(): array
{
    return [
        'pre_invitados' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'nombre' => 'VARCHAR(100) NOT NULL',
                'apellido' => 'VARCHAR(100) NOT NULL',
                'confirmacion' => 'VARCHAR(10) NOT NULL',
                'restriccion_alimentaria' => "VARCHAR(50) DEFAULT 'No'",
                'comentario' => 'TEXT NULL',
                'cantidad_acompanantes' => 'INT DEFAULT 0',
                'total_personas' => 'INT DEFAULT 0',
                'fecha_registro' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'origen' => "VARCHAR(30) DEFAULT 'form_public'",
                'activo' => 'TINYINT(1) DEFAULT 1',
            ],
            'indexes' => [],
        ],
        'pre_invitados_listado_mesa' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'id_pre_invitado' => 'INT NOT NULL',
                'nombre' => 'VARCHAR(100) NOT NULL',
                'apellido' => 'VARCHAR(100) NOT NULL',
                'restriccion_alimentaria' => "VARCHAR(50) DEFAULT 'No'",
                'comentario' => 'TEXT NULL',
                'orden' => 'INT DEFAULT 0',
                'fecha_registro' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            ],
            'indexes' => ['INDEX id_pre_invitado (id_pre_invitado)'],
        ],
        'pre_invitados_tel' => [
            'columns' => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'id_pre_invitado' => 'INT NOT NULL',
                'telefono' => 'VARCHAR(30) NOT NULL',
                'fecha_registro' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            ],
            'indexes' => ['INDEX id_pre_invitado (id_pre_invitado)'],
        ],
    ];
}

function dqs_rsvp_form_schema_migration_plan(mysqli $conn, string $profile = 'contract_v1'): array
{
    $warnings = [];
    if ($profile !== 'contract_v1') {
        return [
            'profile' => $profile,
            'supported_profile' => 'contract_v1',
            'operations' => [],
            'sql_preview' => [],
            'warnings' => [[
                'profile' => $profile,
                'message' => 'UNI-019 solo genera migraciones para contract_v1; legacy_pre_v1 queda como diagnóstico/compatibilidad.',
            ]],
            'destructive' => false,
        ];
    }

    $definition = dqs_rsvp_form_schema_migration_contract_v1_definition();
    $operations = [];
    $sqlPreview = [];

    foreach ($definition as $tableName => $tableSpec) {
        $existingColumns = dqs_rsvp_form_schema_information_columns($conn, $tableName);
        $columns = $tableSpec['columns'];
        if ($existingColumns === []) {
            $sql = dqs_rsvp_form_schema_migration_create_table_sql($tableName, $tableSpec);
            $operations[] = [
                'type' => 'create_table',
                'table' => $tableName,
                'columns' => array_keys($columns),
                'destructive' => false,
                'sql' => $sql,
            ];
            $sqlPreview[] = $sql;
            continue;
        }

        foreach ($columns as $columnName => $definitionSql) {
            if (!in_array($columnName, $existingColumns, true)) {
                $sql = 'ALTER TABLE `' . $tableName . '` ADD COLUMN `' . $columnName . '` ' . $definitionSql;
                $operations[] = [
                    'type' => 'add_column',
                    'table' => $tableName,
                    'column' => $columnName,
                    'destructive' => false,
                    'sql' => $sql,
                ];
                $sqlPreview[] = $sql;
            }
        }
    }

    if ($operations === []) {
        $warnings[] = ['message' => 'No hay cambios pendientes para contract_v1.'];
    }

    return [
        'profile' => 'contract_v1',
        'operations' => $operations,
        'sql_preview' => $sqlPreview,
        'warnings' => $warnings,
        'destructive' => false,
    ];
}

function dqs_rsvp_form_schema_migration_create_table_sql(string $tableName, array $tableSpec): string
{
    $parts = [];
    foreach ($tableSpec['columns'] as $columnName => $definitionSql) {
        $parts[] = '  `' . $columnName . '` ' . $definitionSql;
    }
    foreach ($tableSpec['indexes'] as $indexSql) {
        $parts[] = '  ' . $indexSql;
    }
    return 'CREATE TABLE `' . $tableName . '` (' . PHP_EOL . implode(',' . PHP_EOL, $parts) . PHP_EOL . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
}


function dqs_rsvp_form_schema_migration_plan_without_connection(string $profile = 'contract_v1'): array
{
    if ($profile !== 'contract_v1') {
        return [
            'profile' => $profile,
            'supported_profile' => 'contract_v1',
            'operations' => [],
            'sql_preview' => [],
            'warnings' => [[
                'profile' => $profile,
                'message' => 'UNI-019 solo genera migraciones para contract_v1; legacy_pre_v1 queda como diagnóstico/compatibilidad.',
            ]],
            'destructive' => false,
        ];
    }

    $operations = [];
    $sqlPreview = [];
    foreach (dqs_rsvp_form_schema_migration_contract_v1_definition() as $tableName => $tableSpec) {
        $sql = dqs_rsvp_form_schema_migration_create_table_sql($tableName, $tableSpec);
        $operations[] = [
            'type' => 'create_table',
            'table' => $tableName,
            'columns' => array_keys($tableSpec['columns']),
            'destructive' => false,
            'sql' => $sql,
        ];
        $sqlPreview[] = $sql;
    }

    return [
        'profile' => 'contract_v1',
        'operations' => $operations,
        'sql_preview' => $sqlPreview,
        'warnings' => [[
            'message' => 'Plan generado sin conexión DB: asume tablas ausentes solo para preview; no se ejecutó SQL.',
        ]],
        'destructive' => false,
    ];
}

function dqs_rsvp_form_schema_migration_execute(mysqli $conn, array $plan): array
{
    $executed = [];
    $skipped = [];
    foreach (($plan['operations'] ?? []) as $operation) {
        $sql = (string) ($operation['sql'] ?? '');
        if ($sql === '') {
            $skipped[] = ['operation' => $operation, 'reason' => 'Operación sin SQL.'];
            continue;
        }
        if (!@$conn->query($sql)) {
            $skipped[] = ['operation' => $operation, 'reason' => $conn->error];
            continue;
        }
        $executed[] = $operation;
    }
    return ['executed' => $executed, 'skipped' => $skipped];
}
