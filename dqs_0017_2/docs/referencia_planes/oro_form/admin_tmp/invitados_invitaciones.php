<?php
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Incluir conexión (usa $conn, no $db)
include '../conexion.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['invitado_id']);

    if (isset($_POST['marcar_inactivo_pendiente'])) {
        // Acción específica para Pendientes
        $conn->query("UPDATE invitados SET activo = 0 WHERE id = $id");
        header("Location: ?new=invitaciones&fuente=pendiente");
        exit();
    }

    if (isset($_POST['marcar_inactivo_enviado'])) {
        // Acción específica para Enviados
        $conn->query("UPDATE invitados SET activo = 0 WHERE id = $id");
        $conn->close(); // Cerrar la conexión antes de redirigir
        header("Location: ?new=invitaciones&fuente=enviado");
        exit();
    }

    // --- NUEVA LÓGICA PARA CONFIRMACIÓN ---
    if (isset($_POST['confirmar_invitado'])) {
        // Obtener cantidad_mayores y cantidad_menores desde la base de datos
        // o si los tienes disponibles en el formulario, úsalos directamente.
        // Por simplicidad, los obtendremos de la base de datos para asegurar consistencia.
        $stmt_select = $conn->prepare("SELECT cantidad_mayores, cantidad_menores FROM invitados WHERE id = ?");
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $result_select = $stmt_select->get_result();
        $fila_invitado = $result_select->fetch_assoc();

        $cantidad_mayores = $fila_invitado['cantidad_mayores'] ?? 0;
        $cantidad_menores = $fila_invitado['cantidad_menores'] ?? 0;

        $sql = "UPDATE invitados SET
                    confirmacion = 'Si',
                    confirmacion_fecha = NOW(),
                    confirmacion_comentario = '',
                    confirmacion_mayores = ?,
                    confirmacion_menores = ?,
                    alimento = 'No'
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $cantidad_mayores, $cantidad_menores, $id);

        if ($stmt->execute()) {
            // Éxito al confirmar
            $stmt->close();
            $stmt_select->close();
            $conn->close(); // Cerrar la conexión antes de redirigir
            header("Location: ?new=invitaciones&fuente=pendiente"); // Puedes redirigir a donde consideres apropiado
            exit();
        } else {
            // Error al confirmar
            echo "Error al confirmar el invitado: " . $stmt->error;
        }
        $stmt->close();
        $stmt_select->close();
    }
    // --- FIN NUEVA LÓGICA PARA CONFIRMACIÓN ---
}


// Consulta de invitados CON invitación enviada
$consulta_enviados = "
SELECT
*
FROM
(
    SELECT
    a.id
    , a.nombre
    , a.apellido
    , b.invitados
    , c.id_invitado
    , c.id_invitados_tel
    , c.fecha_envio
    , c.estado_api
    , c.detalle_api
    , CAST(SUBSTRING(JSON_EXTRACT(c.detalle_api, '$.contacts[0].input'), 4, 10) AS UNSIGNED) AS numero_enviado
    , RANK() OVER (PARTITION BY a.id, c.id_invitados_tel  ORDER BY c.fecha_envio DESC) AS rnk
    , a.activo
    FROM invitados a
    LEFT JOIN
    (
        SELECT
            a.id_invitados,
            CASE WHEN cantidad_mayores+cantidad_menores<2 THEN nombre_invitado ELSE
            CONCAT(
            IF(COUNT(*) > 1,
                SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', COUNT(*) - 1),
                GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
            ),
            ' y ',
            SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
            ) END AS invitados
        FROM invitados_listado_mesa a
        INNER JOIN invitados b ON a.id_invitados=b.id
        GROUP BY a.id_invitados
    ) b
    ON a.id = b.id_invitados
    INNER JOIN invitaciones_estado c
    ON a.id = c.id_invitado
    ORDER BY activo DESC, estado_api DESC, apellido ASC, nombre ASC
) a
WHERE rnk=1
;
";

