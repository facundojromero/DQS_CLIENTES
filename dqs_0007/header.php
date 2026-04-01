<?php

// Cargar las secciones activas
$secciones = [];
$query = "SELECT seccion FROM info_mostrar WHERE activo = 1";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $secciones[] = $row['seccion'];
    }
}


?>

<?php
$script = $_SERVER['SCRIPT_NAME'];
$es_tienda = 
    strpos($script, '/tienda/') !== false ||
    strpos($script, 'procesar_compra.php') !== false ||
    strpos($script, 'finalizar_compra.php') !== false;
?>

<!-- Start header -->
<header class="top-header">
	<nav class="navbar header-nav navbar-expand-lg">
		<div class="container">
			
			<?php if (in_array('logo', $secciones)): ?>	
				<a class="navbar-brand" href="<?= $es_tienda ? '../#' : '#' ?>">
					<img src="<?= $es_tienda ? '../images/logo/logo.jpg' : 'images/logo/logo.jpg' ?>" alt="image">
				</a>
			<?php endif; ?>
			
			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-wd" aria-controls="navbar-wd" aria-expanded="false" aria-label="Toggle navigation">
				<span></span>
				<span></span>
				<span></span>
			</button>
			<div class="collapse navbar-collapse justify-content-end" id="navbar-wd">

				<ul class="nav navbar-nav">
					<?php if (in_array('about', $secciones)): ?>
						<li><a class="nav-link" href="<?= $es_tienda ? '../inicio.php#about' : '#about' ?>">Nosotros</a></li>
					<?php endif; ?>

					<?php if (in_array('story', $secciones)): ?>
						<li><a class="nav-link" href="<?= $es_tienda ? '../inicio.php#story' : '#story' ?>">Historia</a></li>
					<?php endif; ?>

					<?php //if (in_array('family', $secciones)): ?>
						<!-- <li><a class="nav-link" href="<?= $es_tienda ? '../inicio.php#family' : '#family' ?>">Familia</a></li> -->
					<?php //endif; ?>

					<?php if (in_array('gallery', $secciones)): ?>
						<li><a class="nav-link" href="<?= $es_tienda ? '../inicio.php#gallery' : '#gallery' ?>">Fotos</a></li>
					<?php endif; ?>

					<?php if (in_array('events', $secciones)): ?>
						<li><a class="nav-link" href="<?= $es_tienda ? '../inicio.php#events' : '#events' ?>">Eventos</a></li>
					<?php endif; ?>

					<?php if (in_array('wedding', $secciones)): ?>
						<li><a class="nav-link" href="<?= $es_tienda ? '../inicio.php#wedding' : '#wedding' ?>">Más Info</a></li>
					<?php endif; ?>

                    <li><a class="nav-link <?= $es_tienda ? 'active' : '' ?>" href="<?= $es_tienda ? '../tienda/' : 'tienda/' ?>">Regalar</a></li>




					<?php if (in_array('contact', $secciones)): ?>
						<li><a class="nav-link" href="<?= $es_tienda ? '../inicio.php#contact' : '#contact' ?>">Contactar</a></li>
					<?php endif; ?>
					
<li><a class="nav-link open-rsvp-modal" href="#">RSVP</a></li>
				</ul>						

			</div>
		</div>
	</nav>
</header>
<!-- End header -->
