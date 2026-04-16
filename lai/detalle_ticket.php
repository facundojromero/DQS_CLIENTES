<?php
include 'conexion.php';


session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "
SELECT a.id
     , a.fecha_hora
     , a.producto
     , a.precio
     , a.forma
     , b.producto_id
     , c.nombre
     , SUM(b.cantidad) AS cantidad
     , SUM(b.precio) AS precio
FROM ventas a
INNER JOIN venta_detalles b ON a.id = b.venta_id
INNER JOIN productos c ON b.producto_id = c.id
WHERE a.activado = 1 AND a.id = $id
GROUP BY a.id, a.fecha_hora, a.producto, a.precio, a.forma, b.producto_id, c.nombre
";

$resultado = $conn->query($sql);

$venta_info = null;
$detalles = array();

if ($resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        if (!$venta_info) {
            $venta_info = array(
                'id' => $fila['id'],
                'fecha_hora' => $fila['fecha_hora'],
                'precio' => $fila['precio'],
                'forma' => $fila['forma']
            );
        }

        $detalles[] = array(
            'nombre' => $fila['nombre'],
            'cantidad' => $fila['cantidad'],
            'precio' => $fila['precio']
        );
    }
} else {
    echo "<p style='font-family:sans-serif;'>No se encontró la venta.</p>";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Ticket</title>
    <link rel="stylesheet" href="formato.css">
</head>
<body>

<div class="center-panel">
    <h2>Detalle del Ticket</h2>

    <div style="margin-bottom: 20px;">
        <p><strong>ID Venta:</strong> <?php echo $venta_info['id']; ?></p>
        <p><strong>Fecha y Hora:</strong> <?php echo $venta_info['fecha_hora']; ?></p>
        <p><strong>Forma de Pago:</strong> <?php echo $venta_info['forma']; ?></p>
        <p><strong>Total Venta:</strong> $<?php echo number_format($venta_info['precio'], 2, ',', '.'); ?></p>
    </div>

    <h3>Productos</h3>
    <table>
        <tr>
            <th>Nombre</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
        </tr>
        <?php foreach ($detalles as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                <td><?php echo $item['cantidad']; ?></td>
                <td>$<?php echo number_format($item['precio'], 2, ',', '.'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
