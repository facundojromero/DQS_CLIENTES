<?php
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Incluimos el archivo que contiene la función de envío de correo
require_once 'enviar_correo.php'; 
require_once '../tienda/regalo_libre_helper.php';

asegurarEstructuraRegaloLibre($conn);

// Conexión a la base de datos (asegúrate de que esta parte esté configurada correctamente)
// require_once 'db_connect.php'; // Esto es un ejemplo, no está en tu código original

// --- NUEVA CONSULTA AGREGADA ---
// Consulta para obtener la información del casamiento y datos bancarios.
$sql_datos_cliente = "SELECT d.portada_titulo, a.cbu_titular, a.cbu, a.alias, a.cbu_dolar, a.alias_dolar 
FROM cliente a 
INNER JOIN `user` b ON a.user_id = b.id 
INNER JOIN (SELECT nombre_carpeta FROM admin_config WHERE fecha_creacion = (SELECT MAX(fecha_creacion) FROM admin_config)) c ON 1=1
INNER JOIN info_casamiento d 
LIMIT 1";

$result_datos_cliente = $conn->query($sql_datos_cliente);
$datos_cliente = $result_datos_cliente->fetch_assoc();

// Guardamos portada_titulo en una variable separada si existe
$portada_titulo = isset($datos_cliente['portada_titulo']) ? $datos_cliente['portada_titulo'] : 'Dije que Sí'; 
// --- FIN NUEVA CONSULTA ---


// Verificar si se ha solicitado cancelar un regalo
if (isset($_POST['cancelar'])) {
    $id = $_POST['id'];
    $sql = "UPDATE regalos SET activo = 0 WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        echo "Regalo cancelado correctamente.";
    } else {
        echo "Error al cancelar el regalo: " . $conn->error;
    }
}

// Deshacer la confirmación de un pago
if (isset($_POST['deshacer_confirmacion'])) {
    $regalo_id = $_POST['id'];
    $sql = "DELETE FROM regalos_confirmacion WHERE regalo_id = $regalo_id";

    // Usamos $conn->query() para mantener la consistencia
    if ($conn->query($sql) === TRUE) {
        $_SESSION['mensaje'] = "Se ha deshecho la confirmación del regalo correctamente.";
    } else {
        $_SESSION['mensaje'] = "Error al deshacer la confirmación: " . $conn->error;
    }
    header("Location: ?new=ventas&view=yaConfirmados");
    exit();
}

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

// Confirmar pago - LÓGICA DE ENVÍO DE CORREO ACTUALIZADA
if (isset($_POST['confirmar_pago'])) {
    $regalo_id = $_POST['id'];

    // 2. Obtener los datos del regalo antes de confirmar
    $sql_regalo = "SELECT * FROM regalos WHERE id = $regalo_id";
    $result_regalo = $conn->query($sql_regalo);

    if ($result_regalo->num_rows > 0) {
        $regalo = $result_regalo->fetch_assoc();

        // Determinar símbolo, tipo de cuenta, CBU y Alias
        if ($regalo['pago_con'] == 1) { // Pesos
            $simbolo_moneda = '$';
            $tipo_cuenta = 'Pesos ARS"';
            $cbu_a_usar = $datos_cliente['cbu'];
            $alias_a_usar = $datos_cliente['alias'];
        } else { // Dólares
            $simbolo_moneda = 'u$s';
            $tipo_cuenta = 'Dólares USD';
            $cbu_a_usar = $datos_cliente['cbu_dolar'];
            $alias_a_usar = $datos_cliente['alias_dolar'];
        }

        // 3. Insertar la confirmación del pago
        $sql_insert = "INSERT INTO regalos_confirmacion (regalo_id) VALUES ($regalo_id)";

        if ($conn->query($sql_insert) === TRUE) {
            
            // 4. Si la confirmación fue exitosa, enviamos el correo
            $correo_enviado = enviarCorreoConfirmacion(
                $regalo['email'],
                $regalo['nombre'],
                $regalo['apellido'],
                $regalo['monto_total'],
                $regalo['productos'],
                $regalo['forma_pago'],
                $regalo['compartido'],
                $regalo['mensaje'],
                $cbu_a_usar, // CBU/CVU de la cuenta correcta
                $datos_cliente['cbu_titular'], // Titular
                $alias_a_usar, // Alias de la cuenta correcta
                $simbolo_moneda, 
                $tipo_cuenta,     
                $portada_titulo // ¡NUEVO PARÁMETRO!
            );

            if ($correo_enviado) {
                $_SESSION['mensaje'] = "Se ha registrado el regalo correctamente y el correo de confirmación fue enviado. ✅";
            } else {
                $_SESSION['mensaje'] = "Se ha registrado el regalo correctamente, pero **falló el envío del correo de confirmación**. ⚠️";
            }

        } else {
            $_SESSION['mensaje'] = "Error al actualizar la información: " . $conn->error;
        }

    } else {
        $_SESSION['mensaje'] = "Error: No se encontró el regalo para confirmar.";
    }

    header("Location: ?new=ventas&view=confirmarPago");
    exit();
}

