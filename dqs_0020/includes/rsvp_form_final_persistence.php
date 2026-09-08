<?php
/**
 * UNI-022: persistencia final RSVP formulario hacia invitados*.
 * Seguro de incluir: no imprime, no abre DB y no ejecuta SQL al cargarse.
 */

declare(strict_types=1);

function dqs_rsvp_form_final_persistence_schema_ready(mysqli $conn): array
{
    $tables = [];
    $missing = [];
    $warnings = [];
    foreach (['invitados', 'invitados_listado_mesa', 'invitados_tel'] as $table) {
        $columns = dqs_rsvp_form_final_persistence_columns($conn, $table);
        $tables[$table] = ['exists' => count($columns) > 0, 'columns' => array_keys($columns), 'column_meta' => $columns, 'mapping' => []];
        if (!$tables[$table]['exists']) {
            $missing[] = $table;
        }
    }

    $inv = $tables['invitados']['columns'];
    foreach (['id', 'nombre', 'apellido'] as $column) {
        if (!in_array($column, $inv, true)) {
            $missing[] = 'invitados.' . $column;
        }
    }
    $tables['invitados']['mapping'] = dqs_rsvp_form_final_persistence_detect_mapping($inv, [
        'id' => ['id'], 'nombre' => ['nombre'], 'apellido' => ['apellido'], 'codigo' => ['codigo'],
        'confirmacion' => ['confirmacion'], 'confirmacion_fecha' => ['confirmacion_fecha'],
        'confirmacion_comentario' => ['confirmacion_comentario'], 'alimento' => ['alimento'],
        'cantidad_mayores' => ['cantidad_mayores'], 'cantidad_menores' => ['cantidad_menores'],
        'confirmacion_mayores' => ['confirmacion_mayores'], 'confirmacion_menores' => ['confirmacion_menores'],
        'acompanado' => ['acompanado', 'acompaniado'], 'activo' => ['activo'],
        'fecha_registro' => ['fecha_registro'], 'ingreso' => ['ingreso'],
    ]);

    $tel = $tables['invitados_tel']['columns'];
    $tables['invitados_tel']['mapping'] = dqs_rsvp_form_final_persistence_detect_mapping($tel, [
        'id' => ['id'], 'guest_id' => ['id_invitados', 'id_invitado'], 'phone' => ['tel_enviar', 'telefono', 'phone'],
    ]);
    if ($tables['invitados_tel']['exists'] && (!isset($tables['invitados_tel']['mapping']['guest_id']) || !isset($tables['invitados_tel']['mapping']['phone']))) {
        $warnings[] = ['table' => 'invitados_tel', 'message' => 'No se detectó mapeo completo para teléfono; se omitirá si no está disponible.'];
    }

    $mesa = $tables['invitados_listado_mesa']['columns'];
    $tables['invitados_listado_mesa']['mapping'] = dqs_rsvp_form_final_persistence_detect_mapping($mesa, [
        'id' => ['id'], 'guest_id' => ['id_invitados', 'id_invitado'], 'display_name' => ['nombre_invitado', 'nombre'],
        'nombre' => ['nombre2', 'nombre'], 'apellido' => ['apellido2', 'apellido'], 'mesa' => ['mesa'],
        'es_menor' => ['es_menor'], 'asiste' => ['asiste'], 'confirm_date' => ['confirm_date'], 'alimento' => ['alimento'], 'alimento_comentario' => ['alimento_comentario'],
    ]);
    if ($tables['invitados_listado_mesa']['exists'] && (!isset($tables['invitados_listado_mesa']['mapping']['guest_id']) || !isset($tables['invitados_listado_mesa']['mapping']['display_name']))) {
        $warnings[] = ['table' => 'invitados_listado_mesa', 'message' => 'No se detectó mapeo mínimo para integrantes.'];
    }

    return ['ready' => count($missing) === 0, 'target' => 'invitados*', 'tables' => $tables, 'missing_required' => $missing, 'warnings' => $warnings];
}


function dqs_rsvp_form_final_persistence_code_column_diagnostic(array $schema): array
{
    $m = $schema['tables']['invitados']['mapping'] ?? [];
    $codeColumn = $m['codigo'] ?? null;
    if ($codeColumn === null) { return ['exists' => false]; }
    $meta = $schema['tables']['invitados']['column_meta'][$codeColumn] ?? [];
    return [
        'exists' => true,
        'column' => $codeColumn,
        'data_type' => $meta['DATA_TYPE'] ?? null,
        'column_type' => $meta['COLUMN_TYPE'] ?? null,
        'character_maximum_length' => $meta['CHARACTER_MAXIMUM_LENGTH'] ?? null,
        'numeric_precision' => $meta['NUMERIC_PRECISION'] ?? null,
        'generated_format' => dqs_rsvp_form_final_persistence_code_generated_format($meta),
    ];
}

