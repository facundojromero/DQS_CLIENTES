<?php
include 'conexion.php';
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Inicializar variables
$productos = array();
$combinaciones = array();
$total_consumidos = array();

// Obtener productos
$sql_productos = "SELECT * FROM productos where activo=1 ORDER BY orden IS NULL, orden ASC";
$result_productos = $conn->query($sql_productos);
if ($result_productos->num_rows > 0) {
    while ($row = $result_productos->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Obtener combinaciones
$sql_combinaciones = "SELECT * FROM combinaciones where activo=1 ORDER BY id ASC";
$result_combinaciones = $conn->query($sql_combinaciones);
if ($result_combinaciones->num_rows > 0) {
    while ($row = $result_combinaciones->fetch_assoc()) {
        $combinaciones[] = $row;
    }
}


// Función para agregar productos al carrito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_carrito'])) {
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    $tipo = $_POST['tipo'];
    $usuario_id = $_SESSION['usuario_id'];
	$sql = "INSERT INTO carrito (producto_id, cantidad, tipo, id_usuario) VALUES ($producto_id, $cantidad, '$tipo', $usuario_id)";
    $conn->query($sql);


}


// Función para eliminar productos del carrito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_carrito'])) {
    $carrito_id = $_POST['carrito_id'];
	$usuario_id = $_SESSION['usuario_id'];
	$sql = "DELETE FROM carrito WHERE id = $carrito_id AND id_usuario = $usuario_id";
    $conn->query($sql);


}



// Calcular el total consumido actualizado SIEMPRE
$usuario_id = $_SESSION['usuario_id'];
$sql_total_consumidos = "SELECT 
    SUM(CASE carrito.tipo WHEN 'producto' THEN productos.precio * carrito.cantidad ELSE combinaciones.precio * carrito.cantidad END) AS total_efectivo,
    SUM(CASE carrito.tipo WHEN 'producto' THEN COALESCE(productos.precio_mercadopago, productos.precio) * carrito.cantidad ELSE COALESCE(combinaciones.precio_mercadopago, combinaciones.precio) * carrito.cantidad END) AS total_mercadopago
FROM carrito 
LEFT JOIN productos ON carrito.producto_id = productos.id AND carrito.tipo = 'producto' 
LEFT JOIN combinaciones ON carrito.producto_id = combinaciones.id AND carrito.tipo = 'combinacion'
WHERE carrito.id_usuario = $usuario_id";


$result_total_consumidos = $conn->query($sql_total_consumidos);
$total_consumidos = array();

if ($result_total_consumidos->num_rows > 0) {
    while ($row = $result_total_consumidos->fetch_assoc()) {
        $total_consumidos[] = $row;
    }
}


?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Venta - LAI</title>
    <link rel="stylesheet" href="formato.css">