function renderDetalleRegalo($conn, $regaloId) {
    $html = '';
    $sqlDetalle = "SELECT d.cantidad, d.subtotal, d.monto_libre, p.titulo
                  FROM regalos_detalles d
                  LEFT JOIN productos p ON p.id = d.producto_id
                  WHERE d.regalo_id = ?";
    $stmtDetalle = $conn->prepare($sqlDetalle);
    if (!$stmtDetalle) {
        return $html;
    }

    $stmtDetalle->bind_param('i', $regaloId);
    $stmtDetalle->execute();
    $resultDetalle = $stmtDetalle->get_result();

    if ($resultDetalle && $resultDetalle->num_rows > 0) {
        $html .= "<ul>";
        while ($detalle = $resultDetalle->fetch_assoc()) {
            $esRegaloLibre = $detalle['monto_libre'] !== null || esTituloRegaloLibre($detalle['titulo']);
            $nombreProducto = $esRegaloLibre ? REGALO_LIBRE_TITULO : $detalle['titulo'];
            $cantidad = $esRegaloLibre ? 1 : (int)$detalle['cantidad'];
            $montoLibre = $detalle['monto_libre'] !== null ? (float)$detalle['monto_libre'] : (float)$detalle['subtotal'];
            $montoTexto = number_format($montoLibre, 0, ',', '.');
            $subtotalTexto = number_format((float)$detalle['subtotal'], 0, ',', '.');

            if ($esRegaloLibre) {
                $html .= "<li><strong>" . REGALO_LIBRE_TITULO . "</strong> - Monto: $ " . $montoTexto . " - Subtotal: $ " . $subtotalTexto . "</li>";
            } else {
                $html .= "<li>" . htmlspecialchars($nombreProducto) . " - Cantidad: " . $cantidad . " - Subtotal: $ " . $subtotalTexto . "</li>";
            }
        }
        $html .= "</ul>";
    }

    $stmtDetalle->close();
    return $html;
}

// Consultar los regalos activos que no están en regalos_confirmacion
$sql = "SELECT a.* , CASE WHEN pago_con=1 THEN '$' WHEN pago_con=2 THEN 'u\$s' END pagon_con_forma
FROM regalos a
LEFT JOIN regalos_confirmacion b
ON a.id = b.regalo_id
WHERE b.regalo_id IS NULL AND a.activo = 1
ORDER BY a.reg_date DESC";
$result = $conn->query($sql);

// Consultar el total ganado agrupado por forma de pago
$sql_totales = "SELECT SUM(CASE WHEN pago_con=1 THEN monto_total END) AS total_ganado_pesos
, SUM(CASE WHEN pago_con=2 THEN monto_total END) AS total_ganado_dolares FROM regalos a
INNER JOIN regalos_confirmacion b
ON a.id = b.regalo_id
WHERE a.activo = 1";
$result_totales = $conn->query($sql_totales);

