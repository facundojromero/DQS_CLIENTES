<?php
include_once 'conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Recoger datos del formulario principal
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
    $confirmacion = isset($_POST['confirmar_asistencia']) ? $_POST['confirmar_asistencia'] : 'No';
    
    // Nuevo campo: Comentario general (si asiste o si no)
    $comentario2 = isset($_POST['confirmacion_comentario2']) ? trim($_POST['confirmacion_comentario2']) : '';
    
    // Valores por defecto
    $cantidad_mayores = 0;
    $cantidad_menores = 0;
    $alimento = 'No';
    $contenido = ''; // Detalle de dieta / Alergias
    
    // Array para guardar acompañantes (si existen)
    $acompanantes_data = [];

    // Si asiste, recogemos los valores específicos
    if ($confirmacion === 'Si') {
        $cantidad_mayores = isset($_POST['cantidad_mayores']) ? (int)$_POST['cantidad_mayores'] : 1;
        $cantidad_menores = isset($_POST['cantidad_menores']) ? (int)$_POST['cantidad_menores'] : 0;
        $alimento = isset($_POST['alimento']) ? $_POST['alimento'] : 'No';
        $contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';
        
        // Recogemos los acompañantes del array dinámico que generó el JS
        if (isset($_POST['acompanantes']) && is_array($_POST['acompanantes'])) {
            $acompanantes_data = $_POST['acompanantes'];
        }
    }

    if (empty($nombre) || empty($apellido)) {
        echo json_encode(['success' => false, 'message' => 'El nombre y apellido son obligatorios.']);
        exit();
    }

    // 2. Generar datos automáticos para la BD
    $codigo = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6)); 
    $fecha_registro = date('Y-m-d');
    $activo = 1;
    $acompanado = ($cantidad_mayores + $cantidad_menores) > 1 ? 1 : 0;
    $id_prioridad = 1;
    $ingreso = 'Inicio';

    if ($conn) {
        $conn->begin_transaction();

        try {
            // --- A) INSERTAR PRINCIPAL EN TABLA: invitados ---
            // Se agrega 'confirmacion_comentario2' al final del INSERT
            $stmt1 = $conn->prepare("INSERT INTO invitados 
                (nombre, apellido, activo, acompanado, cantidad_mayores, id_prioridad, ingreso, cantidad_menores, fecha_registro, 
                 confirmacion, confirmacion_fecha, confirmacion_comentario, confirmacion_mayores, confirmacion_menores, alimento, codigo, confirmacion_comentario2) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)");

            if (!$stmt1) {
                throw new Exception("Error preparando insert invitados: " . $conn->error);
            }

            /* IMPORTANTE: bind_param
               Hemos pasado de 15 a 16 parámetros. 
               La cadena de tipos ahora es "ssiiississsiisss" (agregamos una 's' al final para el comentario2).
            */
            $stmt1->bind_param("ssiiississsiisss", 
                $nombre, $apellido, $activo, $acompanado, $cantidad_mayores, $id_prioridad, $ingreso, $cantidad_menores, $fecha_registro,
                $confirmacion, $contenido, $cantidad_mayores, $cantidad_menores, $alimento, $codigo, $comentario2
            );

            if (!$stmt1->execute()) {
                throw new Exception("Error ejecutando insert invitados: " . $stmt1->error);
            }

            // Obtenemos el ID del invitado principal recién creado
            $id_invitados = $conn->insert_id;
            $stmt1->close();

            // --- B) INSERTAR PRINCIPAL EN TABLA: invitados_listado_mesa ---
            $stmt2 = $conn->prepare("INSERT INTO invitados_listado_mesa (id_invitados, nombre_invitado, nombre2, apellido2) VALUES (?, ?, ?, ?)");
            if (!$stmt2) {
                throw new Exception("Error preparando insert mesa principal: " . $conn->error);
            }
            $stmt2->bind_param("isss", $id_invitados, $nombre, $nombre, $apellido);
            $stmt2->execute();
            $stmt2->close();

            // --- C) INSERTAR ACOMPAÑANTES (LOOP) ---
            if (!empty($acompanantes_data)) {
                $stmt3 = $conn->prepare("INSERT INTO invitados_listado_mesa (id_invitados, nombre_invitado, nombre2, apellido2) VALUES (?, ?, ?, ?)");
                
                if (!$stmt3) {
                    throw new Exception("Error preparando insert acompañantes: " . $conn->error);
                }

                foreach ($acompanantes_data as $extra) {
                    $extra_nombre = trim($extra['nombre']);
                    $extra_apellido = trim($extra['apellido']);

                    if (!empty($extra_nombre) && !empty($extra_apellido)) {
                        $stmt3->bind_param("isss", $id_invitados, $extra_nombre, $extra_nombre, $extra_apellido);
                        $stmt3->execute();
                    }
                }
                $stmt3->close();
            }

            // Confirmar transacción
            $conn->commit();

            $msg = ($confirmacion === 'Si') ? "¡Muchas gracias $nombre! Tu asistencia ya fue confirmada." : "Gracias $nombre por avisar.";
            echo json_encode(['success' => true, 'message' => $msg]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error en el sistema: ' . $e->getMessage()]);
        }
        
        $conn->close();

    } else {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>