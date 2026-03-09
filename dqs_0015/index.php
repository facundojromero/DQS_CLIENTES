<?php
error_reporting(E_ERROR);
include_once 'conexion.php';

// --- CARGA DE DATOS (Mantenida igual al original) ---
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

$query = "SELECT
    CONCAT(UCASE(LEFT(nombre, 1)), LCASE(SUBSTRING(nombre, 2))) AS nombre,
    IF(RIGHT(texto, 1) = '.', texto, CONCAT(texto, '.')) AS texto
FROM info_nosotros WHERE activo = 1 ORDER BY id ASC;";
$result = mysqli_query($conn, $query);
$info_nosotros = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $info_nosotros[] = $row;
    }
}

$nombre1 = isset($info_nosotros[0]['nombre']) ? $info_nosotros[0]['nombre'] : '';
$texto1 = isset($info_nosotros[0]['texto']) ? $info_nosotros[0]['texto'] : '';
$nombre2 = isset($info_nosotros[1]['nombre']) ? $info_nosotros[1]['nombre'] : '';
$texto2 = isset($info_nosotros[1]['texto']) ? $info_nosotros[1]['texto'] : '';

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
    fecha, titulo, IF(RIGHT(texto, 1) = '.', texto, CONCAT(texto, '.')) AS texto
FROM info_historia WHERE activo = 1 ORDER BY fecha ASC;";
$result = mysqli_query($conn, $query);
$info_historia = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $info_historia[] = $row;
    }
}

// Lógica de Galería
$gallery_dir = 'images/gallery/';
if (is_dir($gallery_dir)) {
    $images = array_diff(scandir($gallery_dir), array('.', '..'));
    $image_extensions = array('jpg', 'jpeg', 'png', 'gif');
    $images = array_filter($images, function($image) use ($image_extensions) {
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $image_extensions);
    });

    function resize_image($file, $w, $h) {
        list($width, $height) = getimagesize($file);
        $src = imagecreatefromjpeg($file);
        $dst = imagecreatetruecolor($w, $h);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $width, $height);
        imagejpeg($dst, $file);
    }
    // Nota: Comenté el resize automático para no sobrecargar si no es necesario, descomentar si se usa
    /*
    foreach ($images as $image) {
        $image_path = $gallery_dir . $image;
        list($width, $height) = getimagesize($image_path);
        if ($width != 900 || $height != 700) { resize_image($image_path, 900, 700); }
    }
    */
} else {
    $images = [];
}

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
    titulo, descripcion, direccion, url, imagen, icono, tipo_visual
FROM info_eventos WHERE activo = 1 ORDER BY orden;";
$result = mysqli_query($conn, $query);
$info_eventos = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $info_eventos[] = $row;
    }
}

$query = "SELECT titulo, descripcion, direccion, url, icono FROM info_otra WHERE activo = 1 ORDER BY orden";
$result = mysqli_query($conn, $query);
$info_otra = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $info_otra[] = $row;
    }
}

