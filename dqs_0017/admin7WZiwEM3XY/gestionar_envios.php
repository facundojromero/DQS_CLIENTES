<?php
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Incluir el archivo de conexión
include_once '../conexion.php'; // Ajusta la ruta
require_once '../includes/admin_feature_guard.php';

// Verificar la conexión
if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

dqs_require_admin_whatsapp_enabled($conn);

// UNI-054 Fase 1: resolver la fuente efectiva con un mapa SQL cerrado.
$effective_plan_config = dqs_get_effective_plan_config($conn);
$contact_source = $effective_plan_config['fuente_envios_whatsapp'] ?? 'ninguno';
$raw_source_result = @$conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'fuente_envios_whatsapp' LIMIT 1");
if ($raw_source_result && ($raw_source_row = $raw_source_result->fetch_assoc())) {
    $raw_source = (string)($raw_source_row['setting_value'] ?? '');
    if (!in_array($raw_source, ['invitados', 'pre_invitados', 'ninguno'], true)) {
        $contact_source = 'ninguno';
    }
}

$contact_source_queries = [
    'invitados' => [
        'select' => 'a.id AS id_invitados, b.id AS id_invitados_tel, a.nombre, a.apellido, b.tel_enviar, a.activo, e.invitados',
        'from' => 'invitados a INNER JOIN invitados_tel b ON a.id = b.id_invitados',
        'members' => 'invitados_listado_mesa',
        'member_fk' => 'id_invitados',
        'member_name' => 'nombre_invitado',
        'member_order' => 'id',
        'contact_table' => 'invitados',
        'phone_table' => 'invitados_tel',
        'phone_fk' => 'id_invitados',
        'phone_value' => 'tel_enviar',
    ],
    'pre_invitados' => [
        'select' => 'a.id AS id_invitados, b.id AS id_invitados_tel, a.nombre, a.apellido, b.telefono AS tel_enviar, a.activo, e.invitados',
        'from' => 'pre_invitados a INNER JOIN pre_invitados_tel b ON a.id = b.id_pre_invitado',
        'members' => 'pre_invitados_listado_mesa',
        'member_fk' => 'id_pre_invitado',
        'member_name' => "TRIM(CONCAT(nombre, ' ', apellido))",
        'member_order' => 'orden',
        'contact_table' => 'pre_invitados',
        'phone_table' => 'pre_invitados_tel',
        'phone_fk' => 'id_pre_invitado',
        'phone_value' => 'telefono',
    ],
];

if (!isset($contact_source_queries[$contact_source])) {
    http_response_code(403);
    echo '<p>No hay una fuente válida de contactos habilitada para los envíos WhatsApp.</p>';
    $conn->close();
    exit();
}

