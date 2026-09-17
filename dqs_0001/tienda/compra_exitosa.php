<?php
error_reporting(E_ERROR);
include_once '../conexion.php'; 

// Obtener el ID de la URL y decodificarlo
$id_codificado = $_GET['id'] ?? null;
if (!$id_codificado) {
    header("Location: index.php");
    exit();
}

// Decodificamos el ID para obtener el número original
$regalo_id = base64_decode($id_codificado);

// **Validación extra**: Asegurarse de que el ID decodificado sea un número
if (!is_numeric($regalo_id)) {
    header("Location: index.php");
    exit();
}

// Continuar con la consulta a la base de datos usando el ID decodificado
$sql = "SELECT nombre, apellido, email, forma_pago, monto_total, productos, compartido, mensaje FROM regalos WHERE id = '$regalo_id' AND activo = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $compra = $result->fetch_assoc();
    $nombre = $compra['nombre'];
    $apellido = $compra['apellido'];
    $email = $compra['email'];
    $forma_pago = $compra['forma_pago'];
    $monto_total = $compra['monto_total'];
    $productos_str = $compra['productos'];
    $compartido = $compra['compartido'];
    $mensaje = $compra['mensaje'];
} else {
    header("Location: index.php");
    exit();
}

// Obtener datos bancarios del vendedor (copiado de tu código original)
$sql_datos_bancarios = "
    SELECT 
        a.cbu_titular,
        a.cbu,
        a.alias
    FROM cliente a
    INNER JOIN `user` b ON a.user_id = b.id 
    INNER JOIN (
        SELECT nombre_carpeta 
        FROM admin_config 
        WHERE fecha_creacion = (SELECT MAX(fecha_creacion) FROM admin_config)
    ) c
    LIMIT 1
";
$result_datos_bancarios = $conn->query($sql_datos_bancarios);
$cbu_titular = "Titular desconocido";
$cbu = "CBU no disponible";
$alias = "Alias no disponible";
if ($result_datos_bancarios && $result_datos_bancarios->num_rows > 0) {
    $datos = $result_datos_bancarios->fetch_assoc();
    $cbu_titular = $datos['cbu_titular'];
    $cbu = $datos['cbu'];
    $alias = $datos['alias'];
}

// Obtener info del casamiento para el header/footer
$query = "SELECT portada_titulo, portada_frase, portada_fecha, portada_fecha_hora FROM info_casamiento";
$result_info = mysqli_query($conn, $query);
if ($result_info && mysqli_num_rows($result_info) > 0) {
    $row = mysqli_fetch_assoc($result_info);
    $portada_titulo = $row['portada_titulo'];
    $portada_frase = $row['portada_frase'];
    $portada_fecha = $row['portada_fecha'];
    $portada_fecha_hora = $row['portada_fecha_hora'];
} else {
    $portada_titulo = "#Fulano & #Mengano";
    $portada_frase = "Nos casamos";
    $portada_fecha = "8 de Diciembre 2040";
    $portada_fecha_hora = "2040-12-08 00:00:00";
}

$query_eventos = "SELECT * FROM info_eventos WHERE activo=1";
$result_eventos = mysqli_query($conn, $query_eventos);
$eventos = [];
if ($result_eventos && mysqli_num_rows($result_eventos) > 0) {
    while ($row_evento = mysqli_fetch_assoc($result_eventos)) {
        $eventos[] = $row_evento;
    }
}

