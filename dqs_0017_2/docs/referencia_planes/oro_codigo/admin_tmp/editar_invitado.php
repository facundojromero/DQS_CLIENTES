<?php
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Incluir el archivo de conexión
include_once '../conexion.php';

// Verificar si la conexión a la BD está cargada
if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Inicializar $mensaje desde la sesión si existe
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

$id_invitado_actual = $_GET['id'];

// --- CONSULTAS PARA CARGAR DATOS EN EL FORMULARIO (GET REQUEST) ---

// Obtener los datos del invitado principal
$sql = "SELECT a.*, c.categoria_acompanante, d.categoria_prioridad
        FROM invitados a
        INNER JOIN intivados_acompanante c ON a.acompanado = c.id
        INNER JOIN invitados_prioridad d ON a.id_prioridad = d.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_invitado_actual);
$stmt->execute();
$result = $stmt->get_result();
$invitado = $result->fetch_assoc();

// Obtener los nombres y apodos del invitado principal y sus acompañantes
$sql_nombres = "SELECT id, nombre_invitado, nombre2, apellido2 FROM invitados_listado_mesa WHERE id_invitados = ?";
$stmt_nombres = $conn->prepare($sql_nombres);
$stmt_nombres->bind_param("i", $id_invitado_actual);
$stmt_nombres->execute();
$result_nombres = $stmt_nombres->get_result();
$nombres_listado = [];
while ($row_nombre = $result_nombres->fetch_assoc()) {
    $nombres_listado[] = $row_nombre;
}

$nombre_invitado_principal = '';
$acompanantes_existentes = [];

if (!empty($nombres_listado)) {
    $nombre_invitado_principal = array_shift($nombres_listado)['nombre_invitado'];
    foreach ($nombres_listado as $acomp_row) {
        $acompanantes_existentes[] = [
            'nombre_invitado' => $acomp_row['nombre_invitado'], // Este es el apodo
            'nombre2' => $acomp_row['nombre2'],
            'apellido2' => $acomp_row['apellido2']
        ];
    }
}


// Obtener los teléfonos existentes
$sql_telefonos_db_load = "SELECT id, tel_enviar FROM invitados_tel WHERE id_invitados = ?";
$stmt_telefonos_db_load = $conn->prepare($sql_telefonos_db_load);
$stmt_telefonos_db_load->bind_param("i", $id_invitado_actual);
$stmt_telefonos_db_load->execute();
$result_telefonos_db_load = $stmt_telefonos_db_load->get_result();
$telefonos_invitado_existentes = [];
while ($row_tel = $result_telefonos_db_load->fetch_assoc()) {
    $telefonos_invitado_existentes[] = ['id' => $row_tel['id'], 'value' => $row_tel['tel_enviar']];
}

// Obtener las opciones de acompañante
$sql = "SELECT id, categoria_acompanante FROM intivados_acompanante";
$result = $conn->query($sql);
$acompanante_opciones = [];
while ($row = $result->fetch_assoc()) {
    $acompanante_opciones[] = $row;
}

// Obtener las opciones de prioridad
$sql = "SELECT id, categoria_prioridad FROM invitados_prioridad";
$result = $conn->query($sql);
$prioridad_opciones = [];
while ($row = $result->fetch_assoc()) {
    $prioridad_opciones[] = $row;
}

