<?php
error_reporting(E_ERROR);
include_once '../conexion.php'; // Ruta ajustada
// include_once '../contador.php'; // Lo movemos al final, como en el index.php principal

// --- CARGA DE DATOS (Mantenida igual al original, con rutas ajustadas) ---
$query = "SELECT portada_titulo, portada_frase, portada_fecha, portada_fecha_hora FROM info_casamiento";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
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

// Consultas de info_nosotros, info_historia, info_eventos, info_otra omitidas por brevedad,
// pero se asume que si existieran, se cargarían aquí.
// Simulación de secciones activas (si no vienen de BD)
$secciones = ['cronometro', 'about', 'story', 'gallery', 'events', 'wedding', 'contact']; 

// Lógica de Galería (Ruta ajustada)
$gallery_dir = '../images/gallery/'; 
$images = []; // Inicializamos la variable para evitar errores si no existe el directorio
// La lógica completa de galería no es crítica para la tienda, pero mantenemos las rutas relativas correctas
// si decides incluir una sección de galería en la tienda.

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
    <title>Nos casamos - Lista de Regalos</title>
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="../images/apple-touch-icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/pogo-slider.min.css">

    <?php
    // Ruta al archivo que guarda la preferencia de estilo (Ajustada)
    $styleFile = '../current_style.txt'; 
    $currentStyle = 'style.css'; // Estilo por defecto si no se encuentra el archivo

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> 
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        /* Estilos CSS del index.php principal mantenidos */
        .msg_error { color: #d8000c; background-color: #ffbaba; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .btn-rsvp-open {
            background-color: #d4a373; /* Color dorado/beige ajusta a tu tema */
            color: white;
            padding: 15px 40px;
            font-size: 1.2rem;
            border-radius: 50px;
            border: none;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        .btn-rsvp-open:hover {
            background-color: #bc8a5f;
            cursor: pointer;
            transform: scale(1.05);
        }
    </style>
</head>
<body id="tienda" data-spy="scroll" data-target="#navbar-wd" data-offset="98">
    
	<div id="preloader">
		<div class="preloader pulse">
			<i class="fa fa-heartbeat" aria-hidden="true"></i>
		</div>
    </div>

	<?php require '../header.php'; ?>
	
	
	    <div class="ulockd-home-slider">
    	<div class="container-fluid">
    		<div class="row">
    			<div class="pogoSlider" id="js-main-slider">
    				<div class="pogoSlider-slide" data-transition="zipReveal" data-duration="1500" style="background-image:url(../images/slider-01.jpg?<?php echo time(); ?>);">
    					<div class="lbox-caption">
    						<div class="lbox-details">
    							<h1><?php echo $portada_titulo; ?></h1>
    							<h2><?php echo $portada_frase; ?></h2>
    							<p><strong><?php echo $portada_fecha; ?></strong></p>
    						</div>
    					</div>
    				</div>
    				<div class="pogoSlider-slide" data-transition="blocksReveal" data-duration="1500" style="background-image:url(../images/slider-02.jpg?<?php echo time(); ?>);">
    					<div class="lbox-caption">
    						<div class="lbox-details">
    							<h1><?php echo $portada_titulo; ?></h1>
    							<h2><?php echo $portada_frase; ?></h2>
    							<p><strong><?php echo $portada_fecha; ?></strong></p>
    						</div>
    					</div>
    				</div>
    				<div class="pogoSlider-slide" data-transition="shrinkReveal" data-duration="2000" style="background-image:url(../images/slider-03.jpg?<?php echo time(); ?>);">
    					<div class="lbox-caption">
    						<div class="lbox-details">
    							<h1><?php echo $portada_titulo; ?></h1>
    							<h2><?php echo $portada_frase; ?></h2>
    							<p><strong><?php echo $portada_fecha; ?></strong></p>
    						</div>
    					</div>
    				</div>
    			</div>
    		</div>
    	</div>
    </div>
	
	
	
	
	
	
	
	
	<div id="cronometro" class="cronometro-box <?php echo in_array('cronometro', $secciones) ? 'activo' : ''; ?>">
        <div class="about-a1">
            <div class="container">
            	<div class="row">
                    <div class="lbox-caption2">
                        <div class="lbox-details2">
                            <a href="#" class="btn open-rsvp-modal">RSVP</a>
                            <a href="../inicio.php" class="btn">Inicio</a>

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

<?php if ($mostrar_moneda): ?>
    <div class="pagination">
        <form id="currencyForm" method="GET" action="">
            <label for="currencySelect">Moneda:</label>
            <select id="currencySelect" name="currency" onchange="this.form.submit()">
                <option value="1" <?php if(isset($_GET['currency']) && $_GET['currency'] == '1') echo 'selected'; ?>>Pesos</option>
                <option value="2" <?php if(isset($_GET['currency']) && $_GET['currency'] == '2') echo 'selected'; ?>>Dólares</option>
            </select>
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


    <div class="modal fade" id="confirmacionModal" tabindex="-1" role="dialog" aria-labelledby="confirmacionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                </div>
        </div>
    </div>
    <footer>
        <?php require '../footer.php'; ?> </footer>
    
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
    <script src="../js/simplyCountdown.js"></script>

    <script>
        // Lógica de Cronómetro (Usando variables PHP)
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

    <script>
    $(document).ready(function() {

        // Al hacer click en cualquier botón con clase .open-rsvp-modal
        $('.open-rsvp-modal').on('click', function(e) {
            e.preventDefault();
            
            // Carga el archivo confirmacion_modal.php (RUTA AJUSTADA: ../)
            $('#confirmacionModal .modal-content').load('../confirmacion_modal.php', function(response, status, xhr) {
                if (status == "error") {
                    console.log("Error al cargar el modal: " + xhr.status + " " + xhr.statusText);
                    alert("Error al cargar el formulario. Intente nuevamente.");
                } else {
                    $('#confirmacionModal').modal('show');
                }
            });
        });

        // Manejo del envío del formulario dentro del modal (Delegado)
        $(document).on('submit', '#formConfirmacion', function(e) {
            e.preventDefault();

            var form = $(this);
            var formData = form.serialize();

            form.find('button[type="submit"]').prop('disabled', true).text('Enviando...');

            $.ajax({
                type: 'POST',
                // RUTA AJUSTADA: Asumiendo que procesar_confirmacion.php está en el directorio padre
                url: '../procesar_confirmacion.php', 
                data: formData,
                dataType: 'json',
                success: function(response) {
                    var messageDiv = $('#modalMessage');

                    if (response.success) { 
                        messageDiv.removeClass('msg_error alert-danger').addClass('alert alert-success').html(response.message).show();
                        form.hide();
                        $('#introTextConfirmacion').hide(); 
                    } else {
                        messageDiv.removeClass('alert-success').addClass('msg_error alert alert-danger').text(response.message).show();
                        form.find('button[type="submit"]').prop('disabled', false).text('Enviar Confirmación');
                    }
                },
                error: function(xhr, status, error) {
                    $('#modalMessage').removeClass('alert-success').addClass('msg_error alert alert-danger').text('Hubo un error de conexión. Inténtalo de nuevo.').show();
                    form.find('button[type="submit"]').prop('disabled', false).text('Enviar Confirmación');
                }
            });
        });
        
        // Manejo visual de campos (Delegado para que funcione después de la carga AJAX)
        $(document).on("change", "#entrada", function() {
            var val = $(this).val();
            if(val === 'Si') {
                $('#campos-asistencia').slideDown();
                $('#cantidad_mayores').prop('required', true);
            } else {
                $('#campos-asistencia').slideUp();
                $('#cantidad_mayores').prop('required', false);
            }
        });

        $(document).on("change", "#alimento", function() {
            if ($(this).val() !== "No") {
                $('#contenido-group').show();
            } else {
                $('#contenido-group').hide();
            }
        });

    });
    </script>
    </body>
</html>

<?php
include_once '../contador.php'; // Ruta ajustada e incluido al final
mysqli_close($conn);
?>