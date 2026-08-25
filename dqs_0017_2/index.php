<?php
error_reporting(E_ERROR);
include_once 'conexion.php';
include_once 'tienda/regalo_libre_helper.php';
require_once __DIR__ . '/includes/rsvp_mode.php';
require_once __DIR__ . '/includes/plan_config.php';

$dqsRsvpMode = dqs_rsvp_get_effective_mode($conn);
$historiaSectionTitle = dqs_get_plan_config_value('historia_section_title', $conn);
$historiaSectionSubtitle = dqs_get_plan_config_value('historia_section_subtitle', $conn);
$historiaSectionHeaderVisible = dqs_get_plan_config_value('historia_section_header_visible', $conn) === '1';
$publicTextConfig = dqs_get_plan_config($conn);

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



$nombre1 = $info_nosotros[0]['nombre'];
$texto1 = $info_nosotros[0]['texto'];
$nombre2 = $info_nosotros[1]['nombre'];
$texto2 = $info_nosotros[1]['texto'];




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




$gallery_dir = 'images/gallery/';


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


foreach ($images as $image) {
    $image_path = $gallery_dir . $image;
    list($width, $height) = getimagesize($image_path);
    if ($width != 900 || $height != 700) {
        resize_image($image_path, 900, 700);
    }
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
        END, ' ', YEAR(fecha), ' ', DATE_FORMAT(fecha, '%H:%i'), ' hs'), 'Fecha no disponible') AS fecha,
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




$configVisualRegalos = obtenerConfiguracionVisualRegalos($conn);
$regalosEnabled = dqs_can_use_gifts($conn);
$mostrarListaRegalos = $regalosEnabled && $configVisualRegalos['mostrar_lista_regalos'];
$mostrarTransferenciaRegalos = $regalosEnabled && $configVisualRegalos['mostrar_transferencia_regalos'];
$configLinkRegalar = obtenerDestinoLinkRegalar($conn, false);
$mostrarLinkRegalar = $configLinkRegalar['mostrar'];
$regalosLinkHref = $configLinkRegalar['href'];
$datosBancariosRegalos = array(
    'cbu_titular' => '',
    'cbu' => '',
    'alias' => '',
    'cbu_dolar' => '',
    'alias_dolar' => '',
);
$query_datos_bancarios_regalos = "SELECT cbu_titular, cbu, alias, cbu_dolar, alias_dolar FROM cliente WHERE user_id = 1 LIMIT 1";
$result_datos_bancarios_regalos = mysqli_query($conn, $query_datos_bancarios_regalos);
if ($result_datos_bancarios_regalos && mysqli_num_rows($result_datos_bancarios_regalos) > 0) {
    $datosBancariosRegalos = mysqli_fetch_assoc($result_datos_bancarios_regalos);
}
$mostrarCuentaPesosRegalos = !empty($datosBancariosRegalos['cbu']) || !empty($datosBancariosRegalos['alias']);
$mostrarCuentaDolaresRegalos = !empty($datosBancariosRegalos['cbu_dolar']) || !empty($datosBancariosRegalos['alias_dolar']);
$mostrarTransferenciaRegalos = $mostrarTransferenciaRegalos && ($mostrarCuentaPesosRegalos || $mostrarCuentaDolaresRegalos);