// --- PROCESAMIENTO DEL FORMULARIO (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recopilar datos generales del invitado
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $acompanado = $_POST['acompanado'];
    $cantidad_mayores = $_POST['cantidad_mayores'];
    $cantidad_menores = $_POST['cantidad_menores'];
    $ingreso = $_POST['ingreso'];
    $id_prioridad = $_POST['id_prioridad'];
    $nombre_invitado = $_POST['nombre_invitado'];
    $telefonos = isset($_POST['telefonos']) ? $_POST['telefonos'] : [];

    $acompanante_nombres = isset($_POST['acompanante_nombre']) ? $_POST['acompanante_nombre'] : [];
    $acompanante_apellidos = isset($_POST['acompanante_apellido']) ? $_POST['acompanante_apellido'] : [];
    $acompanante_apodos = isset($_POST['acompanante_apodo']) ? $_POST['acompanante_apodo'] : [];
    
    // Validar cantidades
    $totalInvitados = $cantidad_mayores + $cantidad_menores;
    $totalNombres = 1; // Contar el nombre principal
    foreach ($acompanante_nombres as $nombre_comp) {
        if (!empty(trim($nombre_comp))) {
            $totalNombres++;
        }
    }
    
    if ($totalInvitados !== $totalNombres) {
        $_SESSION['mensaje'] = "La cantidad de mayores y menores debe ser igual al número total de nombres (incluyendo el nombre principal y sus acompañantes).";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        // Actualizar la tabla 'invitados'
        $sql_update_invitado = "UPDATE invitados
                                SET nombre = ?, apellido = ?, acompanado = ?, cantidad_mayores = ?, cantidad_menores = ?, ingreso = ?, id_prioridad = ?
                                WHERE id = ?";
        $stmt_update_invitado = $conn->prepare($sql_update_invitado);
        $stmt_update_invitado->bind_param("ssiiisis", $nombre, $apellido, $acompanado, $cantidad_mayores, $cantidad_menores, $ingreso, $id_prioridad, $id_invitado_actual);
        $stmt_update_invitado->execute();

        // Eliminar nombres antiguos
        $sql_delete_nombres = "DELETE FROM invitados_listado_mesa WHERE id_invitados = ?";
        $stmt_delete_nombres = $conn->prepare($sql_delete_nombres);
        $stmt_delete_nombres->bind_param("i", $id_invitado_actual);
        $stmt_delete_nombres->execute();

        // Insertar el nombre principal
        $nombre_invitado_a_insertar = !empty(trim($nombre_invitado)) ? $nombre_invitado : $nombre;
        $sql_insert_nombre_principal = "INSERT INTO invitados_listado_mesa (id_invitados, nombre_invitado) VALUES (?, ?)";
        $stmt_insert_nombre_principal = $conn->prepare($sql_insert_nombre_principal);
        $stmt_insert_nombre_principal->bind_param("is", $id_invitado_actual, $nombre_invitado_a_insertar);
        $stmt_insert_nombre_principal->execute();

        // Insertar los nombres adicionales
        foreach ($acompanante_nombres as $index => $nombre_acompanante) {
            if (!empty(trim($nombre_acompanante))) {
                $apellido_acompanante = isset($acompanante_apellidos[$index]) ? $acompanante_apellidos[$index] : '';
                $apodo_acompanante_a_insertar = isset($acompanante_apodos[$index]) && !empty(trim($acompanante_apodos[$index])) ? $acompanante_apodos[$index] : $nombre_acompanante;
                
                $sql_insert_acompanante = "INSERT INTO invitados_listado_mesa (id_invitados, nombre_invitado, nombre2, apellido2) VALUES (?, ?, ?, ?)";
                $stmt_insert_acompanante = $conn->prepare($sql_insert_acompanante);
                $stmt_insert_acompanante->bind_param("isss", $id_invitado_actual, $apodo_acompanante_a_insertar, $nombre_acompanante, $apellido_acompanante);
                $stmt_insert_acompanante->execute();
            }
        }
        
        // Lógica para actualizar/insertar/eliminar en invitados_tel
        $sql_delete_tels = "DELETE FROM invitados_tel WHERE id_invitados = ?";
        $stmt_delete_tels = $conn->prepare($sql_delete_tels);
        $stmt_delete_tels->bind_param("i", $id_invitado_actual);
        $stmt_delete_tels->execute();

        foreach ($telefonos as $telefono) {
            if (!empty(trim($telefono))) {
                $sql_insert_tel = "INSERT INTO invitados_tel (id_invitados, tel_enviar) VALUES (?, ?)";
                $stmt_insert_tel = $conn->prepare($sql_insert_tel);
                $stmt_insert_tel->bind_param("is", $id_invitado_actual, $telefono);
                $stmt_insert_tel->execute();
            }
        }

        $_SESSION['mensaje'] = "Los datos del invitado han sido actualizados exitosamente.";
        $query_params = $_GET;
        $query_params['activadisimo'] = 1;
        $query_params['idd'] = $id_invitado_actual;
        $query_string = http_build_query($query_params);
        header("Location: ?new=invitados&$query_string");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Invitado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos CSS idénticos a nuevo_invitado.php */
        .formulario {
            display: flex;
            flex-direction: column;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .confirmacion-body {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .navbar-link {
            text-decoration: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        
        .msg_error {
            background-color: #f2dede;
            color: #a94442;
        }

        /* === ESTILOS PARA LA SECCIÓN DE ACOMPAÑANTES === */
        #acompanante-container {
            border: 1px dashed #ccc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        /* Contenedor de cada fila de acompañante */
        .acompanante-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        /* Ajuste de los form-groups dentro de la fila */
        .acompanante-row .form-group {
            flex: 1 1 22%;
            margin-bottom: 0;
        }
        
        /* Oculta las etiquetas de los campos individuales para ahorrar espacio */
        .acompanante-row .form-group label {
            display: none;
        }
        
        /* Contenedor de etiquetas generales */
        .compartido-labels {
            display: flex;
            gap: 10px;
        }
        
        /* Estilos de las etiquetas generales */
        .compartido-labels p {
            flex: 1 1 22%;
            text-align: left;
            font-weight: bold;
            color: #555;
            margin: 0;
        }

        /* === MEDIA QUERY PARA PANTALLAS MÁS PEQUEÑAS (ej. celulares) === */
        @media (max-width: 768px) {
            .container {
                max-width: 95%;
                padding: 15px;
            }
            
            /* Los campos se apilan verticalmente */
            .acompanante-row {
                flex-direction: column;
                gap: 5px;
            }
            
            /* Cada campo de la fila ocupa el 100% del ancho */
            .acompanante-row .form-group {
                flex: 1 1 100%;
                margin-bottom: 10px;
            }
            
            /* Las etiquetas se muestran para cada campo individual */
            .acompanante-row .form-group label {
                display: block;
            }
            
            /* Oculta las etiquetas generales */
            .compartido-labels {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Invitado</h1>
        

        <form method="post" action="" class="formulario" onsubmit="return validarFormulario()">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($invitado['nombre']); ?>" required>
            </div>
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($invitado['apellido']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="nombre_invitado">Apodo del invitado:</label>
                <input type="text" id="nombre_invitado" name="nombre_invitado" value="<?php echo htmlspecialchars($nombre_invitado_principal); ?>" placeholder="Ej: Fede y Caro">
            </div>
            
            <div class="form-group">
                <label for="tel_enviar">Teléfono:</label>
                <input type="text" id="tel_enviar" name="telefonos[]" value="<?php echo htmlspecialchars($telefonos_invitado_existentes[0]['value'] ?? ''); ?>" placeholder="Sin 0 y sin 15" maxlength="10">
            </div>
            
            <div class="form-group">
                <label for="acompanado">Acompañado:</label>
                <select id="acompanado" name="acompanado" required>
                    <?php foreach ($acompanante_opciones as $opcion): ?>
                        <option value="<?php echo htmlspecialchars($opcion['id']); ?>" <?php echo $opcion['id'] == $invitado['acompanado'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($opcion['categoria_acompanante']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="cantidad_mayores">Cantidad de Mayores:</label>
                <input type="number" id="cantidad_mayores" name="cantidad_mayores" value="<?php echo htmlspecialchars($invitado['cantidad_mayores']); ?>" required min="0">
            </div>
            <div class="form-group">
                <label for="cantidad_menores">Cantidad de Menores:</label>
                <input type="number" id="cantidad_menores" name="cantidad_menores" value="<?php echo htmlspecialchars($invitado['cantidad_menores']); ?>" required min="0">
            </div>
            <div class="form-group">
                <label for="ingreso">Ingreso:</label>
                <select id="ingreso" name="ingreso" required>
                    <option value="Inicio" <?php echo $invitado['ingreso'] == 'Inicio' ? 'selected' : ''; ?>>Inicio</option>
                    <option value="Tarde" <?php echo $invitado['ingreso'] == 'Tarde' ? 'selected' : ''; ?>>Tarde</option>
                </select>
            </div>
            <div class="form-group">
                <label for="id_prioridad">Prioridad:</label>
                <select id="id_prioridad" name="id_prioridad" required>
                    <?php foreach ($prioridad_opciones as $opcion): ?>
                        <option value="<?php echo htmlspecialchars($opcion['id']); ?>" <?php echo $opcion['id'] == $invitado['id_prioridad'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($opcion['categoria_prioridad']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="acompanante-container">
                <h2>Acompañantes</h2>
                <div class="compartido-labels">
                    <p>Nombre</p>
                    <p>Apellido</p>
                    <p>Apodo (opcional)</p>
                    <p>Teléfono (opcional)</p>
                </div>

                <?php
                $num_acompanantes = count($acompanantes_existentes);
                $num_telefonos_existentes = count($telefonos_invitado_existentes);

                // Empezar desde el segundo teléfono (el primero es para el invitado principal)
                $telefono_index = 1;

                // Generar los campos para los acompañantes existentes
                foreach ($acompanantes_existentes as $acompanante):
                ?>
                    <div class="acompanante-row">
                        <div class="form-group">
                            <label>Nombre:</label>
                            <input type="text" name="acompanante_nombre[]" value="<?php echo htmlspecialchars($acompanante['nombre2']); ?>" placeholder="Nombre">
                        </div>
                        <div class="form-group">
                            <label>Apellido:</label>
                            <input type="text" name="acompanante_apellido[]" value="<?php echo htmlspecialchars($acompanante['apellido2']); ?>" placeholder="Apellido">
                        </div>
                        <div class="form-group">
                            <label>Apodo:</label>
                            <input type="text" name="acompanante_apodo[]" value="<?php echo htmlspecialchars($acompanante['nombre_invitado']); ?>" placeholder="Apodo">
                        </div>
                        <div class="form-group">
                            <label>Teléfono:</label>
                            <input type="text" name="telefonos[]" value="<?php echo htmlspecialchars($telefonos_invitado_existentes[$telefono_index]['value'] ?? ''); ?>" placeholder="Sin 0 y sin 15" maxlength="10">
                        </div>
                    </div>
                <?php
                    $telefono_index++;
                endforeach;

                // Si hay teléfonos sin acompañantes asociados, los mostramos
                for ($i = $telefono_index; $i < $num_telefonos_existentes; $i++):
                ?>
                    <div class="acompanante-row">
                        <div class="form-group">
                            <label>Nombre:</label>
                            <input type="text" name="acompanante_nombre[]" placeholder="Nombre">
                        </div>
                        <div class="form-group">
                            <label>Apellido:</label>
                            <input type="text" name="acompanante_apellido[]" placeholder="Apellido">
                        </div>
                        <div class="form-group">
                            <label>Apodo:</label>
                            <input type="text" name="acompanante_apodo[]" placeholder="Apodo (opcional)">
                        </div>
                        <div class="form-group">
                            <label>Teléfono:</label>
                            <input type="text" name="telefonos[]" value="<?php echo htmlspecialchars($telefonos_invitado_existentes[$i]['value'] ?? ''); ?>" placeholder="Sin 0 y sin 15" maxlength="10">
                        </div>
                    </div>
                <?php endfor; ?>
                
                <button type="button" id="addAcompanante" class="navbar-link">
                    <i class="fas fa-plus"></i> Agregar Acompañante
                </button>
            </div>

            <div class='confirmacion-body'>
                <div>
                    <button type="submit" class="navbar-link">
                        <i class="fas fa-save navbar-icon"></i> Guardar
                    </button>
                </div>
                <div>
                    <button type="button" class="navbar-link" onclick="window.history.back();">
                        <i class="fas fa-times navbar-icon"></i> Cancelar
                    </button>
                </div>
            </div>
        </form>
    </div>
    <script>
        document.getElementById('addAcompanante').addEventListener('click', function() {
            var container = document.getElementById('acompanante-container');
            var div = document.createElement('div');
            div.className = 'acompanante-row';

            var nameGroup = document.createElement('div');
            nameGroup.className = 'form-group';
            nameGroup.innerHTML = '<label>Nombre:</label><input type="text" name="acompanante_nombre[]" placeholder="Nombre">';
            div.appendChild(nameGroup);

            var apellidoGroup = document.createElement('div');
            apellidoGroup.className = 'form-group';
            apellidoGroup.innerHTML = '<label>Apellido:</label><input type="text" name="acompanante_apellido[]" placeholder="Apellido">';
            div.appendChild(apellidoGroup);
            
            var apodoGroup = document.createElement('div');
            apodoGroup.className = 'form-group';
            apodoGroup.innerHTML = '<label>Apodo:</label><input type="text" name="acompanante_apodo[]" placeholder="Apodo (opcional)">';
            div.appendChild(apodoGroup);

            var phoneGroup = document.createElement('div');
            phoneGroup.className = 'form-group';
            phoneGroup.innerHTML = '<label>Teléfono:</label><input type="text" name="telefonos[]" placeholder="Sin 0 y sin 15" maxlength="10">';
            div.appendChild(phoneGroup);
            
            container.insertBefore(div, this);
        });

        function validarFormulario() {
            var cantidadMayores = parseInt(document.getElementById('cantidad_mayores').value) || 0;
            var cantidadMenores = parseInt(document.getElementById('cantidad_menores').value) || 0;
            var totalInvitados = cantidadMayores + cantidadMenores;
            
            var acompananteNombres = document.querySelectorAll('#acompanante-container input[name="acompanante_nombre[]"]');

            var totalNombres = 1; // Contar el nombre principal
            acompananteNombres.forEach(function(input) {
                if (input.value.trim()) {
                    totalNombres++;
                }
            });

            if (totalInvitados !== totalNombres) {
                alert('La cantidad de mayores y menores debe ser igual al número total de nombres (incluyendo el nombre principal y sus acompañantes).');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>