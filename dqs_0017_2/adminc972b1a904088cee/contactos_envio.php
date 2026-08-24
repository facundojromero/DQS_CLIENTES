<?php
/**
 * Listado y baja lógica del staging de contactos para envíos WhatsApp.
 */

if (!isset($conn) || !$conn instanceof mysqli) {
    http_response_code(500);
    echo '<p>No se pudo consultar el listado de contactos.</p>';
    return;
}

// Defensa en profundidad si el archivo fuese incluido desde otro controlador.
require_once __DIR__ . '/../includes/admin_feature_guard.php';
require_once __DIR__ . '/includes/guest_create_shared.php';
dqs_require_admin_contactos_envio($conn);

if (empty($_SESSION['guest_create_csrf'])) {
    $_SESSION['guest_create_csrf'] = bin2hex(random_bytes(32));
}

$stateFilter = (string)($_REQUEST['estado'] ?? 'activos');
if (!in_array($stateFilter, ['activos', 'inactivos', 'todos'], true)) {
    $stateFilter = 'activos';
}

// Inactivar/reactivar es siempre POST, opera únicamente sobre pre_invitados y
// consulta las colas por ID. Nunca elimina ni acepta tablas/fuentes del cliente.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array((string)($_GET['accion'] ?? ''), ['inactivar', 'reactivar'], true)) {
    $message = 'No fue posible cambiar el estado del contacto de envío.';
    if (!hash_equals($_SESSION['guest_create_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        $message = 'Error: la sesión del formulario venció.';
    } else {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $message = 'El contacto de envío solicitado no es válido.';
        } else {
            $stmt = $conn->prepare('SELECT `activo` FROM `pre_invitados` WHERE `id` = ? LIMIT 1');
            $stmt->bind_param('i', $id); $stmt->execute();
            $contact = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$contact) {
                $message = 'El contacto de envío solicitado no existe.';
            } else {
                $queue = dqs_guest_contact_queue_status($conn, $id);
                if ($queue['blocked']) {
                    if ($queue['status'] === 'a_enviar') {
                        $message = 'Este contacto está en la cola A Enviar. Para inactivarlo, primero retiralo de la cola.';
                    } elseif ($queue['status'] === 'enviados') {
                        $message = 'Este contacto ya fue enviado. Para evitar inconsistencias, no puede inactivarse en esta fase.';
                    } else {
                        $message = $queue['error'];
                    }
                    if (($_GET['accion'] ?? '') === 'reactivar' && $queue['status'] !== 'error') {
                        $message = 'Este contacto inactivo aparece en una cola de envío. Para evitar inconsistencias, no puede reactivarse.';
                    }
                } else {
                    $newState = ($_GET['accion'] === 'reactivar') ? 1 : 0;
                    $stmt = $conn->prepare('UPDATE `pre_invitados` SET `activo` = ? WHERE `id` = ?');
                    $stmt->bind_param('ii', $newState, $id); $stmt->execute(); $stmt->close();
                    $message = $newState
                        ? 'El contacto de envío se reactivó correctamente.'
                        : 'El contacto de envío se inactivó correctamente.';
                }
            }
        }
    }
    $_SESSION['contactos_envio_mensaje'] = $message;
    header('Location: ?new=contactos_envio&estado=' . rawurlencode($stateFilter));
    exit;
}

// El alta vive deliberadamente en un módulo separado y conserva este archivo
// como controlador/listado de Contactos de envío.
if (($_GET['accion'] ?? '') === 'nuevo') {
    require __DIR__ . '/nuevo_contacto_envio.php';
    return;
}
if (($_GET['accion'] ?? '') === 'editar') {
    require __DIR__ . '/editar_contacto_envio.php';
    return;
}

/** @return array<string,mixed> */
function dqs_contactos_envio_contract(mysqli $db): array
{
    // Lectura y alta comparten exactamente la misma detección legacy/moderna.
    return dqs_guest_pre_contract($db);
}