function dqs_rsvp_form_final_persistence_dedupe_diagnostic(array $schema): array
{
    $m = $schema['tables']['invitados']['mapping'] ?? [];
    $telMap = $schema['tables']['invitados_tel']['mapping'] ?? [];
    $dateColumn = dqs_rsvp_form_final_persistence_dedupe_date_column($schema);
    return [
        'dedupe_strategy' => 'nombre_apellido_confirmacion_recent_window' . (isset($telMap['guest_id'], $telMap['phone']) ? '_with_phone_when_present' : ''),
        'dedupe_uses_code_prefix' => false,
        'dedupe_window_minutes' => 5,
        'dedupe_date_column' => $dateColumn,
        'dedupe_phone_join_available' => isset($telMap['guest_id'], $telMap['phone']),
    ];
}

function dqs_rsvp_form_final_persistence_code_generated_format(array $meta): string
{
    $numericTypes = ['tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'numeric'];
    $textTypes = ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'];
    $dataType = strtolower((string)($meta['DATA_TYPE'] ?? ''));
    if (in_array($dataType, $numericTypes, true)) { return 'numeric_6_digits'; }
    $formMinimumLength = strlen('FORM-YYYYMMDD-HHMMSS-0000');
    $maxLength = isset($meta['CHARACTER_MAXIMUM_LENGTH']) ? (int)$meta['CHARACTER_MAXIMUM_LENGTH'] : 0;
    if (in_array($dataType, $textTypes, true) && ($maxLength === 0 || $maxLength >= $formMinimumLength)) { return 'FORM-YYYYMMDD-HHMMSS-RAND'; }
    return 'numeric_6_digits_string';
}

function dqs_rsvp_form_final_persistence_dedupe_date_column(array $schema): ?string
{
    $m = $schema['tables']['invitados']['mapping'] ?? [];
    $meta = $schema['tables']['invitados']['column_meta'] ?? [];
    foreach (['confirmacion_fecha', 'fecha_registro'] as $key) {
        if (!isset($m[$key])) { continue; }
        $column = $m[$key];
        if (dqs_rsvp_form_final_persistence_column_has_time($meta[$column] ?? [])) { return $column; }
    }
    return null;
}

function dqs_rsvp_form_final_persistence_column_has_time(array $meta): bool
{
    $timeTypes = ['datetime', 'timestamp', 'time'];
    return in_array(strtolower((string)($meta['DATA_TYPE'] ?? '')), $timeTypes, true);
}

function dqs_rsvp_form_final_persistence_build_insert_preview(array $normalizedPayload, array $schema): array
{
    $schema = dqs_rsvp_form_final_persistence_preview_schema($schema);
    $principal = $normalizedPayload['principal'] ?? [];
    $adultCompanions = $principal['confirmacion'] === 'Si' && isset($normalizedPayload['adultos']) && is_array($normalizedPayload['adultos']) ? $normalizedPayload['adultos'] : (($principal['confirmacion'] === 'Si' && isset($normalizedPayload['acompanantes']) && is_array($normalizedPayload['acompanantes'])) ? $normalizedPayload['acompanantes'] : []);
    $minors = $principal['confirmacion'] === 'Si' && isset($normalizedPayload['menores']) && is_array($normalizedPayload['menores']) ? $normalizedPayload['menores'] : [];
    $companions = array_merge($adultCompanions, $minors);
    $operations = [];
    $operations[] = ['operation' => 'insert_principal', 'table' => 'invitados', 'fields' => dqs_rsvp_form_final_persistence_principal_fields($principal, $normalizedPayload, $schema, '<generated>')];
    if (isset($schema['tables']['invitados_listado_mesa']['mapping']['guest_id'], $schema['tables']['invitados_listado_mesa']['mapping']['display_name'])) {
        $operations[] = ['operation' => 'insert_member_principal', 'table' => 'invitados_listado_mesa', 'fields' => dqs_rsvp_form_final_persistence_principal_member_fields($principal, $schema)];
    }
    if (($principal['telefono'] ?? '') !== '' && isset($schema['tables']['invitados_tel']['mapping']['guest_id'], $schema['tables']['invitados_tel']['mapping']['phone'])) {
        $operations[] = ['operation' => 'insert_phone', 'table' => 'invitados_tel', 'fields' => ['id_invitados' => '<invitados.id>', 'tel_enviar' => $principal['telefono']]];
    }
    foreach ($adultCompanions as $i => $companion) {
        $operations[] = ['operation' => 'insert_companion_adult', 'table' => 'invitados_listado_mesa', 'fields' => dqs_rsvp_form_final_persistence_member_fields((int)$i + 1, $companion, $schema, 0, false)];
    }
    foreach ($minors as $i => $minor) {
        $operations[] = ['operation' => 'insert_minor', 'table' => 'invitados_listado_mesa', 'fields' => dqs_rsvp_form_final_persistence_member_fields((int)$i + 1, $minor, $schema, 0, true)];
    }
    return ['target' => 'invitados*', 'contains_sql' => false, 'writes_database' => false, 'operations' => $operations];
}

function dqs_rsvp_form_final_persistence_preview_schema(array $schema): array
{
    $defaults = [
        'invitados' => [
            'nombre' => 'nombre', 'apellido' => 'apellido', 'codigo' => 'codigo',
            'confirmacion' => 'confirmacion', 'confirmacion_fecha' => 'confirmacion_fecha',
            'confirmacion_comentario' => 'confirmacion_comentario', 'alimento' => 'alimento',
            'cantidad_mayores' => 'cantidad_mayores', 'cantidad_menores' => 'cantidad_menores',
            'confirmacion_mayores' => 'confirmacion_mayores', 'confirmacion_menores' => 'confirmacion_menores',
            'acompanado' => 'acompanado', 'activo' => 'activo', 'fecha_registro' => 'fecha_registro', 'ingreso' => 'ingreso',
        ],
        'invitados_tel' => ['guest_id' => 'id_invitados', 'phone' => 'tel_enviar'],
        'invitados_listado_mesa' => [
            'guest_id' => 'id_invitados', 'display_name' => 'nombre_invitado',
            'nombre' => 'nombre2', 'apellido' => 'apellido2', 'mesa' => 'mesa',
            'es_menor' => 'es_menor', 'asiste' => 'asiste', 'confirm_date' => 'confirm_date',
            'alimento' => 'alimento', 'alimento_comentario' => 'alimento_comentario',
        ],
    ];
    foreach ($defaults as $table => $mapping) {
        if (!isset($schema['tables'][$table]['mapping']) || count($schema['tables'][$table]['mapping']) === 0) {
            $schema['tables'][$table]['mapping'] = $mapping;
        }
    }
    return $schema;
}

function dqs_rsvp_form_final_persistence_validate_persistable_payload(array $normalizedPayload): array
{
    $errors = [];
    $p = $normalizedPayload['principal'] ?? [];
    if (($p['nombre'] ?? '') === '') { $errors[] = ['field' => 'nombre', 'message' => 'El nombre es requerido.']; }
    if (($p['apellido'] ?? '') === '') { $errors[] = ['field' => 'apellido', 'message' => 'El apellido es requerido.']; }
    if (!in_array(($p['confirmacion'] ?? ''), ['Si', 'No'], true)) { $errors[] = ['field' => 'confirmacion', 'message' => 'La confirmación debe ser Si o No.']; }
    if (($p['confirmacion'] ?? '') === 'Si') {
        foreach (['adultos' => ($normalizedPayload['adultos'] ?? $normalizedPayload['acompanantes'] ?? []), 'menores' => ($normalizedPayload['menores'] ?? [])] as $group => $people) {
            foreach ($people as $i => $a) {
                if (($a['nombre'] ?? '') === '') { $errors[] = ['field' => $group . '.' . ($i + 1) . '.nombre', 'message' => 'Nombre requerido.']; }
                if (($a['apellido'] ?? '') === '') { $errors[] = ['field' => $group . '.' . ($i + 1) . '.apellido', 'message' => 'Apellido requerido.']; }
            }
        }
    }
    return ['valid' => count($errors) === 0, 'errors' => $errors];
}

function dqs_rsvp_form_final_persistence_save(mysqli $conn, array $normalizedPayload, array $options = []): array
{
    $schema = $options['schema'] ?? dqs_rsvp_form_final_persistence_schema_ready($conn);
    if (($schema['ready'] ?? false) !== true || ($schema['target'] ?? '') !== 'invitados*') { throw new RuntimeException('Schema invitados* no listo.'); }
    $validation = dqs_rsvp_form_final_persistence_validate_persistable_payload($normalizedPayload);
    if (!$validation['valid']) { throw new InvalidArgumentException('Payload no persistible.'); }
    $p = $normalizedPayload['principal'];
    $duplicate = dqs_rsvp_form_final_persistence_find_recent_duplicate($conn, $p, $schema);
    if ($duplicate !== null) { return ['ok' => true, 'target' => 'invitados*', 'persisted' => false, 'deduped' => true, 'principal_id' => $duplicate, 'inserted' => ['invitados' => 0, 'invitados_tel' => 0, 'invitados_listado_mesa' => 0]]; }

    $adultCompanions = $p['confirmacion'] === 'Si' ? ($normalizedPayload['adultos'] ?? ($normalizedPayload['acompanantes'] ?? [])) : [];
    $minors = $p['confirmacion'] === 'Si' ? ($normalizedPayload['menores'] ?? []) : [];
    $conn->begin_transaction();
    try {
        $code = isset($schema['tables']['invitados']['mapping']['codigo']) ? dqs_rsvp_form_final_persistence_unique_code($conn, $schema) : '';
        $fields = dqs_rsvp_form_final_persistence_principal_fields($p, $normalizedPayload, $schema, $code);
        dqs_rsvp_form_final_persistence_insert($conn, 'invitados', $fields);
        $principalId = (int)$conn->insert_id;
        $inserted = ['invitados' => 1, 'invitados_tel' => 0, 'invitados_listado_mesa' => 0];
        if (($p['telefono'] ?? '') !== '' && isset($schema['tables']['invitados_tel']['mapping']['guest_id'], $schema['tables']['invitados_tel']['mapping']['phone'])) {
            $m = $schema['tables']['invitados_tel']['mapping'];
            dqs_rsvp_form_final_persistence_insert($conn, 'invitados_tel', [$m['guest_id'] => $principalId, $m['phone'] => $p['telefono']]);
            $inserted['invitados_tel']++;
        }
        if (isset($schema['tables']['invitados_listado_mesa']['mapping']['guest_id'], $schema['tables']['invitados_listado_mesa']['mapping']['display_name'])) {
            $fields = dqs_rsvp_form_final_persistence_principal_member_fields($p, $schema, $principalId);
            dqs_rsvp_form_final_persistence_insert($conn, 'invitados_listado_mesa', $fields);
            $inserted['invitados_listado_mesa']++;
            foreach ($adultCompanions as $i => $companion) {
                $fields = dqs_rsvp_form_final_persistence_member_fields((int)$i + 1, $companion, $schema, $principalId, false);
                dqs_rsvp_form_final_persistence_insert($conn, 'invitados_listado_mesa', $fields);
                $inserted['invitados_listado_mesa']++;
            }
            foreach ($minors as $i => $minor) {
                $fields = dqs_rsvp_form_final_persistence_member_fields((int)$i + 1, $minor, $schema, $principalId, true);
                dqs_rsvp_form_final_persistence_insert($conn, 'invitados_listado_mesa', $fields);
                $inserted['invitados_listado_mesa']++;
            }
        }
        $conn->commit();
        return ['ok' => true, 'target' => 'invitados*', 'persisted' => true, 'deduped' => false, 'principal_id' => $principalId, 'inserted' => $inserted];
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function dqs_rsvp_form_final_persistence_columns(mysqli $conn, string $table): array
{
    $columns = [];
    $stmt = $conn->prepare('SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION');
    if (!$stmt) { return $columns; }
    $stmt->bind_param('s', $table);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($result && ($row = $result->fetch_assoc())) { $columns[$row['COLUMN_NAME']] = $row; }
    }
    $stmt->close();
    return $columns;
}

function dqs_rsvp_form_final_persistence_detect_mapping(array $columns, array $candidates): array
{ $m = []; foreach ($candidates as $k => $names) { foreach ($names as $n) { if (in_array($n, $columns, true)) { $m[$k] = $n; break; } } } return $m; }

function dqs_rsvp_form_final_persistence_principal_fields(array $p, array $normalizedPayload, array $schema, string $code): array
{
    $m = $schema['tables']['invitados']['mapping'] ?? [];
    $f = [$m['nombre'] ?? 'nombre' => $p['nombre'] ?? '', $m['apellido'] ?? 'apellido' => $p['apellido'] ?? ''];
    if (isset($m['codigo']) && $code !== '') { $f[$m['codigo']] = $code; }
    if (isset($m['confirmacion'])) { $f[$m['confirmacion']] = $p['confirmacion'] ?? ''; }
    if (isset($m['confirmacion_fecha'])) { $f[$m['confirmacion_fecha']] = date('Y-m-d H:i:s'); }
    if (isset($m['confirmacion_comentario'])) { $f[$m['confirmacion_comentario']] = $p['comentario'] ?? ''; }
    if (isset($m['alimento'])) { $f[$m['alimento']] = $p['alimento'] ?? ($p['restriccion_alimentaria'] ?? 'No'); }
    $confirmedAdults = ($p['confirmacion'] ?? '') === 'Si' ? (int)($normalizedPayload['cantidad_adultos'] ?? 1) : 0;
    $confirmedMinors = ($p['confirmacion'] ?? '') === 'Si' ? (int)($normalizedPayload['cantidad_menores'] ?? 0) : 0;
    if (isset($m['cantidad_mayores'])) { $f[$m['cantidad_mayores']] = $confirmedAdults; }
    if (isset($m['cantidad_menores'])) { $f[$m['cantidad_menores']] = $confirmedMinors; }
    if (isset($m['confirmacion_mayores'])) { $f[$m['confirmacion_mayores']] = $confirmedAdults; }
    if (isset($m['confirmacion_menores'])) { $f[$m['confirmacion_menores']] = $confirmedMinors; }
    if (isset($m['acompanado'])) { $f[$m['acompanado']] = ($confirmedAdults > 1 || $confirmedMinors > 0) ? 1 : 0; }
    if (isset($m['activo'])) { $f[$m['activo']] = 1; }
    if (isset($m['fecha_registro'])) { $f[$m['fecha_registro']] = date('Y-m-d H:i:s'); }
    if (isset($m['ingreso'])) { $f[$m['ingreso']] = 'form_public'; }
    return $f;
}

function dqs_rsvp_form_final_persistence_principal_member_fields(array $p, array $schema, int $principalId = 0): array
{
    $m = $schema['tables']['invitados_listado_mesa']['mapping'] ?? [];
    $display = trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''));
    $f = [];
    if ($principalId > 0 && isset($m['guest_id'])) { $f[$m['guest_id']] = $principalId; } else { $f['id_invitados'] = '<invitados.id>'; }
    if (isset($m['display_name'])) { $f[$m['display_name']] = $display; } else { $f['nombre_invitado'] = $display; }
    if (isset($m['nombre'])) { $f[$m['nombre']] = $p['nombre'] ?? ''; } else { $f['nombre2'] = $p['nombre'] ?? ''; }
    if (isset($m['apellido'])) { $f[$m['apellido']] = $p['apellido'] ?? ''; } else { $f['apellido2'] = $p['apellido'] ?? ''; }
    if (isset($m['es_menor'])) { $f[$m['es_menor']] = 0; }
    if (isset($m['mesa'])) { $f[$m['mesa']] = 0; }
    if (isset($m['asiste'])) { $f[$m['asiste']] = ($p['confirmacion'] ?? '') === 'Si' ? 1 : 0; }
    if (isset($m['confirm_date'])) { $f[$m['confirm_date']] = date('Y-m-d H:i:s'); }
    if (isset($m['alimento'])) { $f[$m['alimento']] = $p['alimento'] ?? ($p['restriccion_alimentaria'] ?? 'No'); }
    if (isset($m['alimento_comentario'])) { $f[$m['alimento_comentario']] = $p['alimento_comentario'] ?? ''; }
    return $f;
}

