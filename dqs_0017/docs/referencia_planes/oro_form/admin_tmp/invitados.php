<?php
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Incluir el archivo de conexión
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo
// Depuración: Verificar si la conexión a la BD está cargada
if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
$mensaje = '';


//para borrar el invitado
if (isset($_POST['borrar'])) {
    $id = $_POST['id'];
    $sql = "UPDATE invitados SET activo = 2 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $mensaje = "El invitado ha sido borrado exitosamente.";

        // --- INICIO DE LA MODIFICACIÓN PARA REDIRECCIÓN CON FILTROS ---
        $redirect_url = "?new=invitados";

        // Añadir los filtros existentes a la URL de redirección
        if (isset($_GET['confirmacion']) && $_GET['confirmacion'] !== '') {
            $redirect_url .= "&confirmacion=" . urlencode($_GET['confirmacion']);
        }
        if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
            $redirect_url .= "&busqueda=" . urlencode($_GET['busqueda']);
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $redirect_url .= "&status=" . urlencode($_GET['status']);
        }
        if (isset($_GET['ingreso']) && $_GET['ingreso'] !== '') {
            $redirect_url .= "&ingreso=" . urlencode($_GET['ingreso']);
        }
        if (isset($_GET['prioridad']) && $_GET['prioridad'] !== '') {
            $redirect_url .= "&prioridad=" . urlencode($_GET['prioridad']);
        }
        // Añadir el nuevo filtro de invitación
        if (isset($_GET['invitacion']) && $_GET['invitacion'] !== '') {
            $redirect_url .= "&invitacion=" . urlencode($_GET['invitacion']);
        }
        if (isset($_GET['discrepancia']) && $_GET['discrepancia'] !== '') {
            $redirect_url .= "&discrepancia=" . urlencode($_GET['discrepancia']);
        }

        // Redirigir al usuario manteniendo los filtros
        header("Location: " . $redirect_url);
        exit();
        // --- FIN DE LA MODIFICACIÓN PARA REDIRECCIÓN CON FILTROS ---

    } else {
        $mensaje = "Error al marcar el invitado como borrado.";
    }
}





if (isset($_POST['confirmar'])) {
    $id = $_POST['id'];
    // Verificar el estado actual de la confirmación
    $sql = "SELECT confirmacion FROM invitados WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['confirmacion'] == 'Si') {
        // Desconfirmar
        $sql = "UPDATE invitados SET
                        confirmacion = NULL,
                        confirmacion_fecha = NULL,
                        confirmacion_comentario = NULL,
                        confirmacion_mayores = NULL,
                        confirmacion_menores = NULL,
                        alimento = NULL
                    WHERE id = ?";
        $mensaje = "La desconfirmación ha sido registrada exitosamente.";
    } else {
        // Confirmar
        $sql = "UPDATE invitados SET
                        confirmacion = 'Si',
                        confirmacion_fecha = NOW(),
                        confirmacion_comentario = '',
                        confirmacion_mayores = cantidad_mayores,
                        confirmacion_menores = cantidad_menores,
                        alimento = 'No'
                    WHERE id = ?";
        $mensaje = "La confirmación ha sido registrada exitosamente.";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $mensaje = $mensaje; // Aquí se asigna el mensaje de éxito o desconfirmación

        // --- INICIO DE LA MODIFICACIÓN PARA REDIRECCIÓN CON FILTROS ---
        $redirect_url = "?new=invitados";

        // Añadir los filtros existentes a la URL de redirección
        if (isset($_GET['confirmacion']) && $_GET['confirmacion'] !== '') {
            $redirect_url .= "&confirmacion=" . urlencode($_GET['confirmacion']);
        }
        if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
            $redirect_url .= "&busqueda=" . urlencode($_GET['busqueda']);
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $redirect_url .= "&status=" . urlencode($_GET['status']);
        }
        if (isset($_GET['ingreso']) && $_GET['ingreso'] !== '') {
            $redirect_url .= "&ingreso=" . urlencode($_GET['ingreso']);
        }
        if (isset($_GET['prioridad']) && $_GET['prioridad'] !== '') {
            $redirect_url .= "&prioridad=" . urlencode($_GET['prioridad']);
        }
        // Añadir el nuevo filtro de invitación
        if (isset($_GET['invitacion']) && $_GET['invitacion'] !== '') {
            $redirect_url .= "&invitacion=" . urlencode($_GET['invitacion']);
        }
        if (isset($_GET['discrepancia']) && $_GET['discrepancia'] !== '') {
            $redirect_url .= "&discrepancia=" . urlencode($_GET['discrepancia']);
        }

        // Redirigir al usuario manteniendo los filtros
        header("Location: " . $redirect_url);
        exit();
        // --- FIN DE LA MODIFICACIÓN PARA REDIRECCIÓN CON FILTROS ---

    } else {
        $mensaje = "Error al registrar la confirmación/desconfirmación.";
    }
}