// Simulamos secciones activas si no existen en la BD
$secciones = ['cronometro', 'about', 'story', 'gallery', 'events', 'wedding', 'contact']; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>Tu fiesta. Dije que sí!</title>
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/pogo-slider.min.css">

    <?php
    $styleFile = 'current_style.txt';
    $currentStyle = 'style.css';
    if (file_exists($styleFile)) {
        $content = file_get_contents($styleFile);
        if ($content !== false) {
            $currentStyle = trim($content);
        }
    }
    ?>
    <link rel="stylesheet" href="css/<?php echo htmlspecialchars($currentStyle); ?>">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/custom.css">

    <style>
        .icon-container { display: flex; justify-content: center; align-items: center; width: 100%; height: 100%; }
        .msg_error { color: #d8000c; background-color: #ffbaba; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body id="home" data-spy="scroll" data-target="#navbar-wd" data-offset="98">

	<div id="preloader">
		<div class="preloader pulse">
			<i class="fa fa-heartbeat" aria-hidden="true"></i>
		</div>
    </div>
    
    <?php require 'header.php'; ?>
    
<div class="ulockd-home-slider">
    <div class="container-fluid">
        <div class="row">
            <div class="pogoSlider" id="js-main-slider">
                <?php
                // 1. Buscamos todas las imágenes que empiecen con "slider-" en la carpeta images
                // El GLOB_BRACE permite buscar jpg, png o jpeg
                $imagenes = glob("images/slider-*.{jpg,jpeg,png,gif}", GLOB_BRACE);

                // 2. Definimos las transiciones que quieres ir rotando
                $transiciones = ['zipReveal', 'blocksReveal', 'shrinkReveal'];
                
                // 3. Recorremos las imágenes encontradas
                foreach ($imagenes as $indice => $ruta_imagen) {
                    // Seleccionamos una transición diferente para cada imagen basándonos en el índice
                    $transicion_actual = $transiciones[$indice % count($transiciones)];
                    $duracion = ($transicion_actual == 'shrinkReveal') ? 2000 : 1500;
                ?>
                    <div class="pogoSlider-slide" 
                         data-transition="<?php echo $transicion_actual; ?>" 
                         data-duration="<?php echo $duracion; ?>" 
                         style="background-image:url(<?php echo $ruta_imagen; ?>?<?php echo time(); ?>);">
                        
                        <div class="lbox-caption">
                            <div class="lbox-details">
                                <h1><?php echo $portada_titulo; ?></h1>
                                <h2><?php echo $portada_frase; ?></h2>
                                <p><strong><?php echo $portada_fecha; ?></strong></p>
                            </div>
                        </div>
                    </div>
                <?php 
                } // Fin del foreach 
                ?>
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
                                <a href="tienda/" class="btn">Regalar</a>
                                <?php if (in_array('cronometro', $secciones)): ?>
                                   <p><div class="simply-countdown simply-countdown-one"></div></p>
                                <?php endif; ?>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>

	<?php if (in_array('about', $secciones)): ?>
	<div id="about" class="about-box">
		<div class="about-a1">
			<div class="container">
				<div class="row">
					<div class="col-lg-12">
						<div class="title-box">
                            <h2>
                                <?php echo $nombre1; ?>
                                <?php if (!empty($nombre2)) { echo ' <span>&</span> ' . $nombre2; } ?>
                            </h2>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12 col-md-12 col-sm-12">
						<div class="row align-items-center about-main-info">
							<div class="col-lg-8 col-md-6 col-sm-12">
								<h2> Acerca de <span><?php echo $nombre1; ?></span></h2>
								<p><?php echo $texto1; ?></p>
							</div>
							<div class="col-lg-4 col-md-6 col-sm-12">
								<div class="about-img">
									<img class="img-fluid rounded" src="images/about/img_01.jpg" alt="" />
								</div>
							</div>
						</div>
                        <?php if (!empty($nombre2)) : ?>
                        	<div class="row align-items-center about-main-info">
                        		<div class="col-lg-4 col-md-6 col-sm-12">
                        			<div class="about-img">
                        				<img class="img-fluid rounded" src="images/about/img_02.jpg" alt="" />
                        			</div>
                        		</div>
                        		<div class="col-lg-8 col-md-6 col-sm-12">
                        			<h2>Acerca de <span><?php echo $nombre2; ?></span></h2>
                        			<p><?php echo $texto2; ?></p>
                        		</div>
                        	</div>
                        <?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

    <?php if (in_array('story', $secciones)): ?>
    <div id="story" class="story-box main-timeline-box">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <h2>Nuestra Historia</h2>
                        <p>Desde el primer encuentro hasta el compromiso, nuestra historia está llena de momentos inolvidables y amor verdadero.</p>
                    </div>
                </div>
            </div>
            <?php foreach ($info_historia as $index => $historia): ?>
            <div class="row timeline-element <?php echo ($index % 2 == 1) ? 'reverse separline' : 'separline'; ?>">
                <div class="timeline-date-panel col-xs-12 col-md-6 align-left">
                    <div class="time-line-date-content">
                        <p class="mbr-timeline-date mbr-fonts-style display-font"><?php echo $historia['formato_fecha']; ?></p>
                    </div>
                </div>
                <span class="iconBackground"></span>
                <div class="<?php echo ($index % 2 == 0) ? 'col-xs-12 col-md-6 align-left' : 'col-xs-12 col-md-6 align-right'; ?>">
                    <div class="timeline-text-content">
                        <h4 class="mbr-timeline-title pb-3 mbr-fonts-style display-font"><?php echo $historia['titulo']; ?></h4>
                        <p class="mbr-timeline-text mbr-fonts-style display-7"><?php echo $historia['texto']; ?></p>
                     </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('gallery', $secciones)): ?>
    <div id="gallery" class="gallery-box">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <h2>Fotos</h2>
                        <p>Algunos de nuestros mejores momentos.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <ul class="popup-gallery clearfix">
                    <?php foreach ($images as $image): ?>
                    <li>
                        <a href="<?php echo $gallery_dir . $image; ?>">
                            <img class="img-fluid" src="<?php echo $gallery_dir . $image; ?>" alt="single image">
                            <span class="overlay"><i class="fa fa-heart-o" aria-hidden="true"></i></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('events', $secciones)): ?>
    <div id="events" class="events-box">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <h2>Eventos</h2>
                        <p>Estamos emocionados de compartir estos momentos con ustedes.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php foreach ($info_eventos as $evento): ?>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="event-inner">
                        <div class="event-img">
                            <?php if ($evento['tipo_visual'] == 'imagen'): ?>
                                <img class="img-fluid" src="images/events/<?php echo $evento['imagen']; ?>" alt="" />
                            <?php else: ?>
                                <div class="icon-container">
                                    <i class="<?php echo $evento['icono']; ?> evento-icono"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h2>

                            <?php echo $evento['titulo']; ?>
                            <br>
                            <?php if ($evento['fecha'] != 'Fecha no disponible'): ?>
                                <?php echo $evento['fecha']; ?>
                            <?php endif; ?>
                            
                        </h2>
