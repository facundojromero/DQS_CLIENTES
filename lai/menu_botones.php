<?php
include 'conexion.php';
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
    <div class="menu-buttons">
        <a href="exportacion_excel.php" class="menu-button">Exportar Excel</a>
        <a href="modificar_precios.php" class="menu-button">Modificar Productos</a>
        <a href="modificar_combinaciones.php" class="menu-button">Modificar Promociones</a>		
        <a href="resumen_ventas.php" class="menu-button">Resumen de Ventas</a>
    </div>
