<?php
/**
 * UNI-018: perfiles internos de schema para RSVP formulario.
 *
 * Helper side-effect free: no imprime al incluirse, no abre conexiones y solo
 * diagnostica metadata con information_schema cuando recibe un mysqli externo.
 */

declare(strict_types=1);

require_once __DIR__ . '/rsvp_form_contract.php';

function dqs_rsvp_form_schema_profiles(): array
{
    return [
        'contract_v1' => [
            'name' => 'contract_v1',
            'description' => 'Perfil limpio/futuro alineado al contrato UNI-014 y al plan UNI-017.',
            'tables' => [
                'pre_invitados' => ['id', 'nombre', 'apellido', 'confirmacion', 'restriccion_alimentaria', 'comentario', 'cantidad_acompanantes', 'total_personas', 'fecha_registro', 'origen', 'activo'],
                'pre_invitados_listado_mesa' => ['id', 'id_pre_invitado', 'nombre', 'apellido', 'restriccion_alimentaria', 'comentario', 'orden', 'fecha_registro'],
                'pre_invitados_tel' => ['id', 'id_pre_invitado', 'telefono', 'fecha_registro'],
            ],
        ],
        'legacy_pre_v1' => [
            'name' => 'legacy_pre_v1',
            'description' => 'Perfil compatible con esquemas históricos tipo dqs_0011 basados en tablas pre_*.',
            'tables' => [
                'pre_invitados' => [
                    'id', 'nombre', 'apellido', 'activo', 'acompanado', 'cantidad_mayores', 'id_prioridad', 'ingreso',
                    'cantidad_menores', 'fecha_registro', 'confirmacion', 'confirmacion_fecha', 'confirmacion_comentario',
                    'confirmacion_mayores', 'confirmacion_menores', 'alimento', 'codigo',
                ],
                'pre_invitados_listado_mesa' => ['id', 'id_invitados', 'nombre_invitado', 'mesa', 'nombre2', 'apellido2'],
                'pre_invitados_tel' => ['id', 'id_invitados', 'tel_enviar'],
            ],
        ],
    ];
}

function dqs_rsvp_form_schema_profile_names(): array
{
    return array_keys(dqs_rsvp_form_schema_profiles());
}

function dqs_rsvp_form_schema_required_columns(string $profile): array
{
    $profiles = dqs_rsvp_form_schema_profiles();
    return $profiles[$profile]['tables'] ?? [];
}

function dqs_rsvp_form_schema_diagnostics_by_profile(mysqli $conn): array
{
    $profiles = dqs_rsvp_form_schema_profiles();
    $targetTables = dqs_rsvp_form_schema_target_tables($profiles);
    $existingTables = [];
    $existingColumns = [];

    foreach ($targetTables as $tableName) {
        $columns = dqs_rsvp_form_schema_information_columns($conn, $tableName);
        $existingColumns[$tableName] = $columns;
        if ($columns !== []) {
            $existingTables[] = $tableName;
        }
    }

    $byProfile = [];
    foreach ($profiles as $profileName => $profile) {
        $required = $profile['tables'];
        $missingTables = [];
        $missingColumns = [];
        $warnings = [];
        $requiredCount = 0;
        $matchedCount = 0;

        foreach ($required as $tableName => $columns) {
            $requiredCount += count($columns);
            if (!in_array($tableName, $existingTables, true)) {
                $missingTables[] = $tableName;
                $missingColumns[$tableName] = $columns;
                $warnings[] = [
                    'table' => $tableName,
                    'message' => 'Tabla pre_* ausente; advertencia no fatal para UNI-018.',
                ];
                continue;
            }

            $present = $existingColumns[$tableName] ?? [];
            $matchedCount += count(array_intersect($columns, $present));
            $missingColumns[$tableName] = array_values(array_diff($columns, $present));
            foreach ($missingColumns[$tableName] as $columnName) {
                $warnings[] = [
                    'table' => $tableName,
                    'column' => $columnName,
                    'message' => 'Columna faltante para este perfil; no es fatal porque UNI-018 no persiste.',
                ];
            }
        }

        $missingColumnCount = dqs_rsvp_form_schema_missing_column_count($missingColumns);
        $score = $requiredCount > 0 ? round($matchedCount / $requiredCount, 4) : 0.0;
        $byProfile[$profileName] = [
            'profile' => $profileName,
            'ready' => $missingTables === [] && $missingColumnCount === 0,
            'score' => $score,
            'matched_columns' => $matchedCount,
            'required_columns_count' => $requiredCount,
            'existing_tables' => array_values(array_intersect(array_keys($required), $existingTables)),
            'missing_tables' => $missingTables,
            'existing_columns' => array_intersect_key($existingColumns, $required),
            'missing_columns' => $missingColumns,
            'warnings' => $warnings,
        ];
    }

    $diagnostics = [
        'read_only' => true,
        'target_tables' => $targetTables,
        'existing_tables' => $existingTables,
        'profiles' => $byProfile,
    ];
    $diagnostics['detection'] = dqs_rsvp_form_schema_detect_profile($diagnostics);

    return $diagnostics;
}