$contact_query = $contact_source_queries[$contact_source];
// Lógica para manejar las acciones de POST (agregar, quitar, marcar como enviado, etc.)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json');

    $id_invitados = filter_input(INPUT_POST, 'id_invitados', FILTER_VALIDATE_INT);
    $id_invitados_tel = filter_input(INPUT_POST, 'id_invitados_tel', FILTER_VALIDATE_INT);

    if (!$id_invitados || !$id_invitados_tel) {
        echo json_encode(["status" => "error", "message" => "Error: Datos incompletos."]);
        $conn->close();
        exit();
    }

    // El cliente no elige la familia de tablas ni el teléfono. Ambos se resuelven
    // desde la fuente efectiva de esta request y el mapa cerrado anterior.
    $phone_table = $contact_query['phone_table'];
    $phone_fk = $contact_query['phone_fk'];
    $phone_value = $contact_query['phone_value'];
    $sql_phone = "SELECT $phone_value AS tel_enviar FROM $phone_table WHERE id = ? AND $phone_fk = ? LIMIT 1";
    $stmt_phone = $conn->prepare($sql_phone);
    $stmt_phone->bind_param('ii', $id_invitados_tel, $id_invitados);
    $stmt_phone->execute();
    $phone_row = $stmt_phone->get_result()->fetch_assoc();
    $stmt_phone->close();
    if (!$phone_row) {
        echo json_encode(["status" => "error", "message" => "El contacto no pertenece a la fuente efectiva."]);
        $conn->close();
        exit();
    }
    $tel_enviar = (string)$phone_row['tel_enviar'];

    // Los contactos staging inactivos nunca pueden ingresar (ni reingresar) a
    // A Enviar. La fuente efectiva, no el request, determina esta validación.
    if ($contact_source === 'pre_invitados'
        && in_array($_POST['accion'], ['agregar_a_enviar', 'mover_a_enviar_de_enviado'], true)) {
        $stmt_active = $conn->prepare('SELECT 1 FROM `pre_invitados` WHERE `id` = ? AND `activo` = 1 LIMIT 1');
        $stmt_active->bind_param('i', $id_invitados); $stmt_active->execute(); $stmt_active->store_result();
        $is_active = $stmt_active->num_rows === 1; $stmt_active->close();
        if (!$is_active) {
            echo json_encode(['status' => 'error', 'message' => 'El contacto de envío está inactivo y no puede agregarse a A Enviar.']);
            $conn->close();
            exit();
        }
    }

    switch ($_POST['accion']) {
        case 'agregar_a_enviar':
            $fecha_agregado = date('Y-m-d H:i:s');
            $sql = "INSERT INTO invitados_a_enviar (id_invitados, id_invitados_tel, tel_enviar, fecha_agregado) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $id_invitados, $id_invitados_tel, $tel_enviar, $fecha_agregado);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Invitado agregado a la lista de envío."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error al agregar invitado: " . $stmt->error]);
            }
            $stmt->close();
            break;

        case 'quitar_de_enviar':
            $sql = "DELETE FROM invitados_a_enviar WHERE id_invitados = ? AND id_invitados_tel = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id_invitados, $id_invitados_tel);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Invitado quitado de la lista de envío."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error al quitar invitado: " . $stmt->error]);
            }
            $stmt->close();
            break;

        case 'marcar_enviado':
            $fecha_envio = date('Y-m-d H:i:s');
            // Mover el registro de la tabla 'a_enviar' a la tabla 'enviados'
            $conn->begin_transaction();
            try {
                $sql_insert = "INSERT INTO invitados_enviados (id_invitados, id_invitados_tel, tel_enviar, fecha_envio) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iiss", $id_invitados, $id_invitados_tel, $tel_enviar, $fecha_envio);
                $stmt_insert->execute();

                $sql_delete = "DELETE FROM invitados_a_enviar WHERE id_invitados = ? AND id_invitados_tel = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("ii", $id_invitados, $id_invitados_tel);
                $stmt_delete->execute();

                $conn->commit();
                echo json_encode(["status" => "success", "message" => "Invitado marcado como enviado."]);
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                echo json_encode(["status" => "error", "message" => "Error de transacción: " . $exception->getMessage()]);
            }
            break;

        case 'mover_a_enviar_de_enviado':
            $conn->begin_transaction();
            try {
                $nuevo_tel_enviar = $tel_enviar;

                // Insertar de vuelta en 'invitados_a_enviar' usando el teléfono consultado
                $fecha_agregado = date('Y-m-d H:i:s');
                $sql_insert = "INSERT INTO invitados_a_enviar (id_invitados, id_invitados_tel, tel_enviar, fecha_agregado) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iiss", $id_invitados, $id_invitados_tel, $nuevo_tel_enviar, $fecha_agregado);
                $stmt_insert->execute();

                // Eliminar de 'invitados_enviados'
                $sql_delete = "DELETE FROM invitados_enviados WHERE id_invitados = ? AND id_invitados_tel = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("ii", $id_invitados, $id_invitados_tel);
                $stmt_delete->execute();

                $conn->commit();
                echo json_encode(["status" => "success", "message" => "Invitado movido de vuelta a la lista 'A Enviar'.", "tel_enviar" => $nuevo_tel_enviar]);
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                echo json_encode(["status" => "error", "message" => "Error de transacción: " . $exception->getMessage()]);
            }
            break;
    }
    $conn->close();
    exit();
}