// Consultar los productos vendidos y cobrados
$sql_vendidos_cobrados = "SELECT a.*, b.confirm_date 
, CASE WHEN pago_con=1 THEN '$' WHEN pago_con=2 THEN 'u\$s' END pagon_con_forma 
FROM regalos a
INNER JOIN regalos_confirmacion b
ON a.id = b.regalo_id
WHERE a.activo = 1
ORDER BY b.confirm_date DESC";
$result_vendidos_cobrados = $conn->query($sql_vendidos_cobrados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regalos</title>
    <link rel="stylesheet" href="combined-styles.css"> </head>
<body>
    <h1>Regalos</h1>

    <?php if ($mensaje): ?>
        <div class="alert">
            <p><?php echo $mensaje; ?></p>
        </div>
    <?php endif; ?>

<div class='totales'>
    <p>
        <strong>Recaudado:</strong> $ <?php
            if ($result_totales->num_rows > 0) {
                 $result_totales->data_seek(0);
                 $row_totales = $result_totales->fetch_assoc();
                 echo number_format($row_totales['total_ganado_pesos'], 0, '', '.');
            } else {
                 echo "0";
            }
        ?>
        
        y u$s <?php
            if ($result_totales->num_rows > 0) {
                 $result_totales->data_seek(0);
                 $row_totales = $result_totales->fetch_assoc();
                 echo number_format($row_totales['total_ganado_dolares'], 0, '', '.');
            } else {
                 echo "0";
            }
        ?>        
        <span class="refresh-icon" onclick="window.location.reload();" style="cursor: pointer;" title="Haga clic para refrescar los datos">
            &#x21BB;
        </span>
    </p>
</div>

<?php
$view = isset($_GET['view']) ? $_GET['view'] : 'confirmarPago';

if ($view == 'confirmarPago') {
    echo '<h2>Confirmar pago</h2>';
    echo '<div class="grid-container">';
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<div class='grid-item' id='gift-{$row['id']}'>
                <h2>{$row['nombre']} {$row['apellido']}</h2>";

            // Condición para mostrar 'Compartido con:' solo si no está vacío
            if (!empty($row['compartido'])) {
                echo "<h5>Compartido con: {$row['compartido']}</h5>";
            } else {
                // Mantiene el espacio si está vacío
                echo "<h5>&nbsp;</h5>";
            }

            echo "<h5>Regalo N°: {$row['id']}</h5>
                <p>Email: {$row['email']}</p>
                <p>Teléfono: {$row['telefono']}</p>
                <p>Forma de Pago: {$row['forma_pago']}</p>
                <p>Monto Total: {$row['pagon_con_forma']} " . number_format($row['monto_total'], 0, ',', '.') . "</p>
                <p>Productos: {$row['productos']}</p>
                <p>Fecha: " . (new DateTime($row['reg_date']))->modify('-3 hours')->format('Y-m-d H:i:s') . "</p>
                <form method='post' action=''>
                    <input type='hidden' name='id' value='{$row['id']}'>
                    <button type='submit' name='cancelar' onclick='return handleCancel(event, {$row['id']});'>Borrar</button>
                    <button type='submit' name='confirmar_pago'>Confirmar Pago</button>
                </form>
            </div>";
        }
    } else {
        echo "<p>No hay más regalos que confirmar.</p>
        <p><a href='?new=ventas&view=yaConfirmados'>Ir a Recibidos</a></p>";
    }
    echo '</div>';
} elseif ($view == 'yaConfirmados') {
    echo '<h2>Ya confirmados</h2>';
    echo '<div class="grid-container">';
    if ($result_vendidos_cobrados->num_rows > 0) {
        while($row = $result_vendidos_cobrados->fetch_assoc()) {
            echo "<div class='grid-item' id='gift-{$row['id']}'>
                <h2>{$row['nombre']} {$row['apellido']}</h2>
                ";
            if (!empty($row['compartido'])) {
                echo "<h5>Compartido con: {$row['compartido']}</h5>";
            } else {
                echo "<h5>&nbsp;</h5>";
            }
            echo "<h5>Regalo N°: {$row['id']}</h5>
                <p>Email: {$row['email']}</p>
                <p>Teléfono: {$row['telefono']}</p>
                <p>Forma de Pago: {$row['forma_pago']}</p>
                <p>Monto Total: {$row['pagon_con_forma']} " . number_format($row['monto_total'], 0, ',', '.') . "</p>
                <p>Productos: {$row['productos']}</p>
                <p>Fecha: " . (new DateTime($row['reg_date']))->modify('-3 hours')->format('Y-m-d H:i:s') . "</p>
                <p>Fecha confirmación: " . (new DateTime($row['confirm_date']))->modify('-3 hours')->format('Y-m-d H:i:s') . "</p>
                <form method='post' action=''>
                    <input type='hidden' name='id' value='{$row['id']}'>
                    <button type='submit' name='deshacer_confirmacion' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro de que quieres deshacer la confirmación de este pago?\");' title='Deshacer confirmación'>
                        <i class='fa fa-undo' aria-hidden='true'></i>
                    </button>
                    <button type='submit' name='cancelar' class='btn btn-secondary btn-sm' onclick='return handleCancel(event, {$row['id']});' title='Borrar regalo'>
                        <i class='fa fa-trash' aria-hidden='true'></i>
                    </button>
                    <button type='button' class='btn btn-success btn-sm' onclick='event.preventDefault(); window.open(\"https://api.whatsapp.com/send?phone=54{$row['telefono']}\", \"_blank\")' title='Enviar mensaje por WhatsApp'>
                        <i class='fa fa-whatsapp' aria-hidden='true'></i> WhatsApp 📱
                    </button>
                </form>
            </div>";
        }
    } else {
        echo "<p>No hay registros de regalos.</p>";
    }
    echo '</div>';
}
?>

    <div class="notification" id="notification">
        <span id="notification-message"></span>
        <button onclick="undoAction()">Deshacer</button>
    </div>
    <script src="script.js"></script>
</body>
</html>
<?php
// Asegúrate de que $conn está disponible (conexión a la DB)
if (isset($conn)) {
    $conn->close();
}
?>