function dqs_rsvp_form_final_persistence_member_fields(int $index, array $a, array $schema, int $principalId = 0, bool $isMinor = false): array
{
    $m = $schema['tables']['invitados_listado_mesa']['mapping'] ?? [];
    $display = trim(($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? ''));
    $f = [];
    if ($principalId > 0 && isset($m['guest_id'])) { $f[$m['guest_id']] = $principalId; } else { $f['id_invitados'] = '<invitados.id>'; }
    if (isset($m['display_name'])) { $f[$m['display_name']] = $display; } else { $f['nombre_invitado'] = $display; }
    if (isset($m['nombre'])) { $f[$m['nombre']] = $a['nombre'] ?? ''; } else { $f['nombre2'] = $a['nombre'] ?? ''; }
    if (isset($m['apellido'])) { $f[$m['apellido']] = $a['apellido'] ?? ''; } else { $f['apellido2'] = $a['apellido'] ?? ''; }
    if (isset($m['es_menor'])) { $f[$m['es_menor']] = $isMinor ? 1 : 0; }
    if (isset($m['mesa'])) { $f[$m['mesa']] = 0; }
    if (isset($m['asiste'])) { $f[$m['asiste']] = 1; }
    if (isset($m['confirm_date'])) { $f[$m['confirm_date']] = date('Y-m-d H:i:s'); }
    if (isset($m['alimento'])) { $f[$m['alimento']] = $a['alimento'] ?? ($a['restriccion_alimentaria'] ?? 'No'); }
    if (isset($m['alimento_comentario'])) { $f[$m['alimento_comentario']] = $a['alimento_comentario'] ?? ($a['comentario'] ?? ''); }
    return $f;
}

