<?php
include 'conexion.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// 1. Total acumulado
$sql_total = "
SELECT SUM(a.precio) AS total_dia
FROM ventas a
WHERE a.activado = 1
";
$result_total = $conn->query($sql_total);
$row_total = $result_total->fetch_assoc();
$total_dia = $row_total['total_dia'];

// 2. Recaudación por forma de pago
$sql_formas = "
SELECT a.forma, SUM(a.precio) AS total
FROM ventas a
WHERE a.activado = 1
GROUP BY a.forma
";
$formas_pago = $conn->query($sql_formas);

// 3. Detalle por producto
$sql_productos = "
SELECT producto nombre, SUM(cantidad) cantidad, SUM(total) total
FROM
(
	SELECT producto, COUNT(producto) AS cantidad, SUM(precio) AS total
	FROM ventas a
	WHERE producto NOT IN ('Carrito')
	GROUP BY producto
	UNION ALL
	SELECT c.nombre, SUM(b.cantidad) AS cantidad, SUM(b.precio) AS total
	FROM ventas a
	JOIN venta_detalles b ON a.id = b.venta_id
	JOIN productos c ON b.producto_id = c.id
	WHERE a.activado = 1
	GROUP BY c.nombre
	ORDER BY cantidad DESC
) a
GROUP BY producto
ORDER BY cantidad DESC
;
";
$productos = $conn->query($sql_productos);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Ventas - Acumulado</title>
    <link rel="stylesheet" href="formato.css">
</head>
<body>

<div class="center-panel">
    <h1>Resumen de Ventas (Acumulado)</h1>
				<div class="menu-buttons">
        <a href="index.php" class="menu-button">Volver a Caja</a>
   </div>
    <h3>Total Recaudado</h3>
    <p style="font-size: 20px; font-weight: bold;">
        $<?php echo number_format($total_dia, 2, ',', '.'); ?>
    </p>

    <h3>Recaudación por Forma de Pago</h3>
    <table>
        <tr>
            <th>Forma de Pago</th>
            <th>Total</th>
        </tr>
        <?php while($f = $formas_pago->fetch_assoc()): ?>
            <tr>
                <td><?php echo $f['forma']; ?></td>
                <td>$<?php echo number_format($f['total'], 2, ',', '.'); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h3>Detalle por Producto</h3>
    <table>
        <tr>
            <th>Producto</th>
            <th>Cantidad Vendida</th>
            <th>Total Recaudado</th>
        </tr>
        <?php while($p = $productos->fetch_assoc()): ?>
            <tr>
                <td><?php echo $p['nombre']; ?></td>
                <td><?php echo $p['cantidad']; ?></td>
                <td>$<?php echo number_format($p['total'], 2, ',', '.'); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

</div>

</body>
</html>