$datetime = new DateTime($portada_fecha_hora);
$year = $datetime->format('Y');
$month = $datetime->format('m');
$day = $datetime->format('d');
$hours = $datetime->format('H');
$minutes = $datetime->format('i');
$seconds = $datetime->format('s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Regalo Confirmado</title>
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="../images/apple-touch-icon.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/pogo-slider.min.css">
    
    <?php
    $styleFile = '../current_style.txt';
    $currentStyle = '../css/style.css'; 
    if (file_exists($styleFile)) {
        $content = file_get_contents($styleFile);
        if ($content !== false) {
            $currentStyle = trim($content);
        }
    }
    ?>
    <link rel="stylesheet" href="../css/<?php echo htmlspecialchars($currentStyle); ?>">   
    
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/custom.css">
    <link rel="stylesheet" href="../css/icomoon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Regalos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body id="tienda" data-spy="scroll" data-target="#navbar-wd" data-offset="98">

    <?php require '../header.php'; ?>
    
    <div id="cronometro" class="cronometro-box <?php echo in_array('cronometro', $secciones) ? 'activo' : ''; ?>">
        <div class="about-a1">
            <div class="container">
                <div class="row">
                    <div class="lbox-caption2">
                        <div class="lbox-details2">
                            <a href="<?= $es_tienda ? '../#rsvp' : 'rsvp.php' ?>" class="btn">RSVP</a>
                            <a href="<?= $es_tienda ? './' : 'tienda/' ?>" class="btn">Regalar</a>
                            <?php if (in_array('cronometro', $secciones)): ?>                                
                                <p><div class="simply-countdown simply-countdown-one"></div></p>
                            <?php endif; ?>                                                             
                        </div>
                    </div>
                </div>                    
            </div>
        </div>
    </div>
    
    <div class="pagination">
        <a href="index.php" id="homeLink" class="ver-carrito"><i class="fas fa-home"></i></a>
    </div>

    <div class="checkout-container">
        <div class='confirmation-message' style='max-width: 700px; padding: 30px;'>
            <h2 style='text-align: center; margin-bottom: 25px;'><i class='fas fa-check-circle'></i> Regalo confirmado</h2>
            <p style='text-align: center; font-size: 18px; margin-bottom: 30px;'><strong>Gracias, <?php echo $nombre . " " . $apellido; ?></strong><br>Tu regalo fue procesado exitosamente.</p>
            <?php if (!empty($compartido)) : ?>
                <p><strong><i class='fas fa-users'></i> Compartido con: </strong><?php echo $compartido; ?></p>
            <?php endif; ?>
            <div style='margin-bottom: 25px;'>
                <p><strong><i class='fas fa-credit-card'></i> Forma de Pago:</strong> <?php echo $forma_pago; ?></p>
                <p><strong><i class='fas fa-dollar-sign'></i> Monto Total:</strong> <?php echo number_format($monto_total, 2, ',', '.'); ?></p>
                <p><strong><i class='fas fa-box'></i> Productos:</strong><br><?php echo $productos_str; ?></p>
            </div>
            <div style='margin-bottom: 25px;'>
                <h4><i class='fas fa-university'></i> Datos bancarios</h4>
                <p><strong>Titular:</strong> <?php echo $cbu_titular; ?></p>
                <p><strong>Alias:</strong> <?php echo $alias; ?></p>
                <p><strong>CBU:</strong> <?php echo $cbu; ?></p>
            </div>
            <div style='margin-bottom: 25px;'>
                <p><strong><i class='fas fa-envelope'></i> Confirmación enviada a:</strong><br><?php echo $email; ?></p>
            </div>
        </div>
        <div>
            <a href="./" class="button"><i class="fas fa-shopping-cart"></i> Seguir Regalando</a>
        </div>
    </div>
    
    <?php require '../footer.php'; ?>
    
    <script src="../js/jquery.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/jquery.magnific-popup.min.js"></script>
    <script src="../js/jquery.pogo-slider.min.js"></script>
    <script src="../js/slider-index.js"></script>
    <script src="../js/smoothscroll.js"></script>
    <script src="../js/form-validator.min.js"></script>
    <script src="../js/contact-form-script.js"></script>
    <script src="../js/custom.js"></script>
    <script src="../js/jquery.easing.1.3.js"></script>
    <script src="../js/jquery.waypoints.min.js"></script>
    <script src="../js/owl.carousel.min.js"></script>
    <script src="../js/jquery.countTo.js"></script>
    <script src="../js/jquery.stellar.min.js"></script>
    <script src="../js/magnific-popup-options.js"></script>
    <script src="../js/simplyCountdown.js"></script>
    <script src="../js/main.js"></script>
    <script>
        simplyCountdown('.simply-countdown-one', {
            year: <?php echo $year; ?>,
            month: <?php echo $month; ?>,
            day: <?php echo $day; ?>,
            hours: <?php echo $hours; ?>,
            minutes: <?php echo $minutes; ?>,
            seconds: <?php echo $seconds; ?>
        });
        $('#simply-countdown-losange').simplyCountdown({
            year: <?php echo $year; ?>,
            month: <?php echo $month; ?>,
            day: <?php echo $day; ?>,
            hours: <?php echo $hours; ?>,
            minutes: <?php echo $minutes; ?>,
            seconds: <?php echo $seconds; ?>,
            enableUtc: true
        });
    </script>
    <script>
        function resizeIframe(obj) {
            obj.style.height = obj.contentWindow.document.documentElement.scrollHeight + 'px';
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>