?>
<!DOCTYPE html>
<html lang="en"><head>
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
    
    
     @font-face {
        font-family: 'the-seasons-regular';
        src: url('the-seasons-regular.ttf') format('truetype');
    }
    
    
    
    
    

        
    
    
    
        .event-img {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%; /* Asegúrate de que el contenedor tenga una altura definida */
        }

        .icon-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
        }
        
                .msg_error {
            color: #d8000c;
            background-color: #ffbaba;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

    </style>


</head>
<body id="home" data-spy="scroll" data-target="#navbar-wd" data-offset="98">

	<div id="preloader">
		<div class="preloader pulse">
			<i class="fa fa-heartbeat" aria-hidden="true"></i>
		</div>
    </div>
    
    <?php require 'header.php'; ?>
    
    
    <?php ?>


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
                                <div class="hero-actions" aria-label="Accesos principales">
                                    <?php if ($mostrarLinkRegalar): ?>
                                    <a href="<?php echo htmlspecialchars($regalosLinkHref); ?>" class="btn hero-action hero-action--gift">
                                        <i class="fas fa-gift" aria-hidden="true"></i>
                                        <span>Regalar</span>
                                    </a>
                                    <?php endif; ?>
                                    <a href="#rsvp" class="btn hero-action hero-action--rsvp">
                                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                                        <span>Confirmar asistencia</span>
                                    </a>
                                </div>
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
            <?php if ($historiaSectionHeaderVisible && ($historiaSectionTitle !== '' || $historiaSectionSubtitle !== '')): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <?php if ($historiaSectionTitle !== ''): ?>
                        <h2><?php echo htmlspecialchars($historiaSectionTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <?php endif; ?>
                        <?php if ($historiaSectionSubtitle !== ''): ?>
                        <p><?php echo nl2br(htmlspecialchars($historiaSectionSubtitle, ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
            <?php if ($publicTextConfig['eventos_section_header_visible'] === '1' && ($publicTextConfig['eventos_section_title'] !== '' || $publicTextConfig['eventos_section_subtitle'] !== '')): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <?php if ($publicTextConfig['eventos_section_title'] !== ''): ?><h2><?php echo htmlspecialchars($publicTextConfig['eventos_section_title'], ENT_QUOTES, 'UTF-8'); ?></h2><?php endif; ?>
                        <?php if ($publicTextConfig['eventos_section_subtitle'] !== ''): ?><p><?php echo nl2br(htmlspecialchars($publicTextConfig['eventos_section_subtitle'], ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
                            <?php if ($evento['fecha'] != 'Fecha no disponible'): ?>
                                <?php echo $evento['fecha']; ?>
                            <?php endif; ?>
                            <?php echo $evento['titulo']; ?>
                        </h2>
                        <?php if (!empty($evento['descripcion'])): ?>
                            <p><?php echo $evento['descripcion']; ?></p>
                        <?php endif; ?>
                        <?php if (!empty($evento['direccion'])): ?>
                            <p><?php echo $evento['direccion']; ?></p>
                        <?php endif; ?>

                        <?php
                            if (!empty($evento['url'])):
                                $url = $evento['url'];

                                if (strpos($url, 'https://') !== 0) {
                                    $url = 'https://' . $url;
                                }

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



<?php if (in_array('wedding', $secciones) || $mostrarTransferenciaRegalos): ?>
    <div id="wedding" class="wedding-box">
        <div class="container">
            <?php if ($publicTextConfig['mas_info_section_header_visible'] === '1' && ($publicTextConfig['mas_info_section_title'] !== '' || $publicTextConfig['mas_info_section_subtitle'] !== '')): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <?php if ($publicTextConfig['mas_info_section_title'] !== ''): ?><h2><?php echo htmlspecialchars($publicTextConfig['mas_info_section_title'], ENT_QUOTES, 'UTF-8'); ?></h2><?php endif; ?>
                        <?php if ($publicTextConfig['mas_info_section_subtitle'] !== ''): ?><p><?php echo nl2br(htmlspecialchars($publicTextConfig['mas_info_section_subtitle'], ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <?php foreach ($info_otra as $evento): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="serviceBox">
                        <div class="service-icon"><i class="<?php echo $evento['icono']; ?>"></i></div>
                        <h3 class="title"><?php echo $evento['titulo']; ?></h3>
                        <?php if (!empty($evento['descripcion'])): ?>
                            <p class="description"><?php echo $evento['descripcion']; ?></p>
                        <?php endif; ?>

                        <?php if (!empty($evento['direccion'])): ?>
                            <h4><?php echo $evento['direccion']; ?></h4>
                        <?php endif; ?>

                        <?php if (!empty($evento['url'])): ?>
                            <a href="<?php echo $evento['url']; ?>" target="_blank">Link ></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($mostrarTransferenciaRegalos): ?>
                <div class="col-md-4 col-sm-6" id="regalar">
                    <div class="serviceBox">
                        <div class="service-icon"><i class="fas fa-gift"></i></div>
                        <h3 class="title"><?php echo htmlspecialchars($configVisualRegalos['titulo_transferencia_regalos']); ?></h3>

                        <div class="description">
                            <?php if ($mostrarCuentaPesosRegalos): ?>
                            <div class="bank-card">
                                <?php if (!empty($configVisualRegalos['titulo_cuenta_pesos_regalos'])): ?>
                                    <span class="bank-title"><strong><?php echo htmlspecialchars($configVisualRegalos['titulo_cuenta_pesos_regalos']); ?></strong></span>
                                <?php endif; ?>

                                <?php if (!empty($datosBancariosRegalos['cbu_titular'])): ?>
                                <div class="data-row">
                                    <span><?php echo htmlspecialchars($datosBancariosRegalos['cbu_titular']); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($datosBancariosRegalos['cbu'])): ?>
                                <div class="data-row">
                                    <span>CBU/CVU: <?php echo htmlspecialchars($datosBancariosRegalos['cbu']); ?></span>
                                    <i class="far fa-copy copy-icon" data-copy="<?php echo htmlspecialchars($datosBancariosRegalos['cbu'], ENT_QUOTES); ?>" aria-label="Copiar CBU/CVU" role="button" tabindex="0"></i>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($datosBancariosRegalos['alias'])): ?>
                                <div class="data-row">
                                    <span>Alias: <?php echo htmlspecialchars($datosBancariosRegalos['alias']); ?></span>
                                    <i class="far fa-copy copy-icon" data-copy="<?php echo htmlspecialchars($datosBancariosRegalos['alias'], ENT_QUOTES); ?>" aria-label="Copiar alias" role="button" tabindex="0"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($mostrarCuentaDolaresRegalos): ?>
                            <div class="bank-card">
                                <?php if (!empty($configVisualRegalos['titulo_cuenta_dolares_regalos'])): ?>
                                    <span class="bank-title"><strong><?php echo htmlspecialchars($configVisualRegalos['titulo_cuenta_dolares_regalos']); ?></strong></span>
                                <?php endif; ?>

                                <?php if (!empty($datosBancariosRegalos['cbu_titular'])): ?>
                                <div class="data-row">
                                    <span><?php echo htmlspecialchars($datosBancariosRegalos['cbu_titular']); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($datosBancariosRegalos['cbu_dolar'])): ?>
                                <div class="data-row">
                                    <span>CBU/CVU: <?php echo htmlspecialchars($datosBancariosRegalos['cbu_dolar']); ?></span>
                                    <i class="far fa-copy copy-icon" data-copy="<?php echo htmlspecialchars($datosBancariosRegalos['cbu_dolar'], ENT_QUOTES); ?>" aria-label="Copiar CBU/CVU dólares" role="button" tabindex="0"></i>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($datosBancariosRegalos['alias_dolar'])): ?>
                                <div class="data-row">
                                    <span>Alias: <?php echo htmlspecialchars($datosBancariosRegalos['alias_dolar']); ?></span>
                                    <i class="far fa-copy copy-icon" data-copy="<?php echo htmlspecialchars($datosBancariosRegalos['alias_dolar'], ENT_QUOTES); ?>" aria-label="Copiar alias dólares" role="button" tabindex="0"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <?php endif; ?>




<?php if (in_array('contact', $secciones)): ?>
	<div id="contact" class="contact-box">
		<div class="container">
            <?php if ($publicTextConfig['contacto_section_header_visible'] === '1' && ($publicTextConfig['contacto_section_title'] !== '' || $publicTextConfig['contacto_section_subtitle'] !== '')): ?>
			<div class="row">
				<div class="col-lg-12">
					<div class="title-box">
                        <?php if ($publicTextConfig['contacto_section_title'] !== ''): ?><h2><?php echo htmlspecialchars($publicTextConfig['contacto_section_title'], ENT_QUOTES, 'UTF-8'); ?></h2><?php endif; ?>
                        <?php if ($publicTextConfig['contacto_section_subtitle'] !== ''): ?><p><?php echo nl2br(htmlspecialchars($publicTextConfig['contacto_section_subtitle'], ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
					</div>
				</div>
			</div>
            <?php endif; ?>
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


    <div id="rsvp" class="rsvp-box">
        <div class="container">
            <?php if ($publicTextConfig['rsvp_section_header_visible'] === '1' && $publicTextConfig['rsvp_section_title'] !== ''): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <h2><?php echo htmlspecialchars($publicTextConfig['rsvp_section_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($dqsRsvpMode === 'form'): ?>
<?php require __DIR__ . '/includes/rsvp_form_public.php'; ?>
            <?php else: ?>
            <p style="text-align: center;">Busca por el código que te llegó en la invitación</p>
            <form action="#rsvp" method="get" class="form_search">
                <div align="center">
                    <input type="text" name="busqueda" id="busqueda" placeholder="Insertar código" value="<?php echo isset($_REQUEST['busqueda']) ? htmlspecialchars($_REQUEST['busqueda']) : ''; ?>">
                    <input type="submit" value="Buscar" class="btn_search">
                </div>
            </form>
            <?php
            $busqueda = isset($_REQUEST['busqueda']) ? strtolower($_REQUEST['busqueda']) : '';
            if (!empty($busqueda)) {
                $query = mysqli_query($conn, "
                SELECT
                CASE WHEN a.cantidad_mayores > 1 THEN e.titulo_invitados ELSE CONCAT(titulo_invitados,' ',a.apellido) END nombre,
                CASE WHEN LENGTH(a.apellido) > 3 THEN CONCAT(SUBSTRING(a.apellido, 1, 3), '.') ELSE CONCAT(SUBSTRING(a.apellido, 1, 2), '.') END AS apellido,
                a.id id_invitados,
                TO_BASE64(a.id) AS base64_id_invitados,
                a.codigo,
                e.*,
                a.cantidad_mayores,
                a.confirmacion,
                a.confirmacion_mayores,
                a.confirmacion_menores
                FROM invitados a
                LEFT JOIN intivados_acompanante b ON a.acompanado = b.id
                LEFT JOIN invitados_prioridad c ON a.id_prioridad = c.id
                INNER JOIN (
                    SELECT
                    CASE WHEN cantidad_mayores > 1 THEN e.titulo_invitados ELSE CONCAT(titulo_invitados,' ',apellido) END nombre,
                    nombre nombre_revision,
                    apellido apellido_revision,
                    CASE WHEN LENGTH(apellido) > 3 THEN CONCAT(SUBSTRING(apellido, 1, 3), '.') ELSE CONCAT(SUBSTRING(apellido, 1, 2), '.') END AS apellido,
                    a.id id_invitados,
                    TO_BASE64(a.id) AS base64_id_invitados,
                    cantidad_mayores,
                    cantidad_menores,
                    ingreso,
                    categoria_acompanante acompanado,
                    e.invitados,
                    e.titulo_invitados
                    -- , REPLACE(tel_enviar_concatenado, ',', ' ó ') AS tel_enviar_concat
                    FROM invitados a
                    LEFT JOIN intivados_acompanante b ON a.acompanado = b.id
                    LEFT JOIN invitados_prioridad c ON a.id_prioridad = c.id
                    LEFT JOIN (
                        SELECT
                        aa.id_invitados,
                        bb.invitados,
                        bb.titulo_invitados,
                        ROW_NUMBER() OVER (PARTITION BY aa.id_invitados ORDER BY aa.id_invitados ASC) AS numero_fila
                        -- , GROUP_CONCAT(CONCAT('xxxxxx', SUBSTRING(tel_enviar, 7, 5))) AS tel_enviar_concatenado
                        FROM invitados_listado_mesa aa
                        INNER JOIN (
                            SELECT
                            a.id_invitados,
                            SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ' y '), ' y ', 2) AS titulo_invitados,
                            CASE WHEN cantidad_mayores<2 THEN nombre_invitado ELSE
                            CONCAT(
                                IF(COUNT(*) > 1,
                                SUBSTRING_INDEX(
                                    GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '),
                                    ', ',
                                    COUNT(*) - 1
                                ),
                                GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
                                ),
                                ' y ',
                                SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
                            ) END AS invitados
                            -- , GROUP_CONCAT(CONCAT('xxxxxx', SUBSTRING(tel_enviar, 7, 5))) AS tel_enviar_concatenado
                            FROM invitados_listado_mesa a
                            INNER JOIN invitados b ON a.id_invitados=b.id
                            WHERE 1=1
                            GROUP BY a.id_invitados
                        ) bb ON aa.id_invitados = bb.id_invitados
                        WHERE 1=1
                        GROUP BY aa.id_invitados
                    ) e ON a.id = e.id_invitados
                    WHERE 1=1
                    GROUP BY a.id
                ) e ON a.id = e.id_invitados
                WHERE 1=1
                AND a.codigo='$busqueda'
                AND activo=1
                GROUP BY a.id
                ORDER BY nombre, apellido ASC
                ");
                $result_confirmar = mysqli_num_rows($query);
                if ($result_confirmar > 0) {
                    while ($data = mysqli_fetch_array($query)) {


if ($data["confirmacion"] == "Si") {
    echo "
    <div class='row justify-content-center mt-4'>
        <div class='col-md-8'>
            <div class='card shadow-sm border-0' style='background-color: #f9f9f9; border-radius: 15px;'>
                <div class='card-body text-center p-4'>
                    <div class='mb-3'>
                        <i class='fas fa-check-circle' style='font-size: 40px; color: #28a745;'></i>
                    </div>
                    <h4 class='mb-2' style='font-family: \"the-seasons-regular\", serif; letter-spacing: 2px; color: #333;'>
                        ¡ASISTENCIA CONFIRMADA!
                    </h4>
                    <p class='text-muted mb-4'>Estamos muy felices de que nos acompañes.</p>
                    
                    <div class='row text-left justify-content-center'>
                        <div class='col-sm-10 col-md-8'>
                            <ul class='list-group list-group-flush mb-4' style='background: transparent;'>
                                <li class='list-group-item d-flex justify-content-between align-items-center' style='background: transparent;'>
                                    <strong><i class='fas fa-users mr-2'></i> Invitados:</strong>
                                    <span>" . $data["invitados"] . "</span>
                                </li>
                                <li class='list-group-item d-flex justify-content-between align-items-center' style='background: transparent;'>
                                    <strong><i class='fas fa-user mr-2'></i> Mayores:</strong>
                                    <span class='badge badge-pill badge-dark' style='font-size: 14px;'>" . $data["confirmacion_mayores"] . "</span>
                                </li>
                                <li class='list-group-item d-flex justify-content-between align-items-center' style='background: transparent;'>
                                    <strong><i class='fas fa-child mr-2'></i> Menores:</strong>
                                    <span class='badge badge-pill badge-dark' style='font-size: 14px;'>" . $data["confirmacion_menores"] . "</span>
                                </li>
                            </ul>

                            <div class='text-center'>
                                <a href='#' class='btn btn-outline-secondary btn-sm confirmar-modal-btn' 
                                   data-toggle='modal' data-target='#confirmacionModal' 
                                   data-codigo='" . $data["codigo"] . "' style='border-radius: 20px; padding: 5px 20px;'>
                                    <i class='fas fa-edit mr-1'></i> Modificar Asistencia
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>";
}


else {
                            echo "<div style='text-align: center;'>
                            <table width='100%'>
                            <tr>
                            <th>Invitación</th>
                            <th>Confirmar</th>
                            </tr>
                            <tr>
                            <td>" . $data["invitados"] . "</td>
<td>
    <a class='link_edit confirmar-modal-btn' href='#'
       data-toggle='modal' data-target='#confirmacionModal'
       data-codigo='" . $data["codigo"] . "'>
        <i class='fas fa-user-edit'></i> Confirmar
    </a>
</td>
                            </tr>
                            </table>
                            </div>";
                        }
                    }
                } else {
                    echo "<div style='text-align: center;'><p class='msg_error'>No se encontró el código. Por favor, verifica en la invitación.</p></div>";
                }
            }
            ?>
            <?php endif; ?>
        </div>
    </div>
    
    
    
    
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

    function resizeIframe(obj) {
        obj.style.height = obj.contentWindow.document.documentElement.scrollHeight + 'px';
    }


    $(document).ready(function() {
        var lastSearchCode = ''; 


        $('.confirmar-modal-btn').on('click', function(e) {
            e.preventDefault();
            var codigo = $(this).data('codigo');
            lastSearchCode = codigo;


            $('#confirmacionModal .modal-content').load('confirmacion_modal.php?codigo=' + codigo, function() {
                $('#confirmacionModal').modal('show');
            });
        });


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
                        let contentToDisplay = response.message;


                        if (response.data && response.data.mayores !== undefined && response.data.menores !== undefined) {
                            contentToDisplay += '<br>Mayores: ' + response.data.mayores + '<br>Menores: ' + response.data.menores;
                        }
                        

                        messageDiv.removeClass('msg_error alert-danger').addClass('alert alert-success').html(contentToDisplay).show();
                        

                        form.hide();

                        $('#introTextConfirmacion').hide(); 

                    } else {

                        messageDiv.removeClass('alert-success').addClass('msg_error alert alert-danger').text(response.message).show();

                        form.find('button[type="submit"]').prop('disabled', false).text('Enviar Confirmación');
                    }
                },

                error: function(xhr, status, error) {
                    $('#modalMessage').removeClass('alert-success').addClass('msg_error alert alert-danger').text('No cerrar ventana por favor.').show();

                    form.find('button[type="submit"]').prop('disabled', false).text('Enviar Confirmación');
                }
            });
        });


        $('#confirmacionModal').on('hidden.bs.modal', function (e) {

            if (lastSearchCode) {
                window.location.href = 'index.php?busqueda=' + lastSearchCode + '&_nocache=' + new Date().getTime() + '#rsvp';
            }
        });




        $(document).on("change", "#alimento", function() {
            var alimentoSelect = $(this);
            var contenidoGroup = $('#contenido-group');
            if (alimentoSelect.val() !== "No") {
                contenidoGroup.show();
            } else {
                contenidoGroup.hide();
            }
        }).trigger("change");


        $(document).on("change", "#entrada", function() {
            var entradaSelect = $(this);
            var contenidoMay = $('#mayores-container');
            var contenidoMen = $('#menores-container');       
            var contenidoAli = $('#alimento-container');           
            var contenidoGroup = $('#contenido-group');

            if (entradaSelect.val() !== "No") {
                contenidoMay.show();
                contenidoMen.show();                    
                contenidoAli.show();

                if ($("#alimento").val() !== "No") {
                    contenidoGroup.show();
                } else {
                    contenidoGroup.hide();
                }
            } else {
                contenidoMay.hide();
                contenidoMen.hide();     
                contenidoAli.hide();
                contenidoGroup.hide();
            }
        }).trigger("change");

    });
</script>


<script>
    function copyText(value, icon) {
        const showFeedback = function() {
            if (!icon) return;
            icon.classList.remove('fa-copy', 'far');
            icon.classList.add('fa-check', 'fas', 'copied');
            setTimeout(function() {
                icon.classList.remove('fa-check', 'fas', 'copied');
                icon.classList.add('fa-copy', 'far');
            }, 1400);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value).then(showFeedback).catch(function() {
                fallbackCopyText(value, showFeedback);
            });
        } else {
            fallbackCopyText(value, showFeedback);
        }
    }

    document.addEventListener('click', function(event) {
        const icon = event.target.closest('.copy-icon');
        if (!icon) return;
        copyText(icon.getAttribute('data-copy') || '', icon);
    });

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        const icon = event.target.closest('.copy-icon');
        if (!icon) return;
        event.preventDefault();
        copyText(icon.getAttribute('data-copy') || '', icon);
    });

    function fallbackCopyText(value, callback) {
        const input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', 'readonly');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.focus();
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        callback();
    }
</script>
</body>
</html>

<?php
include_once 'contador.php';
?>

<?php
mysqli_close($conn);
?>