// Mostrar mensaje de éxito si está presente en la URL
if (isset($_GET['activadisimo']) && $_GET['activadisimo'] == 1) {
    $mensaje = "Se ha actualizado correctamente.";
    echo "<script>
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'invitaciones/index.php?id_invitados=" . $_GET['idd'] . "', true);
        xhr.send();
    </script>";
}




// *** NUEVO BLOQUE PARA EJECUTAR EL SCRIPT EN SEGUNDO PLANO (OCULTO) ***
if (isset($_GET['open_card']) && $_GET['open_card'] == 1 && isset($_GET['id_invitados_to_open'])) {
    $id_invitado_a_ejecutar = htmlspecialchars($_GET['id_invitados_to_open']);
    echo "<script>
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'invitaciones/index.php?id_invitados=$id_invitado_a_ejecutar', true);
        xhr.send();
    </script>";
}
// *******************************************************************




// Manejar el cambio de estado (Esta sección ya la habíamos modificado)
if (isset($_POST['cambiar_estado'])) {
    $id = $_POST['id'];
    // Obtener el estado actual y los datos del invitado
    $sql = "SELECT activo, nombre, apellido FROM invitados WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $estado_actual = $row['activo'];
    $nombre = $row['nombre'];
    $apellido = $row['apellido'];

    // Cambiar el estado
    $nuevo_estado = $estado_actual ? 0 : 1;
    $sql = "UPDATE invitados SET activo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $nuevo_estado, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mensaje = "El estado del invitado ha sido cambiado exitosamente: $nombre $apellido.";

        // --- MODIFICACIÓN INICIA AQUÍ ---
        // Construir la URL de redirección con los filtros actuales
        $redirect_url = "?new=invitados";
        if (isset($_GET['confirmacion']) && $_GET['confirmacion'] !== '') {
            $redirect_url .= "&confirmacion=" . urlencode($_GET['confirmacion']);
        }
        if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
            $redirect_url .= "&busqueda=" . urlencode($_GET['busqueda']);
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $redirect_url .= "&status=" . urlencode($_GET['status']);
        }
        if (isset($_GET['ingreso']) && $_GET['ingreso'] !== '') {
            $redirect_url .= "&ingreso=" . urlencode($_GET['ingreso']);
        }
        if (isset($_GET['prioridad']) && $_GET['prioridad'] !== '') {
            $redirect_url .= "&prioridad=" . urlencode($_GET['prioridad']);
        }
        // Añadir el nuevo filtro de invitación
        if (isset($_GET['invitacion']) && $_GET['invitacion'] !== '') {
            $redirect_url .= "&invitacion=" . urlencode($_GET['invitacion']);
        }
        if (isset($_GET['discrepancia']) && $_GET['discrepancia'] !== '') {
            $redirect_url .= "&discrepancia=" . urlencode($_GET['discrepancia']);
        }

        // Redirigir al usuario
        header("Location: " . $redirect_url);
        exit();
        // --- MODIFICACIÓN TERMINA AQUÍ ---

    } else {
        $mensaje = "Error al cambiar el estado del invitado: $nombre $apellido.";
    }
}

