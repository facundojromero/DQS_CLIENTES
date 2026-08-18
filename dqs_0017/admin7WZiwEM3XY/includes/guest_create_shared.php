<?php
/** Lógica compartida del alta de invitados definitivos y contactos de envío. */

function dqs_guest_columns(mysqli $db, string $table): array
{
    $allowed = ['invitados', 'invitados_tel', 'invitados_listado_mesa', 'pre_invitados', 'pre_invitados_tel', 'pre_invitados_listado_mesa'];
    if (!in_array($table, $allowed, true)) {
        return [];
    }
    $result = $db->query("SHOW COLUMNS FROM `$table`");
    $columns = [];
    while ($result && $row = $result->fetch_assoc()) {
        $columns[] = (string)$row['Field'];
    }
    return $columns;
}

/** @return array<string,mixed> */
function dqs_guest_pre_contract(mysqli $db): array
{
    $main = dqs_guest_columns($db, 'pre_invitados');
    $phones = dqs_guest_columns($db, 'pre_invitados_tel');
    $members = dqs_guest_columns($db, 'pre_invitados_listado_mesa');
    if (!in_array('id', $main, true)) {
        return ['error' => 'No se reconoce pre_invitados: falta la columna id.'];
    }

    $legacy = in_array('id_invitados', $phones, true) && in_array('tel_enviar', $phones, true)
        && in_array('id_invitados', $members, true) && in_array('nombre_invitado', $members, true);
    $modern = in_array('id_pre_invitado', $phones, true) && in_array('telefono', $phones, true)
        && in_array('id_pre_invitado', $members, true) && in_array('nombre', $members, true);
    if (!$legacy && !$modern) {
        return ['error' => 'El contrato de columnas pre_* no coincide con un perfil soportado.'];
    }

    // Si ambos juegos existen, se prioriza moderno, igual que gestionar_envios
    // y que el detector que ya utilizaba el listado aprobado.
    return [
        'profile' => $modern ? 'modern' : 'legacy',
        'main_columns' => $main,
        'phone_columns' => $phones,
        'phone_fk' => $modern ? 'id_pre_invitado' : 'id_invitados',
        'phone_value' => $modern ? 'telefono' : 'tel_enviar',
        'member_fk' => $modern ? 'id_pre_invitado' : 'id_invitados',
        'member_label' => $modern ? 'nombre' : 'nombre_invitado',
        'member_columns' => $members,
    ];
}

/** @return array<string,mixed> */
function dqs_guest_contract(mysqli $db, string $context): array
{
    if ($context === 'contacto_envio') {
        $contract = dqs_guest_pre_contract($db);
        if (isset($contract['error'])) return $contract;
        $contract['main_table'] = 'pre_invitados';
        $contract['phone_table'] = 'pre_invitados_tel';
        $contract['member_table'] = 'pre_invitados_listado_mesa';
        return $contract;
    }
    if ($context !== 'invitado') return ['error' => 'Contexto de alta inválido.'];
    return [
        'profile' => 'invitados',
        'main_table' => 'invitados', 'phone_table' => 'invitados_tel', 'member_table' => 'invitados_listado_mesa',
        'main_columns' => dqs_guest_columns($db, 'invitados'),
        'phone_fk' => 'id_invitados', 'phone_value' => 'tel_enviar',
        'member_fk' => 'id_invitados', 'member_label' => 'nombre_invitado',
        'member_columns' => dqs_guest_columns($db, 'invitados_listado_mesa'),
    ];
}

