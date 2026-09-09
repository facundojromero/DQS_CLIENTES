<?php
include_once 'conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_invitado = isset($_POST['codigo_invitado']) ? $_POST['codigo_invitado'] : '';
    $confirmacion = isset($_POST['confirmar_asistencia']) ? $_POST['confirmar_asistencia'] : 'No';

    // Obtener los nuevos parámetros del formulario
    // Asegúrate de que los 'name' de tus campos en el formulario coincidan con estos $_POST
    $alimento = isset($_POST['alimento']) ? $_POST['alimento'] : 'No';
    $contenido = isset($_POST['contenido']) ? $_POST['contenido'] : ''; // Este es para la aclaración/comentario

    // Si la confirmación es "No", forzar 0 en mayores y menores
    // y también limpiar los campos de alimento/contenido si no aplican
    if ($confirmacion === 'No') {
        $cantidad_mayores = 0;
        $cantidad_menores = 0;
        $alimento = 'No'; // Si no asiste, el criterio alimenticio no aplica
        $contenido = '';   // Y la aclaración tampoco
    } else {
        $cantidad_mayores = isset($_POST['cantidad_mayores']) ? (int)$_POST['cantidad_mayores'] : 0;
        $cantidad_menores = isset($_POST['cantidad_menores']) ? (int)$_POST['cantidad_menores'] : 0;
        // Si asiste, los valores de $alimento y $contenido ya fueron capturados arriba
    }

    $response = ['success' => false, 'message' => ''];

    if (!empty($codigo_invitado)) {
        if ($conn) {
            // *** MODIFICACIÓN DE LA CONSULTA SQL ***
            // Añadimos 'alimento' y 'confirmacion_comentario' a la lista de campos a actualizar
            $stmt = $conn->prepare("UPDATE invitados SET confirmacion = ?, confirmacion_mayores = ?, confirmacion_menores = ?, alimento = ?, confirmacion_comentario = ?, confirmacion_fecha = NOW() WHERE codigo = ?");
            
            if ($stmt) {
                // *** MODIFICACIÓN DE bind_param ***
                // Los tipos de los parámetros deben coincidir con el orden de la consulta
                // 'siisss' significa:
                // s: string ($confirmacion)
                // i: integer ($cantidad_mayores)
                // i: integer ($cantidad_menores)
                // s: string ($alimento)
                // s: string ($contenido)
                // s: string ($codigo_invitado)
                $stmt->bind_param("siisss", $confirmacion, $cantidad_mayores, $cantidad_menores, $alimento, $contenido, $codigo_invitado);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    if ($confirmacion === 'No') {
                        $response['message'] = "<p class='msg_error'>Lástima que no vas a poder asistir 😢 (Igual, podés cambiar de opinión más adelante)</p>";
                    } else {
                        $response['message'] = "¡Tu asistencia ha sido confirmada con éxito! 🎉";
                        $response['data'] = [
                            'codigo' => $codigo_invitado,
                            'mayores' => $cantidad_mayores,
                            'menores' => $cantidad_menores,
                            'confirmacion' => $confirmacion,
                            'alimento' => $alimento,          // Añadir alimento a la respuesta
                            'contenido' => $contenido         // Añadir contenido a la respuesta
                        ];
                    }
                } else {
                    $response['message'] = 'Error al confirmar la asistencia: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
            }
            $conn->close();
        } else {
            $response['message'] = 'Error de conexión a la base de datos.';
        }
    } else {
        $response['message'] = 'Código de invitado no proporcionado.';
    }

    echo json_encode($response);
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
    exit();
}
?>