function dqs_rsvp_form_schema_detect_profile(array $diagnostics): array
{
    $scores = [];
    $bestProfile = null;
    $bestScore = 0.0;

    foreach (($diagnostics['profiles'] ?? []) as $profileName => $profileDiagnostics) {
        $score = (float) ($profileDiagnostics['score'] ?? 0.0);
        $scores[$profileName] = $score;
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestProfile = (string) $profileName;
        }
    }

    if ($bestProfile === null || $bestScore <= 0.0) {
        return ['best_profile' => null, 'reason' => 'No hay coincidencias suficientes con tablas pre_*.', 'scores' => $scores];
    }

    $ties = array_keys(array_filter($scores, static fn($score): bool => (float) $score === $bestScore));
    if (count($ties) > 1) {
        return ['best_profile' => null, 'reason' => 'Empate entre perfiles: ' . implode(', ', $ties) . '.', 'scores' => $scores];
    }

    return ['best_profile' => $bestProfile, 'reason' => 'Perfil con mayor score de columnas requeridas presentes.', 'scores' => $scores];
}

function dqs_rsvp_form_schema_build_mapping_plan(array $normalizedPayload, string $profile): array
{
    if (!in_array($profile, dqs_rsvp_form_schema_profile_names(), true)) {
        return dqs_rsvp_form_schema_base_plan($profile, [], [['field' => 'profile', 'message' => 'Perfil no reconocido.']], ['No se generó plan para un perfil desconocido.']);
    }

    $principal = $normalizedPayload['principal'] ?? [];
    $companions = is_array($normalizedPayload['acompanantes'] ?? null) ? $normalizedPayload['acompanantes'] : [];
    $total = (int) ($normalizedPayload['totales']['total_personas'] ?? (($principal['confirmacion'] ?? '') === 'Si' ? 1 + count($companions) : 0));

    if ($profile === 'contract_v1') {
        $operations = [[
            'operation' => 'plan_principal',
            'table' => 'pre_invitados',
            'fields' => dqs_rsvp_form_schema_pick($principal, ['nombre', 'apellido', 'confirmacion', 'restriccion_alimentaria', 'comentario']),
        ]];
        if (($principal['telefono'] ?? '') !== '') {
            $operations[] = ['operation' => 'plan_phone', 'table' => 'pre_invitados_tel', 'fields' => ['id_pre_invitado' => '<future_pre_invitados_id>', 'telefono' => $principal['telefono']]];
        }
        foreach ($companions as $index => $companion) {
            $operations[] = ['operation' => 'plan_companion', 'table' => 'pre_invitados_listado_mesa', 'index' => $index + 1, 'fields' => array_merge(['id_pre_invitado' => '<future_pre_invitados_id>'], dqs_rsvp_form_schema_pick($companion, ['nombre', 'apellido', 'restriccion_alimentaria', 'comentario']))];
        }
        return dqs_rsvp_form_schema_base_plan($profile, $operations, [], ['Plan contract_v1 no ejecutable alineado al contrato normalizado.']);
    }

    $operations = [[
        'operation' => 'plan_principal',
        'table' => 'pre_invitados',
        'fields' => [
            'nombre' => $principal['nombre'] ?? '',
            'apellido' => $principal['apellido'] ?? '',
            'confirmacion' => $principal['confirmacion'] ?? '',
            'alimento' => $principal['restriccion_alimentaria'] ?? 'No',
            'confirmacion_comentario' => $principal['comentario'] ?? '',
            'confirmacion_mayores' => $total,
            'confirmacion_menores' => 0,
            'acompanado' => $total > 1 ? 1 : 0,
        ],
    ]];
    if (($principal['telefono'] ?? '') !== '') {
        $operations[] = ['operation' => 'plan_phone', 'table' => 'pre_invitados_tel', 'fields' => ['id_invitados' => '<future_pre_invitados_id>', 'tel_enviar' => $principal['telefono']]];
    }

    $gaps = [];
    foreach ($companions as $index => $companion) {
        $display = trim(($companion['nombre'] ?? '') . ' ' . ($companion['apellido'] ?? ''));
        $operations[] = ['operation' => 'plan_companion', 'table' => 'pre_invitados_listado_mesa', 'index' => $index + 1, 'fields' => ['id_invitados' => '<future_pre_invitados_id>', 'nombre_invitado' => $display, 'nombre2' => $companion['nombre'] ?? '', 'apellido2' => $companion['apellido'] ?? '']];
        if (($companion['restriccion_alimentaria'] ?? 'No') !== 'No') {
            $gaps[] = ['field' => 'acompanantes.' . ($index + 1) . '.restriccion_alimentaria', 'message' => 'legacy_pre_v1 no define columna histórica para restricción alimentaria por acompañante.'];
        }
        if (($companion['comentario'] ?? '') !== '') {
            $gaps[] = ['field' => 'acompanantes.' . ($index + 1) . '.comentario', 'message' => 'legacy_pre_v1 no define columna histórica para comentario por acompañante.'];
        }
    }

    return dqs_rsvp_form_schema_base_plan($profile, $operations, $gaps, ['Plan legacy_pre_v1 no ejecutable compatible con nombres históricos pre_*.']);
}