// Consulta de invitados SIN invitación enviada
$consulta_no_enviados = "SELECT
    a.id id_clientes,
    a.apellido,
    a.nombre,
    e.invitados,
    b.categoria_acompanante acompanado,
    a.cantidad_mayores,
    a.cantidad_menores,
    a.ingreso,
    f.categoria_prioridad,
    g.tel_enviar tel,
    a.confirmacion,
    a.confirmacion_fecha,
    a.alimento,
    a.confirmacion_comentario alimento_comentario,
    a.confirmacion_mayores,
    a.confirmacion_menores,
    a.activo
FROM invitados a
LEFT JOIN intivados_acompanante b ON a.acompanado = b.id
LEFT JOIN (
    SELECT
        a.id_invitados,
        CASE WHEN cantidad_mayores+cantidad_menores<2 THEN nombre_invitado ELSE
        CONCAT(
            IF(COUNT(*) > 1,
                SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', COUNT(*) - 1),
                GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
            ),
            ' y ',
            SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
        ) END AS invitados
    FROM invitados_listado_mesa a
    INNER JOIN invitados b ON a.id_invitados=b.id
    GROUP BY a.id_invitados
) e ON a.id = e.id_invitados
LEFT JOIN invitados_prioridad f ON a.id_prioridad = f.id
LEFT JOIN (
    SELECT id_invitados, GROUP_CONCAT(tel_enviar SEPARATOR ', ') AS tel_enviar
    FROM invitados_tel
    GROUP BY id_invitados
) g ON a.id = g.id_invitados
LEFT JOIN invitaciones_estado i ON a.id = i.id_invitado
WHERE a.activo = 1
AND i.id_invitado IS NULL
AND a.confirmacion IS NULL
order BY Apellido, Nombre
";

// Ejecutar las consultas
$result_enviados = $conn->query($consulta_enviados);
$result_no_enviados = $conn->query($consulta_no_enviados);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Invitaciones</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos básicos de la ventana modal */
        .modal {
            display: none; /* Oculto por defecto */
            position: fixed; /* Posición fija */
            z-index: 1; /* Se superpone a otros elementos */
            left: 0;
            top: 0;
            width: 100%; /* Ancho completo */
            height: 100%; /* Alto completo */
            overflow: auto; /* Habilitar scroll si es necesario */
            background-color: rgba(0,0,0,0.4); /* Negro con opacidad */
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% desde arriba y centrado */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Ancho ajustable */
            text-align: center;
            border-radius: 10px;
        }

        /* Estilos para el nuevo formato con divs */
        .invitation-list {
            display: flex;
            flex-direction: column;
            gap: 10px; /* Espacio entre cada tarjeta de invitado */
            max-height: 580px;
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

        /* Modificación: Hacemos que los spans dentro de invitation-details se muestren como bloques */
        .invitation-details span {
            display: block; /* Cada span ocupará su propia línea */
        }

        .invitation-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Estilos para los botones de acción dentro de las tarjetas */
        .invitation-actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2em;
            padding: 5px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }

        .invitation-actions button:hover {
            background-color: #e9e9e9;
        }

        .invitation-actions .delete-button {
            color: red;
        }

        .invitation-actions .confirm-button {
            color: blue;
        }

        .invitation-actions .status-icon {
            font-size: 1.2em;
            margin-left: 10px;
        }

        .chart-row {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            flex-wrap: wrap; /* Permite que los elementos se envuelvan en pantallas pequeñas */
        }

        .chart-row > div {
            flex: 1;
            min-width: 300px; /* Ancho mínimo para cada columna */
            padding: 20px;
            background-color: #fefefe;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }



        /* Estilos para los botones principales "Enviar invitación" y "Enviar errores" */
        /* Estos estilos están copiados directamente de .navbar-link en combined-styles.css */
        .main-action-button { /* Nueva clase para estos botones */
            display: inline-flex;
            align-items: center;
            background-color: #444; /* Color de fondo original de navbar-link */
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
            background-color: #555; /* Color de hover original de navbar-link */
        }

        .main-action-button .navbar-icon { /* Usamos el ícono existente de navbar-icon */
            margin-right: 8px;
        }
    </style>
</head>
<body>

<div id="loadingModal" class="modal">
  <div class="modal-content">
    <p>Procesando el envío... Por favor espere.</p>
    <p>Esto puede tardar unos minutos.</p>
  </div>
</div>

<script>
function showLoadingModal() {
    document.getElementById('loadingModal').style.display = 'block';
}

function hideLoadingModal() {
    document.getElementById('loadingModal').style.display = 'none';
}

function sendInvitations() { // Para el botón "Enviar invitación" (Pendientes)
    if (confirm('¿Estás seguro de que quieres enviar las invitaciones pendientes?')) {
        showLoadingModal();
        fetch('whatsapp/envio_invitaciones.php', {
            method: 'GET'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('La respuesta de la red no fue correcta al enviar invitaciones.');
            }
            return response.text();
        })
        .then(data => {
            console.log("Respuesta de envio_invitaciones.php:", data);
            hideLoadingModal();
            alert('Proceso de envío de invitaciones finalizado. La página se recargará.');
            location.reload();
        })
        .catch(error => {
            console.error('Error durante el envío de invitaciones:', error);
            hideLoadingModal();
            alert('Hubo un error al intentar enviar las invitaciones. Por favor, revise la consola para más detalles.');
            location.reload();
        });
    }
}

