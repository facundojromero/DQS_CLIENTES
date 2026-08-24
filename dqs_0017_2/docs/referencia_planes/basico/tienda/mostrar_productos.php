<?php
// Incluir el archivo de conexión
include '../conexion.php';

// Obtener la página actual de la URL, si no está presente, por defecto es 1
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productosPorPagina = 24; // Número de productos por página
$offset = ($paginaActual - 1) * $productosPorPagina;

// Obtener el criterio de ordenación desde la solicitud GET
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Obtener la moneda seleccionada (1 para Pesos, 2 para Dólares)
$currency = isset($_GET['currency']) ? (int)$_GET['currency'] : 1;

// Obtener la cotización del dólar de la tabla cliente
$query_dolar = "SELECT cotizacion_dolar FROM cliente WHERE user_id=1";
$result_dolar = $conn->query($query_dolar);
$cotizacion_dolar = 1; // Valor por defecto
if ($result_dolar && $result_dolar->num_rows > 0) {
    $row_dolar = $result_dolar->fetch_assoc();
    $cotizacion_dolar = $row_dolar['cotizacion_dolar'];
}


// Construir la consulta SQL con el criterio de ordenación y la paginación
$sql = "SELECT id, titulo, descripcion, precio FROM productos where activo = 1";
if ($sort == 'price') {
    $sql .= " ORDER BY precio ASC";
} elseif ($sort == 'alphabetical') {
    $sql .= " ORDER BY titulo ASC";
}
$sql .= " LIMIT $productosPorPagina OFFSET $offset";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    // Salida de datos de cada fila
    while($row = $result->fetch_assoc()) {
        echo "<div class='producto'>";
        echo "<h2>" . htmlspecialchars($row["titulo"]) . "</h2>";
        //echo "<p>" . htmlspecialchars($row["descripcion"]) . "</p>";
        
        // Lógica para mostrar el precio en la moneda correcta
        $precio_a_mostrar = $row["precio"];
        $simbolo_moneda = "$";

        if ($currency == 2) { // Dólares
            $precio_a_mostrar = $row["precio"] / $cotizacion_dolar;
            $simbolo_moneda = "u\$s";
        }
        
        echo "<p class='precio'>" . htmlspecialchars($simbolo_moneda) . " " . htmlspecialchars(number_format($precio_a_mostrar, 0, '', '.')) . "</p>";

        // Obtener las imágenes del producto
        $producto_id = $row["id"];
        $sql_imagenes = "SELECT url FROM imagenes WHERE producto_id = $producto_id";
        $result_imagenes = $conn->query($sql_imagenes);
        if ($result_imagenes && $result_imagenes->num_rows > 0) {
            echo "<div class='carousel'>";
            $first_image = true;
            while($row_imagen = $result_imagenes->fetch_assoc()) {
                if ($first_image) {
                    echo "<img src='imagenes/" . htmlspecialchars($row_imagen["url"]) . "' alt='" . htmlspecialchars($row["titulo"]) . "'>";
                    $first_image = false;
                } else {
                    echo "<img src='imagenes/" . htmlspecialchars($row_imagen["url"]) . "' alt='" . htmlspecialchars($row["titulo"]) . "' style='display:none;'>";
                }
            }
            echo "</div>";
        }
        echo "<button class='add-to-cart button' data-id='" . htmlspecialchars($row["id"]) . "'><i class='fas fa-gift'></i> Regalar</button>";
        echo "</div>";
    }
} else {
    echo "0 resultados";
}
$conn->close();
?>