function dqs_guest_valid_option(mysqli $db, string $table, int $id): bool
{
    if (!in_array($table, ['intivados_acompanante', 'invitados_prioridad'], true)) return false;
    $stmt = $db->prepare("SELECT 1 FROM `$table` WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->store_result();
    $valid = $stmt->num_rows > 0; $stmt->close();
    return $valid;
}

function dqs_guest_insert(mysqli $db, array $contract, string $part, array $values): void
{
    $tableKeys = ['main' => 'main_table', 'phone' => 'phone_table', 'member' => 'member_table'];
    if (!isset($tableKeys[$part], $contract[$tableKeys[$part]]) || !$values) throw new RuntimeException('Inserción inválida.');
    $table = $contract[$tableKeys[$part]]; // contrato cerrado, jamás procede del request
    $allowed = ['invitados', 'invitados_tel', 'invitados_listado_mesa', 'pre_invitados', 'pre_invitados_tel', 'pre_invitados_listado_mesa'];
    if (!in_array($table, $allowed, true)) throw new RuntimeException('Tabla inválida.');
    $columns = array_keys($values);
    $stmt = $db->prepare("INSERT INTO `$table` (`" . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($values), '?')) . ')');
    // mysqli transmite null como SQL NULL aun usando el tipo de enlace "s".
    $params = array_values($values);
    $types = str_repeat('s', count($params)); $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new RuntimeException('No se pudo insertar el registro.');
    $stmt->close();
}

/** Valida y normaliza exactamente el payload usado por alta y edición. */
function dqs_guest_validate_payload(mysqli $db, array $post): array
{
    $data = [
        'nombre' => trim((string)($post['nombre'] ?? '')), 'apellido' => trim((string)($post['apellido'] ?? '')),
        'apodo' => trim((string)($post['nombre_invitado'] ?? '')),
        'phones' => is_array($post['telefonos'] ?? null) ? $post['telefonos'] : [],
        'acompanado' => filter_var($post['acompanado'] ?? null, FILTER_VALIDATE_INT),
        'prioridad' => filter_var($post['id_prioridad'] ?? null, FILTER_VALIDATE_INT),
        'mayores' => filter_var($post['cantidad_mayores'] ?? null, FILTER_VALIDATE_INT),
        'menores' => filter_var($post['cantidad_menores'] ?? null, FILTER_VALIDATE_INT),
        'ingreso' => (string)($post['ingreso'] ?? ''),
        'names' => is_array($post['acompanante_nombre'] ?? null) ? $post['acompanante_nombre'] : [],
        'last_names' => is_array($post['acompanante_apellido'] ?? null) ? $post['acompanante_apellido'] : [],
        'nicknames' => is_array($post['acompanante_apodo'] ?? null) ? $post['acompanante_apodo'] : [],
        'minor_flags' => is_array($post['acompanante_es_menor'] ?? null) ? $post['acompanante_es_menor'] : [],
        'titular_minor' => isset($post['titular_es_menor']) ? 1 : 0,
    ];
    $data['phones'] = array_map(static fn($v) => trim((string)$v), $data['phones']);
    $named = array_filter($data['names'], static fn($v) => trim((string)$v) !== '');
    if ($data['nombre'] === '' || $data['apellido'] === '' || ($data['phones'][0] ?? '') === '') return ['error'=>'Nombre, apellido y teléfono principal son obligatorios.'];
    if ($data['acompanado'] === false || !dqs_guest_valid_option($db, 'intivados_acompanante', $data['acompanado'])) return ['error'=>'La opción de acompañado no es válida.'];
    if ($data['prioridad'] === false || !dqs_guest_valid_option($db, 'invitados_prioridad', $data['prioridad'])) return ['error'=>'La prioridad no es válida.'];
    if (!in_array($data['ingreso'], ['Inicio', 'Tarde'], true)) return ['error'=>'El ingreso no es válido.'];
    if ($data['mayores'] === false || $data['menores'] === false || $data['mayores'] < 0 || $data['menores'] < 0) return ['error'=>'Las cantidades deben ser mayores o iguales a cero.'];
    if ($data['mayores'] + $data['menores'] !== 1 + count($named)) return ['error'=>'La suma de mayores y menores debe coincidir con el titular y los acompañantes con nombre cargado.'];
    $data['named_count'] = count($named);
    return ['error'=>'', 'data'=>$data];
}