function dqs_rsvp_form_final_persistence_insert(mysqli $conn, string $table, array $fields): void
{
    $columns = array_keys($fields);
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
    $stmt = $conn->prepare($sql);
    if (!$stmt) { throw new RuntimeException('No se pudo preparar escritura en ' . $table); }
    $types = ''; $values = [];
    foreach ($fields as $v) { $types .= is_int($v) ? 'i' : 's'; $values[] = $v; }
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); throw new RuntimeException('No se pudo guardar en ' . $table . ': ' . $err); }
    $stmt->close();
}

function dqs_rsvp_form_final_persistence_unique_code(mysqli $conn, array $schema): string
{
    $codeColumn = $schema['tables']['invitados']['mapping']['codigo'] ?? 'codigo';
    $codeMeta = $schema['tables']['invitados']['column_meta'][$codeColumn] ?? [];
    $format = dqs_rsvp_form_final_persistence_code_generated_format($codeMeta);
    for ($i = 0; $i < 10; $i++) {
        $code = $format === 'FORM-YYYYMMDD-HHMMSS-RAND' ? 'FORM-' . date('Ymd-His') . '-' . random_int(1000, 9999) : (string)random_int(100000, 999999);
        $stmt = $conn->prepare('SELECT id FROM invitados WHERE `' . $codeColumn . '` = ? LIMIT 1');
        if (!$stmt) { return $code; }
        $stmt->bind_param('s', $code); $stmt->execute(); $res = $stmt->get_result(); $exists = $res && $res->fetch_assoc(); $stmt->close();
        if (!$exists) { return $code; }
    }
    return $format === 'FORM-YYYYMMDD-HHMMSS-RAND' ? 'FORM-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) : (string)random_int(100000, 999999);
}

