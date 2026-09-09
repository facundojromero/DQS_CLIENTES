<?php
include_once 'conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
    exit();
}

$codigo_invitado = isset($_POST['codigo_invitado']) ? trim($_POST['codigo_invitado']) : '';
$confirmacion = isset($_POST['confirmar_asistencia']) ? $_POST['confirmar_asistencia'] : 'No';
$seleccionados = isset($_POST['seleccionados']) && is_array($_POST['seleccionados']) ? $_POST['seleccionados'] : [];
$alimento_persona = isset($_POST['alimento_persona']) && is_array($_POST['alimento_persona']) ? $_POST['alimento_persona'] : [];
$comentario_persona = isset($_POST['comentario_persona']) && is_array($_POST['comentario_persona']) ? $_POST['comentario_persona'] : [];

$response = ['success' => false, 'message' => ''];

if (empty($codigo_invitado)) {
    $response['message'] = 'Código de invitado no proporcionado.';
    echo json_encode($response);
    exit();
}

if (!$conn) {
    $response['message'] = 'Error de conexión a la base de datos.';
    echo json_encode($response);
    exit();
}

$stmt_invitado = $conn->prepare('SELECT id FROM invitados WHERE codigo = ? LIMIT 1');
if (!$stmt_invitado) {
    $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
    echo json_encode($response);
    exit();
}

$stmt_invitado->bind_param('s', $codigo_invitado);
$stmt_invitado->execute();
$result_invitado = $stmt_invitado->get_result();
$invitado = $result_invitado ? $result_invitado->fetch_assoc() : null;
$stmt_invitado->close();

if (!$invitado) {
    $response['message'] = 'Código de invitado no válido.';
    echo json_encode($response);
    exit();
}

$invitado_id = (int)$invitado['id'];

if ($confirmacion === 'Si' && count($seleccionados) === 0) {
    $response['message'] = 'Si confirmás asistencia, debés seleccionar al menos una persona.';
    echo json_encode($response);
    exit();
}

$conn->begin_transaction();

try {
    $cantidad_mayores = 0;
    $cantidad_menores = 0;
    $alimento_resumen = 'No';
    $contenido_resumen = '';

    if ($confirmacion === 'No') {
        $stmt_no = $conn->prepare('UPDATE invitados_listado_mesa SET asiste = 0, confirm_date = NOW(), alimento = "No", alimento_comentario = NULL WHERE id_invitados = ?');
        if (!$stmt_no) {
            throw new Exception('Error al preparar actualización de no asistencia: ' . $conn->error);
        }
        $stmt_no->bind_param('i', $invitado_id);
        $stmt_no->execute();
        $stmt_no->close();
    } else {
        $ids = array_values(array_filter(array_map('intval', $seleccionados), function ($id) {
            return $id > 0;
        }));

        if (count($ids) === 0) {
            throw new Exception('Si confirmás asistencia, debés seleccionar al menos una persona.');
        }

        $stmt_reset = $conn->prepare('UPDATE invitados_listado_mesa SET asiste = 0, confirm_date = NOW(), alimento = "No", alimento_comentario = NULL WHERE id_invitados = ?');
        if (!$stmt_reset) {
            throw new Exception('Error al preparar reseteo de asistencia: ' . $conn->error);
        }
        $stmt_reset->bind_param('i', $invitado_id);
        $stmt_reset->execute();
        $stmt_reset->close();

        $stmt_selected = $conn->prepare('UPDATE invitados_listado_mesa SET asiste = 1, confirm_date = NOW(), alimento = ?, alimento_comentario = ? WHERE id_invitados = ? AND id = ?');
        if (!$stmt_selected) {
            throw new Exception('Error al preparar actualización de seleccionados: ' . $conn->error);
        }

        foreach ($ids as $pid) {
            $alimento = isset($alimento_persona[$pid]) ? trim($alimento_persona[$pid]) : 'No';
            $comentario = isset($comentario_persona[$pid]) ? trim($comentario_persona[$pid]) : null;

            if ($alimento === '') {
                $alimento = 'No';
            }
            if ($alimento === 'No') {
                $comentario = null;
            }

            $stmt_selected->bind_param('ssii', $alimento, $comentario, $invitado_id, $pid);
            $stmt_selected->execute();
        }
        $stmt_selected->close();

        $stmt_totales = $conn->prepare('SELECT
                SUM(CASE WHEN asiste=1 AND es_menor=0 THEN 1 ELSE 0 END) AS cant_mayores,
                SUM(CASE WHEN asiste=1 AND es_menor=1 THEN 1 ELSE 0 END) AS cant_menores
            FROM invitados_listado_mesa
            WHERE id_invitados = ?');
        if (!$stmt_totales) {
            throw new Exception('Error al preparar cálculo de totales: ' . $conn->error);
        }
        $stmt_totales->bind_param('i', $invitado_id);
        $stmt_totales->execute();
        $totales = $stmt_totales->get_result()->fetch_assoc();
        $stmt_totales->close();

        $cantidad_mayores = isset($totales['cant_mayores']) ? (int)$totales['cant_mayores'] : 0;
        $cantidad_menores = isset($totales['cant_menores']) ? (int)$totales['cant_menores'] : 0;

        if (($cantidad_mayores + $cantidad_menores) === 0) {
            throw new Exception('No se pudo registrar asistentes para este código.');
        }

        $stmt_restricciones = $conn->prepare('SELECT nombre_invitado, alimento, alimento_comentario
            FROM invitados_listado_mesa
            WHERE id_invitados = ?
              AND asiste = 1
              AND alimento <> "No"');
        if ($stmt_restricciones) {
            $stmt_restricciones->bind_param('i', $invitado_id);
            $stmt_restricciones->execute();
            $result_restricciones = $stmt_restricciones->get_result();

            $detalles = [];
            while ($row = $result_restricciones->fetch_assoc()) {
                $item = $row['nombre_invitado'] . ': ' . $row['alimento'];
                if (!empty($row['alimento_comentario'])) {
                    $item .= ' (' . $row['alimento_comentario'] . ')';
                }
                $detalles[] = $item;
            }
            $stmt_restricciones->close();

            if (count($detalles) > 0) {
                $alimento_resumen = 'Ver detalle por invitado';
                $contenido_resumen = implode(' | ', $detalles);
            }
        }
    }

    $stmt_update_invitados = $conn->prepare('UPDATE invitados
            SET confirmacion = ?,
                confirmacion_mayores = ?,
                confirmacion_menores = ?,
                alimento = ?,
                confirmacion_comentario = ?,
                confirmacion_fecha = NOW()
            WHERE codigo = ?');
    if (!$stmt_update_invitados) {
        throw new Exception('Error al preparar actualización principal: ' . $conn->error);
    }

    $stmt_update_invitados->bind_param('siisss', $confirmacion, $cantidad_mayores, $cantidad_menores, $alimento_resumen, $contenido_resumen, $codigo_invitado);
    $stmt_update_invitados->execute();
    $stmt_update_invitados->close();

    $conn->commit();

    $response['success'] = true;
    if ($confirmacion === 'No') {
        $response['message'] = 'Lástima que no vas a poder asistir 😢 (Igual, podés cambiar de opinión más adelante)';
    } else {
        $response['message'] = '¡Tu asistencia ha sido confirmada con éxito! 🎉';
        $response['data'] = [
            'codigo' => $codigo_invitado,
            'mayores' => $cantidad_mayores,
            'menores' => $cantidad_menores,
            'confirmacion' => $confirmacion,
        ];
    }
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error al confirmar la asistencia: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>
