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
    unset($_SESSION['mensaje']); // Limpiar el mensaje de la sesión después de mostrarlo
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $acompanado = $_POST['acompanado'];
    $cantidad_mayores = $_POST['cantidad_mayores'];
    $cantidad_menores = $_POST['cantidad_menores'];
    $ingreso = $_POST['ingreso'];
    $id_prioridad = $_POST['id_prioridad'];
    $telefonos = isset($_POST['telefonos']) ? $_POST['telefonos'] : [];
    $nombre_invitado = $_POST['nombre_invitado'];

    // Nuevos arrays para nombres, apellidos y apodos de acompañantes
    $acompanante_nombres = isset($_POST['acompanante_nombre']) ? $_POST['acompanante_nombre'] : [];
    $acompanante_apellidos = isset($_POST['acompanante_apellido']) ? $_POST['acompanante_apellido'] : [];
    $acompanante_apodos = isset($_POST['acompanante_apodo']) ? $_POST['acompanante_apodo'] : [];

    // Validar que la cantidad de mayores + menores sea igual al número total de nombres
    $totalInvitados = $cantidad_mayores + $cantidad_menores;
    $totalNombres = 1; // Contar el nombre principal
    foreach ($acompanante_nombres as $nombre_comp) {
        if (!empty(trim($nombre_comp))) {
            $totalNombres++;
        }
    }

    if ($totalInvitados !== $totalNombres) {
        $_SESSION['mensaje'] = "La cantidad de mayores y menores debe ser igual al número total de nombres (incluyendo el nombre principal).";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Generar un código único
        function generarCodigoUnico($longitud = 6) {
            $caracteres = '0123456789';
            $codigo = '';
            for ($i = 0; $i < $longitud; $i++) {
                $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
            }
            return $codigo;
        }
        $codigo = generarCodigoUnico();
        $query_verificar = mysqli_query($conn, "SELECT COUNT(*) as total FROM invitados WHERE codigo = '$codigo'");
        $resultado_verificar = mysqli_fetch_assoc($query_verificar);
        while ($resultado_verificar['total'] > 0) {
            $codigo = generarCodigoUnico();
            $query_verificar = mysqli_query($conn, "SELECT COUNT(*) as total FROM invitados WHERE codigo = '$codigo'");
            $resultado_verificar = mysqli_fetch_assoc($query_verificar);
        }
        
        $query_insert = mysqli_query($conn, "INSERT INTO invitados (nombre, apellido, acompanado, cantidad_mayores, cantidad_menores, id_prioridad, ingreso, fecha_registro, codigo) VALUES ('$nombre', '$apellido', '$acompanado', '$cantidad_mayores', '$cantidad_menores', '$id_prioridad', '$ingreso', now(), '$codigo')");
        if ($query_insert) {
            $id_invitado = mysqli_insert_id($conn);
            $nombre_invitado_a_insertar = !empty(trim($nombre_invitado)) ? $nombre_invitado : $nombre;

            $query_insert_nombre = mysqli_query($conn, "INSERT INTO invitados_listado_mesa (id_invitados, nombre_invitado) VALUES ('$id_invitado', '$nombre_invitado_a_insertar')");

            foreach ($acompanante_nombres as $index => $nombre_acompanante) {
                if (!empty(trim($nombre_acompanante))) {
                    $apellido_acompanante = isset($acompanante_apellidos[$index]) ? $acompanante_apellidos[$index] : '';
                    $apodo_acompanante_a_insertar = isset($acompanante_apodos[$index]) && !empty(trim($acompanante_apodos[$index])) ? $acompanante_apodos[$index] : $nombre_acompanante;
                    
                    $query_insert_acompanante = mysqli_query($conn, "INSERT INTO invitados_listado_mesa (id_invitados, nombre_invitado, nombre2, apellido2) VALUES ('$id_invitado', '$apodo_acompanante_a_insertar', '$nombre_acompanante', '$apellido_acompanante')");
                }
            }
            
            foreach ($telefonos as $telefono) {
                if (!empty(trim($telefono))) {
                    $query_insert_tel = mysqli_query($conn, "INSERT INTO invitados_tel (id_invitados, tel_enviar) VALUES ('$id_invitado', '$telefono')");
                }
            }

            $_SESSION['mensaje'] = "Se ha registrado nuevo invitado.";
            $redirect_url = "?new=invitados&nuevo=0&open_card=1&id_invitados_to_open=" . $id_invitado;
            header("Location: " . $redirect_url);
            exit(); 
        } else {
            $_SESSION['mensaje'] = '<p class="msg_error">Error al guardar el invitado.</p>'; 
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Invitado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>

        
        .formulario {
            display: flex;
            flex-direction: column;
            /*gap: 20px;*/
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
            flex-wrap: wrap; /* Permite que los elementos se envuelvan en pantallas pequeñas */
        }
        
        /* Ajuste de los form-groups dentro de la fila */
        .acompanante-row .form-group {
            flex: 1 1 22%; /* Cada campo ocupa un 22% del espacio */
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
                gap: 5px; /* Reduce el espacio entre campos */
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
        <h1>Registrar Invitado</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert <?php echo (strpos($mensaje, 'Error') !== false) ? 'msg_error' : ''; ?>">
                <p><?php echo $mensaje; ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="formulario" onsubmit="return validarFormulario()">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required>
            </div>
            
            <div class="form-group">
                <label for="nombre_invitado">Apodo del invitado:</label>
                <input type="text" id="nombre_invitado" name="nombre_invitado" placeholder="Ej: Fede">
            </div>
            
            <div class="form-group">
                <label for="tel_enviar">Teléfono:</label>
                <input type="text" id="tel_enviar" name="telefonos[]" placeholder="Sin 0 y sin 15" maxlength="10" required>
            </div>
            
            <div class="form-group">
                <label for="acompanado">Acompañado:</label>
                <select id="acompanado" name="acompanado" required>
                    <?php foreach ($acompanante_opciones as $opcion): ?>
                        <option value="<?php echo $opcion['id']; ?>"><?php echo htmlspecialchars($opcion['categoria_acompanante']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="cantidad_mayores">Cantidad de Mayores:</label>
                <input type="number" id="cantidad_mayores" name="cantidad_mayores" value="0" required min="0">
            </div>
            <div class="form-group">
                <label for="cantidad_menores">Cantidad de Menores:</label>
                <input type="number" id="cantidad_menores" name="cantidad_menores" value="0" required min="0">
            </div>
            <div class="form-group">
                <label for="ingreso">Ingreso:</label>
                <select id="ingreso" name="ingreso" required>
                    <option value="Inicio">Inicio</option>
                    <option value="Tarde">Tarde</option>
                </select>
            </div>
            <div class="form-group">
                <label for="id_prioridad">Prioridad:</label>
                <select id="id_prioridad" name="id_prioridad" required>
                    <?php foreach ($prioridad_opciones as $opcion): ?>
                        <option value="<?php echo $opcion['id']; ?>"><?php echo htmlspecialchars($opcion['categoria_prioridad']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="acompanante-container">
                <h2>Acompañantes</h2>
                <div class="compartido-labels">
                    <p>Nombre</p>
                    <p>Apellido</p>
                    <p>Apodo</p>
                    <p>Teléfono</p>
                </div>
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
                        <input type="text" name="telefonos[]" placeholder="Sin 0 y sin 15" maxlength="10">
                    </div>
                </div>
                <button type="submit" id="addAcompanante" class="navbar-link">
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