function dqs_rsvp_form_schema_build_all_mapping_plans(array $normalizedPayload): array
{
    $plans = [];
    foreach (dqs_rsvp_form_schema_profile_names() as $profile) {
        $plans[$profile] = dqs_rsvp_form_schema_build_mapping_plan($normalizedPayload, $profile);
    }
    return $plans;
}

function dqs_rsvp_form_schema_target_tables(array $profiles): array
{
    $tables = [];
    foreach ($profiles as $profile) {
        $tables = array_merge($tables, array_keys($profile['tables'] ?? []));
    }
    return array_values(array_unique($tables));
}

function dqs_rsvp_form_schema_information_columns(mysqli $conn, string $tableName): array
{
    $sql = 'select column_name from information_schema.columns where table_schema = database() and table_name = ? order by ordinal_position';
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
                $columns[] = (string) $row['column_name'];
            }
        }
    }
    $statement->close();
    return $columns;
}

function dqs_rsvp_form_schema_missing_column_count(array $missingColumns): int
{
    $count = 0;
    foreach ($missingColumns as $columns) {
        $count += is_array($columns) ? count($columns) : 0;
    }
    return $count;
}

function dqs_rsvp_form_schema_pick(array $source, array $keys): array
{
    $picked = [];
    foreach ($keys as $key) {
        $picked[$key] = $source[$key] ?? '';
    }
    return $picked;
}

function dqs_rsvp_form_schema_base_plan(string $profile, array $operations, array $mappingGaps, array $notes): array
{
    return [
        'profile' => $profile,
        'executable' => false,
        'write_enabled' => false,
        'contains_sql' => false,
        'operations' => $operations,
        'mapping_gaps' => $mappingGaps,
        'notes' => $notes,
    ];
}