// Obtener los filtros si existen
$confirmacionFiltro = isset($_GET['confirmacion']) ? $_GET['confirmacion'] : '';
$busquedaFiltro = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$statusFiltro = isset($_GET['status']) ? $_GET['status'] : '';
$ingresoFiltro = isset($_GET['ingreso']) ? $_GET['ingreso'] : '';
$prioridadFiltro = isset($_GET['prioridad']) ? $_GET['prioridad'] : '';
$invitacionFiltro = isset($_GET['invitacion']) ? $_GET['invitacion'] : ''; // Nuevo filtro
$discrepanciaFiltro = isset($_GET['discrepancia']) ? $_GET['discrepancia'] : ''; // Nuevo filtro


// Consulta para obtener los invitados con filtros
$sql = "SELECT
 a.id id_clientes,
  CASE 	WHEN a.id>999 THEN a.id
         WHEN a.id>99 THEN CONCAT('0',a.id)
         WHEN a.id>9 THEN CONCAT('00',a.id)
         ELSE CONCAT('000',a.id) END id_imagen,
 a.nombre,
 a.apellido,
 e.invitados,
 b.categoria_acompanante acompanado,
 a.cantidad_mayores,
 a.cantidad_menores,
 a.ingreso,
 f.id id_prioridad,
 f.categoria_prioridad,
 g.tel_enviar tel,
 a.alimento,
 a.fecha_registro,
 a.confirmacion,
 a.confirmacion_fecha,
 a.confirmacion_comentario,
 a.confirmacion_comentario2, -- CAMPO AGREGADO PARA EL MENSAJE
 a.confirmacion_mayores,
 a.confirmacion_menores,
 a.codigo,
 CASE WHEN h.tel_enviar IS NULL THEN 'No enviada' ELSE 'Si enviada' END invitacion,
 a.activo
FROM invitados a
LEFT JOIN intivados_acompanante b ON a.acompanado = b.id
LEFT JOIN (
 SELECT
 a.id_invitados,
 CASE WHEN cantidad_mayores+cantidad_menores<2 THEN nombre_invitado ELSE
 CONCAT(
 IF(COUNT(*) > 1,
 SUBSTRING_INDEX(
 GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '),
 ', ',
 COUNT(*) - 1
 ),
 GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
 ) ,
 ' y ',
 SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
 ) END AS invitados
 FROM invitados_listado_mesa a
 INNER JOIN invitados b
 ON a.id_invitados=b.id
 GROUP BY a.id_invitados
) e ON a.id = e.id_invitados
LEFT JOIN invitados_prioridad f ON a.id_prioridad = f.id
LEFT JOIN
 (
 SELECT
     id_invitados,
     GROUP_CONCAT(tel_enviar SEPARATOR ', ') AS tel_enviar
FROM
     invitados_tel a
GROUP BY
     id_invitados
 )
 g ON a.id = g.id_invitados
 LEFT JOIN
 (
 SELECT
     id_invitados,
     GROUP_CONCAT(tel_enviar SEPARATOR ', ') AS tel_enviar
 FROM
     invitados_enviados a
 GROUP BY
     id_invitados
     ) h
 ON a.id = h.id_invitados
WHERE 1=1
and a.activo < 2
"; // Esto permite agregar condiciones adicionales fácilmente
if ($statusFiltro !== '') {
 $sql .= " AND activo = '$statusFiltro'";
}

// --- LOGICA DE FILTROS ACTUALIZADA ---
if ($discrepanciaFiltro == 'Discrepancia') {
    $sql .= " AND a.confirmacion = 'Si' AND (a.cantidad_mayores + a.cantidad_menores) <> (a.confirmacion_mayores + a.confirmacion_menores)";
} elseif ($confirmacionFiltro !== '') {
    if ($confirmacionFiltro == 'NULL') {
        $sql .= " AND confirmacion IS NULL";
    } elseif ($confirmacionFiltro == 'ConMensaje') { // NUEVO FILTRO PARA MENSAJES
        $sql .= " AND a.confirmacion_comentario2 IS NOT NULL AND TRIM(a.confirmacion_comentario2) <> ''";
    } else {
        $sql .= " AND confirmacion = '$confirmacionFiltro'";
    }
}