function sendErrors() { // Para el botón "Enviar erroneos"
    if (confirm('¿Estás seguro de que quieres intentar reenviar los mensajes con error?')) {
        showLoadingModal();
        fetch('whatsapp/reenvio_invitaciones_erroneas.php', { // RUTA CAMBIADA AQUÍ
            method: 'GET'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('La respuesta de la red no fue correcta al reenviar errores.');
            }
            return response.text();
        })
        .then(data => {
            console.log("Respuesta de reenvio_invitaciones_erroneas.php:", data);
            hideLoadingModal();
            alert('Proceso de reenvío de mensajes con error finalizado. La página se recargará.');
            location.reload();
        })
        .catch(error => {
            console.error('Error durante el reenvío de mensajes con error:', error);
            hideLoadingModal();
            alert('Hubo un error al intentar reenviar los mensajes con error. Por favor, revise la consola para más detalles.');
            location.reload();
        });
    }
}
</script>



    <div>
        <h2>Pendientes de Enviar</h2>
        <?php
        // Calcular cantidad de teléfonos reales (pendientes)
        $telefonos_pendientes = 0;
        $result_no_enviados->data_seek(0); // reiniciar puntero
        while ($fila = $result_no_enviados->fetch_assoc()) {
            $telefonos_pendientes += substr_count($fila['tel'], ',') + 1;
        }
        // Reset otra vez para el loop real de abajo
        $result_no_enviados->data_seek(0);
        ?>

        <p id="resultCount">Total de mensajes para enviar: <?= $telefonos_pendientes ?></p>
        <button
            onclick="sendInvitations();" class="main-action-button"
            <?= ($telefonos_pendientes >= 20) ? 'disabled style="opacity:0.5; cursor:not-allowed;" title="Solamente puedes enviar menor a 20 mensajes pendientes"' : '' ?>>
            <i class="fas fa-paper-plane navbar-icon"></i> Enviar invitación
        </button>
        <br>

        <div class="invitation-list">
            <?php while ($inv = $result_no_enviados->fetch_assoc()): ?>
                <div class="invitation-card">
                    <div class="invitation-details">
                        <strong><?= htmlspecialchars($inv['apellido']) ?>, <?= htmlspecialchars($inv['nombre']) ?></strong>
                        <span>Invitados: <?= htmlspecialchars($inv['invitados']) ?></span>
                        <span>Enviar a: <?= htmlspecialchars($inv['tel']) ?></span>
                    </div>
                    <div class="invitation-actions">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="invitado_id" value="<?= $inv['id_clientes'] ?>">
                            <button type="submit" name="marcar_inactivo_pendiente"
                                title="Marcar como inactivo"
                                class="delete-button"
                                onclick="return confirm('¿Marcar como inactivo este invitado?');">
                                🗑️
                            </button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="invitado_id" value="<?= $inv['id_clientes'] ?>">
                            <button type="submit" name="confirmar_invitado"
                                title="Confirmar Invitado"
                                class="confirm-button"
                                onclick="return confirm('¿Estás seguro de confirmar la asistencia de este invitado?');">
                                <i class="fas fa-user-check" style="color:green;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div>
        <h2>Invitaciones Enviadas</h2>
        <?php
        $telefonos_enviados = 0;
        $result_enviados->data_seek(0); // Reinicia el puntero
        while ($fila = $result_enviados->fetch_assoc()) {
            if (strtolower(trim($fila['estado_api'])) === 'enviado') {
                $telefonos_enviados += substr_count($fila['tel'], ',') + 1;
            }
        }
        $result_enviados->data_seek(0); // Lo volvés a dejar en 0 para usar después
        ?>

        <?php
        $telefonos_error = 0;
        $result_enviados->data_seek(0); // Reinicia el puntero

        while ($fila = $result_enviados->fetch_assoc()) {
            if (strtolower(trim($fila['estado_api'])) === 'error') {
                $telefonos_error += substr_count($fila['tel'], ',') + 1;
            }
        }

        $result_enviados->data_seek(0); // Para poder seguir usando $result_enviados después
        ?>

        <p id="resultCount">Total de mensajes enviados: <?= $telefonos_enviados ?></p>
        <button
            onclick="sendErrors();" class="main-action-button"
            <?= ($telefonos_error > 20) ? 'disabled style="opacity:0.5; cursor:not-allowed;" title="Solamente puedes enviar menor a 20 mensajes erroneos"' : '' ?>>
            <i class="fas fa-paper-plane navbar-icon"></i> Enviar errores
        </button>
        <br>

        <div class="invitation-list">
            <?php while ($inv = $result_enviados->fetch_assoc()): ?>
                <div class="invitation-card <?= ($inv['activo'] == 0) ? 'inactive' : '' ?>">
                    <div class="invitation-details">
                        <strong><?= htmlspecialchars($inv['apellido']) ?>, <?= htmlspecialchars($inv['nombre']) ?></strong>
                        <span>Invitados: <?= htmlspecialchars($inv['invitados']) ?></span>
                        <span>Enviado al: <?= htmlspecialchars($inv['numero_enviado']) ?></span>

<?php
    $estado = strtolower(trim($inv['estado_api']));
    $estilo_inline = '';

    if ($estado === 'enviado') {
        $estilo_inline = 'background-color:#d4edda;color:#155724;padding:2px 6px;border-radius:4px;font-weight:bold;';
    } elseif ($estado === 'error') {
        $estilo_inline = 'background-color:#f8d7da;color:#721c24;padding:2px 6px;border-radius:4px;font-weight:bold;';
    } else {
        $estilo_inline = 'background-color:#e2e3e5;color:#383d41;padding:2px 6px;border-radius:4px;font-weight:bold;';
    }
?>
<span style="<?= $estilo_inline ?>">
    Estado: <?= htmlspecialchars($inv['estado_api']) ?>
</span>


                    </div>
                    <div class="invitation-actions">
                        <?php if (strtolower($inv['estado_api']) !== 'enviado'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="invitado_id" value="<?= $inv['id'] ?>">
                                <button type="submit" name="marcar_inactivo_enviado"
                                    title="Marcar como inactivo"
                                    class="delete-button"
                                    onclick="return confirm('¿Marcar como inactivo este invitado?');">
                                    🗑️
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>


</body>
</html>