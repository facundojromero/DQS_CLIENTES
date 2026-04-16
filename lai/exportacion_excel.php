<?php
include 'conexion.php';
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="ventas.xls"');
header('Cache-Control: max-age=0');

$query = "SELECT a.id
, a.fecha_hora
, a.producto
, a.precio
, a.activado
, a.forma
, b.usuario FROM ventas a LEFT JOIN usuarios b ON a.id_usuario = b.id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    // Obtener los nombres de las columnas
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        echo $field->name . "\t";
    }
    echo "\n";

    while ($row = $result->fetch_assoc()) {
        foreach ($fields as $field) {
            echo (isset($row[$field->name]) ? $row[$field->name] : '') . "\t";
        }
        echo "\n";
    }
} else {
    echo "No hay datos disponibles.";
}

$conn->close();
?>