<?php if (!empty($evento['descripcion'])): ?>
    <p><?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?></p>
<?php endif; ?>
                        <?php if (!empty($evento['direccion'])): ?>
                            <p><?php echo $evento['direccion']; ?></p>
                        <?php endif; ?>
                        <?php
                            if (!empty($evento['url'])):
                                $url = $evento['url'];
                                if (strpos($url, 'https://') !== 0) { $url = 'https://' . $url; }
                                $texto = (strpos($url, 'maps') !== false) ? 'Ver ubicación' : 'Ver link';
                            ?>
                            <a href="<?php echo $url; ?>" target="_blank"><?php echo $texto; ?> ></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php if (in_array('wedding', $secciones)): ?>
    <div id="wedding" class="wedding-box">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <h2>Más Info</h2>
                        <p>Descubre más sobre nuestro evento.</p>
                    </div>
                </div>
            </div>

<div class="row">
    <?php foreach ($info_otra as $evento): ?>
    <div class="col-md-4 col-sm-6">
        <div class="serviceBox">
            <div class="service-icon"><i class="<?php echo $evento['icono']; ?>"></i></div>
            <h3 class="title"><?php echo $evento['titulo']; ?></h3>
            
            <div class="bank-details-container">
                <?php 
                    $text = $evento['descripcion'];
                    
                    // Separamos el texto cuando detecta la palabra "CUENTA"
                    // Esto crea un array con cada cuenta por separado
                    $parts = preg_split('/(?=CUENTA)/i', $text, -1, PREG_SPLIT_NO_EMPTY);

                    foreach ($parts as $part): 
                        // Limpiamos un poco el texto de la parte
                        $part = trim($part);
                        if (empty($part)) continue;

                        // Buscamos CBU y Alias para extraerlos
                        preg_match('/CBU:?\s*(\d+)/i', $part, $cbu_match);
                        preg_match('/Alias:?\s*([\w\.]+)/i', $part, $alias_match);
                        
                        // El título es lo que está antes del CBU
                        $titulo_cuenta = trim(explode('CBU', $part)[0]);
                ?>
                    <div class="bank-block">
                        <span class="bank-label"><?php echo strtoupper($titulo_cuenta); ?></span>
                        
                        <?php if (isset($cbu_match[1])): ?>
                            <div class="data-row">
                                <span class="data-text">CBU: <strong><?php echo $cbu_match[1]; ?></strong></span>
                                <i class="far fa-copy copy-icon" onclick="copyToClipboard('<?php echo $cbu_match[1]; ?>', this)"></i>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($alias_match[1])): ?>
                            <div class="data-row">
                                <span class="data-text">Alias: <strong><?php echo $alias_match[1]; ?></strong></span>
                                <i class="far fa-copy copy-icon" onclick="copyToClipboard('<?php echo $alias_match[1]; ?>', this)"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($evento['direccion'])): ?>
                <h4 class="location-text"><?php echo $evento['direccion']; ?></h4>
            <?php endif; ?>
            
                        <?php if (!empty($evento['url'])): ?>
                <a href="<?php echo $evento['url']; ?>" class="read-more" target="_blank">Link ></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('contact', $secciones)): ?>
	<div id="contact" class="contact-box">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="title-box">
						<h2>Contactar con nosotros</h2>
						<p>Si quieres enviarnos un mensaje.</p>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 col-sm-12 col-xs-12">
				  <div class="contact-block">
					<form id="contactForm" method="POST" action="enviar.php">
					  <div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<input type="text" class="form-control" id="name" name="name" placeholder="Nombre" required data-error="Por favor, ingresar nombre">
								<div class="help-block with-errors"></div>
							</div>
						</div>
						<div class="col-md-12">
							<div class="form-group">
								<input type="text" placeholder="Email" id="email" class="form-control" name="email" required data-error="Por favor, ingresar email">
								<div class="help-block with-errors"></div>
							</div>
						</div>
                        <div class="col-md-12">
							<div class="form-group">
								<textarea class="form-control" id="message" placeholder="Mensaje" rows="8" data-error="Por favor, escribi tu mensaje para enviar" required></textarea>
								<div class="help-block with-errors"></div>
							</div>
							<div class="submit-button text-center">
								<button class="btn btn-common" id="submit" type="submit">Enviar Mensaje</button>
								<div id="msgSubmit" class="h3 text-center hidden"></div>
								<div class="clearfix"></div>
							</div>
						</div>
					  </div>
					</form>
				  </div>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>


    <footer>
        <?php require 'footer.php'; ?>
	</footer>
    
	<script src="js/jquery.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/jquery.pogo-slider.min.js"></script>
	<script src="js/slider-index.js"></script>
	<script src="js/smoothscroll.js"></script>
	<script src="js/form-validator.min.js"></script>
    <script src="js/contact-form-script.js"></script>
    <script src="js/custom.js"></script>
    <script src="js/simplyCountdown.js"></script>

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

    <div class="modal fade" id="confirmacionModal" tabindex="-1" role="dialog" aria-labelledby="confirmacionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {

        // Al hacer click en cualquier botón con clase .open-rsvp-modal
        $('.open-rsvp-modal').on('click', function(e) {
            e.preventDefault();
            
            // Carga el archivo confirmacion_modal.php (SIN buscar codigo)
            $('#confirmacionModal .modal-content').load('confirmacion_modal.php', function(response, status, xhr) {
                if (status == "error") {
                    console.log("Error al cargar el modal: " + xhr.status + " " + xhr.statusText);
                    alert("Error al cargar el formulario. Intente nuevamente.");
                } else {
                    $('#confirmacionModal').modal('show');
                }
            });
        });

        // Manejo del envío del formulario dentro del modal
        $(document).on('submit', '#formConfirmacion', function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var formData = form.serialize();

            form.find('button[type="submit"]').prop('disabled', true).text('Enviando...');

            $.ajax({
                type: 'POST',
                url: url,
                data: formData,
                dataType: 'json',
                success: function(response) {
                    var messageDiv = $('#modalMessage');

                    if (response.success) { 
                        // Muestra mensaje de éxito y oculta el formulario
                        messageDiv.removeClass('msg_error alert-danger').addClass('alert alert-success').html(response.message).show();
                        form.hide();
                        $('#introTextConfirmacion').hide(); 

                        // Opcional: Cerrar modal después de unos segundos
                        // setTimeout(function(){ $('#confirmacionModal').modal('hide'); }, 3000);

                    } else {
                        // Muestra error
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
        
        // Manejo visual de campos (Mostrar/Ocultar detalles al cambiar selects)
        // Nota: Estos eventos también se pueden definir dentro de confirmacion_modal.php,
        // pero tenerlos delegados aquí asegura que funcionen tras la carga AJAX.
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


<script>
function copyToClipboard(text, element) {
    const input = document.createElement("input");
    input.setAttribute("value", text);
    document.body.appendChild(input);
    input.select();
    document.execCommand("copy");
    document.body.removeChild(input);

    // Feedback visual en el icono
    element.classList.remove('fa-copy');
    element.classList.add('fa-check', 'success');
    
    setTimeout(() => {
        element.classList.remove('fa-check', 'success');
        element.classList.add('fa-copy');
    }, 2000);
}
</script>


</body>
</html>

<?php
include_once 'contador.php';
mysqli_close($conn);
?>