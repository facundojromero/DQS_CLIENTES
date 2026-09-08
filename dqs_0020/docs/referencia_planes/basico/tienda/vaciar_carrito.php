<?php
session_start();
include '../conexion.php';
$session_id = session_id();
$sql = "DELETE FROM carrito WHERE session_id = '$session_id'";
if ($conn->query($sql) === TRUE) {
    echo "Carrito vaciado";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>