</head>
<body>


            <h1>VENTAS VARIOS PRODUCTOS</h1>
            <form id="productos-form" method="POST">
                <input type="hidden" name="precio" value="">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $producto): ?>
                            <button type="button" class="product" id="producto-<?php echo htmlspecialchars($producto['id']); ?>" onclick="agregarCarrito(<?php echo htmlspecialchars($producto['id']); ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo htmlspecialchars($producto['precio']); ?>, <?php echo htmlspecialchars($producto['precio_mercadopago']); ?>, 'producto')">
                            <?php echo htmlspecialchars($producto['nombre']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($combinaciones)): ?>
                        <?php foreach ($combinaciones as $combinacion): ?>
                            <button type="button" class="product" id="combinacion-<?php echo htmlspecialchars($combinacion['id']); ?>" onclick="agregarCarrito(<?php echo htmlspecialchars($combinacion['id']); ?>, '<?php echo htmlspecialchars($combinacion['nombre']); ?>', <?php echo htmlspecialchars($combinacion['precio']); ?>, <?php echo htmlspecialchars($combinacion['precio_mercadopago']); ?>, 'combinacion')">
                                Combo: <?php echo htmlspecialchars($combinacion['nombre']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </form>

<?php
// Consulta al carrito
$usuario_id = $_SESSION['usuario_id'];
$sql_carrito = "SELECT carrito.id, carrito.tipo, carrito.cantidad, 
                CASE carrito.tipo 
                    WHEN 'producto' THEN productos.nombre 
                    ELSE combinaciones.nombre 
                END AS nombre 
                FROM carrito 
                LEFT JOIN productos ON carrito.producto_id = productos.id AND carrito.tipo = 'producto' 
                LEFT JOIN combinaciones ON carrito.producto_id = combinaciones.id AND carrito.tipo = 'combinacion'
                WHERE carrito.id_usuario = $usuario_id";
$result_carrito = $conn->query($sql_carrito);
?>

<?php if ($result_carrito->num_rows > 0): ?>
    <h1>Carrito de Compras</h1>
    <form id="carrito-form" method="POST">
        <input type="hidden" name="forma_pago2" id="forma_pago_hidden">
        <table>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Acciones</th>
            </tr>
            <?php while ($row = $result_carrito->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['cantidad']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="carrito_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <button type="submit" name="eliminar_carrito" class="action-button cancel-button">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <label class="forma-pago-label">Forma de pago:</label>
        <div class="forma-pago-container">
<input type="radio" class="radio-input" name="forma_pago2" id="efectivo_carrito" value="Efectivo" required onchange="updatePrices2()">
<label class="radio-label" for="efectivo_carrito">Efectivo</label>

<input type="radio" class="radio-input" name="forma_pago2" id="mercado_pago_carrito" value="Mercado Pago" onchange="updatePrices2()">
<label class="radio-label" for="mercado_pago_carrito">Mercado Pago</label>

<input type="radio" class="radio-input" name="forma_pago2" id="gratis_carrito" value="Gratis" onchange="updatePrices2()">
<label class="radio-label" for="gratis_carrito">Gratis</label>

        </div>
    </form>


				
				
				
<!-- Mensaje inicial -->
<h1><div id="mensaje-total">Total: (Seleccionar forma de pago)</div></h1>

<!-- Total oculto por defecto -->
<h1><div id="total-consumido" style="display: none;">
	
	<?php foreach ($total_consumidos as $total_consumido): ?> 
	<?php echo floor($total_consumido['total']); ?>
	<?php endforeach; ?>
</div></h1>
		
				
                <button type="submit" name="registrar_venta" class="product" onclick="setFormaPago()">Registrar Venta</button>
            </form>
			
<?php endif; ?>			

	


    <script>
        function agregarCarrito(id, nombre, precio, precioMercadoPago, tipo) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'producto_id';
            inputId.value = id;
            form.appendChild(inputId);
            const inputCantidad = document.createElement('input');
            inputCantidad.type = 'hidden';
            inputCantidad.name = 'cantidad';
            inputCantidad.value = 1;
            form.appendChild(inputCantidad);
            const inputTipo = document.createElement('input');
            inputTipo.type = 'hidden';
            inputTipo.name = 'tipo';
            inputTipo.value = tipo;
            form.appendChild(inputTipo);
            const inputAgregar = document.createElement('input');
            inputAgregar.type = 'hidden';
            inputAgregar.name = 'agregar_carrito';
            inputAgregar.value = '1';
            form.appendChild(inputAgregar);
            document.body.appendChild(form);
            form.submit();
        }







function updatePrices2() {
    const formaPago = document.querySelector('input[name="forma_pago2"]:checked').value;
    const total_consumidos = <?php echo json_encode($total_consumidos); ?>;
    let total_precio2 = 0;

    total_consumidos.forEach((consumido) => {
        const totalEfectivo = parseFloat(consumido.total_efectivo || 0);
        const totalMercadoPago = parseFloat(consumido.total_mercadopago || 0);
        const nuevoPrecio2 = formaPago === "Mercado Pago" ? totalMercadoPago : totalEfectivo;
        total_precio2 += nuevoPrecio2;
    });

    document.getElementById('total-consumido').textContent = `Total: $ ${total_precio2.toFixed(0)}`;
}









		
		
        function setFormaPago() {
            const formaPago = document.querySelector('input[name="forma_pago2"]:checked').value;
            document.getElementById('forma_pago_hidden').value = formaPago;
        }
    </script>
	
	
<script>
document.addEventListener("DOMContentLoaded", function () {
	const radios = document.querySelectorAll('input[name="forma_pago2"]');
	const totalDiv = document.getElementById("total-consumido");
	const mensaje = document.getElementById("mensaje-total");

	radios.forEach(radio => {
		radio.addEventListener("change", function () {
			if (this.checked) {
				totalDiv.style.display = "block";
				mensaje.style.display = "none";
			}
		});
	});
});
</script>



	
</body>
</html>


