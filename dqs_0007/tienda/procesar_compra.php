<?php
error_reporting(E_ERROR);
include_once '../conexion.php';
include_once 'regalo_libre_helper.php';
include 'enviar_correo.php';
include 'enviar_correo_vendedor.php';
session_start();

asegurarEstructuraRegaloLibre($conn);
$regaloLibreId = obtenerOCrearProductoRegaloLibre($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$session_id = session_id();

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$codigo_area = preg_replace('/\D/', '', $_POST['codigo_area'] ?? '');
$numero = preg_replace('/\D/', '', $_POST['numero'] ?? '');
$telefono = $codigo_area . $numero;
$forma_pago = trim($_POST['forma_pago'] ?? '');
$compartido_array = isset($_POST['compartido']) && is_array($_POST['compartido']) ? $_POST['compartido'] : array();
$compartido_limpio = array();
foreach ($compartido_array as $persona) {
    $persona = trim($persona);
    if ($persona !== '') {
        $compartido_limpio[] = $persona;
    }
}
$compartido = implode(', ', $compartido_limpio);
$mensaje = trim($_POST['mensaje'] ?? '');
$activo = 1;
$moneda = isset($_POST['currency']) ? (int)$_POST['currency'] : 2;
if ($moneda !== 1 && $moneda !== 2) {
    $moneda = 2;
}

if ($nombre === '' || $apellido === '' || $email === '' || $telefono === '' || $forma_pago === '') {
    echo "<div class='error-message'><p>Faltan datos obligatorios.</p></div>";
    exit();
}

$query_dolar = 'SELECT cotizacion_dolar FROM cliente WHERE user_id=1';
$result_dolar = $conn->query($query_dolar);
$cotizacion_dolar = 1;
if ($result_dolar && $result_dolar->num_rows > 0) {
    $row_dolar = $result_dolar->fetch_assoc();
    $cotizacion_dolar = (float)$row_dolar['cotizacion_dolar'];
}

$sqlCarrito = "SELECT p.id, p.titulo, p.precio, c.cantidad, c.monto_libre
              FROM carrito c
              INNER JOIN productos p ON p.id = c.producto_id
              WHERE c.session_id = ?";
$stmtCarrito = $conn->prepare($sqlCarrito);
$stmtCarrito->bind_param('s', $session_id);
$stmtCarrito->execute();
$resultCarrito = $stmtCarrito->get_result();

$monto_total = 0;
$productos = array();
$productos2 = array();

while ($row = $resultCarrito->fetch_assoc()) {
    $esRegaloLibre = ((int)$row['id'] === (int)$regaloLibreId) || $row['monto_libre'] !== null;
    $cantidad = $esRegaloLibre ? 1 : max(1, (int)$row['cantidad']);

    if ($esRegaloLibre) {
        $montoLibre = sanitizarMontoRegaloLibre($row['monto_libre']);
        if ($montoLibre === null) {
            echo "<div class='error-message'><p>El monto de Gift Card es inválido.</p></div>";
            $stmtCarrito->close();
            exit();
        }

        $precio = $montoLibre;
        if ($moneda === 1 && $cotizacion_dolar > 0) {
            $precio = $precio * $cotizacion_dolar;
        }
        $subtotal = $precio;
        $productos[] = 'Gift Card: ' . ($moneda === 2 ? 'u$s ' : '$ ') . number_format($subtotal, 0, '', '.');
    } else {
        $precio = (float)$row['precio'];
        if ($moneda === 1 && $cotizacion_dolar > 0) {
            $precio = $precio * $cotizacion_dolar;
        }
        $subtotal = $precio * $cantidad;
        $productos[] = $row['titulo'] . ' (Cantidad: ' . $cantidad . ', Subtotal: ' . ($moneda === 2 ? 'u$s ' : '$ ') . number_format($subtotal, 0, '', '.') . ')';
    }

    $monto_total += $subtotal;

    $productos2[] = array(
        'id' => (int)$row['id'],
        'cantidad' => $cantidad,
        'precio' => $precio,
        'subtotal' => $subtotal,
        'monto_libre' => $esRegaloLibre ? $precio : null
    );
}
$stmtCarrito->close();

if (empty($productos2)) {
    echo "<div class='error-message'><p>El carrito está vacío.</p></div>";
    exit();
}

$productos_str = implode(', ', $productos);

$cbu_titular = 'Titular desconocido';
$cbu_pesos = 'CBU no disponible';
$alias_pesos = 'Alias no disponible';
$cbu_dolar = 'CBU USD no disponible';
$alias_dolar = 'Alias USD no disponible';
$portada_titulo = 'Dije que Sí';

$sql_datos_bancarios = "SELECT d.portada_titulo, a.cbu_titular, a.cbu, a.alias, a.cbu_dolar, a.alias_dolar
FROM cliente a
INNER JOIN `user` b ON a.user_id = b.id
INNER JOIN (SELECT nombre_carpeta FROM admin_config WHERE fecha_creacion = (SELECT MAX(fecha_creacion) FROM admin_config)) c ON 1=1
INNER JOIN info_casamiento d
LIMIT 1";

$result_datos_bancarios = $conn->query($sql_datos_bancarios);
if ($result_datos_bancarios && $result_datos_bancarios->num_rows > 0) {
    $datos = $result_datos_bancarios->fetch_assoc();
    $portada_titulo = $datos['portada_titulo'] ?? 'Dije que Sí';
    $cbu_titular = $datos['cbu_titular'];
    $cbu_pesos = $datos['cbu'];
    $alias_pesos = $datos['alias'];
    $cbu_dolar = $datos['cbu_dolar'];
    $alias_dolar = $datos['alias_dolar'];
}

if ((int)$moneda === 2) {
    $cbu_a_mostrar = $cbu_dolar;
    $alias_a_mostrar = $alias_dolar;
    $simbolo_moneda = 'u$s';
    $tipo_cuenta_a_mostrar = 'Dólares USD';
} else {
    $cbu_a_mostrar = $cbu_pesos;
    $alias_a_mostrar = $alias_pesos;
    $simbolo_moneda = '$';
    $tipo_cuenta_a_mostrar = 'Pesos ARS';
}

$sqlRegalo = 'INSERT INTO regalos (nombre, apellido, email, telefono, forma_pago, monto_total, productos, compartido, mensaje, activo, pago_con) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
$stmtRegalo = $conn->prepare($sqlRegalo);
$stmtRegalo->bind_param('sssssdsssii', $nombre, $apellido, $email, $telefono, $forma_pago, $monto_total, $productos_str, $compartido, $mensaje, $activo, $moneda);

if (!$stmtRegalo->execute()) {
    $stmtRegalo->close();
    $conn->close();
    echo "<div class='error-message'><p>Hubo un error al procesar tu compra. Por favor, intenta nuevamente.</p></div>";
    exit();
}

$regalo_id = $conn->insert_id;
$stmtRegalo->close();

$sqlDetalle = 'INSERT INTO regalos_detalles (regalo_id, producto_id, cantidad, monto_libre, subtotal) VALUES (?, ?, ?, ?, ?)';
$stmtDetalle = $conn->prepare($sqlDetalle);
foreach ($productos2 as $producto) {
    $producto_id = (int)$producto['id'];
    $cantidad = (int)$producto['cantidad'];
    $subtotal = (float)$producto['subtotal'];
    $montoLibre = $producto['monto_libre'] !== null ? (float)$producto['monto_libre'] : null;
    $stmtDetalle->bind_param('iiidd', $regalo_id, $producto_id, $cantidad, $montoLibre, $subtotal);
    $stmtDetalle->execute();
}
$stmtDetalle->close();

$sql_delete_carrito = 'DELETE FROM carrito WHERE session_id = ?';
$stmtDelete = $conn->prepare($sql_delete_carrito);
$stmtDelete->bind_param('s', $session_id);
$stmtDelete->execute();
$stmtDelete->close();

if (strtolower(trim($forma_pago)) === 'transferencia') {
    enviarCorreoConfirmacion(
        $email, $nombre, $apellido, $monto_total, $productos_str, $forma_pago, $compartido, $mensaje,
        $cbu_a_mostrar, $cbu_titular, $alias_a_mostrar,
        $simbolo_moneda, $tipo_cuenta_a_mostrar,
        $portada_titulo
    );

    enviarCorreoVendedor(
        $nombre, $apellido, $monto_total, $productos_str, $forma_pago, $compartido, $mensaje, $telefono, $email, $regalo_id,
        $cbu_a_mostrar, $cbu_titular, $alias_a_mostrar,
        $simbolo_moneda, $tipo_cuenta_a_mostrar
    );
}

$conn->close();
$id_codificado = base64_encode($regalo_id);
header("Location: compra_exitosa.php?id=$id_codificado&currency=$moneda");
exit();
?>