function dqs_rsvp_form_final_persistence_find_recent_duplicate(mysqli $conn, array $p, array $schema): ?int
{
    $m = $schema['tables']['invitados']['mapping'] ?? [];
    if (!isset($m['nombre'], $m['apellido'], $m['confirmacion'])) { return null; }
    $dateColumn = dqs_rsvp_form_final_persistence_dedupe_date_column($schema);
    if ($dateColumn === null) { return null; }

    $phone = (string)($p['telefono'] ?? '');
    $telMap = $schema['tables']['invitados_tel']['mapping'] ?? [];
    $idColumn = $m['id'] ?? 'id';
    $params = [$p['nombre'], $p['apellido'], $p['confirmacion']];
    $types = 'sss';
    $from = 'invitados i';
    $where = 'i.`' . $m['nombre'] . '` = ? AND i.`' . $m['apellido'] . '` = ? AND i.`' . $m['confirmacion'] . '` = ? AND i.`' . $dateColumn . '` >= (NOW() - INTERVAL 5 MINUTE)';

    if ($phone !== '' && isset($telMap['guest_id'], $telMap['phone'])) {
        $from .= ' INNER JOIN invitados_tel t ON t.`' . $telMap['guest_id'] . '` = i.`' . $idColumn . '`';
        $where .= ' AND t.`' . $telMap['phone'] . '` = ?';
        $types .= 's';
        $params[] = $phone;
    }

    $sql = 'SELECT i.`' . $idColumn . '` AS id FROM ' . $from . ' WHERE ' . $where . ' ORDER BY i.`' . $idColumn . '` DESC LIMIT 1';
    $stmt = $conn->prepare($sql); if (!$stmt) { return null; }
    $stmt->bind_param($types, ...$params);
    $id = null; if ($stmt->execute()) { $r = $stmt->get_result(); if ($r && ($row = $r->fetch_assoc())) { $id = (int)$row['id']; } }
    $stmt->close(); return $id;
}