// Lógica para manejar las acciones de los botones de la tarjeta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($contact_source !== 'invitados') {
        http_response_code(403);
        echo 'Esta fuente solo admite movimientos de la cola en esta fase.';
        $conn->close();
        exit();
    }

    $id_invitado = intval($_POST['id_invitado']);

    if (isset($_POST['confirmar_invitado'])) {
        $stmt = $conn->prepare("UPDATE invitados SET confirmacion = 'Si' WHERE id = ?");
        $stmt->bind_param("i", $id_invitado);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['toggle_activo'])) {
        // Obtenemos el estado actual
        $sql_activo = "SELECT activo FROM invitados WHERE id = ?";
        $stmt_activo = $conn->prepare($sql_activo);
        $stmt_activo->bind_param("i", $id_invitado);
        $stmt_activo->execute();
        $result_activo = $stmt_activo->get_result();
        $invitado = $result_activo->fetch_assoc();

        $nuevo_estado = ($invitado['activo'] == 1) ? 0 : 1;

        // Actualizamos el estado
        $stmt_update = $conn->prepare("UPDATE invitados SET activo = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $nuevo_estado, $id_invitado);
        $stmt_update->execute();
        $stmt_update->close();
        $stmt_activo->close();
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Las colas históricas se reutilizan deliberadamente. Sus ids se interpretan
// exclusivamente contra la familia efectiva durante toda esta request. Si se
// cambia de plan con colas pobladas puede haber colisiones de ids; UNI-054 Fase 2
// no modifica el esquema ni intenta migrarlas.
$member_table = $contact_query['members'];
$member_fk = $contact_query['member_fk'];
$member_name = $contact_query['member_name'];
$member_order = $contact_query['member_order'];
$subquery_contactos = "
    LEFT JOIN (
        SELECT
            $member_fk,
            CASE WHEN COUNT(*) > 1 THEN
                CONCAT(SUBSTRING_INDEX(GROUP_CONCAT($member_name ORDER BY m.$member_order ASC SEPARATOR ', '), ', ', COUNT(*) - 1), ' y ', SUBSTRING_INDEX(GROUP_CONCAT($member_name ORDER BY m.$member_order ASC SEPARATOR ', '), ', ', -1))
            ELSE
                GROUP_CONCAT($member_name ORDER BY m.$member_order ASC SEPARATOR ', ')
            END AS invitados
        FROM $member_table m
        GROUP BY $member_fk
    ) e ON a.id = e.$member_fk
";

// Obtener los invitados que ya están en la lista "A Enviar"
$invitados_a_enviar = [];
$sql_a_enviar = "SELECT {$contact_query['select']} FROM {$contact_query['contact_table']} a
INNER JOIN invitados_a_enviar c
ON a.id = c.id_invitados
INNER JOIN {$contact_query['phone_table']} b
ON c.id_invitados_tel = b.id AND b.{$contact_query['phone_fk']} = a.id
$subquery_contactos
where a.activo<2
ORDER BY apellido ASC, nombre ASC";
$result_a_enviar = $conn->query($sql_a_enviar);
if ($result_a_enviar->num_rows > 0) {
    while($row = $result_a_enviar->fetch_assoc()) {
        $invitados_a_enviar[] = $row;
    }
}
$ids_a_enviar = array_column($invitados_a_enviar, 'id_invitados');

// Obtener los invitados que ya están en la lista de "Enviados"
$invitados_enviados = [];
$sql_enviados = "SELECT {$contact_query['select']} FROM {$contact_query['contact_table']} a
INNER JOIN invitados_enviados c
ON a.id = c.id_invitados
INNER JOIN {$contact_query['phone_table']} b
ON c.id_invitados_tel = b.id AND b.{$contact_query['phone_fk']} = a.id
$subquery_contactos
ORDER BY apellido ASC, nombre ASC";
$result_enviados = $conn->query($sql_enviados);
if ($result_enviados->num_rows > 0) {
    while($row = $result_enviados->fetch_assoc()) {
        $invitados_enviados[] = $row;
    }
}
$ids_enviados = array_column($invitados_enviados, 'id_invitados');

// Obtener los filtros si existen
$confirmacionFiltro = isset($_GET['confirmacion']) ? $_GET['confirmacion'] : '';
$busquedaFiltro = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$statusFiltro = isset($_GET['status']) ? $_GET['status'] : '';
$ingresoFiltro = isset($_GET['ingreso']) ? $_GET['ingreso'] : '';
$prioridadFiltro = isset($_GET['prioridad']) ? $_GET['prioridad'] : '';

// Consulta para obtener los contactos disponibles desde la fuente efectiva.
// Las consultas históricas de "A Enviar" y "Enviados" permanecen intactas arriba.
$invitados_activos = [];
$sql_activos = "SELECT {$contact_query['select']} FROM {$contact_query['from']}
$subquery_contactos
WHERE 1=1
AND a.activo" . ($contact_source === 'pre_invitados' ? ' = 1' : '<2');

$ids_excluir = array_merge($ids_a_enviar, $ids_enviados);
if (!empty($ids_excluir)) {
    $sql_activos .= " AND a.id NOT IN (" . implode(',', $ids_excluir) . ")";
}

// Agregar condiciones de filtro a la consulta
if ($statusFiltro !== '') {
    $sql_activos .= " AND a.activo = '$statusFiltro'";
}
if ($confirmacionFiltro !== '') {
    if ($confirmacionFiltro == 'NULL') {
        $sql_activos .= " AND a.confirmacion IS NULL";
    } else {
        $sql_activos .= " AND a.confirmacion = '$confirmacionFiltro'";
    }
}
if ($busquedaFiltro !== '') {
    $sql_activos .= " AND (a.nombre LIKE '%$busquedaFiltro%' OR a.apellido LIKE '%$busquedaFiltro%')";
}
if ($contact_source === 'invitados' && $ingresoFiltro !== '') {
    $sql_activos .= " AND a.ingreso = '$ingresoFiltro'";
}
if ($contact_source === 'invitados' && $prioridadFiltro !== '') {
    $sql_activos .= " AND a.id_prioridad = '$prioridadFiltro'";
}

$sql_activos .= " ORDER BY apellido ASC, nombre ASC";

$result_activos = $conn->query($sql_activos);
if ($result_activos->num_rows > 0) {
    while($row = $result_activos->fetch_assoc()) {
        $invitados_activos[] = $row;
    }
}

$conn->close();

// Contar la cantidad de invitados en cada lista
$count_activos = count($invitados_activos);
$count_a_enviar = count($invitados_a_enviar);
$count_enviados = count($invitados_enviados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Envíos</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos específicos para la interfaz de drag and drop */
        .drag-and-drop-container {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            margin-top: 30px;
        }
        .drag-and-drop-box {
            width: 30%;
            min-height: 400px;
            border: 2px dashed #ccc;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
            overflow-y: auto; /* Para que la lista sea scrollable */
        }
        .draggable-item {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 5px;
            cursor: grab;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-size: 14px;
        }
        .draggable-item:active {
            cursor: grabbing;
        }
        .not-draggable {
            cursor: default !important;
        }
        .drag-and-drop-box h2 {
            text-align: center;
            margin-top: 0;
            color: #333;
        }

        /* Estilos del archivo invitados_invitaciones.php */
        .invitation-list {
            display: flex;
            flex-direction: column;
            gap: 10px; /* Espacio entre cada tarjeta de invitado */
            max-height: 420px;
            overflow-y: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 10px;
            background-color: #f9f9f9;
        }

        .invitation-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s ease-in-out;
            margin-bottom: 5px; /* Ajuste para drag and drop */
        }

        .invitation-card:hover {
            transform: translateY(-3px);
        }

        .invitation-card.inactive {
            opacity: 0.6;
            background-color: #f0f0f0;
        }

        .invitation-details {
            flex-grow: 1;
        }

        .invitation-details strong {
            display: block;
            font-size: 1.1em;
            margin-bottom: 5px;
        }

        .invitation-details span {
            display: block; /* Cada span ocupará su propia línea */
        }

        /* Estilos para los botones de acción */
        .invitation-actions {
            display: flex;
            gap: 5px;
            margin-left: 10px;
        }
        .invitation-actions button {
            background-color: transparent;
            border: none;
            cursor: pointer;
        }
        .invitation-actions .toggle-button {
            color: #007bff;
        }
        .invitation-actions .toggle-button.active {
            color: #28a745;
        }
        .invitation-actions .toggle-button.inactive {
            color: #dc3545;
        }
        .invitation-actions .confirm-button {
            font-size: 1.2em;
            line-height: 1;
        }

        /* Estilos para los botones principales */
        .main-action-button {
            display: inline-flex;
            align-items: center;
            background-color: #444;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 15px;
        }

        .main-action-button:hover {
            background-color: #555;
        }

        .main-action-button .navbar-icon {
            margin-right: 8px;
        }
    </style>
</head>
<body>

    <h2>Gestión de Envíos Masivos</h2>

    <div class="search-container">
        <div class="search-item">
            <label for="searchInput">Buscar invitados:</label>
            <input type="text" id="searchInput" class="search-input" placeholder="Buscar invitados..." value="<?php echo htmlspecialchars($busquedaFiltro); ?>">
        </div>
        <div class="search-item">
            <label for="confirmationFilter">Filtrar por confirmación:</label>
            <select id="confirmationFilter" class="search-input" onchange="applyFilter()">
                <option value="">Todos</option>
                <option value="Si" <?php if ($confirmacionFiltro == 'Si') echo 'selected'; ?>>Si</option>
                <option value="No" <?php if ($confirmacionFiltro == 'No') echo 'selected'; ?>>No</option>
                <option value="NULL" <?php if ($confirmacionFiltro == 'NULL') echo 'selected'; ?>>No Confirmado</option>
            </select>
        </div>
        <div class="search-item">
            <label for="statusFilter">Filtrar por estado:</label>
            <select id="statusFilter" class="search-input" onchange="applyFilter()">
                <option value="">Todos</option>
                <option value="1" <?php if ($statusFiltro == '1') echo 'selected'; ?>>Activos</option>
                <option value="0" <?php if ($statusFiltro == '0') echo 'selected'; ?>>Inactivos</option>
            </select>
        </div>
        <div class="search-item">
            <label for="ingresoFilter">Filtrar por ingreso:</label>
            <select id="ingresoFilter" class="search-input" onchange="applyFilter()">
                <option value="">Todos</option>
                <option value="Inicio" <?php if ($ingresoFiltro == 'Inicio') echo 'selected'; ?>>Inicio</option>
                <option value="Tarde" <?php if ($ingresoFiltro == 'Tarde') echo 'selected'; ?>>Tarde</option>
            </select>
        </div>
        <div class="search-item">
            <label for="prioridadFilter">Filtrar por prioridad:</label>
            <select id="prioridadFilter" class="search-input" onchange="applyFilter()">
                <option value="">Todos</option>
                <option value="1" <?php if ($prioridadFiltro == '1') echo 'selected'; ?>>Importante</option>
                <option value="2" <?php if ($prioridadFiltro == '2') echo 'selected'; ?>>Medio Importante</option>
                <option value="3" <?php if ($prioridadFiltro == '3') echo 'selected'; ?>>Normal</option>
                <option value="4" <?php if ($prioridadFiltro == '4') echo 'selected'; ?>>No necesario</option>
            </select>
        </div>
        <div class="search-item">
            <button type="button" class="navbar-link" onclick="resetFilters()">
                <i class="fas fa-redo navbar-icon"></i> Resetear
            </button>
        </div>
    </div>

    <div class="drag-and-drop-container">
        <div id="source-list" class="drag-and-drop-box">
            <h2>Pendientes de Enviar (<?php echo $count_activos; ?>)</h2>
            <div class="invitation-list">
                <?php foreach ($invitados_activos as $invitado): ?>
                    <div
                        class="invitation-card draggable-item <?= ($invitado['activo'] == 0) ? 'inactive' : '' ?>"
                        draggable="true"
                        data-id-invitados="<?php echo htmlspecialchars($invitado['id_invitados']); ?>"
                        data-id-invitados-tel="<?php echo htmlspecialchars($invitado['id_invitados_tel']); ?>"
                        data-tel-enviar="<?php echo htmlspecialchars($invitado['tel_enviar']); ?>"
                    >
                        <div class="invitation-details">
                            <strong><?= htmlspecialchars($invitado['apellido']) ?>, <?= htmlspecialchars($invitado['nombre']) ?></strong>
                            <span>Invitados: <?= htmlspecialchars($invitado['invitados']) ?></span>
                            <span>Teléfono: <?= htmlspecialchars($invitado['tel_enviar']); ?></span>
                        </div>
                        <?php if ($contact_source === 'invitados'): ?>
                        <div class="invitation-actions">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_invitado" value="<?= $invitado['id_invitados'] ?>">
                                <button type="submit" name="toggle_activo"
                                    title="<?= ($invitado['activo'] == 1) ? 'Desactivar Invitado' : 'Activar Invitado' ?>"
                                    class="toggle-button <?= ($invitado['activo'] == 1) ? 'active' : 'inactive' ?>"
                                    onclick="return confirm('¿Estás seguro de <?= ($invitado['activo'] == 1) ? 'desactivar' : 'activar' ?> este invitado?');">
                                    <i class="fas <?= ($invitado['activo'] == 1) ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                </button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_invitado" value="<?= $invitado['id_invitados'] ?>">
                                <button type="submit" name="confirmar_invitado"
                                    title="Confirmar Invitado"
                                    class="confirm-button"
                                    onclick="return confirm('¿Estás seguro de confirmar la asistencia de este invitado?');">
                                    <i class="fas fa-user-check" style="color:green;"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="target-list-a-enviar" class="drag-and-drop-box">
            <h2>A Enviar (<?php echo $count_a_enviar; ?>)</h2>
            <div class="invitation-list">
                <?php foreach ($invitados_a_enviar as $invitado): ?>
                    <div
                        class="invitation-card draggable-item <?= ($invitado['activo'] == 0) ? 'inactive' : '' ?>"
                        draggable="true"
                        data-id-invitados="<?php echo htmlspecialchars($invitado['id_invitados']); ?>"
                        data-id-invitados-tel="<?php echo htmlspecialchars($invitado['id_invitados_tel']); ?>"
                        data-tel-enviar="<?php echo htmlspecialchars($invitado['tel_enviar']); ?>"
                    >
                        <div class="invitation-details">
                            <strong><?= htmlspecialchars($invitado['apellido']) ?>, <?= htmlspecialchars($invitado['nombre']) ?></strong>
                            <span>Invitados: <?= htmlspecialchars($invitado['invitados']) ?></span>
                            <span>Teléfono: <?= htmlspecialchars($invitado['tel_enviar']); ?></span>
                        </div>
                        <?php if ($contact_source === 'invitados'): ?>
                        <div class="invitation-actions">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_invitado" value="<?= $invitado['id_invitados'] ?>">
                                <button type="submit" name="toggle_activo"
                                    title="<?= ($invitado['activo'] == 1) ? 'Desactivar Invitado' : 'Activar Invitado' ?>"
                                    class="toggle-button <?= ($invitado['activo'] == 1) ? 'active' : 'inactive' ?>"
                                    onclick="return confirm('¿Estás seguro de <?= ($invitado['activo'] == 1) ? 'desactivar' : 'activar' ?> este invitado?');">
                                    <i class="fas <?= ($invitado['activo'] == 1) ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                </button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_invitado" value="<?= $invitado['id_invitados'] ?>">
                                <button type="submit" name="confirmar_invitado"
                                    title="Confirmar Invitado"
                                    class="confirm-button"
                                    onclick="return confirm('¿Estás seguro de confirmar la asistencia de este invitado?');">
                                    <i class="fas fa-user-check" style="color:green;"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="target-list-enviados" class="drag-and-drop-box">
            <h2>Enviados (<?php echo $count_enviados; ?>)</h2>
            <div class="invitation-list">
                <?php foreach ($invitados_enviados as $invitado): ?>
                    <div
                        class="invitation-card draggable-item <?= ($invitado['activo'] == 0) ? 'inactive' : '' ?>"
                        draggable="true"
                        data-id-invitados="<?php echo htmlspecialchars($invitado['id_invitados']); ?>"
                        data-id-invitados-tel="<?php echo htmlspecialchars($invitado['id_invitados_tel']); ?>"
                        data-tel-enviar="<?php echo htmlspecialchars($invitado['tel_enviar']); ?>"
                    >
                        <div class="invitation-details">
                            <strong><?= htmlspecialchars($invitado['apellido']) ?>, <?= htmlspecialchars($invitado['nombre']) ?></strong>
                            <span>Invitados: <?= htmlspecialchars($invitado['invitados']) ?></span>
                            <span>Teléfono: <?= htmlspecialchars($invitado['tel_enviar']); ?></span>
                        </div>
                        <?php if ($contact_source === 'invitados'): ?>
                        <div class="invitation-actions">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_invitado" value="<?= $invitado['id_invitados'] ?>">
                                <button type="submit" name="toggle_activo"
                                    title="<?= ($invitado['activo'] == 1) ? 'Desactivar Invitado' : 'Activar Invitado' ?>"
                                    class="toggle-button <?= ($invitado['activo'] == 1) ? 'active' : 'inactive' ?>"
                                    onclick="return confirm('¿Estás seguro de <?= ($invitado['activo'] == 1) ? 'desactivar' : 'activar' ?> este invitado?');">
                                    <i class="fas <?= ($invitado['activo'] == 1) ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                </button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_invitado" value="<?= $invitado['id_invitados'] ?>">
                                <button type="submit" name="confirmar_invitado"
                                    title="Confirmar Invitado"
                                    class="confirm-button"
                                    onclick="return confirm('¿Estás seguro de confirmar la asistencia de este invitado?');">
                                    <i class="fas fa-user-check" style="color:green;"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        const sourceList = document.getElementById('source-list').querySelector('.invitation-list');
        const targetListAEnviar = document.getElementById('target-list-a-enviar').querySelector('.invitation-list');
        const targetListEnviados = document.getElementById('target-list-enviados').querySelector('.invitation-list');

        let draggedItem = null;

        let autoRefreshInFlight = false;
        let queueMutationInFlight = false;
        let autoRefreshWarningShown = false;
        const autoRefreshInterval = 5000;
        const autoRefreshColumnIds = [
            'source-list',
            'target-list-a-enviar',
            'target-list-enviados'
        ];

        document.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('draggable-item') && e.target.getAttribute('draggable') === 'true') {
                draggedItem = e.target;
                e.dataTransfer.effectAllowed = 'move';
                const data = {
                    id_invitados: draggedItem.dataset.idInvitados,
                    id_invitados_tel: draggedItem.dataset.idInvitadosTel,
                    tel_enviar: draggedItem.dataset.telEnviar
                };
                e.dataTransfer.setData('application/json', JSON.stringify(data));
            } else {
                e.preventDefault();
            }
        });

        document.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        document.addEventListener('dragend', () => {
            draggedItem = null;
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            const targetBox = e.target.closest('.drag-and-drop-box');

            if (!targetBox || !draggedItem) return;

            // Guardamos la lista de origen antes de mover el elemento
            const sourceBox = draggedItem.parentNode;

            // Solo permitir el drop en las listas correctas
            if (targetBox.id === 'source-list' && sourceBox.parentNode.id === 'target-list-a-enviar') {
                targetBox.querySelector('.invitation-list').appendChild(draggedItem);
                enviarDatosAlServidor('quitar_de_enviar', draggedItem, sourceBox, targetBox);
            } else if (targetBox.id === 'target-list-a-enviar' && sourceBox.parentNode.id === 'source-list') {
                targetBox.querySelector('.invitation-list').appendChild(draggedItem);
                enviarDatosAlServidor('agregar_a_enviar', draggedItem, sourceBox, targetBox);
            } else if (targetBox.id === 'target-list-enviados' && sourceBox.parentNode.id === 'target-list-a-enviar') {
                targetBox.querySelector('.invitation-list').appendChild(draggedItem);
                // Desactivamos el arrastre del elemento
                draggedItem.setAttribute('draggable', 'false');
                draggedItem.classList.add('not-draggable');
                enviarDatosAlServidor('marcar_enviado', draggedItem, sourceBox, targetBox);
            } else if (targetBox.id === 'target-list-a-enviar' && sourceBox.parentNode.id === 'target-list-enviados') {
                 targetBox.querySelector('.invitation-list').appendChild(draggedItem);
                 // Volvemos a activar el arrastre y quitamos la clase not-draggable
                 draggedItem.setAttribute('draggable', 'true');
                 draggedItem.classList.remove('not-draggable');
                 enviarDatosAlServidor('mover_a_enviar_de_enviado', draggedItem, sourceBox, targetBox);
            }
        });

        function enviarDatosAlServidor(accion, itemElement, sourceBox, targetBox) {
            queueMutationInFlight = true;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'gestionar_envios.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (this.status >= 200 && this.status < 400) {
                    const response = JSON.parse(this.responseText);
                    if (response.status === 'success') {
                        console.log(response.message);
                        // Actualizar el número de teléfono si el elemento se movió de 'Enviados' a 'A Enviar'
                        if (accion === 'mover_a_enviar_de_enviado' && response.tel_enviar) {
                            itemElement.dataset.telEnviar = response.tel_enviar;
                            itemElement.querySelector('.invitation-details span:nth-of-type(2)').textContent = `Teléfono: ${response.tel_enviar}`;
                        }
                        actualizarContadores();
                    } else {
                        console.error("Error del servidor:", response.message);
                        alert("Hubo un error al registrar el envío. El elemento será devuelto a su posición original.");
                        // Si hay un error, devolvemos el elemento a la lista de origen
                        sourceBox.appendChild(itemElement);
                    }
                } else {
                    console.error("Error de conexión con el servidor.");
                    alert("No se pudo conectar al servidor. El elemento será devuelto a su posición original.");
                    // Si hay un error, devolvemos el elemento a la lista de origen
                    sourceBox.appendChild(itemElement);
                }
            };

            xhr.onerror = function() {
                console.error("Error de conexión.");
                alert("No se pudo conectar al servidor. El elemento será devuelto a su posición original.");
                sourceBox.appendChild(itemElement);
            };

            xhr.onloadend = function() {
                queueMutationInFlight = false;
            };

            const id_invitados = itemElement.dataset.idInvitados;
            const id_invitados_tel = itemElement.dataset.idInvitadosTel;
            const tel_enviar = itemElement.dataset.telEnviar;
            const data = `accion=${accion}&id_invitados=${id_invitados}&id_invitados_tel=${id_invitados_tel}&tel_enviar=${tel_enviar}`;
            xhr.send(data);
        }

        function obtenerFirmaColumna(column) {
            const list = column.querySelector('.invitation-list');
            const cards = Array.from(list.querySelectorAll('.invitation-card')).map((card) => ({
                idColumn: column.id,
                idInvitados: card.dataset.idInvitados || '',
                idInvitadosTel: card.dataset.idInvitadosTel || '',
                content: card.innerHTML.replace(/\s+/g, ' ').trim(),
                classes: card.className
            }));

            return JSON.stringify(cards);
        }

        function advertirErrorAutoRefresh(error) {
            if (!autoRefreshWarningShown) {
                console.warn('No se pudo actualizar automáticamente el estado de envíos.', error);
                autoRefreshWarningShown = true;
            }
        }

        async function refrescarEstadoEnvios() {
            if (document.hidden || draggedItem !== null || queueMutationInFlight || autoRefreshInFlight) {
                return;
            }

            autoRefreshInFlight = true;

            try {
                const response = await fetch(window.location.href, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const html = await response.text();
                const refreshedDocument = new DOMParser().parseFromString(html, 'text/html');
                const currentColumns = autoRefreshColumnIds.map((id) => document.getElementById(id));
                const refreshedColumns = autoRefreshColumnIds.map((id) => refreshedDocument.getElementById(id));
                const responseIsLogin = refreshedDocument.querySelector('input[type="password"]') !== null;
                const responseIsValid = !responseIsLogin && refreshedColumns.every((column) =>
                    column && column.querySelector('.invitation-list') && column.querySelector('h2')
                );

                if (!responseIsValid) {
                    throw new Error('La respuesta no contiene las columnas de envíos esperadas.');
                }

                const currentSignature = currentColumns.map(obtenerFirmaColumna).join('|');
                const refreshedSignature = refreshedColumns.map(obtenerFirmaColumna).join('|');

                if (currentSignature !== refreshedSignature) {
                    const scrollPositions = currentColumns.map((column) =>
                        column.querySelector('.invitation-list').scrollTop
                    );

                    currentColumns.forEach((column, index) => {
                        const currentList = column.querySelector('.invitation-list');
                        const refreshedList = refreshedColumns[index].querySelector('.invitation-list');
                        currentList.innerHTML = refreshedList.innerHTML;
                        column.querySelector('h2').textContent = refreshedColumns[index].querySelector('h2').textContent;

                        const maximumScroll = Math.max(0, currentList.scrollHeight - currentList.clientHeight);
                        currentList.scrollTop = Math.min(scrollPositions[index], maximumScroll);
                    });
                }

                autoRefreshWarningShown = false;
            } catch (error) {
                advertirErrorAutoRefresh(error);
            } finally {
                autoRefreshInFlight = false;
            }
        }

        setInterval(refrescarEstadoEnvios, autoRefreshInterval);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                refrescarEstadoEnvios();
            }
        });

        function actualizarContadores() {
            const countActivos = sourceList.querySelectorAll('.draggable-item').length;
            const countAEnviar = targetListAEnviar.querySelectorAll('.draggable-item').length;
            const countEnviados = targetListEnviados.querySelectorAll('.draggable-item').length;

            document.querySelector('#source-list h2').textContent = `Pendientes de Enviar (${countActivos})`;
            document.querySelector('#target-list-a-enviar h2').textContent = `A Enviar (${countAEnviar})`;
            document.querySelector('#target-list-enviados h2').textContent = `Enviados (${countEnviados})`;
        }

        // Funciones para aplicar y resetear filtros (sin cambios)
        function applyFilter() {
            var confirmation = document.getElementById('confirmationFilter').value;
            var search = document.getElementById('searchInput').value;
            var status = document.getElementById('statusFilter').value;
            var ingreso = document.getElementById('ingresoFilter').value;
            var prioridad = document.getElementById('prioridadFilter').value;

            var url = "?new=envioinvitaciones&";
            if (confirmation) url += "confirmacion=" + confirmation + "&";
            if (search) url += "busqueda=" + search + "&";
            if (status) url += "status=" + status + "&";
            if (ingreso) url += "ingreso=" + ingreso + "&";
            if (prioridad) url += "prioridad=" + prioridad + "&";

            if (url.endsWith("&")) {
                url = url.slice(0, -1);
            }
            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = "?new=envioinvitaciones";
        }

        document.getElementById('searchInput').addEventListener('keyup', function() {
            applyFilter();
        });
    </script>
</body>
</html>
