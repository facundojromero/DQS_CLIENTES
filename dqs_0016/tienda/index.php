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



// Consulta para verificar si cbu_dolar o alias_dolar tienen valor y obtener la configuración de Regalos
$query_cliente = "SELECT cbu_titular, cbu, alias, cbu_dolar, alias_dolar, regalos_modo_visualizacion, regalos_titulo FROM cliente WHERE user_id = 1";
$result_cliente = mysqli_query($conn, $query_cliente);
$mostrar_moneda = false; // Variable para controlar la visibilidad
$regalos_modo_visualizacion = 'productos';
$regalos_titulo = '¿NOS QUERÉS HACER UN REGALO?';
$datos_bancarios = [
    'titular' => '',
    'cbu' => '',
    'alias' => '',
    'cbu_dolar' => '',
    'alias_dolar' => ''
];

if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {
    $row_cliente = mysqli_fetch_assoc($result_cliente);
    $datos_bancarios['titular'] = trim($row_cliente['cbu_titular'] ?? '');
    $datos_bancarios['cbu'] = trim($row_cliente['cbu'] ?? '');
    $datos_bancarios['alias'] = trim($row_cliente['alias'] ?? '');
    $datos_bancarios['cbu_dolar'] = trim($row_cliente['cbu_dolar'] ?? '');
    $datos_bancarios['alias_dolar'] = trim($row_cliente['alias_dolar'] ?? '');

    if (!empty($row_cliente['cbu_dolar']) || !empty($row_cliente['alias_dolar'])) {
        $mostrar_moneda = true;
    }
    if (!empty($row_cliente['regalos_modo_visualizacion']) && in_array($row_cliente['regalos_modo_visualizacion'], ['productos', 'datos_bancarios'], true)) {
        $regalos_modo_visualizacion = $row_cliente['regalos_modo_visualizacion'];
    }
    if (!empty($row_cliente['regalos_titulo'])) {
        $regalos_titulo = $row_cliente['regalos_titulo'];
    }
}

