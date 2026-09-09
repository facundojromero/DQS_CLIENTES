<?php
error_reporting(E_ERROR);
include_once '../conexion.php';
include 'enviar_correo.php';
include 'enviar_correo_vendedor.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = session_id();

    // -----------------------------------------------------
    // 1. CAPTURA DE VARIABLES
    // -----------------------------------------------------
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

    // Captura el valor de la moneda
    $moneda = $_POST['currency'] ?? 1; // 1 por defecto si no se encuentra

    
    // -----------------------------------------------------
    // 2. LÓGICA DE DATOS BANCARIOS (PESOS Y DÓLARES) y PORTADA
    // -----------------------------------------------------

    // Inicialización de variables de CBU/Alias y Portada
    $cbu_titular = "Titular desconocido";
    $cbu_pesos = "CBU no disponible";
    $alias_pesos = "Alias no disponible";
    $cbu_dolar = "CBU USD no disponible";
    $alias_dolar = "Alias USD no disponible";
    $portada_titulo = "Dije que Sí"; // Valor por defecto

    // MODIFICAMOS LA CONSULTA PARA INCLUIR portada_titulo
    $sql_datos_bancarios = "SELECT d.portada_titulo, a.cbu_titular, a.cbu, a.alias, a.cbu_dolar, a.alias_dolar 
    FROM cliente a 
    INNER JOIN `user` b ON a.user_id = b.id 
    INNER JOIN (SELECT nombre_carpeta FROM admin_config WHERE fecha_creacion = (SELECT MAX(fecha_creacion) FROM admin_config)) c ON 1=1
    INNER JOIN info_casamiento d
    LIMIT 1";

    $result_datos_bancarios = $conn->query($sql_datos_bancarios);
    
    if ($result_datos_bancarios && $result_datos_bancarios->num_rows > 0) {
        $datos = $result_datos_bancarios->fetch_assoc();
        
        // Asignamos la nueva variable
        $portada_titulo = $datos['portada_titulo'] ?? 'Dije que Sí'; 

        $cbu_titular = $datos['cbu_titular'];
        $cbu_pesos = $datos['cbu'];      // CBU Pesos (ARS)
        $alias_pesos = $datos['alias'];  // Alias Pesos (ARS)
        $cbu_dolar = $datos['cbu_dolar']; // CBU Dólares (USD)
        $alias_dolar = $datos['alias_dolar']; // Alias Dólares (USD)
    }

    // Lógica para seleccionar el CBU/Alias correcto a mostrar/enviar por email
    $cbu_a_mostrar = "";
    $alias_a_mostrar = "";
    $simbolo_moneda = "";
    $tipo_cuenta_a_mostrar = "";

    if ((int)$moneda === 2) { // Dólares
        $cbu_a_mostrar = $cbu_dolar;
        $alias_a_mostrar = $alias_dolar;
        $simbolo_moneda = "u\$s";
        $tipo_cuenta_a_mostrar = "Dólares USD";
    } else { // Pesos (moneda 1 o cualquier otra cosa por defecto)
        $cbu_a_mostrar = $cbu_pesos;
        $alias_a_mostrar = $alias_pesos;
        $simbolo_moneda = "$";
        $tipo_cuenta_a_mostrar = "Pesos ARS";
    }

    
    // -----------------------------------------------------
    // 3. INSERCIÓN EN BASE DE DATOS
    // -----------------------------------------------------
    
    // El INSERT sigue siendo el mismo, incluyendo 'pago_con'
    $sql = "INSERT INTO regalos (nombre, apellido, email, telefono, forma_pago, monto_total, productos, compartido, mensaje, activo, pago_con) VALUES ('$nombre', '$apellido', '$email', '$telefono', '$forma_pago', '$monto_total', '$productos', '$compartido', '$mensaje', '$activo', '$moneda')";

    if ($conn->query($sql) === TRUE) {
        $regalo_id = $conn->insert_id;

        // Inserción de detalles (sin cambios)
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

        // Borrado de carrito (sin cambios)
        $sql_delete_carrito = "DELETE FROM carrito WHERE session_id = '$session_id'";
        $conn->query($sql_delete_carrito);

        
        // -----------------------------------------------------
        // 4. ENVÍO DE CORREOS
        // -----------------------------------------------------
        if (strtolower(trim($forma_pago)) === 'transferencia') {
            
            // Correo al Cliente (PARAMETRO portada_titulo AÑADIDO)
            enviarCorreoConfirmacion(
                $email, $nombre, $apellido, $monto_total, $productos, $forma_pago, $compartido, $mensaje, 
                $cbu_a_mostrar, $cbu_titular, $alias_a_mostrar, 
                $simbolo_moneda, $tipo_cuenta_a_mostrar,
                $portada_titulo // ¡NUEVO PARÁMETRO!
            );
            
            // Correo al Vendedor (PARAMETRO portada_titulo AÑADIDO)
            enviarCorreoVendedor(
                $nombre, $apellido, $monto_total, $productos, $forma_pago, $compartido, $mensaje, $telefono, $email, $regalo_id, 
                $cbu_a_mostrar, $cbu_titular, $alias_a_mostrar, 
                $simbolo_moneda, $tipo_cuenta_a_mostrar,
                $portada_titulo // ¡NUEVO PARÁMETRO!
            );
        }

        $conn->close();

        // **REDIRECCIÓN FINAL**
        // Codificamos el ID y agregamos el valor de la moneda en la URL
        $id_codificado = base64_encode($regalo_id);
        header("Location: compra_exitosa.php?id=$id_codificado&currency=$moneda");
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