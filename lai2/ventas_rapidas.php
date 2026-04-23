<?php
include 'conexion.php';
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}


//EL FORMULARIO DE REGISTRO ESTÁ EN EL INDEX PARA QUE NO SE NOTE EL REFRESH



// Consulta SQL para obtener los totales de ventas por forma de pago
$sql_totales = "
SELECT * FROM (
    SELECT 'TOTAL' AS agrupacion, SUM(precio) AS total_precio 
    FROM ventas 
    WHERE activado = 1 
    AND forma NOT IN ('Entrada', 'Gratis')

    UNION ALL

    SELECT forma, SUM(precio) AS total_precio 
    FROM ventas 
    WHERE activado = 1 
    GROUP BY forma 
) AS subconsulta
ORDER BY 
    CASE agrupacion
        WHEN 'TOTAL' THEN 0
        WHEN 'Efectivo' THEN 1
        WHEN 'Mercado Pago' THEN 2
        WHEN 'Entrada' THEN 3
        WHEN 'Gratis' THEN 4
        ELSE 5
    END;
";

// Ejecutar la consulta y obtener los resultados
$result_totales = $conn->query($sql_totales);
$totales = array();
if ($result_totales->num_rows > 0) {
    while($row = $result_totales->fetch_assoc()) {
        $totales[] = $row;
    }
}





// Consulta para obtener los productos
$sql_productos = "SELECT * FROM productos where activo=1 ORDER BY orden IS NULL, orden ASC";
$result_productos = $conn->query($sql_productos);

// Array para almacenar los productos
$productos = array();
if ($result_productos->num_rows > 0) {
    while($row = $result_productos->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Consulta para obtener las combinaciones
$sql_combinaciones = "SELECT * FROM combinaciones where activo=1 ORDER BY id ASC";
$result_combinaciones = $conn->query($sql_combinaciones);

// Array para almacenar las combinaciones
$combinaciones = array();
if ($result_combinaciones->num_rows > 0) {
    while($row = $result_combinaciones->fetch_assoc()) {
        $combinaciones[] = $row;
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
	


<script>
    // Obtener los productos y combinaciones desde PHP
    const productos = <?php echo json_encode($productos); ?>;
    const combinaciones = <?php echo json_encode($combinaciones); ?>;

    // Función para actualizar los precios
    function updatePrices() {
        const formaPago = document.querySelector('input[name="forma_pago"]:checked') ? document.querySelector('input[name="forma_pago"]:checked').value : null;

        productos.forEach((producto, index) => {
            const button = document.getElementById(`producto-${producto.id}`);
            let nuevoPrecio = producto.precio;
            if (formaPago === "Mercado Pago") {
                nuevoPrecio = parseFloat(producto.precio_mercadopago || producto.precio);
            }
            button.textContent = `${producto.orden !== null ? producto.orden + ' --> ' : ''}${producto.nombre} - $${nuevoPrecio.toFixed(0)}`;
        });

        combinaciones.forEach((combinacion, index) => {
            const button = document.getElementById(`combinacion-${combinacion.id}`);
            let nuevoPrecio = combinacion.precio;
            if (formaPago === "Mercado Pago") {
                nuevoPrecio = parseFloat(combinacion.precio_mercadopago || combinacion.precio);
            }
            button.textContent = `Combo: ${combinacion.nombre} - $${nuevoPrecio.toFixed(0)}`;
        });
    }

    // Llamar a la función al cargar la página para establecer los precios iniciales
    document.addEventListener('DOMContentLoaded', updatePrices);

    function obtenerPrecioPorForma(precio, precioMercadoPago) {
        const formaPago = document.querySelector('input[name="forma_pago"]:checked')?.value;
        return formaPago === "Mercado Pago" ? precioMercadoPago : precio;
    }

    // Función para manejar las teclas numéricas
    document.addEventListener('keydown', function(event) {
        const key = event.key;
        const producto = productos.find(p => p.orden == key);
        if (producto) {
            document.getElementById(`producto-${producto.id}`).click();
        }
    });
</script>
</head>
<body>



            <h1>VENTAS RÁPIDAS</h1>
            <form method="POST">
                <input type="hidden" name="precio" value=""> <!-- Precio se asignará por los botones -->
                
<!-- Selector de forma de pago -->
<?php
// Antes de la sección de HTML, justo después de procesar la venta:
$selected_forma_pago = ""; // Variable para almacenar la forma de pago seleccionada

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['producto'])) {
    // ... código existente para registrar la venta ...
    $selected_forma_pago = $forma_pago; // Almacena la forma de pago seleccionada
}
?>

<label class="forma-pago-label">Forma de pago:</label>
<div class="forma-pago-container">
    <input type="radio" class="radio-input" name="forma_pago" id="efectivo" value="Efectivo" required <?php if ($selected_forma_pago == "Efectivo") echo 'checked'; ?> onchange="updatePrices()">
    <label class="radio-label" for="efectivo">Efectivo</label>
    
    <input type="radio" class="radio-input" name="forma_pago" id="mercado_pago" value="Mercado Pago" <?php if ($selected_forma_pago == "Mercado Pago") echo 'checked'; ?> onchange="updatePrices()">
    <label class="radio-label" for="mercado_pago">Mercado Pago</label>
    
    <input type="radio" class="radio-input" name="forma_pago" id="gratis" value="Gratis" <?php if ($selected_forma_pago == "Gratis") echo 'checked'; ?> onchange="updatePrices()">
    <label class="radio-label" for="gratis">Gratis</label>
</div>







<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
    <?php foreach ($productos as $producto): ?>
        <button type="submit" name="producto" value="<?php echo htmlspecialchars($producto['nombre']); ?>" class="product" id="producto-<?php echo htmlspecialchars($producto['id']); ?>" onclick="document.querySelector('input[name=precio]').value=obtenerPrecioPorForma(<?php echo htmlspecialchars($producto['precio']); ?>, <?php echo htmlspecialchars($producto['precio_mercadopago']); ?>)">
            <?php echo $producto['orden'] !== null ? htmlspecialchars($producto['orden']) . ' --> ' : ''; ?><?php echo htmlspecialchars($producto['nombre']); ?> - $<?php echo floor($producto['precio']); ?>
        </button>
    <?php endforeach; ?>

    <?php foreach ($combinaciones as $combinacion): ?>
        <button type="submit" name="producto" value="<?php echo htmlspecialchars($combinacion['nombre']); ?>" class="product" id="combinacion-<?php echo htmlspecialchars($combinacion['id']); ?>" onclick="document.querySelector('input[name=precio]').value=obtenerPrecioPorForma(<?php echo htmlspecialchars($combinacion['precio']); ?>, <?php echo htmlspecialchars($combinacion['precio_mercadopago']); ?>)">
            Combo: <?php echo htmlspecialchars($combinacion['nombre']); ?> - $<?php echo floor($combinacion['precio']); ?>
        </button>
    <?php endforeach; ?>
</div>


	
	
</form>


			

		

		
		

</body>
</html>

<?php $conn->close(); ?>
