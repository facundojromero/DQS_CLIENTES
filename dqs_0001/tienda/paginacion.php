<?php
// Incluir el archivo de conexión
include '../conexion.php';

// Configuración de la paginación
$productosPorPagina = 24; // Número de productos por página
$totalProductos = $conn->query("SELECT COUNT(*) as total FROM productos where activo=1")->fetch_assoc()['total'];
$totalPaginas = ceil($totalProductos / $productosPorPagina);

// Obtener la página actual de la URL, si no está presente, por defecto es 1
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Obtener el criterio de ordenación desde la solicitud GET
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Generar los botones de paginación
echo '<div class="pagination">';

// Botón "Primera"
if ($paginaActual > 1) {
    echo '<a href="?pagina=1&sort=' . $sort . '" class="pagination-link"><<</a> ';
}

// Botón "Anterior"
if ($paginaActual > 1) {
    $prevPage = $paginaActual - 1;
    echo '<a href="?pagina=' . $prevPage . '&sort=' . $sort . '" class="pagination-link"><</a> ';
}

// Botones de páginas
for ($i = 1; $i <= $totalPaginas; $i++) {
    $activeClass = ($i == $paginaActual) ? 'active' : '';
    echo '<a href="?pagina=' . $i . '&sort=' . $sort . '" class="pagination-link ' . $activeClass . '">' . $i . '</a> ';
}

// Botón "Siguiente"
if ($paginaActual < $totalPaginas) {
    $nextPage = $paginaActual + 1;
    echo '<a href="?pagina=' . $nextPage . '&sort=' . $sort . '" class="pagination-link">></a> ';
}

// Botón "Última"
if ($paginaActual < $totalPaginas) {
    echo '<a href="?pagina=' . $totalPaginas . '&sort=' . $sort . '" class="pagination-link">>></a> ';
}

echo '</div>';

$conn->close();
?>