$cuenta_pesos_visible = !empty($datos_bancarios['titular']) || !empty($datos_bancarios['cbu']) || !empty($datos_bancarios['alias']);
$cuenta_dolares_visible = !empty($datos_bancarios['cbu_dolar']) || !empty($datos_bancarios['alias_dolar']);

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
        .regalos-section-heading {
            padding: 34px 15px 12px;
            text-align: center;
        }
        .regalos-section-heading h1 {
            color: #222;
            font-size: clamp(28px, 5vw, 46px);
            font-weight: 600;
            letter-spacing: 1px;
            margin: 0;
            text-transform: uppercase;
        }
        .bank-gifts-wrapper {
            margin: 0 auto 60px;
            max-width: 980px;
            padding: 0 18px;
        }
        .bank-gifts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .bank-gift-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(80, 67, 58, 0.16);
            border-radius: 18px;
            box-shadow: 0 14px 38px rgba(0, 0, 0, 0.08);
            padding: 28px;
        }
        .bank-gift-card h2 {
            color: #333;
            font-size: 23px;
            margin: 0 0 20px;
        }
        .bank-gift-row {
            border-top: 1px solid #eee9e2;
            padding: 15px 0;
        }
        .bank-gift-row:first-of-type {
            border-top: 0;
            padding-top: 0;
        }
        .bank-gift-label {
            color: #7b7068;
            display: block;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
            margin-bottom: 7px;
            text-transform: uppercase;
        }
        .bank-gift-value-line {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        .bank-gift-value {
            color: #2f2f2f;
            font-size: 17px;
            overflow-wrap: anywhere;
        }
        .bank-copy-btn {
            align-items: center;
            background: #333;
            border: 0;
            border-radius: 999px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            flex: 0 0 auto;
            gap: 6px;
            padding: 8px 12px;
            transition: background .2s ease, transform .2s ease;
        }
        .bank-copy-btn:hover,
        .bank-copy-btn:focus {
            background: #111;
            transform: translateY(-1px);
        }
        .bank-copy-feedback {
            color: #2e7d32;
            display: none;
            font-size: 13px;
            font-weight: 700;
            margin-top: 7px;
        }
        .bank-empty-message {
            background: #fff;
            border: 1px solid #eee9e2;
            border-radius: 14px;
            color: #675f59;
            padding: 24px;
            text-align: center;
        }
        @media (max-width: 576px) {
            .bank-gift-card {
                padding: 22px;
            }
            .bank-gift-value-line {
                align-items: flex-start;
                flex-direction: column;
            }
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
    <section class="regalos-section-heading">
        <h1><?php echo htmlspecialchars($regalos_titulo); ?></h1>
    </section>

<?php if ($regalos_modo_visualizacion === 'productos'): ?>
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
    
    <script src="script.js"></script>
<?php else: ?>
    <section class="bank-gifts-wrapper" aria-label="Datos bancarios para regalos">
        <?php if ($cuenta_pesos_visible || $cuenta_dolares_visible): ?>
            <div class="bank-gifts-grid">
                <?php if ($cuenta_pesos_visible): ?>
                    <article class="bank-gift-card">
                        <h2>Cuenta en pesos</h2>
                        <?php if (!empty($datos_bancarios['titular'])): ?>
                            <div class="bank-gift-row">
                                <span class="bank-gift-label">Titular</span>
                                <span class="bank-gift-value"><?php echo htmlspecialchars($datos_bancarios['titular']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($datos_bancarios['cbu'])): ?>
                            <div class="bank-gift-row">
                                <span class="bank-gift-label">CBU</span>
                                <div class="bank-gift-value-line">
                                    <span class="bank-gift-value"><?php echo htmlspecialchars($datos_bancarios['cbu']); ?></span>
                                    <button type="button" class="bank-copy-btn" data-copy-value="<?php echo htmlspecialchars($datos_bancarios['cbu']); ?>"><i class="fas fa-copy"></i> Copiar</button>
                                </div>
                                <span class="bank-copy-feedback">Copiado</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($datos_bancarios['alias'])): ?>
                            <div class="bank-gift-row">
                                <span class="bank-gift-label">Alias</span>
                                <div class="bank-gift-value-line">
                                    <span class="bank-gift-value"><?php echo htmlspecialchars($datos_bancarios['alias']); ?></span>
                                    <button type="button" class="bank-copy-btn" data-copy-value="<?php echo htmlspecialchars($datos_bancarios['alias']); ?>"><i class="fas fa-copy"></i> Copiar</button>
                                </div>
                                <span class="bank-copy-feedback">Copiado</span>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endif; ?>

                <?php if ($cuenta_dolares_visible): ?>
                    <article class="bank-gift-card">
                        <h2>Cuenta en dólares</h2>
                        <?php if (!empty($datos_bancarios['titular'])): ?>
                            <div class="bank-gift-row">
                                <span class="bank-gift-label">Titular</span>
                                <span class="bank-gift-value"><?php echo htmlspecialchars($datos_bancarios['titular']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($datos_bancarios['cbu_dolar'])): ?>
                            <div class="bank-gift-row">
                                <span class="bank-gift-label">CBU dólar</span>
                                <div class="bank-gift-value-line">
                                    <span class="bank-gift-value"><?php echo htmlspecialchars($datos_bancarios['cbu_dolar']); ?></span>
                                    <button type="button" class="bank-copy-btn" data-copy-value="<?php echo htmlspecialchars($datos_bancarios['cbu_dolar']); ?>"><i class="fas fa-copy"></i> Copiar</button>
                                </div>
                                <span class="bank-copy-feedback">Copiado</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($datos_bancarios['alias_dolar'])): ?>
                            <div class="bank-gift-row">
                                <span class="bank-gift-label">Alias dólar</span>
                                <div class="bank-gift-value-line">
                                    <span class="bank-gift-value"><?php echo htmlspecialchars($datos_bancarios['alias_dolar']); ?></span>
                                    <button type="button" class="bank-copy-btn" data-copy-value="<?php echo htmlspecialchars($datos_bancarios['alias_dolar']); ?>"><i class="fas fa-copy"></i> Copiar</button>
                                </div>
                                <span class="bank-copy-feedback">Copiado</span>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bank-empty-message">Los datos bancarios todavía no están cargados.</div>
        <?php endif; ?>
    </section>
    <script>
        function copyBankGiftValue(value) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(value);
            }

            return new Promise(function(resolve, reject) {
                const textArea = document.createElement('textarea');
                textArea.value = value;
                textArea.style.position = 'fixed';
                textArea.style.left = '-9999px';
                textArea.style.top = '-9999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    document.execCommand('copy') ? resolve() : reject();
                } catch (error) {
                    reject(error);
                } finally {
                    document.body.removeChild(textArea);
                }
            });
        }

        document.querySelectorAll('.bank-copy-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const feedback = button.closest('.bank-gift-row').querySelector('.bank-copy-feedback');
                copyBankGiftValue(button.dataset.copyValue).then(function() {
                    feedback.textContent = 'Copiado';
                    feedback.style.display = 'inline-block';
                    setTimeout(function() { feedback.style.display = 'none'; }, 1800);
                }).catch(function() {
                    feedback.textContent = 'No se pudo copiar';
                    feedback.style.display = 'inline-block';
                    setTimeout(function() { feedback.style.display = 'none'; }, 2200);
                });
            });
        });
    </script>
<?php endif; ?>
    
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