/** Solo consulta las colas históricas por el ID del contacto pre_*; nunca las modifica. */
function dqs_guest_contact_queue_status(mysqli $db, int $id): array
{
    foreach (['invitados_a_enviar' => 'a_enviar', 'invitados_enviados' => 'enviados'] as $table => $status) {
        $stmt = $db->prepare("SELECT 1 FROM `$table` WHERE `id_invitados` = ? LIMIT 1");
        if (!$stmt) return ['blocked'=>true, 'status'=>'error', 'error'=>'No fue posible verificar de forma segura las colas de envío.'];
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->store_result();
        $found = $stmt->num_rows > 0; $stmt->close();
        if ($found) return ['blocked'=>true, 'status'=>$status, 'error'=>''];
    }
    return ['blocked'=>false, 'status'=>'ninguna', 'error'=>''];
}

function dqs_guest_add_member(mysqli $db, array $contract, int $id, string $first, string $last, string $nick, int $isMinor, int $order): void
{
    $columns = $contract['member_columns']; $label = $nick !== '' ? $nick : $first;
    $storedLabel = $contract['member_label'] === 'nombre' ? $first : $label;
    $values = [$contract['member_fk']=>$id, $contract['member_label']=>$storedLabel];
    foreach (['id_pre_invitado'=>$id, 'id_invitados'=>$id, 'nombre'=>$first, 'nombre_invitado'=>$label,
        'nombre2'=>$first, 'apellido2'=>$last, 'apellido'=>$last, 'apodo'=>$label,
        'es_menor'=>$isMinor, 'orden'=>$order] as $column=>$value)
        if (in_array($column, $columns, true) && !array_key_exists($column, $values)) $values[$column] = $value;
    dqs_guest_insert($db, $contract, 'member', $values);
}

function dqs_guest_add_phone(mysqli $db, array $contract, int $id, string $phone): void
{
    $columns = $contract['phone_columns'] ?? dqs_guest_columns($db, $contract['phone_table']);
    $values = [];
    foreach (['id_pre_invitado'=>$id, 'id_invitados'=>$id, 'telefono'=>$phone] as $column=>$value) {
        if (in_array($column, $columns, true)) $values[$column] = $value;
    }
    if (in_array('tel_enviar', $columns, true)) {
        $trimmed = trim($phone);
        $significant = ltrim($trimmed, '0');
        $safe = preg_match('/^[0-9]+$/', $trimmed)
            && (strlen($significant) < 19 || (strlen($significant) === 19 && strcmp($significant, '9223372036854775807') <= 0));
        $values['tel_enviar'] = $safe ? $trimmed : null;
    }
    dqs_guest_insert($db, $contract, 'phone', $values);
}

/** Actualiza exclusivamente las tres tablas pre_* del contrato detectado. */
function dqs_guest_update_contact(mysqli $db, int $id, array $post): array
{
    $validated = dqs_guest_validate_payload($db, $post); if ($validated['error'] !== '') return $validated;
    $d = $validated['data']; $contract = dqs_guest_contract($db, 'contacto_envio');
    if (isset($contract['error'])) return ['error'=>(string)$contract['error']];
    $stmt = $db->prepare('SELECT 1 FROM `pre_invitados` WHERE `id` = ? LIMIT 1'); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->store_result();
    $exists = $stmt->num_rows === 1; $stmt->close(); if (!$exists) return ['error'=>'El contacto de envío solicitado no existe.'];
    $queue = dqs_guest_contact_queue_status($db, $id);
    if ($queue['blocked']) return ['error'=>$queue['error'] ?: 'Este contacto ya está en una cola de envío o fue enviado. Para evitar inconsistencias, no puede editarse en esta fase.'];
    try {
        $db->begin_transaction();
        $candidate = ['nombre'=>$d['nombre'], 'apellido'=>$d['apellido'], 'acompanado'=>$d['acompanado'], 'cantidad_mayores'=>$d['mayores'], 'cantidad_menores'=>$d['menores'], 'id_prioridad'=>$d['prioridad'], 'ingreso'=>$d['ingreso'], 'cantidad_acompanantes'=>$d['named_count'], 'total_personas'=>1+$d['named_count']];
        $sets=[]; $values=[]; foreach ($candidate as $column=>$value) if (in_array($column, $contract['main_columns'], true)) { $sets[]="`$column` = ?"; $values[]=(string)$value; }
        if (!$sets) throw new RuntimeException('Contrato principal incompleto.');
        $stmt=$db->prepare('UPDATE `pre_invitados` SET '.implode(', ', $sets).' WHERE `id` = ?'); $values[]=$id; $types=str_repeat('s',count($values)-1).'i'; $stmt->bind_param($types,...$values); if(!$stmt->execute()) throw new RuntimeException(); $stmt->close();
        foreach ([['pre_invitados_tel',$contract['phone_fk']],['pre_invitados_listado_mesa',$contract['member_fk']]] as [$table,$fk]) { $stmt=$db->prepare("DELETE FROM `$table` WHERE `$fk` = ?"); $stmt->bind_param('i',$id); if(!$stmt->execute()) throw new RuntimeException(); $stmt->close(); }
        dqs_guest_add_member($db,$contract,$id,$d['nombre'],$d['apellido'],$d['apodo'],$d['titular_minor'],0);
        foreach ($d['names'] as $i=>$first) { $first=trim((string)$first); if($first==='')continue; dqs_guest_add_member($db,$contract,$id,$first,trim((string)($d['last_names'][$i]??'')),trim((string)($d['nicknames'][$i]??'')),(int)($d['minor_flags'][$i]??0)===1?1:0,$i+1); }
        dqs_guest_add_phone($db,$contract,$id,$d['phones'][0]);
        foreach ($d['names'] as $i=>$first) if(trim((string)$first)!=='' && ($d['phones'][$i+1]??'')!=='') dqs_guest_add_phone($db,$contract,$id,$d['phones'][$i+1]);
        $db->commit(); return ['error'=>''];
    } catch (Throwable $e) { $db->rollback(); return ['error'=>'No fue posible guardar. No se realizó ningún cambio.']; }
}

