<?php
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo

$sql_totales = "SELECT SUM(monto_total) AS total_ganado FROM regalos a
                INNER JOIN regalos_confirmacion b
                ON a.id = b.regalo_id
                WHERE a.activo = 1";
$result_totales = $conn->query($sql_totales);

if ($result_totales->num_rows > 0) {
    echo "<div class='totales'>";
    while($row_totales = $result_totales->fetch_assoc()) {
        $total_ganado = number_format($row_totales['total_ganado'], 0, '', '.');
        echo "<p><strong>Recaudado:</strong> \${$total_ganado}</p>";
    }
    echo "</div>";
} else {
    echo "<p>No hay registros de pagos confirmados.</p>";
}

$conn->close();
?>