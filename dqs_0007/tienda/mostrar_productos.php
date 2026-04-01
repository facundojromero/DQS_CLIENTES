<?php
include '../conexion.php';
include_once 'regalo_libre_helper.php';

asegurarEstructuraRegaloLibre($conn);
$regaloLibreId = obtenerOCrearProductoRegaloLibre($conn);
$showGiftcard = mostrarGiftCardHabilitada($conn);

$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productosPorPagina = 24;
$offset = ($paginaActual - 1) * $productosPorPagina;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$currency = isset($_GET['currency']) ? (int)$_GET['currency'] : 2;

$query_dolar = "SELECT cotizacion_dolar FROM cliente WHERE user_id=1";
$result_dolar = $conn->query($query_dolar);
$cotizacion_dolar = 1;
if ($result_dolar && $result_dolar->num_rows > 0) {
    $row_dolar = $result_dolar->fetch_assoc();
    $cotizacion_dolar = $row_dolar['cotizacion_dolar'];
}

$tituloRegaloLibreEscapado = $conn->real_escape_string(REGALO_LIBRE_TITULO);
$sql = "SELECT id, titulo, descripcion, precio FROM productos WHERE activo = 1 AND titulo <> '$tituloRegaloLibreEscapado'";
if ($sort == 'price') {
    $sql .= " ORDER BY precio ASC";
} elseif ($sort == 'alphabetical') {
    $sql .= " ORDER BY titulo ASC";
}
$sql .= " LIMIT $productosPorPagina OFFSET $offset";

$result = $conn->query($sql);

if ($showGiftcard && $regaloLibreId > 0 && $paginaActual === 1) {
    echo "<div class='producto producto-regalo-libre'>";
    echo "<h2>" . htmlspecialchars(REGALO_LIBRE_TITULO) . "</h2>";
    echo "<p>Elegí el monto que quieras regalar.</p>";
    echo "<p class='precio'>" . ($currency == 2 ? 'u$s' : '$') . " Monto personalizado</p>";
    echo "<div class='carousel'><img src='imagenes/gifcard.jpg' alt='" . htmlspecialchars(REGALO_LIBRE_TITULO) . "'></div>";
    echo "<button class='add-free-gift button' data-id='" . (int)$regaloLibreId . "'><i class='fas fa-hand-holding-heart'></i> Elegir monto</button>";
    echo "</div>";
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='producto'>";
        echo "<h2>" . htmlspecialchars($row['titulo']) . "</h2>";

        $precio_a_mostrar = (float)$row['precio'];
        $simbolo_moneda = "u\$s";

        if ($currency == 1 && $cotizacion_dolar > 0) {
            $precio_a_mostrar = $precio_a_mostrar * $cotizacion_dolar;
            $simbolo_moneda = "$";
        }

        echo "<p class='precio'>" . htmlspecialchars($simbolo_moneda) . " " . htmlspecialchars(number_format($precio_a_mostrar, 0, '', '.')) . "</p>";

        $producto_id = $row['id'];
        $sql_imagenes = "SELECT url FROM imagenes WHERE producto_id = $producto_id";
        $result_imagenes = $conn->query($sql_imagenes);
        if ($result_imagenes && $result_imagenes->num_rows > 0) {
            echo "<div class='carousel'>";
            $first_image = true;
            while ($row_imagen = $result_imagenes->fetch_assoc()) {
                if ($first_image) {
                    echo "<img src='imagenes/" . htmlspecialchars($row_imagen['url']) . "' alt='" . htmlspecialchars($row['titulo']) . "'>";
                    $first_image = false;
                } else {
                    echo "<img src='imagenes/" . htmlspecialchars($row_imagen['url']) . "' alt='" . htmlspecialchars($row['titulo']) . "' style='display:none;'>";
                }
            }
            echo "</div>";
        }
        echo "<button class='add-to-cart button' data-id='" . htmlspecialchars($row['id']) . "'><i class='fas fa-gift'></i> Regalar</button>";
        echo "</div>";
    }
}

$conn->close();
?>
