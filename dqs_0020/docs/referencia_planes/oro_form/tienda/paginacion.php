<?php
// Incluir el archivo de conexión
include '../conexion.php';
include_once 'regalo_libre_helper.php';

// Configuración de la paginación
$productosPorPagina = 24; // Número de productos por página
$tituloRegaloLibreEscapado = $conn->real_escape_string(REGALO_LIBRE_TITULO);
$totalProductos = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo=1 AND titulo <> '$tituloRegaloLibreEscapado'")->fetch_assoc()['total'];
$totalPaginas = ceil($totalProductos / $productosPorPagina);

// Obtener la página actual de la URL, si no está presente, por defecto es 1
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Obtener el criterio de ordenación desde la solicitud GET
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Obtener la moneda desde la URL, si no está, por defecto es 1 (Pesos)
$currency = isset($_GET['currency']) ? (int)$_GET['currency'] : 1;

// Generar los botones de paginación
echo '<div class="pagination">';

// Botón "Primera"
if ($paginaActual > 1) {
    // Agregamos el parámetro 'currency' al enlace
    echo '<a href="?pagina=1&sort=' . htmlspecialchars($sort) . '&currency=' . htmlspecialchars($currency) . '" class="pagination-link"><<</a> ';
}

// Botón "Anterior"
if ($paginaActual > 1) {
    $prevPage = $paginaActual - 1;
    // Agregamos el parámetro 'currency' al enlace
    echo '<a href="?pagina=' . htmlspecialchars($prevPage) . '&sort=' . htmlspecialchars($sort) . '&currency=' . htmlspecialchars($currency) . '" class="pagination-link"><</a> ';
}

// Botones de páginas
for ($i = 1; $i <= $totalPaginas; $i++) {
    $activeClass = ($i == $paginaActual) ? 'active' : '';
    // Agregamos el parámetro 'currency' al enlace de cada página
    echo '<a href="?pagina=' . htmlspecialchars($i) . '&sort=' . htmlspecialchars($sort) . '&currency=' . htmlspecialchars($currency) . '" class="pagination-link ' . $activeClass . '">' . htmlspecialchars($i) . '</a> ';
}

// Botón "Siguiente"
if ($paginaActual < $totalPaginas) {
    $nextPage = $paginaActual + 1;
    // Agregamos el parámetro 'currency' al enlace
    echo '<a href="?pagina=' . htmlspecialchars($nextPage) . '&sort=' . htmlspecialchars($sort) . '&currency=' . htmlspecialchars($currency) . '" class="pagination-link">></a> ';
}

// Botón "Última"
if ($paginaActual < $totalPaginas) {
    // Agregamos el parámetro 'currency' al enlace
    echo '<a href="?pagina=' . htmlspecialchars($totalPaginas) . '&sort=' . htmlspecialchars($sort) . '&currency=' . htmlspecialchars($currency) . '" class="pagination-link">>></a>';
}

echo '</div>';
?>