if ($busquedaFiltro !== '') {
 $sql .= " AND (a.nombre LIKE '%$busquedaFiltro%' OR a.apellido LIKE '%$busquedaFiltro%')";
}
if ($ingresoFiltro !== '') {
 $sql .= " AND a.ingreso = '$ingresoFiltro'";
}
if ($prioridadFiltro !== '') {
 $sql .= " AND a.id_prioridad = '$prioridadFiltro'";
}
// Nuevo: Añadir filtro de invitación a la consulta
if ($invitacionFiltro !== '') {
    if ($invitacionFiltro == 'Si enviada') {
        $sql .= " AND h.tel_enviar IS NOT NULL";
    } elseif ($invitacionFiltro == 'No enviada') {
        $sql .= " AND h.tel_enviar IS NULL";
    }
}


$result = $conn->query($sql);

// Verificar si hay un ID en la URL para editar
$id = isset($_GET['id']) ? $_GET['id'] : null;
if ($id) {
    // Obtener los datos del invitado
    $sql = "SELECT * FROM invitados WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $invitado = $result->fetch_assoc();
}





?>

<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Bienvenido</title>
 <link rel="stylesheet" href="combined-styles.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> 
 <style>
    /* Estilos del Modal */
    .modal-personalizado { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
    .modal-contenido { background-color: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 400px; position: relative; }
    .cerrar-modal { position: absolute; right: 15px; top: 10px; font-size: 24px; cursor: pointer; color: #666; }
 </style>
</head>

<body>
 <h2>Lista de Invitados</h2>
 <div class="search-container">
 <div class="search-item">
 <label for="searchInput">Buscar invitados:</label>
 <input type="text" id="searchInput" class="search-input" placeholder="Buscar invitados..." value="<?php echo $busquedaFiltro; ?>">
 </div>
 <div class="search-item">
 <label for="confirmationFilter">Filtrar por confirmación:</label>
 <select id="confirmationFilter" class="search-input" onchange="applyFilter()">
 <option value="">Todos</option>
 <option value="Si" <?php if ($confirmacionFiltro == 'Si') echo 'selected'; ?>>Si</option>
 <option value="No" <?php if ($confirmacionFiltro == 'No') echo 'selected'; ?>>No</option>
 <option value="ConMensaje" <?php if ($confirmacionFiltro == 'ConMensaje') echo 'selected'; ?>>Con Mensaje</option> </select>
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
    <label for="invitacionFilter">Filtrar por invitación:</label>
    <select id="invitacionFilter" class="search-input" onchange="applyFilter()">
        <option value="">Todos</option>
        <option value="Si enviada" <?php if ($invitacionFiltro == 'Si enviada') echo 'selected'; ?>>Enviada</option>
        <option value="No enviada" <?php if ($invitacionFiltro == 'No enviada') echo 'selected'; ?>>No Enviada</option>
    </select>
</div>
 <div class="search-item">
 <button type="button" class="navbar-link" onclick="resetFilters()">
 <i class="fas fa-redo navbar-icon"></i> Resetear
 </button>
 </div>
 </div>


<p id="resultCount">Total de resultados: <?php echo $result->num_rows; ?></p>
<button onclick="window.location.href='?new=invitados&nuevo=0'" class="navbar-link">
   <i class="fas fa-user-plus navbar-icon"></i> Nuevo Invitado
</button>



        <?php if ($mensaje): ?>
            <div class="alert">
                <p><?php echo $mensaje; ?></p>
            </div>
        <?php endif; ?>

<?php
// --- Definición de función CIFRAR fuera del while ---
function cifrar($texto) {
    $clave = hash('sha256', 'Virgen.Itati');
    $iv = substr(hash('sha256', 'vector123'), 0, 16);
    $cifrado = openssl_encrypt($texto, 'AES-256-CBC', $clave, 0, $iv);
    return base64_encode($cifrado);
}


// Mostrar el contenido solo si no hay un ID y el parámetro 'nuevo' no es '0'
if (!$id && (!isset($_GET['nuevo']) || $_GET['nuevo'] != '0')): ?>
 <div class="grid-container" id="guestList">
 <?php
 if ($result->num_rows > 0) {
 while($row = $result->fetch_assoc()) {
 // Asignar 0 por defecto si los valores son NULL
 $confirmacion_mayores = $row['confirmacion_mayores'] ?? 0;
 $confirmacion_menores = $row['confirmacion_menores'] ?? 0;





// --- INICIO CÓDIGO WHATSAPP AÑADIDO ---
$telefonos = explode(',', $row['tel']);
$links = [];

// Detectar protocolo y host
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Obtener la URL base subiendo dos niveles de directorio desde el script actual
$base_url_dinamica = dirname(dirname($_SERVER['PHP_SELF']));

// Construir la URL completa de invitacion.php
$invitacion_url = $protocolo . $host . $base_url_dinamica . "/invitacion.php";

$nombre_img = "{$row['id_imagen']}.jpg";
$token = urlencode(cifrar($nombre_img));
$invitacion_url_final = $invitacion_url . "?k=" . $token;

$mensaje_wa = urlencode("Hola {$row['invitados']}, te estamos esperando. Tu codigo es {$row['codigo']}. \nMirá tu invitación: $invitacion_url_final");


// Armado de links WhatsApp
foreach ($telefonos as $tel) {
    $tel = trim($tel);
    $tel_sin_signo = preg_replace('/[^0-9]/', '', $tel);
    if ($tel_sin_signo !== '') {
        $links[] = "<a href='https://wa.me/54{$tel_sin_signo}?text={$mensaje_wa}' target='_blank'>{$tel}</a>";
    }
}

$tel_links = implode(' | ', $links);
// --- FIN CÓDIGO WHATSAPP AÑADIDO ---

 // Determinar el icono de confirmación
 $confirmacion_icon = '';
 if ($row['confirmacion'] == 'Si') {
 $confirmacion_icon = "<i class='fas fa-check'></i>";
 } elseif ($row['confirmacion'] == 'No') {
 $confirmacion_icon = "<i class='fas fa-times'></i>";
 } else {
 $confirmacion_icon = "<i class='fas fa-question'></i>";
 }
 // Determinar el estado actual
 $estado_actual = $row['activo'] == 1 ? 'Activo' : 'Inactivo';
 $inactive_class = $row['activo'] == 0 ? 'inactive' : '';
 echo "<div class='grid-item $inactive_class' id='guest-{$row['id_clientes']}'>
 <h2>{$row['nombre']} {$row['apellido']} {$confirmacion_icon}</h2>
 <div class='confirmacion'>
 <div class='confirmacion-header'>
 <div><i class='fas fa-user-check'></i></div>
 <div><i class='fas fa-child'></i></div>
 </div>
 <div class='confirmacion-body'>
 <div>Confirmación de Mayores</div>
 <div>Confirmación de Menores</div>
 </div>
 <div class='confirmacion-footer'>
 <div>{$confirmacion_mayores}</div>
 <div>{$confirmacion_menores}</div>
 </div>
 </div>



<div class='invitacion'>";
    // MODIFICACIÓN: Mostrar botón de mensaje solo si hay contenido en confirmacion_comentario2
    if (!empty(trim($row['confirmacion_comentario2']))) {
        echo "<a href='#' onclick=\"abrirModalMensaje('" . htmlspecialchars($row['confirmacion_comentario2'], ENT_QUOTES) . "', '{$row['nombre']}')\" class='invitacion-link'>
                <p><i class='fas fa-comment-dots'></i> Ver Mensaje</p>
              </a>";
    }
echo "</div>


 <p>{$confirmacion_icon} Confirmación: {$row['confirmacion']}</p>
 <p><i class='fas fa-users'></i> Invitados: {$row['invitados']}</p>
 <p><i class='fas fa-user-friends'></i> Acompañado: {$row['acompanado']}</p>

 <p><i class='fab fa-whatsapp'></i> WhatsApp: {$tel_links}</p>
  
  <p><i class='fas fa-barcode'></i> Codigo: {$row['codigo']}</p>


 <p><i class='fas fa-user'></i> Cantidad de Mayores: {$row['cantidad_mayores']}</p>
 <p><i class='fas fa-child'></i> Cantidad de Menores: {$row['cantidad_menores']}</p>
 <p><i class='fas fa-door-open'></i> Ingreso: {$row['ingreso']}</p>
 <p class='prioridad prioridad-{$row['id_prioridad']}'><i class='fas fa-exclamation-circle'></i> Prioridad: {$row['categoria_prioridad']}</p>
 <p><i class='fas fa-utensils'></i> Alimento: {$row['alimento']}</p>
 <p><i class='fas fa-utensils'></i> Comentario de alimento: {$row['confirmacion_comentario']}</p>
 <p><i class='fas fa-calendar-alt'></i> Fecha de Confirmación: {$row['confirmacion_fecha']}</p>
 <p>Estado: <span id='estado-{$row['id_clientes']}'>{$estado_actual}</span></p>


<div class='confirmacion-body' style='display: flex; flex-direction: column; align-items: center; gap: 10px;'>
     <div style='display: flex; justify-content: space-between; width: 100%;'>
         <form method='post' action='' style='flex: 1; margin-right: 5px;'>
             <input type='hidden' name='id' value='{$row['id_clientes']}'>
             <button type='submit' name='borrar' class='navbar-link' style='width: 100%; max-width: 150px;'>
                 <i class='fas fa-trash navbar-icon'></i> Borrar
             </button>
         </form>
         <form method='post' action='' style='flex: 1; margin-left: 5px;'>
             <input type='hidden' name='id' value='{$row['id_clientes']}'>
             <button type='submit' name='cambiar_estado' class='navbar-link' style='width: 100%; max-width: 150px;'>
<i class='fas " . ($row['activo'] == 1 ? "fa-toggle-on" : "fa-toggle-off") . " navbar-icon'></i>
" . ($row['activo'] == 1 ? 'Inactivar' : 'Activar') . "

             </button>
         </form>
     </div>
     <div style='display: flex; justify-content: space-between; width: 100%;'>
         <button class='navbar-link' onclick='editGuest({$row['id_clientes']})' style='flex: 1; margin-right: 5px; max-width: 150px;'>
             <i class='fas fa-edit navbar-icon'></i> Editar
         </button>
                     <form method='post' action='' style='flex: 1; margin-left: 5px;'>
                         <input type='hidden' name='id' value='{$row['id_clientes']}'>
                         <button type='submit' name='confirmar' class='navbar-link' style='width: 100%; max-width: 150px;'>
                            <i class='fas " . ($row['confirmacion'] == 'Si' ? "fa-times-circle" : "fa-check-circle") . " navbar-icon'></i>
" . ($row['confirmacion'] == 'Si' ? 'Desconfirmar' : 'Confirmar') . "

                         </button>
                     </form>
     </div>
</div>

 </div>";
 }
 } else {
 echo "<p>No hay invitados registrados.</p>";
 }
 ?>
 </div>
 <?php endif; ?>

 <?php if ($id): ?>
 <div class="edit-container">
 <?php include 'editar_invitado.php'; ?>
 </div>
 <?php endif; ?>


 <?php
// Verificar si el parámetro 'new' está presente en la URL y su valor es '0'
if (isset($_GET['nuevo']) && $_GET['nuevo'] == '0'): ?>
  <div class="edit-container">
    <?php include 'nuevo_invitado.php'; ?>
  </div>
<?php endif; ?>


 </div>


<div id="modalMensaje" class="modal-personalizado">
    <div class="modal-contenido">
        <span class="cerrar-modal" onclick="cerrarModalMensaje()">&times;</span>
        <h3 id="modalTitulo" style="margin-top:0;">Mensaje</h3>
        <p id="modalTexto" style="white-space: pre-wrap; margin-bottom:20px;"></p>
        <button class="navbar-link" style="width:100%" onclick="cerrarModalMensaje()">Cerrar</button>
    </div>
</div>


<script>
    // Funciones del Modal
    function abrirModalMensaje(texto, nombre) {
        document.getElementById('modalTexto').innerText = texto;
        document.getElementById('modalTitulo').innerText = "Mensaje de " + nombre;
        document.getElementById('modalMensaje').style.display = 'flex';
        return false;
    }
    function cerrarModalMensaje() {
        document.getElementById('modalMensaje').style.display = 'none';
    }

    // Función para aplicar filtros y actualizar la URL
    function applyFilter() {
        var confirmation = document.getElementById('confirmationFilter').value;
        var search = document.getElementById('searchInput').value;
        var status = document.getElementById('statusFilter').value;
        var ingreso = document.getElementById('ingresoFilter').value;
        var prioridad = document.getElementById('prioridadFilter').value;
        var invitacion = document.getElementById('invitacionFilter').value;

        var url = "?new=invitados&";
        
        if (confirmation) {
            if (confirmation === 'Discrepancia') {
                url += "discrepancia=Discrepancia&";
            } else if (confirmation === 'ConMensaje') {
                url += "confirmacion=ConMensaje&";
            } else {
                url += "confirmacion=" + confirmation + "&";
            }
        }
        
        if (search) url += "busqueda=" + search + "&";
        if (status) url += "status=" + status + "&";
        if (ingreso) url += "ingreso=" + ingreso + "&";
        if (prioridad) url += "prioridad=" + prioridad + "&";
        if (invitacion) url += "invitacion=" + invitacion + "&";

        if (url.endsWith("&")) {
            url = url.slice(0, -1);
        }

        window.location.href = url;
    }

    function resetFilters() {
        window.location.href = "?new=invitados";
    }

    document.getElementById('searchInput').addEventListener('keyup', function() {
        applyFilter();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const guestList = document.getElementById('guestList');
        if(guestList) {
            const guests = Array.from(guestList.getElementsByClassName('grid-item'));
            guests.sort((a, b) => {
                const nameA = a.querySelector('h2').textContent.toUpperCase();
                const nameB = b.querySelector('h2').textContent.toUpperCase();
                return nameA.localeCompare(nameB);
            });
            guests.forEach(guest => guestList.appendChild(guest));
        }
    });

    function editGuest(id) {
        var confirmation = document.getElementById('confirmationFilter').value;
        var search = document.getElementById('searchInput').value;
        var status = document.getElementById('statusFilter').value;
        var ingreso = document.getElementById('ingresoFilter').value;
        var prioridad = document.getElementById('prioridadFilter').value;
        var invitacion = document.getElementById('invitacionFilter').value;

        var url = "?new=invitados&id=" + id;
        
        if (confirmation) {
            if (confirmation === 'Discrepancia') {
                url += "&discrepancia=Discrepancia";
            } else if (confirmation === 'ConMensaje') {
                url += "&confirmacion=ConMensaje";
            } else {
                url += "&confirmacion=" + confirmation;
            }
        }
        
        if (search) url += "&busqueda=" + search;
        if (status) url += "&status=" + status;
        if (ingreso) url += "&ingreso=" + ingreso;
        if (prioridad) url += "&prioridad=" + prioridad;
        if (invitacion) url += "&invitacion=" + invitacion;

        window.location.href = url;
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.querySelector('.navbar-toggle');
        const navbarList = document.querySelector('.navbar-list');

        if(toggleButton) {
            toggleButton.addEventListener('click', function() {
                navbarList.classList.toggle('active');
            });
        }
    });
</script>

 </body>
 </html>

 <?php
 // Cerrar la conexión
 $conn->close();
 ?>