function dqs_contactos_envio_html($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$contract = dqs_contactos_envio_contract($conn);
$contactos = [];
$diagnostic = $contract['error'] ?? null;

if ($diagnostic === null) {
    $mainColumns = $contract['main_columns'];
    $select = ['id'];
    foreach (['nombre', 'apellido', 'nombre_invitado', 'activo', 'fecha_registro', 'fecha'] as $optional) {
        if (in_array($optional, $mainColumns, true)) {
            $select[] = $optional;
        }
    }

    $where = $stateFilter === 'activos' ? ' WHERE `activo` = 1' : ($stateFilter === 'inactivos' ? ' WHERE `activo` = 0' : '');
    $result = @$conn->query('SELECT `' . implode('`, `', $select) . '` FROM `pre_invitados`' . $where . ' ORDER BY `id` DESC');
    if (!$result) {
        $diagnostic = 'No fue posible leer pre_invitados con el contrato detectado.';
    } else {
        while ($row = $result->fetch_assoc()) {
            $row['telefonos'] = [];
            $row['integrantes'] = [];
            $contactos[(string)$row['id']] = $row;
        }

        $phoneResult = @$conn->query('SELECT `' . $contract['phone_fk'] . '` AS contacto_id, `'
            . $contract['phone_value'] . '` AS valor FROM `pre_invitados_tel` ORDER BY `id` ASC');
        if (!$phoneResult) {
            $diagnostic = 'No fue posible leer los teléfonos del contrato pre_* detectado.';
        } else {
            while ($row = $phoneResult->fetch_assoc()) {
                $key = (string)$row['contacto_id'];
                if (isset($contactos[$key]) && trim((string)$row['valor']) !== '') {
                    $contactos[$key]['telefonos'][] = $row['valor'];
                }
            }
        }
        foreach ($contactos as &$contacto) {
            $queue = dqs_guest_contact_queue_status($conn, (int)$contacto['id']);
            $contacto['queue_blocked'] = $queue['blocked'];
        }
        unset($contacto);

        $memberColumns = $contract['member_columns'];
        $memberNames = array_values(array_intersect(
            ['nombre', 'apellido', 'nombre_invitado', 'nombre2', 'apellido2'],
            $memberColumns
        ));
        if ($diagnostic === null && $memberNames !== []) {
            $memberResult = @$conn->query('SELECT `' . $contract['member_fk'] . '` AS contacto_id, `'
                . implode('`, `', $memberNames) . '` FROM `pre_invitados_listado_mesa` ORDER BY `id` ASC');
            if (!$memberResult) {
                $diagnostic = 'No fue posible leer los integrantes del contrato pre_* detectado.';
            } else {
                while ($row = $memberResult->fetch_assoc()) {
                    $key = (string)$row['contacto_id'];
                    $visible = trim((string)($row['nombre_invitado'] ?? ''));
                    if ($visible === '') $visible = trim((string)($row['nombre'] ?? ''));
                    $last = trim((string)($row['apellido2'] ?? ($row['apellido'] ?? '')));
                    $parts = array_values(array_filter([$visible, $last], static fn($value) => $value !== ''));
                    if (isset($contactos[$key]) && $parts !== []) {
                        if (!isset($contactos[$key]['visible_name'])) {
                            $contactos[$key]['visible_name'] = $visible;
                        }
                        $contactos[$key]['integrantes'][] = implode(' ', $parts);
                    }
                }
            }
        }
    }
}
?>
<section class="contactos-envio" aria-labelledby="contactos-envio-title">
    <div class="admin-page-header">
        <div>
            <h1 id="contactos-envio-title">Contactos de envío</h1>
            <p class="admin-page-subtitle">Estos contactos son solo para envío de invitaciones. No son confirmaciones RSVP.</p>
        </div>
        <a class="navbar-link admin-primary-action" href="?new=contactos_envio&amp;accion=nuevo"><i class="fas fa-user-plus navbar-icon" aria-hidden="true"></i> Nuevo contacto de envío</a>
    </div>

    <?php if (!empty($_SESSION['contactos_envio_mensaje'])): ?>
        <?php
        $messageText = (string)$_SESSION['contactos_envio_mensaje'];
        $isErrorMessage = strpos($messageText, 'Error') !== false
            || strpos($messageText, 'No ') !== false
            || strpos($messageText, 'no puede') !== false;
        ?>
        <div class="alert admin-message<?= $isErrorMessage ? ' admin-message-error' : '' ?>" role="status"><i class="fas <?= $isErrorMessage ? 'fa-exclamation-circle' : 'fa-check-circle' ?>" aria-hidden="true"></i><p><?= dqs_contactos_envio_html($messageText) ?></p></div>
        <?php unset($_SESSION['contactos_envio_mensaje']); ?>
    <?php endif; ?>
    <nav class="admin-filter-bar" aria-label="Filtrar contactos por estado">
        <strong>Estado:</strong>
        <?php foreach (['activos' => 'Activos', 'inactivos' => 'Inactivos', 'todos' => 'Todos'] as $filterValue => $filterLabel): ?>
            <a class="admin-filter-link<?= $stateFilter === $filterValue ? ' is-active' : '' ?>" href="?new=contactos_envio&amp;estado=<?= $filterValue ?>"<?= $stateFilter === $filterValue ? ' aria-current="page"' : '' ?>><?= $filterLabel ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($diagnostic !== null): ?>
        <div class="admin-message admin-message-error" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <strong>Listado bloqueado:</strong> <?= dqs_contactos_envio_html($diagnostic) ?>
        </div>
    <?php elseif ($contactos === []): ?>
        <div class="admin-empty-state"><i class="fas fa-address-book" aria-hidden="true"></i><p>No hay contactos de envío cargados.</p></div>
    <?php else: ?>
        <div class="admin-table-responsive">
        <table class="tabla-estilizada contactos-envio-table">
            <thead><tr><th>ID</th><th>Nombre</th><th>Teléfono/s</th><th>Integrantes / mesa</th><th>Estado staging</th><th>Fecha</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($contactos as $contacto):
                $visibleName = trim((string)($contacto['visible_name'] ?? ($contacto['nombre_invitado'] ?? '')));
                if ($visibleName === '') {
                    $visibleName = trim((string)($contacto['nombre'] ?? '') . ' ' . (string)($contacto['apellido'] ?? ''));
                }
                $date = $contacto['fecha_registro'] ?? ($contacto['fecha'] ?? null);
                $idTarjeta = str_pad((string)(int)$contacto['id'], 4, '0', STR_PAD_LEFT);
                $archivoTarjeta = $idTarjeta . '.jpg';
                $rutaTarjeta = __DIR__ . '/invitaciones/' . $archivoTarjeta;
                $tarjetaExiste = is_file($rutaTarjeta);
            ?>
                <tr>
                    <td><?= dqs_contactos_envio_html($contacto['id']) ?></td>
                    <td><?= dqs_contactos_envio_html($visibleName !== '' ? $visibleName : '—') ?></td>
                    <td><?= dqs_contactos_envio_html($contacto['telefonos'] !== [] ? implode(', ', $contacto['telefonos']) : '—') ?></td>
                    <td><?= dqs_contactos_envio_html($contacto['integrantes'] !== [] ? implode(', ', $contacto['integrantes']) : '—') ?></td>
                    <td><span class="admin-status <?= !empty($contacto['queue_blocked']) ? 'admin-status-queued' : (((string)($contacto['activo'] ?? '0') === '1') ? 'admin-status-active' : 'admin-status-inactive') ?>"><?= !empty($contacto['queue_blocked']) ? 'En cola / enviado' : (((string)($contacto['activo'] ?? '0') === '1') ? 'Activo' : 'Inactivo') ?></span></td>
                    <td><?= dqs_contactos_envio_html($date !== null && $date !== '' ? $date : '—') ?></td>
                    <td><div class="admin-row-actions">
                    <?php if ($tarjetaExiste): ?>
                        <a class="navbar-link admin-action-compact" href="<?= dqs_contactos_envio_html('invitaciones/' . $archivoTarjeta) ?>" target="_blank" rel="noopener" title="Ver tarjeta de invitación"><i class="fas fa-eye" aria-hidden="true"></i> Ver tarjeta</a>
                    <?php else: ?>
                        <small class="admin-muted" title="La tarjeta todavía no fue generada"><i class="fas fa-eye-slash" aria-hidden="true"></i> Sin tarjeta</small>
                    <?php endif; ?>
                    <?php if (!empty($contacto['queue_blocked'])): ?><small class="admin-muted"><i class="fas fa-lock" aria-hidden="true"></i> En cola / enviado</small><?php elseif ((string)($contacto['activo'] ?? '0') === '1'): ?>
                        <a class="navbar-link admin-action-compact" href="?new=contactos_envio&amp;accion=editar&amp;id=<?= (int)$contacto['id'] ?>"><i class="fas fa-edit" aria-hidden="true"></i> Editar</a>
                        <form method="post" action="?new=contactos_envio&amp;accion=inactivar" onsubmit="return confirm('¿Seguro que querés inactivar este contacto de envío?');">
                            <input type="hidden" name="csrf_token" value="<?= dqs_contactos_envio_html($_SESSION['guest_create_csrf']) ?>"><input type="hidden" name="id" value="<?= (int)$contacto['id'] ?>"><input type="hidden" name="estado" value="<?= dqs_contactos_envio_html($stateFilter) ?>"><button type="submit" class="navbar-link admin-action-compact"><i class="fas fa-ban" aria-hidden="true"></i> Inactivar</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="?new=contactos_envio&amp;accion=reactivar" onsubmit="return confirm('¿Seguro que querés reactivar este contacto de envío?');">
                            <input type="hidden" name="csrf_token" value="<?= dqs_contactos_envio_html($_SESSION['guest_create_csrf']) ?>"><input type="hidden" name="id" value="<?= (int)$contacto['id'] ?>"><input type="hidden" name="estado" value="<?= dqs_contactos_envio_html($stateFilter) ?>"><button type="submit" class="navbar-link admin-action-compact"><i class="fas fa-undo" aria-hidden="true"></i> Reactivar</button>
                        </form>
                    <?php endif; ?></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>
