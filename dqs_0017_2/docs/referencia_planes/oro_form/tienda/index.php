<?php
error_reporting(E_ERROR);
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo


include_once '../contador.php';

$query = "SELECT portada_titulo, portada_frase, portada_fecha, portada_fecha_hora FROM info_casamiento";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $portada_titulo = $row['portada_titulo'];
    $portada_frase = $row['portada_frase'];
    $portada_fecha = $row['portada_fecha'];
    $portada_fecha_hora = $row['portada_fecha_hora'];
} else {
    // Valores por defecto si no hay resultados
    $portada_titulo = "#Fulano & #Mengano";
    $portada_frase = "Nos casamos";
    $portada_fecha = "8 de Diciembre 2040";
    $portada_fecha_hora = "2040-12-08 00:00:00";
}


// Obtener los eventos activos de la base de datos
$query = "SELECT * FROM info_eventos WHERE activo=1";
$result = mysqli_query($conn, $query);
$eventos = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $eventos[] = $row;
    }
}



$datetime = new DateTime($portada_fecha_hora);
$year = $datetime->format('Y');
$month = $datetime->format('m');
$day = $datetime->format('d');
$hours = $datetime->format('H');
$minutes = $datetime->format('i');
$seconds = $datetime->format('s');





// Consulta para obtener los datos activos de la tabla info_nosotros
$query = "SELECT 
    CONCAT(UCASE(LEFT(nombre, 1)), LCASE(SUBSTRING(nombre, 2))) AS nombre,
    IF(RIGHT(texto, 1) = '.', texto, CONCAT(texto, '.')) AS texto
FROM 
    info_nosotros
WHERE 
    activo = 1
ORDER BY id ASC;";
$result = mysqli_query($conn, $query);

$info_nosotros = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $info_nosotros[] = $row;
    }
}


// Asignar valores a las variables
$nombre1 = $info_nosotros[0]['nombre'];
$texto1 = $info_nosotros[0]['texto'];
$nombre2 = $info_nosotros[1]['nombre'];
$texto2 = $info_nosotros[1]['texto'];



// Consulta para obtener los datos activos de la tabla info_historia
$query = "SELECT 
    CONCAT(DATE_FORMAT(fecha, '%d '), 
        CASE 
            WHEN MONTH(fecha) = 1 THEN 'Enero'
            WHEN MONTH(fecha) = 2 THEN 'Febrero'
            WHEN MONTH(fecha) = 3 THEN 'Marzo'
            WHEN MONTH(fecha) = 4 THEN 'Abril'
            WHEN MONTH(fecha) = 5 THEN 'Mayo'
            WHEN MONTH(fecha) = 6 THEN 'Junio'
            WHEN MONTH(fecha) = 7 THEN 'Julio'
            WHEN MONTH(fecha) = 8 THEN 'Agosto'
            WHEN MONTH(fecha) = 9 THEN 'Septiembre'
            WHEN MONTH(fecha) = 10 THEN 'Octubre'
            WHEN MONTH(fecha) = 11 THEN 'Noviembre'
            WHEN MONTH(fecha) = 12 THEN 'Diciembre'
        END, 
        DATE_FORMAT(fecha, ' %Y')
    ) AS formato_fecha,
    fecha,
    titulo,
    IF(RIGHT(texto, 1) = '.', texto, CONCAT(texto, '.')) AS texto
FROM info_historia
WHERE activo = 1 
ORDER BY fecha ASC;";
$result = mysqli_query($conn, $query);

$info_historia = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $info_historia[] = $row;
    }
}



// Directorio de la galería
$gallery_dir = 'images/gallery/';

// Obtener la lista de archivos en el directorio de la galería
$images = array_diff(scandir($gallery_dir), array('.', '..'));

// Filtrar solo archivos de imagen
$image_extensions = array('jpg', 'jpeg', 'png', 'gif');
$images = array_filter($images, function($image) use ($image_extensions) {
    $extension = pathinfo($image, PATHINFO_EXTENSION);
    return in_array(strtolower($extension), $image_extensions);
});

// Función para redimensionar imágenes
function resize_image($file, $w, $h) {
    list($width, $height) = getimagesize($file);
    $src = imagecreatefromjpeg($file);
    $dst = imagecreatetruecolor($w, $h);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $width, $height);
    imagejpeg($dst, $file);
}

// Redimensionar las imágenes si es necesario
foreach ($images as $image) {
    $image_path = $gallery_dir . $image;
    list($width, $height) = getimagesize($image_path);
    if ($width != 900 || $height != 700) {
        resize_image($image_path, 900, 700);
    }
}




// Consulta para obtener los datos activos de la tabla info_eventos
$query = "SELECT 
    IFNULL(CONCAT(DAY(fecha), ' ', 
        CASE 
            WHEN MONTH(fecha) = 1 THEN 'Enero'
            WHEN MONTH(fecha) = 2 THEN 'Febrero'
            WHEN MONTH(fecha) = 3 THEN 'Marzo'
            WHEN MONTH(fecha) = 4 THEN 'Abril'
            WHEN MONTH(fecha) = 5 THEN 'Mayo'
            WHEN MONTH(fecha) = 6 THEN 'Junio'
            WHEN MONTH(fecha) = 7 THEN 'Julio'
            WHEN MONTH(fecha) = 8 THEN 'Agosto'
            WHEN MONTH(fecha) = 9 THEN 'Septiembre'
            WHEN MONTH(fecha) = 10 THEN 'Octubre'
            WHEN MONTH(fecha) = 11 THEN 'Noviembre'
            WHEN MONTH(fecha) = 12 THEN 'Diciembre'
        END, ' ', YEAR(fecha)), 'Fecha no disponible') AS fecha,
    titulo,
    descripcion,
    direccion,
    url,
    imagen,
    icono,
    tipo_visual
