<?php
error_reporting(E_ERROR);
include_once '../conexion.php';
include_once 'regalo_libre_helper.php';
session_start();

asegurarEstructuraRegaloLibre($conn);
$regaloLibreId = obtenerOCrearProductoRegaloLibre($conn);

// Consulta para verificar si cbu_dolar o alias_dolar tienen valor en la tabla cliente
$query_cliente = "SELECT cbu_dolar, alias_dolar FROM cliente WHERE user_id = 1";
$result_cliente = $conn->query($query_cliente);
$mostrar_moneda = false; // Variable para controlar la visibilidad

if ($result_cliente && $result_cliente->num_rows > 0) {
    $row_cliente = $result_cliente->fetch_assoc();
    if (!empty($row_cliente['cbu_dolar']) || !empty($row_cliente['alias_dolar'])) {
        $mostrar_moneda = true;
    }
}

// Obtener la moneda seleccionada desde la URL
$currency = isset($_GET['currency']) ? (int)$_GET['currency'] : 2;

// Obtener la cotización del dólar
$query_dolar = "SELECT cotizacion_dolar FROM cliente WHERE user_id=1";
$result_dolar = $conn->query($query_dolar);
$cotizacion_dolar = 1;
if ($result_dolar && $result_dolar->num_rows > 0) {
    $row_dolar = $result_dolar->fetch_assoc();
    $cotizacion_dolar = $row_dolar['cotizacion_dolar'];
}

$session_id = session_id();
$sql = "SELECT productos.id, productos.titulo, productos.descripcion, productos.precio, carrito.cantidad, carrito.monto_libre         FROM carrito         JOIN productos ON carrito.producto_id = productos.id        WHERE carrito.session_id = '" . $conn->real_escape_string($session_id) . "'";
$result = $conn->query($sql);
$total_productos = 0;
$monto_total = 0;
$productos = array();
$productos2 = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $esRegaloLibre = ((int)$row['id'] === (int)$regaloLibreId) || $row['monto_libre'] !== null;
        $cantidad = $esRegaloLibre ? 1 : (int)$row['cantidad'];

        if ($esRegaloLibre) {
            $precio_producto_a_mostrar = (float)$row['monto_libre'];
            if ($currency == 1 && $cotizacion_dolar > 0) {
                $precio_producto_a_mostrar = $precio_producto_a_mostrar * $cotizacion_dolar;
            }
            $subtotal = $precio_producto_a_mostrar;
            $productos[] = "Gift Card: " . ($currency == 2 ? "u\$s " : "$ ") . number_format($subtotal, 0, '', '.');
        } else {
            $precio_producto_base = (float)$row['precio'];
            $precio_producto_a_mostrar = $precio_producto_base;
            if ($currency == 1 && $cotizacion_dolar > 0) {
                $precio_producto_a_mostrar = $precio_producto_base * $cotizacion_dolar;
            }
            $subtotal = $precio_producto_a_mostrar * $cantidad;
            $productos[] = $row['titulo'] . " (Cantidad: " . $cantidad . ", Subtotal: " . ($currency == 2 ? "u\$s " : "$ ") . number_format($subtotal, 0, '', '.') . ")";
        }

        $total_productos += $cantidad;
        $monto_total += $subtotal;

        $detalle = array(
            'id' => (int)$row['id'],
            'cantidad' => $cantidad,
            'precio' => $precio_producto_a_mostrar,
            'monto_libre' => $esRegaloLibre ? $precio_producto_a_mostrar : null,
            'subtotal' => $subtotal
        );
        $productos2[] = $detalle;
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Regalos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
    .currency-selector {
        display: inline-block;
        margin-left: 10px;
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
                            <a href="<?= $es_tienda ? '../' : '/' ?>" class="btn">Inicio</a>
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
        <a href="index.php?currency=<?php echo htmlspecialchars($currency); ?>" id="homeLink" class="ver-carrito"><i class="fas fa-home"></i></a>
    </div>

    <div class="checkout-container">
        <h1>Finalizar Regalo</h1>
        <div class="cart-summary">
            <h2>Resumen del Carrito</h2>
            <p>Total de productos: <?php echo $total_productos; ?></p>
            <p>
                <form id="currencyForm" method="GET" action="finalizar_compra.php" style="display:inline;">
                    Monto total:
                    <strong><?php echo ($currency == 2 ? "u\$s " : "$ "); echo number_format($monto_total, 0, '', '.'); ?></strong>
                    <?php if ($mostrar_moneda): ?>
                        <div class="currency-selector">
                            <input type="radio" id="pesos" name="currency" value="1" <?php if($currency == 1) echo 'checked'; ?> onchange="this.form.submit()">
                            <label for="pesos">Pesos</label>
                            <input type="radio" id="dolares" name="currency" value="2" <?php if($currency == 2) echo 'checked'; ?> onchange="this.form.submit()">
                            <label for="dolares">Dólares</label>
                        </div>
                    <?php endif; ?>
                </form>
            </p>
        </div>
        <form action="procesar_compra.php" method="POST" id="checkoutForm">
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
                    <option value="transferencia">Transferencia Bancaria</option>
                </select>
            </div>
            <input type="hidden" name="monto_total" value="<?php echo $monto_total; ?>">
            <input type="hidden" name="productos" value="<?php echo htmlspecialchars($productos_str); ?>">
            <input type="hidden" name="productos2" value='<?php echo htmlspecialchars($productos_json); ?>'>
            <input type="hidden" name="currency" value="<?php echo $currency; ?>">
            <button type="submit" class="button" id="confirmGiftButton"><i class='fas fa-check'></i> Confirmar Regalo</button>
        </form>
        <a href="index.php?currency=<?php echo htmlspecialchars($currency); ?>" class="button"><i class='fas fa-shopping-cart'></i> Seguir Regalando</a>
        <button id="modifyCartButton" class="button"><i class='fas fa-edit'></i> Modificar carrito</button>
    </div>
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <h2>Tu Carrito</h2>
            <div id="cartItems">
                <?php include 'ver_carrito.php?currency=' . htmlspecialchars($currency); ?>
            </div>
        </div>
    </div>
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
    
    // INICIO MODIFICACIÓN PARA PREVENIR DOBLE CLIC
    document.getElementById('checkoutForm').addEventListener('submit', function() {
        // Deshabilita el botón de envío inmediatamente
        var button = document.getElementById('confirmGiftButton');
        button.disabled = true;
        // Opcional: Cambia el texto para dar feedback al usuario
        button.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Procesando...";
    });
    // FIN MODIFICACIÓN PARA PREVENIR DOBLE CLIC

	</script>
    <script>
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
    
    // FUNCIÓN CORREGIDA para Finalizar Compra
    function loadCartItems() {
        // CORRECCIÓN: Obtener la moneda desde el parámetro 'currency' de la URL.
        const urlParams = new URLSearchParams(window.location.search);
        const selectedCurrency = urlParams.get('currency') || '2'; // Si no está en URL, usa '2' (Dólares)
        
        fetch('ver_carrito.php?currency=' + selectedCurrency)
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
$conn->close();
?>
