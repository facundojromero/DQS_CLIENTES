<?php
session_start();
include '../conexion.php';
$id = $_POST['id'];
$session_id = session_id();
$sql = "DELETE FROM carrito WHERE producto_id = '$id' AND session_id = '$session_id'";
if ($conn->query($sql) === TRUE) {
    echo "Producto eliminado";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>