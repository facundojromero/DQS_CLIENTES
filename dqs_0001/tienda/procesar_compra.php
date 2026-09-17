<?php
error_reporting(E_ERROR);
include_once '../conexion.php';
include 'enviar_correo.php';
include 'enviar_correo_vendedor.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = session_id();

    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $codigo_area = $_POST['codigo_area'];
    $numero = $_POST['numero'];
    $telefono = $codigo_area . $numero;
    $forma_pago = $_POST['forma_pago'];
    $monto_total = $_POST['monto_total'];
    $productos = $_POST['productos'];
    $productos2 = $_POST['productos2'];
    $compartido_array = $_POST['compartido'];
    $compartido = implode(', ', $compartido_array);
    $mensaje = $_POST['mensaje'];
    $activo = 1;

    $sql_datos_bancarios = "SELECT a.cbu_titular, a.cbu, a.alias FROM cliente a INNER JOIN `user` b ON a.user_id = b.id INNER JOIN (SELECT nombre_carpeta FROM admin_config WHERE fecha_creacion = (SELECT MAX(fecha_creacion) FROM admin_config)) c LIMIT 1";
    $result_datos_bancarios = $conn->query($sql_datos_bancarios);
    if ($result_datos_bancarios && $result_datos_bancarios->num_rows > 0) {
        $datos = $result_datos_bancarios->fetch_assoc();
        $cbu_titular = $datos['cbu_titular'];
        $cbu = $datos['cbu'];
        $alias = $datos['alias'];
    } else {
        $cbu_titular = "Titular desconocido";
        $cbu = "CBU no disponible";
        $alias = "Alias no disponible";
    }

    $sql = "INSERT INTO regalos (nombre, apellido, email, telefono, forma_pago, monto_total, productos, compartido, mensaje, activo) VALUES ('$nombre', '$apellido', '$email', '$telefono', '$forma_pago', '$monto_total', '$productos', '$compartido', '$mensaje', '$activo')";

    if ($conn->query($sql) === TRUE) {
        $regalo_id = $conn->insert_id;

        $productos_array = json_decode($productos2, true);
        if (!empty($productos_array)) {
            foreach ($productos_array as $producto) {
                $producto_id = $producto['id'];
                $cantidad = $producto['cantidad'];
                $subtotal = $producto['precio'] * $cantidad;
                $sql_detalle = "INSERT INTO regalos_detalles (regalo_id, producto_id, cantidad, subtotal) VALUES ('$regalo_id', '$producto_id', '$cantidad', '$subtotal')";
                $conn->query($sql_detalle);
            }
        }

        $sql_delete_carrito = "DELETE FROM carrito WHERE session_id = '$session_id'";
        $conn->query($sql_delete_carrito);

        enviarCorreoConfirmacion($email, $nombre, $apellido, $monto_total, $productos, $forma_pago, $compartido, $mensaje, $cbu, $cbu_titular, $alias);
        enviarCorreoVendedor($nombre, $apellido, $monto_total, $productos, $forma_pago, $compartido, $mensaje, $telefono, $email, $regalo_id);

        $conn->close();

        // **AQUÍ CAMBIAMOS LA REDIRECCIÓN**
        // Codificamos el ID antes de enviarlo
        $id_codificado = base64_encode($regalo_id);
        header("Location: compra_exitosa.php?id=$id_codificado");
        exit();
    } else {
        $conn->close();
        echo "<div class='error-message'><p>Hubo un error al procesar tu compra. Por favor, intenta nuevamente.</p></div>";
    }
} else {
    header("Location: index.php");
    exit();
}
?>