/** @return array{error:string,id?:int} */
function dqs_guest_process(mysqli $db, string $context, array $post): array
{
    $validated = dqs_guest_validate_payload($db, $post);
    if ($validated['error'] !== '') return $validated;
    $nombre = trim((string)($post['nombre'] ?? '')); $apellido = trim((string)($post['apellido'] ?? ''));
    $apodo = trim((string)($post['nombre_invitado'] ?? ''));
    $phones = is_array($post['telefonos'] ?? null) ? $post['telefonos'] : [];
    $mainPhone = trim((string)($phones[0] ?? ''));
    $acompanado = filter_var($post['acompanado'] ?? null, FILTER_VALIDATE_INT);
    $prioridad = filter_var($post['id_prioridad'] ?? null, FILTER_VALIDATE_INT);
    $mayores = filter_var($post['cantidad_mayores'] ?? null, FILTER_VALIDATE_INT);
    $menores = filter_var($post['cantidad_menores'] ?? null, FILTER_VALIDATE_INT);
    $ingreso = (string)($post['ingreso'] ?? '');
    $names = is_array($post['acompanante_nombre'] ?? null) ? $post['acompanante_nombre'] : [];
    $lastNames = is_array($post['acompanante_apellido'] ?? null) ? $post['acompanante_apellido'] : [];
    $nicknames = is_array($post['acompanante_apodo'] ?? null) ? $post['acompanante_apodo'] : [];
    $minorFlags = is_array($post['acompanante_es_menor'] ?? null) ? $post['acompanante_es_menor'] : [];
    $namedGuests = array_filter($names, static fn($v) => trim((string)$v) !== '');

    if ($nombre === '' || $apellido === '' || $mainPhone === '') return ['error' => 'Nombre, apellido y teléfono principal son obligatorios.'];
    if ($acompanado === false || !dqs_guest_valid_option($db, 'intivados_acompanante', $acompanado)) return ['error' => 'La opción de acompañado no es válida.'];
    if ($prioridad === false || !dqs_guest_valid_option($db, 'invitados_prioridad', $prioridad)) return ['error' => 'La prioridad no es válida.'];
    if (!in_array($ingreso, ['Inicio', 'Tarde'], true)) return ['error' => 'El ingreso no es válido.'];
    if ($mayores === false || $menores === false || $mayores < 0 || $menores < 0) return ['error' => 'Las cantidades deben ser mayores o iguales a cero.'];
    if ($mayores + $menores !== 1 + count($namedGuests)) return ['error' => 'La suma de mayores y menores debe coincidir con el titular y los acompañantes con nombre cargado.'];

    $contract = dqs_guest_contract($db, $context);
    if (isset($contract['error'])) return ['error' => (string)$contract['error']];
    try {
        $db->begin_transaction();
        $main = [];
        $candidate = ['nombre'=>$nombre, 'apellido'=>$apellido, 'acompanado'=>$acompanado, 'cantidad_mayores'=>$mayores,
            'cantidad_menores'=>$menores, 'id_prioridad'=>$prioridad, 'ingreso'=>$ingreso, 'fecha_registro'=>date('Y-m-d H:i:s'),
            // Equivalentes presentes en el perfil moderno de staging.
            'confirmacion'=>'', 'cantidad_acompanantes'=>count($namedGuests), 'total_personas'=>1 + count($namedGuests),
            'origen'=>'admin_contactos_envio'];
        foreach ($candidate as $column => $value) if (in_array($column, $contract['main_columns'], true)) $main[$column] = $value;
        if (!isset($main['nombre'], $main['apellido'])) throw new RuntimeException('Contrato principal incompleto.');
        if (in_array('codigo', $contract['main_columns'], true)) {
            do {
                $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $table = $contract['main_table'];
                $stmt = $db->prepare("SELECT 1 FROM `$table` WHERE codigo = ? LIMIT 1");
                $stmt->bind_param('s', $code); $stmt->execute(); $stmt->store_result(); $exists = $stmt->num_rows > 0; $stmt->close();
            } while ($exists);
            $main['codigo'] = $code;
        }
        if (in_array('activo', $contract['main_columns'], true)) $main['activo'] = 1;
        dqs_guest_insert($db, $contract, 'main', $main); $id = (int)$db->insert_id;

        $addMember = static function(string $first, string $last, string $nick, int $isMinor, int $order) use ($db, $contract, $id): void {
            $columns = $contract['member_columns']; $label = $nick !== '' ? $nick : $first;
            $values = [$contract['member_fk'] => $id, $contract['member_label'] => ($contract['member_label'] === 'nombre' ? $first : $label)];
            // Campos descriptivos opcionales de ambos perfiles.
            foreach (['id_pre_invitado'=>$id, 'id_invitados'=>$id, 'nombre'=>$first, 'nombre_invitado'=>$label,
                'nombre2'=>$first, 'apellido2'=>$last, 'apellido'=>$last, 'apodo'=>$label,
                'es_menor'=>$isMinor, 'orden'=>$order] as $column=>$value) {
                if (in_array($column, $columns, true) && !array_key_exists($column, $values)) $values[$column] = $value;
            }
            dqs_guest_insert($db, $contract, 'member', $values);
        };
        $addMember($nombre, $apellido, $apodo, isset($post['titular_es_menor']) ? 1 : 0, 0);
        foreach ($names as $i => $first) {
            $first = trim((string)$first); if ($first === '') continue;
            $addMember($first, trim((string)($lastNames[$i] ?? '')), trim((string)($nicknames[$i] ?? '')), (int)($minorFlags[$i] ?? 0) === 1 ? 1 : 0, $i + 1);
        }
        if ($context === 'contacto_envio') dqs_guest_add_phone($db, $contract, $id, $mainPhone);
        else dqs_guest_insert($db, $contract, 'phone', [$contract['phone_fk']=>$id, $contract['phone_value']=>$mainPhone]);
        foreach ($names as $i => $first) {
            if (trim((string)$first) === '') continue;
            $phone = trim((string)($phones[$i + 1] ?? ''));
            if ($phone !== '') {
                if ($context === 'contacto_envio') dqs_guest_add_phone($db, $contract, $id, $phone);
                else dqs_guest_insert($db, $contract, 'phone', [$contract['phone_fk']=>$id, $contract['phone_value']=>$phone]);
            }
        }
        $db->commit(); return ['error'=>'', 'id'=>$id];
    } catch (Throwable $e) {
        $db->rollback(); return ['error'=>'No fue posible guardar. No se realizó ningún cambio.'];
    }
}