FROM info_eventos
WHERE activo = 1
ORDER BY orden;";
$result = mysqli_query($conn, $query);

$info_eventos = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $info_eventos[] = $row;
    }
}



// Consulta para obtener los datos activos de la tabla info_otra
$query = "SELECT 
    titulo,
    descripcion,
    direccion,
    url,
    icono
FROM info_otra
WHERE activo = 1
ORDER BY orden";
$result = mysqli_query($conn, $query);

$info_otra = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $info_otra[] = $row;
    }
}



// Consulta para verificar si cbu_dolar o alias_dolar tienen valor en la tabla cliente
$query_cliente = "SELECT cbu_dolar, alias_dolar FROM cliente WHERE user_id = 1";
$result_cliente = mysqli_query($conn, $query_cliente);
$mostrar_moneda = false; // Variable para controlar la visibilidad

if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {
    $row_cliente = mysqli_fetch_assoc($result_cliente);
    if (!empty($row_cliente['cbu_dolar']) || !empty($row_cliente['alias_dolar'])) {
        $mostrar_moneda = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Nos casamos</title>
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="../images/apple-touch-icon.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/pogo-slider.min.css">
    <?php
    // Ruta al archivo que guarda la preferencia de estilo
    $styleFile = '../current_style.txt';
    $currentStyle = '../css/style.css'; // Estilo por defecto si no se encuentra el archivo

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
    

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">  
    
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Regalos</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        .currency-selector {
            display: inline-block;
            /* margin-left: 10px; */ /* Opcional: ajusta el margen según tu layout */
            font-size: 14px;
        }
        .currency-selector label {
            cursor: pointer;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f7f7f7;
            color: #333;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        .currency-selector input[type="radio"] {
            display: none;
        }
        .currency-selector input[type="radio"]:checked + label {
            background-color: #333;
            color: white;
            border-color: #333;
            font-weight: bold;
        }
        .currency-selector label:hover {
            background-color: #e9e9e9;
        }
    </style>
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
                                <a href="<?= $es_tienda ? '../' : '' ?>" class="btn">Inicio</a>

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
        <a href="#" id="cartLink" class="ver-carrito"><i class="fas fa-shopping-cart"></i></a>
    </div>

<?php 
// Recuperar el valor actual de la moneda o establecer el valor por defecto
$current_currency = isset($_GET['currency']) ? htmlspecialchars($_GET['currency']) : '1';
if ($mostrar_moneda): 
?>
    <div class="pagination">
        <form id="currencyForm" method="GET" action="" style="display:inline;">
            <div class="currency-selector">
                <input type="radio" id="pesos" name="currency" value="1" <?php if($current_currency == '1') echo 'checked'; ?> onchange="this.form.submit()">
                <label for="pesos">Pesos</label>
                <input type="radio" id="dolares" name="currency" value="2" <?php if($current_currency == '2') echo 'checked'; ?> onchange="this.form.submit()">
                <label for="dolares">Dólares</label>
            </div>
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars(isset($_GET['sort']) ? $_GET['sort'] : 'default'); ?>">
        </form>
    </div>
<?php endif; ?>

    <div class="pagination">
        <form id="sortForm" method="GET" action="">
            <label for="sortSelect">Ordenar por:</label>
            <select id="sortSelect" name="sort" onchange="this.form.submit()">
                <option value="default" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'default') echo 'selected'; ?>>Seleccionar</option>
                <option value="price" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'price') echo 'selected'; ?>>Precio</option>
                <option value="alphabetical" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'alphabetical') echo 'selected'; ?>>Alfabéticamente</option>
            </select>
            <input type="hidden" name="currency" value="<?php echo htmlspecialchars(isset($_GET['currency']) ? $_GET['currency'] : '1'); ?>">
        </form>
    </div>
    
    <div class="pagination">
        <?php include 'paginacion.php'; ?>
    </div>
    
    <div class="container2">
        <?php include 'mostrar_productos.php'; ?>
    </div>
    
    <div class="pagination">
        <?php include 'paginacion.php'; ?>
    </div>
    
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <span class="close">x</span>
            <h2>Tu Carrito</h2>
            <div id="cartItems">
                </div>
        </div>
    </div>
    

    <div id="freeGiftModal" class="modal">
        <div class="modal-content" style="max-width:420px;">
            <span class="close close-free-gift">x</span>
            <h2>Gift Card</h2>
            <form id="freeGiftForm">
                <input type="hidden" id="freeGiftProductId" name="producto_id">
                <label for="freeGiftAmount">Monto</label>
                <input type="number" id="freeGiftAmount" name="monto_libre" min="1" step="0.01" placeholder="Ej: 20000" required style="width:100%;padding:10px;margin:10px 0;">
                <button type="submit" class="button"><i class='fas fa-check'></i> Agregar</button>
            </form>
        </div>
    </div>

        <script src="script.js"></script>
    
    <div id="myModal" class="modal">
        <span class="close">×</span>
        <div class="modal-content">
            <img id="modalImage" src="">
            <div class="carousel-buttons">
                <button class="carousel-button prev">❮</button>
                <button class="carousel-button next">❯</button>
            </div>
        </div>
    </div>

    <footer>
    <?php require '../footer.php'; ?>
	</footer>
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
    <script src="../js/bootstrap.min.js"></script>
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
// Cerrar la conexión
mysqli_close($conn);
?>