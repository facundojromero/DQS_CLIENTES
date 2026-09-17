<?php
error_reporting(E_ERROR);
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo




session_start();

$session_id = session_id();
$sql = "SELECT productos.id, productos.titulo, productos.descripcion, productos.precio, carrito.cantidad 
        FROM carrito 
        JOIN productos ON carrito.producto_id = productos.id
        WHERE carrito.session_id = '$session_id'";
$result = $conn->query($sql);
$total_productos = 0;
$monto_total = 0;
$productos = array();
$productos2 = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $total_productos += $row["cantidad"];
        $monto_total += $row["precio"] * $row["cantidad"];
        //$productos[] = $row["titulo"] . " (Cantidad: " . $row["cantidad"] . ", Subtotal: $" . number_format($row["precio"] * $row["cantidad"], 2) . ")";
        $productos[] = $row["titulo"] . " (Cantidad: " . $row["cantidad"] . ", Subtotal: $" . number_format($row["precio"] * $row["cantidad"], 2, ',', '.') . ")";        
        $productos2[] = array(
            "id" => $row["id"],
            "cantidad" => $row["cantidad"],
            "precio" => $row["precio"]
        );
    }
}
$productos_str = implode(", ", $productos);
$productos_json = json_encode($productos2);




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
    
      <link rel="stylesheet" href="styles.css">
      
      
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">  
    
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Regalos</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body id="tienda" data-spy="scroll" data-target="#navbar-wd" data-offset="98">




	<!-- Start header -->
    <?php require '../header.php'; ?>
	
	<!-- End header -->	

	<!-- Start Cronometro -->
        <div id="cronometro" class="cronometro-box <?php echo in_array('cronometro', $secciones) ? 'activo' : ''; ?>">
        <div class="about-a1">
            <div class="container">
            	<div class="row">
                        <div class="lbox-caption2">
                            <div class="lbox-details2">
                                <!--<a href="#events" class="btn">Donde</a>-->
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
	<!-- End Cronometro -->	



 
    
    
    
    <!-- Start Tienda -->    

    <div class="pagination">
        <a href="index.php" id="homeLink" class="ver-carrito"><i class="fas fa-home"></i></a>
    </div>

    
    <div class="checkout-container">
        <h1>Finalizar Regalo</h1>

        <div class="cart-summary">
            <h2>Resumen del Carrito</h2>
            <p>Total de productos: <?php echo $total_productos; ?></p>
            <p>Monto total: $ <?php echo number_format($monto_total, 0); ?></p>
        </div>
        <form action="procesar_compra.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required>
            </div>
			
<div class="form-group">
    <label for="compartido">Comparto el regalo con:</label>
    <div id="compartido-container">
        <input type="text" id="compartido" name="compartido[]" placeholder="Nombre">
        <button type="button" id="addCompartido" class="button">+</button>
    </div>
</div>
			

			
			<div class="form-group">
			  <label for="mensaje">Mensaje:</label>
			  <textarea id="mensaje" name="mensaje" placeholder="Escribe tu mensaje aquí"></textarea>
			</div>
			
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>			

			
            <div class="form-group">
                <label for="telefono">Teléfono:</label>
                <div class="telefono-inputs">
                    0 <input type="tel" id="codigo_area" name="codigo_area" maxlength="4" required>
                    15 <input type="tel" id="numero" name="numero" maxlength="8" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="forma_pago">Forma de Pago:</label>
                <select id="forma_pago" name="forma_pago" required>
                    <!-- otras formas de pago para más adelante -->
                    <!-- <option value="tarjeta">Tarjeta de Crédito/Débito</option> -->
                    <!-- <option value="paypal">PayPal</option>  -->
                    <!-- FIN-->
                    <option value="transferencia">Transferencia Bancaria</option>
                </select>
            </div>
            <!-- Campos ocultos para enviar el monto total y los detalles de los productos -->
            <input type="hidden" name="monto_total" value="<?php echo $monto_total; ?>">
            <input type="hidden" name="productos" value="<?php echo htmlspecialchars($productos_str); ?>">
            <input type="hidden" name="productos2" value='<?php echo htmlspecialchars($productos_json); ?>'>
            <button type="submit" class="button"><i class='fas fa-check'></i> Confirmar Regalo</button>
        </form>
        <a href="index.php" class="button"><i class='fas fa-shopping-cart'></i> Seguir Regalando</a>
        <button id="modifyCartButton" class="button"><i class='fas fa-edit'></i> Modificar carrito</button>
    </div>
    <!-- Ventana emergente del carrito -->
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <h2>Tu Carrito</h2>
            <div id="cartItems">
                <!-- Aquí se listarán los productos -->
                <?php include 'ver_carrito.php'; ?>
            </div>
        </div>
    </div>

    <!-- End Tienda -->
    
       <script src="script.js"></script>
	
	
		<script>
	document.getElementById('addCompartido').addEventListener('click', function() {
	  var container = document.getElementById('compartido-container');
	  var input = document.createElement('input');
	  input.type = 'text';
	  input.name = 'compartido[]';
	  input.placeholder = 'Nombre';
	  container.insertBefore(input, this);
	});
	</script>
    
    <script>
    // Script para manejar la ventana emergente del carrito
    var modal = document.getElementById("cartModal");
    var btn = document.getElementById("modifyCartButton");
    var span = document.getElementsByClassName("close")[0];
    btn.onclick = function() {
        modal.style.display = "block";
        loadCartItems();
    }
    span.onclick = function() {
        modal.style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    function loadCartItems() {
        fetch('ver_carrito.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('cartItems').innerHTML = data;
            addCartFunctionality();
        })
        .catch(error => console.error('Error:', error));
    }
    function addCartFunctionality() {
        const emptyCartButton = document.getElementById('emptyCartButton');
        if (emptyCartButton) {
            emptyCartButton.onclick = function() {
                fetch('vaciar_carrito.php', { method: 'POST' })
                .then(response => response.text())
                .then(data => {
                    loadCartItems();
                })
                .catch(error => console.error('Error:', error));
            }
        }
        document.querySelectorAll('.remove-item').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('eliminar_producto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId
                })
                .then(response => response.text())
                .then(data => {
                    loadCartItems();
                })
                .catch(error => console.error('Error:', error));
            }
        });
        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('modificar_cantidad.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId + '&action=increase'
                })
                .then(response => response.text())
                .then(data => {
                    loadCartItems();
                })
                .catch(error => console.error('Error:', error));
            }
        });
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('modificar_cantidad.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId + '&action=decrease'
                })
                .then(response => response.text())
                .then(data => {
                    loadCartItems();
                })
                .catch(error => console.error('Error:', error));
            }
        });
        const continueShoppingButton = document.getElementById('continueShoppingButton');
        if (continueShoppingButton) {
            continueShoppingButton.onclick = function() {
                document.getElementById('cartModal').style.display = 'none';
            }
        }
    }
    </script> 
    

    
    
	<!-- Start Footer -->
    <?php require '../footer.php'; ?>
	</footer>
	<!-- End Footer -->
	
	
	
	
	
    <!-- ALL JS FILES -->
    <script src="../js/jquery.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <!-- ALL PLUGINS -->
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
$conn->close();
?>